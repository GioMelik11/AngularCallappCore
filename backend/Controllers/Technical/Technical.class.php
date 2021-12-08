<?php

use Routers\dbClass;

class Technical extends dbClass
{
    private $colCount;
    private $cols;

    public function getMain()
    {
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        $queue          = rawurldecode($_REQUEST["queues"]);
        $agent          = rawurldecode($_REQUEST["exts"]);
        
        $start          = $_REQUEST['startDate'];
        $end 	        = $_REQUEST['endDate'];
        $start_date     = $start.' 00:00';
        $end_date       = $end.' 23:59';
        $sl_type        = $_REQUEST["slType"];
        $call_ans_in    = $_REQUEST["callAnsIn"];
        $call_not_after = $_REQUEST["callNotAfter"];
        $source         = 1;

        $reportData = $this->callData($start_date, $end_date, $queue, $agent, $source);


        //die(var_dump($reportData));
        $row_all_calls  = $reportData['row_all_calls'];
        $row_answer     = $reportData['row_answer'];
        $row_abadon     = $reportData['row_abadon'];
        $row_transfer   = $reportData['row_transfer'];
        $row_done_blank = $reportData['row_done_blank'];
        $row_daumush    = $row_answer - $row_done_blank;
        $row_avarage    = $reportData['row_avarage'];

        $answer_percent     = round(((($row_answer) / ($row_answer + $row_abadon)) * 100), 2);
        $unnswer_percent    = round(((($row_abadon) / ($row_answer + $row_abadon)) * 100), 2);
        $transfer_percent   = round(((($row_transfer) / ($row_answer + $row_transfer)) * 100), 2);        
        $damush_percent     = round(((($row_done_blank) / ($row_answer)) * 100), 2);
        $daumush_percent    = round(((($row_answer - $row_done_blank) / ($row_answer)) * 100), 2);

        parent::setQuery("SELECT    'მომართვა' AS tt,
                                    '$row_all_calls' AS sul,
                                    '$row_answer' AS napasuxebi,
                                    '$row_abadon' AS upasuxo,
                                    '$row_transfer' AS gadamisamartebuli,
                                    '$row_done_blank' AS damush,
                                    '$row_daumush' AS daumush,
                                    '$row_avarage' AS avarage_time,
                                    '$answer_percent%' AS answer_percent,
                                    '$unnswer_percent%' AS unnswer_percent,
                                    '$transfer_percent%' AS transfer_percent,
                                    '$damush_percent%' AS damush_percent,
                                    '$daumush_percent%' AS daumush_percent");

        $report = parent::getKendoList($this->colCount, $this->cols);

        return $report;
    }

    public function getPercent()
    {

        //get requested data
        $queue          = rawurldecode($_REQUEST["queues"]);
        $agent          = rawurldecode($_REQUEST["exts"]);
        
        $start          = $_REQUEST['startDate'];
        $end 	        = $_REQUEST['endDate'];
        $start_date     = $start.' 00:00';
        $end_date       = $end.' 23:59';
        $sl_type        = $_REQUEST["slType"];
        $call_ans_in    = $_REQUEST["callAnsIn"];
        $call_not_after = $_REQUEST["callNotAfter"];
        $source         = 1;

        //SL answered calls / answered calls (1)
        if($sl_type == 1) {

            //define variables
            $calls_in_time = $this->get_sum_answered_calls_in_time($start_date, $end_date, $call_ans_in, $queue, $agent, $source);
            $call_answered = $this->get_sum_answered_calls($start_date, $end_date, $queue, $agent, $source);

            //calculate sl percent
            $sl_result = round(($calls_in_time / $call_answered) * 100, 2);

            //set result array
            $result = array("allCalls" => $call_answered, "percentValues" => $sl_result." / ".$call_ans_in);

            //SL answered calls / all calls (2)
        }else if($sl_type == 2){

            //define variables
            $all_calls = $this->get_sum_calls($start_date, $end_date, $queue, $agent, $source);
            $calls_in_time = $this->get_sum_answered_calls_in_time($start_date, $end_date, $call_ans_in, $queue, $agent, $source);

            //calculate sl percent
            $sl_result = round(($calls_in_time / $all_calls) * 100, 2);

            //set result array
            $result = array("allCalls" => $all_calls, "percentValues" => $sl_result." / ".$call_ans_in);

            //SL all calls / all calls (3)
        } else if($sl_type == 3) {

            //define variables
            $all_calls = $this->get_sum_calls($start_date, $end_date, $queue, $agent, $source);
            $all_calls_in_time = $this->get_sum_calls_in_time($start_date, $end_date, $call_ans_in, $queue, $agent, $source);

            //calculate sl percent
            $sl_result = round(($all_calls_in_time / $all_calls) * 100, 2);

            //set result array
            $result = array("allCalls" => $all_calls, "percentValues" => $sl_result." / ".$call_ans_in);

            //SL answered calls / partly all calls (4)
        } else if($sl_type == 4) {

            //define variables
            $calls_in_time = $this->get_sum_answered_calls_in_time($start_date, $end_date, $call_ans_in, $queue, $agent, $source);
            $calls_partly_all = $this->get_sum_partly_calls($start_date, $end_date, $call_not_after, $queue, $agent, $source);

            //calculate sl percent
            $sl_result = round(($calls_in_time / $calls_partly_all) * 100, 2);

            //set result array
            $result = array("allCalls" => $calls_partly_all, "percentValues" => $sl_result." / ".$call_ans_in);

        }

        return $result;
    }

    public function getSlTypes()
    {
        parent::setQuery("  SELECT  id, name
                            FROM    sl_type
                            WHERE   actived = 1");

        $slTypes = parent::getResultArray();

        return  $slTypes['result'];
    }



    public function get_sum_answered_calls_in_time($start_date, $end_date, $call_ans_in, $queue, $agent, $source){
        if ($source == 4) {
            parent::setQuery(" SELECT  COUNT(*) AS calls_in_time
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                		      chat.id
                        			FROM      chat
                        			LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = chat.id AND dashboard_chat_durations.source_id = 2 
                                              AND chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0 
                        			JOIN      user_info ON chat.last_user_id = user_info.user_id
                        			WHERE     chat.last_user_id > 0 AND chat.join_date BETWEEN '$start_date' AND '$end_date' AND chat.last_user_id IN($queue)
                                  
                    			    GROUP BY  chat.id) AS `chat_dur`
                            JOIN    chat ON chat.id = chat_dur.id
                            WHERE   chat.join_date BETWEEN '$start_date' AND '$end_date' AND chat.last_user_id IN($queue)
                            AND     chat_dur.avg_duration < '$call_ans_in'");
        }elseif ($source == 7){
            parent::setQuery(" SELECT   COUNT(*) AS answered_calls
                			FROM   (SELECT SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                						   mail.id
                			        FROM   mail
                			LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = mail.id AND dashboard_chat_durations.source_id = 6
                					  AND mail.user_id != 1 AND dashboard_chat_durations.duration > 0 
                			JOIN      user_info ON mail.user_id = user_info.user_id
                			WHERE     mail.user_id > 0 AND mail.send_datetime BETWEEN '$start_date' AND '$end_date' AND mail.user_id IN($queue)
                						
                			GROUP BY  mail.id) AS `chat_dur`
                			JOIN      mail ON mail.id = chat_dur.id
                			WHERE     mail.send_datetime BETWEEN '$start_date' AND '$end_date' AND mail.user_id IN($queue)
                            AND       chat_dur.avg_duration < '$call_ans_in'");
        }elseif ($source == 6){
            parent::setQuery(" SELECT  COUNT(*) AS answered_calls
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                              fb_chat.id
                                    FROM      fb_chat
                                    LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = fb_chat.id AND dashboard_chat_durations.source_id = 4
                                    AND       fb_chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0
                                    JOIN      user_info ON fb_chat.last_user_id = user_info.user_id
                                    WHERE     fb_chat.last_user_id > 0 AND fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND fb_chat.last_user_id IN($queue)
                                    
                                    GROUP BY  fb_chat.id) AS `chat_dur`
                            JOIN    fb_chat ON fb_chat.id = chat_dur.id
                            WHERE   fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND fb_chat.last_user_id IN($queue)
                            AND     chat_dur.avg_duration < '$call_ans_in'");
        }elseif ($source == 14){
            parent::setQuery(" SELECT  COUNT(*) AS answered_calls
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                              viber_chat.id
                                    FROM      viber_chat
                                    LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = viber_chat.id AND dashboard_chat_durations.source_id = 5
                                    AND       viber_chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0
                                    JOIN      user_info ON viber_chat.last_user_id = user_info.user_id
                                    WHERE     viber_chat.last_user_id > 0 AND viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND viber_chat.last_user_id IN($queue)
                                    
                                    GROUP BY  viber_chat.id) AS `chat_dur`
                            JOIN    viber_chat ON viber_chat.id = chat_dur.id
                            WHERE   viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND viber_chat.last_user_id IN($queue)
                            AND     chat_dur.avg_duration < '$call_ans_in'");
        }else{
            parent::setQuery(" SELECT COUNT(asterisk_call_log.id) AS calls_in_time
                            FROM   asterisk_call_log
                            JOIN   asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                            JOIN   asterisk_extension ON asterisk_call_log.extension_id = asterisk_extension.id
                            WHERE  asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date')
                            AND    asterisk_queue.id IN ($queue)
                            AND    asterisk_extension.id IN ($agent)
                            AND    asterisk_call_log.wait_time < '$call_ans_in'
                            AND    asterisk_call_log.call_type_id = 1
                            AND    asterisk_call_log.call_status_id IN(6,7)");
        }
        
        $result = parent::getResultArray();
        return  $result['result'][0]["calls_in_time"];
    }

    public function get_sum_answered_calls($start_date, $end_date, $queue, $agent, $source) {      
        if ($source == 4) {
            parent::setQuery(" SELECT  COUNT(*) AS answered_calls
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                		      chat.id
                        			FROM      chat
                        			LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = chat.id AND dashboard_chat_durations.source_id = 2 
                                              AND chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0 
                        			JOIN      user_info ON chat.last_user_id = user_info.user_id
                        			WHERE     chat.last_user_id > 0 AND chat.join_date BETWEEN '$start_date' AND '$end_date' AND chat.last_user_id IN($queue)
                                  
                    			    GROUP BY  chat.id) AS `chat_dur`
                            JOIN    chat ON chat.id = chat_dur.id
                            WHERE   chat.join_date BETWEEN '$start_date' AND '$end_date' AND chat.last_user_id IN($queue)");
        }elseif ($source == 7){
            parent::setQuery(" SELECT   COUNT(*) AS answered_calls
                			FROM   (SELECT SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                						   mail.id
                			        FROM   mail
                			LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = mail.id AND dashboard_chat_durations.source_id = 6
                					  AND mail.user_id != 1 AND dashboard_chat_durations.duration > 0 
                			JOIN      user_info ON mail.user_id = user_info.user_id
                			WHERE     mail.user_id > 0 AND mail.send_datetime BETWEEN '$start_date' AND '$end_date' AND mail.user_id IN($queue)
                						
                			GROUP BY  mail.id) AS `chat_dur`
                			JOIN      mail ON mail.id = chat_dur.id
                			WHERE     mail.send_datetime BETWEEN '$start_date' AND '$end_date' AND mail.user_id IN($queue)");
        }elseif ($source == 6){
            parent::setQuery(" SELECT  COUNT(*) AS answered_calls
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                              fb_chat.id
                                    FROM      fb_chat
                                    LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = fb_chat.id AND dashboard_chat_durations.source_id = 4
                                    AND       fb_chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0
                                    JOIN      user_info ON fb_chat.last_user_id = user_info.user_id
                                    WHERE     fb_chat.last_user_id > 0 AND fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND fb_chat.last_user_id IN($queue)
                                    
                                    GROUP BY  fb_chat.id) AS `chat_dur`
                            JOIN    fb_chat ON fb_chat.id = chat_dur.id
                            WHERE   fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND fb_chat.last_user_id IN($queue)");
        }elseif ($source == 14){
            parent::setQuery(" SELECT  COUNT(*) AS answered_calls
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                              viber_chat.id
                                    FROM      viber_chat
                                    LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = viber_chat.id AND dashboard_chat_durations.source_id = 5
                                    AND       viber_chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0
                                    JOIN      user_info ON viber_chat.last_user_id = user_info.user_id
                                    WHERE     viber_chat.last_user_id > 0 AND viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND viber_chat.last_user_id IN($queue)
                                    
                                    GROUP BY  viber_chat.id) AS `chat_dur`
                            JOIN    viber_chat ON viber_chat.id = chat_dur.id
                            WHERE   viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND viber_chat.last_user_id IN($queue)");
        }else{
            parent::setQuery("SELECT COUNT(asterisk_call_log.id) AS answered_calls
                            FROM   asterisk_call_log
                            JOIN   asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                            JOIN   asterisk_extension ON asterisk_call_log.extension_id = asterisk_extension.id
                            WHERE  asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date')
                            AND    asterisk_queue.id IN ($queue)
                            AND    asterisk_extension.id IN ($agent)
                            AND    asterisk_call_log.call_type_id = 1
                            AND    asterisk_call_log.call_status_id IN(6,7)");
        }
       

        $result =  parent::getResultArray();
        return  $result['result'][0]["answered_calls"];

    }

    public function get_sum_calls($start_date, $end_date, $queue, $agent, $source) {        
        if ($source == 4) {
            parent::setQuery(" SELECT COUNT(*) AS all_calls
                            FROM   chat 
                            JOIN   incomming_call ON incomming_call.chat_id = chat.id
                            WHERE  chat.join_date BETWEEN '$start_date' AND '$end_date'
                            AND    chat.last_user_id IN($queue)");
        }elseif ($source == 7){
            parent::setQuery(" SELECT COUNT(*) AS all_calls
                            FROM   mail 
                            JOIN   incomming_call ON incomming_call.mail_chat_id = mail.id
                            WHERE  mail.send_datetime BETWEEN '$start_date' AND '$end_date'
                            AND    mail.user_id IN($queue)");
        }elseif ($source == 6){
            parent::setQuery(" SELECT COUNT(*) AS all_calls
                            FROM   fb_chat
                            JOIN   incomming_call ON incomming_call.fb_chat_id = fb_chat.id
                            WHERE  fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date'
                            AND    fb_chat.last_user_id IN($queue)");
        }elseif ($source == 14){
            parent::setQuery(" SELECT COUNT(*) AS all_calls
                            FROM   viber_chat
                            JOIN   incomming_call ON incomming_call.viber_chat_id = viber_chat.id
                            WHERE  viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date'
                            AND    fb_chat.last_user_id IN($queue)");
        }else{
            parent::setQuery("SELECT COUNT(asterisk_call_log.id) AS all_calls
                           FROM   asterisk_call_log
                           JOIN   asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                           WHERE  asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date') 
                           AND    asterisk_queue.id IN ($queue)
                           AND    asterisk_call_log.call_type_id = 1
                           AND    asterisk_call_log.call_status_id IN(6,7,9)");
        }
  
        $result =  parent::getResultArray();
        return     $result['result'][0]["all_calls"];

    }

    public function get_sum_calls_in_time($start_date, $end_date, $call_ans_in, $queue, $agent, $source) {
        
        if ($source == 4) {
            parent::setQuery(" SELECT  COUNT(*) AS calls_in_time
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                		      chat.id
                        			FROM      chat
                        			LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = chat.id AND dashboard_chat_durations.source_id = 2 
                                              AND chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0 
                        			JOIN      user_info ON chat.last_user_id = user_info.user_id
                        			WHERE     chat.last_user_id > 0 AND chat.join_date BETWEEN '$start_date' AND '$end_date' AND chat.last_user_id IN($queue)
                                  
                    			    GROUP BY  chat.id) AS `chat_dur`
                            JOIN    chat ON chat.id = chat_dur.id
                            WHERE   chat.join_date BETWEEN '$start_date' AND '$end_date' AND chat.last_user_id IN($queue)
                            AND     chat_dur.avg_duration < '$call_ans_in'");
        }elseif ($source == 7){
            parent::setQuery(" SELECT   COUNT(*) AS calls_in_time
                			FROM   (SELECT SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                						   mail.id
                			        FROM   mail
                			LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = mail.id AND dashboard_chat_durations.source_id = 6
                					  AND mail.user_id != 1 AND dashboard_chat_durations.duration > 0 
                			JOIN      user_info ON mail.user_id = user_info.user_id
                			WHERE     mail.user_id > 0 AND mail.send_datetime BETWEEN '$start_date' AND '$end_date' AND mail.user_id IN($queue)
                						
                			GROUP BY  mail.id) AS `chat_dur`
                			JOIN      mail ON mail.id = chat_dur.id
                			WHERE     mail.send_datetime BETWEEN '$start_date' AND '$end_date' AND mail.user_id IN($queue)
                            AND     chat_dur.avg_duration < '$call_ans_in'");
        }elseif ($source == 6){
            parent::setQuery(" SELECT  COUNT(*) AS calls_in_time
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                              fb_chat.id
                                    FROM      fb_chat
                                    LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = fb_chat.id AND dashboard_chat_durations.source_id = 4
                                    AND       fb_chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0
                                    JOIN      user_info ON fb_chat.last_user_id = user_info.user_id
                                    WHERE     fb_chat.last_user_id > 0 AND fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND fb_chat.last_user_id IN($queue)
                                    GROUP BY  mail.id) AS `chat_dur`
                            JOIN    fb_chat ON fb_chat.id = chat_dur.id
                            WHERE   fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND fb_chat.last_user_id IN($queue)
                            AND     chat_dur.avg_duration < '$call_ans_in'");
        }elseif ($source == 14){
            parent::setQuery(" SELECT  COUNT(*) AS calls_in_time
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                              viber_chat.id
                                    FROM      viber_chat
                                    LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = viber_chat.id AND dashboard_chat_durations.source_id = 5
                                    AND       viber_chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0
                                    JOIN      user_info ON viber_chat.last_user_id = user_info.user_id
                                    WHERE     viber_chat.last_user_id > 0 AND viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND viber_chat.last_user_id IN($queue)
                                    GROUP BY  mail.id) AS `chat_dur`
                            JOIN    viber_chat ON viber_chat.id = chat_dur.id
                            WHERE   viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND viber_chat.last_user_id IN($queue)
                            AND     chat_dur.avg_duration < '$call_ans_in'");
        }else{
            parent::setQuery("SELECT COUNT(asterisk_call_log.id) AS calls_in_time
                           FROM   asterisk_call_log
                           JOIN   asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                           WHERE  asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date') 
                           AND    asterisk_queue.id IN ($queue)
                           AND    asterisk_call_log.wait_time < '$call_ans_in'
                           AND    asterisk_call_log.call_type_id = 1
                           AND    asterisk_call_log.call_status_id IN(6,7,9)");
        }

        $result = parent::getResultArray();
        return $result['result'][0]["calls_in_time"];

    }

    public function get_sum_partly_calls($start_date, $end_date, $call_unans_after, $queue, $agent, $source) {
        return $this->get_sum_unanswered_calls_after_time($start_date, $end_date, $call_unans_after, $queue, $agent, $source) + $this->get_sum_answered_calls($start_date, $end_date, $queue, $agent, $source);
    }

    public function get_sum_unanswered_calls_after_time($start_date, $end_date, $call_unans_after, $queue, $agent, $source) {
        
        if ($source == 4) {
            parent::setQuery(" SELECT 0 AS calls_in_time");
        }elseif ($source == 7){
            parent::setQuery(" SELECT 0 AS calls_in_time");
        }elseif ($source == 6){
            parent::setQuery(" SELECT 0 AS calls_in_time");
        }elseif ($source == 14){
            parent::setQuery(" SELECT 0 AS calls_in_time");
        }else{
            parent::setQuery(" SELECT COUNT(asterisk_call_log.id) AS calls_in_time
                            FROM   asterisk_call_log
                            JOIN   asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                            WHERE  asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date')
                            AND    asterisk_queue.id IN ($queue)
                            AND    asterisk_call_log.wait_time > '$call_unans_after'
                            AND    asterisk_call_log.call_type_id = 1
                            AND    asterisk_call_log.call_status_id IN(9)");
        }
        

        $result = parent::getResultArray();
        return  $result['result'][0]["calls_in_time"];

    }

    public function callData($start_date, $end_date, $queue, $agent, $source){
        if ($source == 4) {
            parent::setQuery(" SELECT   COUNT(*) AS count
                            FROM     chat 
                            JOIN     incomming_call ON incomming_call.chat_id = chat.id
                            WHERE    chat.`status` IN(1,2,12,13)
                            AND      chat.join_date BETWEEN '$start_date' AND '$end_date'
                            AND      chat.last_user_id IN($queue)");
            
            $row_all_calls = parent::getResultArray();
            
            parent::setQuery(" SELECT   COUNT(*) AS count
                            FROM     chat 
                            JOIN     incomming_call ON incomming_call.chat_id = chat.id
                            WHERE    chat.`status` IN(1,2,12,13)
                            AND      chat.join_date BETWEEN '$start_date' AND '$end_date'
                            AND      chat.last_user_id IN($queue)");
            
            $row_answer = parent::getResultArray();
            
            parent::setQuery(" SELECT 0 AS count");
            
            $row_abadon = parent::getResultArray();
            
            parent::setQuery(" SELECT 0 AS count");
            
            $row_transfer = parent::getResultArray();
            
            parent::setQuery(" SELECT   SEC_TO_TIME(ROUND(SUM(UNIX_TIMESTAMP(last_request_datetime) - UNIX_TIMESTAMP(join_date))/COUNT(*))) AS avarage
                            FROM     chat 
                            JOIN     incomming_call ON incomming_call.chat_id = chat.id
                            WHERE    chat.`status` IN(1,2,12,13)
                            AND      chat.join_date BETWEEN '$start_date' AND '$end_date'
                            AND      chat.last_user_id IN($queue)");
            
            $row_avarage = parent::getResultArray();
            
            parent::setQuery(" SELECT GROUP_CONCAT(user_info.`name`) AS `queue_info`
                            FROM   user_info
                            WHERE user_id IN($queue)");
            
            $req_info = parent::getResultArray();
            $queue_list = $req_info['result'][0]['queue_info'];
            
        }elseif ($source == 7){
           
            parent::setQuery(" SELECT   COUNT(*) AS count
                            FROM     mail
                            JOIN     incomming_call ON incomming_call.mail_chat_id = mail.id
                            WHERE    mail.`mail_status_id` IN(1,2,3)
                            AND      mail.send_datetime BETWEEN '$start_date' AND '$end_date'
                            AND      mail.user_id IN($queue)");
            
            $row_all_calls = parent::getResultArray();
            
            parent::setQuery(" SELECT   COUNT(*) AS count
                            FROM     mail
                            JOIN     incomming_call ON incomming_call.mail_chat_id = mail.id
                            WHERE    mail.`mail_status_id` IN(1,2,3)
                            AND      mail.send_datetime BETWEEN '$start_date' AND '$end_date'
                            AND      mail.user_id IN($queue)");
            
            $row_answer = parent::getResultArray();
            
            parent::setQuery(" SELECT 0 AS count");
            
            $row_abadon = parent::getResultArray();
            
            parent::setQuery(" SELECT 0 AS count");
            
            $row_transfer = parent::getResultArray();
            
            parent::setQuery(" SELECT   SEC_TO_TIME(ROUND(SUM(UNIX_TIMESTAMP(last_datetime) - UNIX_TIMESTAMP(send_datetime))/COUNT(*))) AS avarage
                            FROM     mail
                            JOIN     incomming_call ON incomming_call.mail_chat_id = mail.id
                            WHERE    mail.`mail_status_id` IN(1,2,3)
                            AND      mail.send_datetime BETWEEN '$start_date' AND '$end_date'
                            AND      mail.user_id IN($queue)");
            
            $row_avarage = parent::getResultArray();
            
            parent::setQuery("SELECT GROUP_CONCAT(user_info.`name`) AS `queue_info` 
                           FROM   user_info 
                           WHERE user_id IN($queue)");
            
            $req_info = parent::getResultArray();
            $queue_list = $req_info['result'][0]['queue_info'];
            
        }elseif ($source == 6){
            
            parent::setQuery(" SELECT   COUNT(*) AS count
                            FROM     fb_chat
                            JOIN     incomming_call ON incomming_call.fb_chat_id = fb_chat.id
                            WHERE    fb_chat.`status` IN(1,2,3)
                            AND      fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date'
                            AND      fb_chat.last_user_id IN($queue)");
            
            $row_all_calls = parent::getResultArray();
            
            parent::setQuery(" SELECT   COUNT(*) AS count
                            FROM     fb_chat
                            JOIN     incomming_call ON incomming_call.fb_chat_id = fb_chat.id
                            WHERE    fb_chat.`status` IN(1,2,3)
                            AND      fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date'
                            AND      fb_chat.last_user_id IN($queue)");
            
            $row_answer = parent::getResultArray();
            
            parent::setQuery(" SELECT 0 AS count");
            
            $row_abadon = parent::getResultArray();
            
            parent::setQuery(" SELECT 0 AS count");
            
            $row_transfer = parent::getResultArray();
            
            parent::setQuery(" SELECT   SEC_TO_TIME(ROUND(SUM(UNIX_TIMESTAMP(last_datetime) - UNIX_TIMESTAMP(first_datetime))/COUNT(*))) AS avarage
                            FROM     fb_chat
                            JOIN     incomming_call ON incomming_call.fb_chat_id = fb_chat.id
                            WHERE    fb_chat.`status` IN(1,2,3)
                            AND      fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date'
                            AND      fb_chat.last_user_id IN($queue)");
            
            $row_avarage = parent::getResultArray();
            
            parent::setQuery(" SELECT GROUP_CONCAT(user_info.`name`) AS `queue_info`
                            FROM   user_info
                            WHERE user_id IN($queue)");
            
            $req_info = parent::getResultArray();
            $queue_list = $req_info['result'][0]['queue_info'];
            
        }elseif ($source == 14){
            
            parent::setQuery(" SELECT   COUNT(*) AS count
                            FROM     viber_chat
                            JOIN     incomming_call ON incomming_call.viber_chat_id = viber_chat.id
                            WHERE    viber_chat.`status` IN(1,2,3)
                            AND      viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date'
                            AND      viber_chat.last_user_id IN($queue)");
            
            $row_all_calls = parent::getResultArray();
            
            parent::setQuery(" SELECT   COUNT(*) AS count
                            FROM     viber_chat
                            JOIN     incomming_call ON incomming_call.viber_chat_id = viber_chat.id
                            WHERE    viber_chat.`status` IN(1,2,3)
                            AND      viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date'
                            AND      viber_chat.last_user_id IN($queue)");
            
            $row_answer = parent::getResultArray();
            
            parent::setQuery(" SELECT 0 AS count");
            
            $row_abadon = parent::getResultArray();
            
            parent::setQuery(" SELECT 0 AS count");
            
            $row_transfer = parent::getResultArray();
            
            parent::setQuery(" SELECT   SEC_TO_TIME(ROUND(SUM(UNIX_TIMESTAMP(last_datetime) - UNIX_TIMESTAMP(first_datetime))/COUNT(*))) AS avarage
                            FROM     viber_chat
                            JOIN     incomming_call ON incomming_call.viber_chat_id = viber_chat.id
                            WHERE    viber_chat.`status` IN(1,2,3)
                            AND      viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date'
                            AND      viber_chat.last_user_id IN($queue)");
            
            $row_avarage = parent::getResultArray();
            
            parent::setQuery(" SELECT GROUP_CONCAT(user_info.`name`) AS `queue_info`
                            FROM   user_info
                            WHERE user_id IN($queue)");
            
            $req_info = parent::getResultArray();
            $queue_list = $req_info['result'][0]['queue_info'];
            
        }else{
            parent::setQuery(" SELECT COUNT(asterisk_call_log.id) AS count
                            FROM   asterisk_call_log
                            JOIN   asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                            WHERE  asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date')
                            AND    asterisk_queue.id IN ($queue)
                            AND    asterisk_call_log.call_type_id = 1
                            AND    asterisk_call_log.call_status_id IN(6,7,9)");
            
            $row_all_calls = parent::getResultArray();
            
            parent::setQuery(" SELECT COUNT(asterisk_call_log.id) AS count,
                                   SUM(IF(asterisk_call_log.call_status_id = 6,1,0)) AS `completeagent`,
                                   SUM(IF(asterisk_call_log.call_status_id = 7,1,0)) AS `completecaller`
                            FROM   asterisk_call_log
                            JOIN   asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                            JOIN   asterisk_extension ON asterisk_call_log.extension_id = asterisk_extension.id
                            WHERE  asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date')
                            AND    asterisk_queue.id IN ($queue)
                            AND    asterisk_extension.id IN ($agent)
                            AND    asterisk_call_log.call_type_id = 1
                            AND    asterisk_call_log.call_status_id IN(6,7)");
            
            $row_answer = parent::getResultArray();
            
            parent::setQuery(" SELECT COUNT(asterisk_call_log.id) AS count
                            FROM   asterisk_call_log
                            JOIN   asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                            WHERE  asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date')
                            AND    asterisk_queue.id IN ($queue)
                            AND    asterisk_call_log.call_type_id = 1
                            AND    asterisk_call_log.call_status_id IN(9)");
            
            $row_abadon = parent::getResultArray();
            
            parent::setQuery(" SELECT 0 AS count");
            
            $row_transfer = parent::getResultArray();
            
            parent::setQuery(" SELECT SEC_TO_TIME(ROUND(sum(talk_time)/COUNT(asterisk_call_log.id))) AS avarage
                            FROM   asterisk_call_log
                            JOIN   asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                            WHERE  asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date')
                            AND    asterisk_queue.id IN ($queue)
                            AND    asterisk_call_log.call_type_id = 1
                            AND    asterisk_call_log.call_status_id IN(6,7,9)");
            
            $row_avarage = parent::getResultArray();
            
            $queue_list = $queue;
            
        }


        if ($source==4) {
            parent::setQuery(" SELECT   COUNT(*) AS count
                            FROM     chat
                            JOIN     incomming_request ON incomming_request.chat_id = chat.id
                            WHERE    chat.`status` IN(1,2,12,13)
                            AND      chat.join_date BETWEEN '$start_date' AND '$end_date'
                            AND      chat.last_user_id IN($queue)
                            AND      incomming_request.user_id > 0");
            
        }elseif ($source == 7){
            parent::setQuery(" SELECT   COUNT(*) AS count
                            FROM     mail
                            JOIN     incomming_request ON incomming_request.mail_chat_id = mail.id
                            WHERE    mail.`mail_status_id` IN(1,2,3)
                            AND      mail.send_datetime BETWEEN '$start_date' AND '$end_date'
                            AND      mail.user_id IN($queue)
                            AND      incomming_request.user_id > 0");
        }elseif ($source == 6){
            parent::setQuery(" SELECT   COUNT(*) AS count
                            FROM     fb_chat
                            JOIN     incomming_request ON incomming_request.fb_chat_id = fb_chat.id
                            WHERE    fb_chat.`status` IN(1,2,3)
                            AND      fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date'
                            AND      fb_chat.last_user_id IN($queue)
                            AND      incomming_request.user_id > 0");
        }elseif ($source == 14){
            parent::setQuery(" SELECT   COUNT(*) AS count
                            FROM     viber_chat
                            JOIN     incomming_request ON incomming_request.viber_chat_id = viber_chat.id
                            WHERE    viber_chat.`status` IN(1,2,3)
                            AND      viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date'
                            AND      viber_chat.last_user_id IN($queue)
                            AND      incomming_request.user_id > 0");
        }else{
            parent::setQuery("	SELECT COUNT(asterisk_call_log.id) AS count
                            FROM   asterisk_call_log
                            JOIN   asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                            JOIN   asterisk_extension ON asterisk_call_log.extension_id = asterisk_extension.id
                            JOIN    incomming_request ON incomming_request.asterisk_call_log_id = asterisk_call_log.id AND incomming_request.user_id>0
                            WHERE  asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date') 
                            AND    asterisk_queue.id IN ($queue)
                            AND    asterisk_extension.id IN ($agent)
                            AND    asterisk_call_log.call_type_id = 1
                            AND    asterisk_call_log.call_status_id IN(6,7)");
        }

        $row_done_blank = parent::getResultArray();


        if ($source == 4) {
            parent::setQuery(" SELECT  SEC_TO_TIME(ROUND((SUM(UNIX_TIMESTAMP(chat.last_request_datetime) - UNIX_TIMESTAMP(chat.join_date)) / COUNT(*)))) AS `sec`,
                                    SEC_TO_TIME(SUM(UNIX_TIMESTAMP(chat.last_request_datetime) - UNIX_TIMESTAMP(chat.join_date))) AS `min`,
                                    SEC_TO_TIME(ROUND(SUM(chat_dur.avg_duration)/COUNT(*))) AS `hold`,
                                    SUM(UNIX_TIMESTAMP(chat.last_request_datetime) - UNIX_TIMESTAMP(chat.join_date)) AS `all_talk_time`
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                              chat.id
                                    FROM      chat
                                    LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = chat.id AND dashboard_chat_durations.source_id = 2 
                                              AND chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0 
                                    JOIN      user_info ON chat.last_user_id = user_info.user_id
                                    WHERE     chat.last_user_id > 0 AND chat.join_date BETWEEN '$start_date' AND '$end_date' AND chat.last_user_id IN($queue)
                                  
                                    GROUP BY  chat.id) AS `chat_dur`
                            JOIN    chat ON chat.id = chat_dur.id
                            WHERE   chat.join_date BETWEEN '$start_date' AND '$end_date' AND chat.last_user_id IN($queue)");
        }elseif ($source == 7){
            parent::setQuery("SELECT   SEC_TO_TIME(ROUND((SUM(UNIX_TIMESTAMP(mail.last_datetime) - UNIX_TIMESTAMP(mail.send_datetime)) / COUNT(*)))) AS `sec`,
                                    SEC_TO_TIME(SUM(UNIX_TIMESTAMP(mail.last_datetime) - UNIX_TIMESTAMP(mail.send_datetime))) AS `min`,
                                    SEC_TO_TIME(ROUND(SUM(chat_dur.avg_duration)/COUNT(*))) AS `hold`,
                                    SUM(UNIX_TIMESTAMP(mail.last_datetime) - UNIX_TIMESTAMP(mail.send_datetime)) AS `all_talk_time`
                            FROM   (SELECT SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                           mail.id
                                    FROM   mail
                            LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = mail.id AND dashboard_chat_durations.source_id = 6
                                      AND mail.user_id != 1 AND dashboard_chat_durations.duration > 0 
                            JOIN      user_info ON mail.user_id = user_info.user_id
                            WHERE     mail.user_id > 0 AND mail.send_datetime BETWEEN '$start_date' AND '$end_date' AND mail.user_id IN($queue)
                                        
                            GROUP BY  mail.id) AS `chat_dur`
                            JOIN      mail ON mail.id = chat_dur.id
                            WHERE     mail.send_datetime BETWEEN '$start_date' AND '$end_date' AND mail.user_id IN($queue)");
        }elseif ($source == 6){
            parent::setQuery(" SELECT  SEC_TO_TIME(ROUND((SUM(UNIX_TIMESTAMP(fb_chat.last_datetime) - UNIX_TIMESTAMP(fb_chat.first_datetime)) / COUNT(*)))) AS `sec`,
                                    SEC_TO_TIME(SUM(UNIX_TIMESTAMP(fb_chat.last_datetime) - UNIX_TIMESTAMP(fb_chat.first_datetime))) AS `min`,
                                    SEC_TO_TIME(ROUND(SUM(chat_dur.avg_duration)/COUNT(*))) AS `hold`,
                                    SUM(UNIX_TIMESTAMP(fb_chat.last_datetime) - UNIX_TIMESTAMP(fb_chat.first_datetime)) AS `all_talk_time`
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                              fb_chat.id
                                    FROM      fb_chat
                                    LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = fb_chat.id AND dashboard_chat_durations.source_id = 4
                                    AND       fb_chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0
                                    JOIN      user_info ON fb_chat.last_user_id = user_info.user_id
                                    WHERE     fb_chat.last_user_id > 0 AND fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND fb_chat.last_user_id IN($queue)
                            
                                    GROUP BY  fb_chat.id) AS `chat_dur`
                            JOIN    fb_chat ON fb_chat.id = chat_dur.id
                            WHERE   fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND fb_chat.last_user_id IN($queue)");
        }elseif ($source == 14){
            parent::setQuery(" SELECT  SEC_TO_TIME(ROUND((SUM(UNIX_TIMESTAMP(viber_chat.last_datetime) - UNIX_TIMESTAMP(viber_chat.first_datetime)) / COUNT(*)))) AS `sec`,
                                    SEC_TO_TIME(SUM(UNIX_TIMESTAMP(viber_chat.last_datetime) - UNIX_TIMESTAMP(viber_chat.first_datetime))) AS `min`,
                                    SEC_TO_TIME(ROUND(SUM(chat_dur.avg_duration)/COUNT(*))) AS `hold`,
                                    SUM(UNIX_TIMESTAMP(viber_chat.last_datetime) - UNIX_TIMESTAMP(viber_chat.first_datetime)) AS `all_talk_time`
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                              viber_chat.id
                                    FROM      viber_chat
                                    LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = viber_chat.id AND dashboard_chat_durations.source_id = 5
                                    AND       viber_chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0
                                    JOIN      user_info ON viber_chat.last_user_id = user_info.user_id
                                    WHERE     viber_chat.last_user_id > 0 AND viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND viber_chat.last_user_id IN($queue)
                                    
                                    GROUP BY  viber_chat.id) AS `chat_dur`
                            JOIN    viber_chat ON viber_chat.id = chat_dur.id
                            WHERE   viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND viber_chat.last_user_id IN($queue)");
        }else{
            parent::setQuery("SELECT   ROUND((SUM(wait_time) / COUNT(*)),2) AS `hold`,
                                    ROUND((SUM(talk_time) / COUNT(*)),2) AS `sec`,
                                    ROUND((SUM(talk_time) / 60 ),2) AS `min`,
                                    SUM(talk_time) AS `all_talk_time`
                           FROM     asterisk_call_log
                           JOIN     asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                           JOIN     asterisk_extension ON asterisk_call_log.extension_id = asterisk_extension.id
                           WHERE    asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date') 
                           AND      asterisk_queue.id IN ($queue)
                           AND      asterisk_extension.id IN ($agent)
                           AND      asterisk_call_log.call_type_id = 1
                           AND      asterisk_call_log.call_status_id IN(6,7)");
        }
        
        $row_clock = parent::getResultArray();

        $reportData = array();

        $reportData['row_all_calls']    = $row_all_calls['result'][0]['count'];
        $reportData['row_answer']       = $row_answer['result'][0]['count'];
        $reportData['row_answer_completeagent']       = $row_answer['result'][0]['completeagent'];
        $reportData['row_answer_completecaller']      = $row_answer['result'][0]['completecaller'];
        $reportData['row_abadon']       = $row_abadon['result'][0]['count'];
        $reportData['row_abadon']       = $row_abadon['result'][0]['count'];
        $reportData['row_transfer']     = $row_transfer['result'][0]['count'];
        $reportData['row_avarage']      = $row_avarage['result'][0]['avarage'];
        $reportData['row_done_blank']   = $row_done_blank['result'][0]['count'];
        $reportData['row_clock_sec']    = $row_clock['result'][0]['sec'];
        $reportData['row_clock_min']    = $row_clock['result'][0]['min'];
        $reportData['row_clock_hold']   = $row_clock['result'][0]['hold'];
        $reportData['row_clock_all_talk_time']   = $row_clock['result'][0]['all_talk_time'];
        


        
        return $reportData;
    }

    public function reportData(){
        $reportData = array();
        $reportData['report_info'] = array();
        $reportData['answer_info'] = array();
        $reportData['unanswer_info'] = array();

        $reportData['totals'] = array();
        $queue          = rawurldecode($_REQUEST["queues"]);
        $agent          = rawurldecode($_REQUEST["exts"]);
        
        $start          = $_REQUEST['startDate'];
        $end 	        = $_REQUEST['endDate'];
        $start_date     = $start.' 00:00';
        $end_date       = $end.' 23:59';
        $sl_type        = $_REQUEST["slType"];
        $call_ans_in    = $_REQUEST["callAnsIn"];
        $call_not_after = $_REQUEST["callNotAfter"];
        $source         = 1;

        $day        = (strtotime($end_date)) -  (strtotime($start_date));
        $day_format = round(($day / (60*60*24)) + 1);

        parent::setQuery("  SELECT  GROUP_CONCAT(number SEPARATOR ', ') AS queue
                            FROM    asterisk_queue
                            WHERE   actived = 1 AND id IN ($queue)");
        $queues = parent::getResultArray();
        $queues = $queues['result'][0]['queue'];
        array_push($reportData['report_info'], array("value" => $queues));
        array_push($reportData['report_info'], array("value" => $start_date));
        array_push($reportData['report_info'], array("value" => $end_date));
        array_push($reportData['report_info'], array("value" => $day_format));


        $reports = $this->callData($start_date, $end_date, $queue, $agent, $source);

        array_push($reportData['answer_info'], array("value" => $reports['row_answer']));
        array_push($reportData['answer_info'], array("value" => $reports['row_clock_sec']));
        array_push($reportData['answer_info'], array("value" => $reports['row_clock_min']));
        array_push($reportData['answer_info'], array("value" => $reports['row_clock_hold']));


        array_push($reportData['unanswer_info'], array("value" => $reports['row_abadon']));
        array_push($reportData['unanswer_info'], array("value" => 'წამი'));
        array_push($reportData['unanswer_info'], array("value" => 1));
        array_push($reportData['unanswer_info'], array("value" => 1));

        array_push($reportData['totals'], array("value" => $reports['row_answer'] ));
        array_push($reportData['totals'], array("value" => $reports['row_abadon']));

        return $reportData;

    }

    public function answeredCallsByOperator(){
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        $queue          = rawurldecode($_REQUEST["queues"]);
        $agent          = rawurldecode($_REQUEST["exts"]);
        
        $start          = $_REQUEST['startDate'];
        $end 	        = $_REQUEST['endDate'];
        $start_date     = $start.' 00:00';
        $end_date       = $end.' 23:59';
        $sl_type        = $_REQUEST["slType"];
        $call_ans_in    = $_REQUEST["callAnsIn"];
        $call_not_after = $_REQUEST["callNotAfter"];
        $source         = 1;

        $reports = $this->callData($start_date, $end_date, $queue, $agent, $source);

        $row_answer = $reports['row_answer'];
        $row_abadon = $reports['row_abadon'];
        $row_clock_all_talk_time = $reports['row_clock_all_talk_time'];

        $all_answer_count = $row_answer;
        $all_abandon_count = $row_abadon;
        $all_answer_completeagent = $reports['row_answer_completeagent'];
        $all_answer_completecaller = $reports['completecaller'];
        
        $sum_duration     = $row_clock_all_talk_time;
        if ($all_abandon_count == '') {
            $all_abandon_count = 0;
        }
        if ($all_answer_completeagent == '') {
            $all_answer_completeagent = 0;
        }
        if ($all_answer_completecaller == '') {
            $all_answer_completecaller = 0;
        }
        
        if ($all_answer_count == '') {
            $all_answer_count = 0;
        }
        
        if ($sum_duration == '') {
            $sum_duration = 0;
        }
        if ($source == 4) {
            parent::setQuery("   SELECT    user_info.`name` AS `agent`,
                                        COUNT(*) AS `num`,
                                        ROUND(COUNT(*) / $all_answer_count * 100,2) AS `call_pr`,
                                        SEC_TO_TIME(ROUND(SUM(UNIX_TIMESTAMP(chat.last_request_datetime)-UNIX_TIMESTAMP(chat.join_date)))) AS `call_time`,
                                        ROUND(SUM(UNIX_TIMESTAMP(chat.last_request_datetime)-UNIX_TIMESTAMP(chat.join_date)) / $sum_duration * 100,2) as `call_time_pr`,
                                        SEC_TO_TIME(ROUND(sum(UNIX_TIMESTAMP(chat.last_request_datetime)-UNIX_TIMESTAMP(chat.join_date)) / count(*))) AS `avg_call_time`,
                                        SEC_TO_TIME(ROUND(sum(chat_dur.avg_duration))) AS `hold_time`,
                                        SEC_TO_TIME(ROUND(SUM(chat_dur.avg_duration) / COUNT(*))) AS `avg_hold_time`
                            FROM   (	SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                                                    chat.id
                                                FROM      chat
                                                LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = chat.id AND dashboard_chat_durations.source_id = 2 
                                                AND 			chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0 
                                                JOIN      user_info ON chat.last_user_id = user_info.user_id
                                                WHERE     chat.last_user_id > 0 AND chat.join_date BETWEEN '$start_date' AND '$end_date' AND chat.last_user_id IN($queue)
                                                GROUP BY  chat.id) AS `chat_dur`
                            JOIN    chat ON chat.id = chat_dur.id
                            JOIN    user_info ON user_info.user_id = chat.last_user_id
                            WHERE   chat.join_date BETWEEN '$start_date' AND '$end_date' AND chat.last_user_id IN($queue)
                            GROUP BY chat.last_user_id");
        }elseif ($source == 7){
            parent::setQuery("SELECT   		user_info.`name` AS `agent`,
                                        COUNT(*) AS `num`,
                                        ROUND(COUNT(*) / $all_answer_count * 100,2) AS `call_pr`,
                                        SEC_TO_TIME(ROUND(SUM(UNIX_TIMESTAMP(mail.last_datetime)-UNIX_TIMESTAMP(mail.send_datetime)))) AS `call_time`,
                                        ROUND(SUM(UNIX_TIMESTAMP(mail.last_datetime)-UNIX_TIMESTAMP(mail.send_datetime)) / $sum_duration * 100,2) as `call_time_pr`,
                                        SEC_TO_TIME(ROUND(sum(UNIX_TIMESTAMP(mail.last_datetime)-UNIX_TIMESTAMP(mail.send_datetime)) / count(*))) AS `avg_call_time`,
                                        SEC_TO_TIME(ROUND(sum(chat_dur.avg_duration))) AS `hold_time`,
                                        SEC_TO_TIME(ROUND(SUM(chat_dur.avg_duration) / COUNT(*))) AS `avg_hold_time`
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                                mail.id
                                    FROM      mail
                                    LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = mail.id AND dashboard_chat_durations.source_id = 6
                                    AND       mail.user_id != 1 AND dashboard_chat_durations.duration > 0 
                                    JOIN      user_info ON mail.user_id = user_info.user_id
                                    WHERE     mail.user_id > 0 AND mail.send_datetime BETWEEN '$start_date' AND '$end_date' AND mail.user_id IN($queue)
                            
                                            GROUP BY  mail.id) AS `chat_dur`
                            JOIN      mail ON mail.id = chat_dur.id
                            JOIN      user_info ON user_info.user_id = mail.user_id
                            WHERE     mail.send_datetime BETWEEN '$start_date' AND '$end_date' AND mail.user_id IN($queue)
                            GROUP BY mail.user_id");
        }elseif ($source == 6){
            parent::setQuery("SELECT   user_info.`name` AS `agent`,
                                    COUNT(*) AS `num`,
                                    ROUND(COUNT(*) / $all_answer_count * 100,2) AS `call_pr`,
                                    SEC_TO_TIME(ROUND(SUM(UNIX_TIMESTAMP(fb_chat.last_datetime)-UNIX_TIMESTAMP(fb_chat.first_datetime)))) AS `call_time`,
                                    ROUND(SUM(UNIX_TIMESTAMP(fb_chat.last_datetime)-UNIX_TIMESTAMP(fb_chat.first_datetime)) / $sum_duration * 100,2) as `call_time_pr`,
                                    SEC_TO_TIME(ROUND(sum(UNIX_TIMESTAMP(fb_chat.last_datetime)-UNIX_TIMESTAMP(fb_chat.first_datetime)) / count(*))) AS `avg_call_time`,
                                    SEC_TO_TIME(ROUND(sum(chat_dur.avg_duration))) AS `hold_time`,
                                    SEC_TO_TIME(ROUND(SUM(chat_dur.avg_duration) / COUNT(*))) AS `avg_hold_time`
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                            fb_chat.id
                                    FROM      fb_chat
                                    LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = fb_chat.id AND dashboard_chat_durations.source_id = 4
                                    AND       fb_chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0
                                    JOIN      user_info ON fb_chat.last_user_id = user_info.user_id
                                    WHERE     fb_chat.last_user_id > 0 AND fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND fb_chat.last_user_id IN($queue)
                                    
                                    GROUP BY  fb_chat.id) AS `chat_dur`
                            JOIN    fb_chat ON fb_chat.id = chat_dur.id
                            JOIN    user_info ON user_info.user_id = fb_chat.last_user_id
                            WHERE   fb_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND fb_chat.last_user_id IN($queue)
                            GROUP BY fb_chat.last_user_id");
        }elseif ($source == 14){
            parent::setQuery("SELECT   user_info.`name` AS `agent`,
                                    COUNT(*) AS `num`,
                                    ROUND(COUNT(*) / $all_answer_count * 100,2) AS `call_pr`,
                                    SEC_TO_TIME(ROUND(SUM(UNIX_TIMESTAMP(viber_chat.last_datetime)-UNIX_TIMESTAMP(viber_chat.first_datetime)))) AS `call_time`,
                                    ROUND(SUM(UNIX_TIMESTAMP(viber_chat.last_datetime)-UNIX_TIMESTAMP(viber_chat.first_datetime)) / $sum_duration * 100,2) as `call_time_pr`,
                                    SEC_TO_TIME(ROUND(sum(UNIX_TIMESTAMP(viber_chat.last_datetime)-UNIX_TIMESTAMP(viber_chat.first_datetime)) / count(*))) AS `avg_call_time`,
                                    SEC_TO_TIME(ROUND(sum(chat_dur.avg_duration))) AS `hold_time`,
                                    SEC_TO_TIME(ROUND(SUM(chat_dur.avg_duration) / COUNT(*))) AS `avg_hold_time`
                            FROM   (SELECT    SUM(dashboard_chat_durations.duration)/COUNT(*) AS `avg_duration`,
                                            viber_chat.id
                                    FROM      viber_chat
                                    LEFT JOIN dashboard_chat_durations ON dashboard_chat_durations.chat_id = viber_chat.id AND dashboard_chat_durations.source_id = 5
                                    AND       viber_chat.last_user_id != 1 AND dashboard_chat_durations.duration > 0
                                    JOIN      user_info ON viber_chat.last_user_id = user_info.user_id
                                    WHERE     viber_chat.last_user_id > 0 AND viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND viber_chat.last_user_id IN($queue)
                                    
                                    GROUP BY  viber_chat.id) AS `chat_dur`
                            JOIN    viber_chat ON viber_chat.id = chat_dur.id
                            JOIN    user_info ON user_info.user_id = viber_chat.last_user_id
                            WHERE   viber_chat.first_datetime BETWEEN '$start_date' AND '$end_date' AND viber_chat.last_user_id IN($queue)
                            GROUP BY viber_chat.last_user_id");
        }else{
            parent::setQuery("SELECT   asterisk_extension.number AS `agent`,
                                    COUNT(*) AS `num`,
                                    CONCAT(ROUND(COUNT(*) / $all_answer_count * 100,2),'%') AS `call_pr`,
                                    SEC_TO_TIME(ROUND(SUM(talk_time))) AS `call_time`,
                                    CONCAT(ROUND(SUM(talk_time) / $sum_duration * 100,2),'%') as `call_time_pr`,
                                    SEC_TO_TIME(ROUND(sum(talk_time) / count(*))) AS `avg_call_time`,
                                    SEC_TO_TIME(sum(wait_time)) AS `hold_time`,
                                    SEC_TO_TIME(ROUND(SUM(wait_time) / COUNT(*))) AS `avg_hold_time`
                            FROM     asterisk_call_log
                            JOIN     asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                            JOIN     asterisk_extension ON asterisk_call_log.extension_id = asterisk_extension.id
                            WHERE    asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date') 
                            AND      asterisk_queue.id IN ($queue)
                            AND      asterisk_extension.id IN ($agent)
                            AND      asterisk_call_log.call_type_id = 1
                            AND      asterisk_call_log.call_status_id IN(6,7)
                            GROUP BY asterisk_call_log.extension_id");
        }
        $ress = parent::getKendoList($this->colCount, $this->cols);

        return $ress;
    }
    public function getServiceLVL(){
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        $queue          = rawurldecode($_REQUEST["queues"]);
        $agent          = rawurldecode($_REQUEST["exts"]);
        
        $start          = $_REQUEST['startDate'];
        $end 	        = $_REQUEST['endDate'];
        $start_date     = $start.' 00:00';
        $end_date       = $end.' 23:59';
        $sl_type        = $_REQUEST["slType"];
        $call_ans_in    = $_REQUEST["callAnsIn"];
        $call_not_after = $_REQUEST["callNotAfter"];
        $source         = 1;


        parent::setQuery(" SELECT   asterisk_call_log.wait_time AS `wait_time`
                        FROM     asterisk_call_log
                        JOIN     asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                        JOIN     asterisk_extension ON asterisk_call_log.extension_id = asterisk_extension.id
                        WHERE    asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date') 
                        AND      asterisk_queue.id IN ($queue)
                        AND      asterisk_extension.id IN ($agent)
                        AND      asterisk_call_log.call_type_id = 1
                        AND      asterisk_call_log.call_status_id IN(6,7)");
        $w15 = 0;
        $w30 = 0;
        $w45 = 0;
        $w60 = 0;
        $w75 = 0;
        $w90 = 0;
        $w91 = 0;

        $res_service_level = parent::getResultArray();
        foreach($res_service_level['result'] AS $res_service_level_r) {

            if ($res_service_level_r['wait_time'] < 15) {
                $w15++;
            }

            if ($res_service_level_r['wait_time'] < 30) {
                $w30++;
            }

            if ($res_service_level_r['wait_time'] < 45) {
                $w45++;
            }

            if ($res_service_level_r['wait_time'] < 60) {
                $w60++;
            }

            if ($res_service_level_r['wait_time'] < 75) {
                $w75++;
            }

            if ($res_service_level_r['wait_time'] < 90) {
                $w90++;
            }

            $w91++;

        }

        $d30 = $w30 - $w15;
        $d45 = $w45 - $w30;
        $d60 = $w60 - $w45;
        $d75 = $w75 - $w60;
        $d90 = $w90 - $w75;
        $d91 = $w91 - $w90;


        $p15 = round($w15 * 100 / $w91);
        $p30 = round($w30 * 100 / $w91);
        $p45 = round($w45 * 100 / $w91);
        $p60 = round($w60 * 100 / $w91);
        $p75 = round($w75 * 100 / $w91);
        $p90 = round($w90 * 100 / $w91);


        $kendoEmulator = array();

            array_push($kendoEmulator, array(   "pasuxi" => '15 წამში',
                                                "raodenoba" => $w15,
                                                "delta" => '',
                                                "procenti" => $p15));

            array_push($kendoEmulator, array(   "pasuxi" => '30 წამში',
                                                "raodenoba" => $w30,
                                                "delta" => $d30,
                                                "procenti" => $p30));

            array_push($kendoEmulator, array(   "pasuxi" => '45 წამში',
                                                "raodenoba" => $w45,
                                                "delta" => $d45,
                                                "procenti" => $p45));

            array_push($kendoEmulator, array(   "pasuxi" => '60 წამში',
                                                "raodenoba" => $w60,
                                                "delta" => $d60,
                                                "procenti" => $p60));


            array_push($kendoEmulator, array(   "pasuxi" => '75 წამში',
                                                "raodenoba" => $w75,
                                                "delta" => $d75,
                                                "procenti" => $p75));

            array_push($kendoEmulator, array(   "pasuxi" => '90 წამში',
                                                "raodenoba" => $w90,
                                                "delta" => $d90,
                                                "procenti" => $p90));

            array_push($kendoEmulator, array(   "pasuxi" => '90+ წამში',
                                                "raodenoba" => $w91,
                                                "delta" => $d91,
                                                "procenti" => "100%"));
        return $kendoEmulator;
    }

    public function answerCall(){
        $queue          = rawurldecode($_REQUEST["queues"]);
        $agent          = rawurldecode($_REQUEST["exts"]);
        
        $start          = $_REQUEST['startDate'];
        $end 	        = $_REQUEST['endDate'];
        $start_date     = $start.' 00:00';
        $end_date       = $end.' 23:59';
        $sl_type        = $_REQUEST["slType"];
        $call_ans_in    = $_REQUEST["callAnsIn"];
        $call_not_after = $_REQUEST["callNotAfter"];
        $source         = 1;

        parent::setQuery("SELECT   COUNT(asterisk_call_log.id) AS count,
                                                    asterisk_queue.number AS `dst_queue`
                                           FROM     asterisk_call_log
                                           JOIN     asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                                           JOIN     asterisk_extension ON asterisk_call_log.extension_id = asterisk_extension.id
                                           WHERE    asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date') 
                                           AND      asterisk_queue.id IN ($queue)
                                           AND      asterisk_extension.id IN ($agent)
                                           AND      asterisk_call_log.call_type_id = 1
                                           AND      asterisk_call_log.call_status_id IN(6,7)
                                           GROUP BY asterisk_call_log.queue_id");
                            
        $answer_que = parent::getResultArray();

        $kendoEmulator = array();

        $reports = $this->callData($start_date, $end_date, $queue, $agent, $source);
        foreach($answer_que['result'] AS $row_answer_que){
            array_push($kendoEmulator, array(   "rigi" => $row_answer_que['dst_queue'],
                                                "sul" => $row_answer_que['count'],
                                                "procenti" => round($row_answer_que['count'] / $reports['row_answer'] * 100,2)));
        }

        return $kendoEmulator;
    }
    public function disconnectReason(){
        $queue          = rawurldecode($_REQUEST["queues"]);
        $agent          = rawurldecode($_REQUEST["exts"]);
        
        $start          = $_REQUEST['startDate'];
        $end 	        = $_REQUEST['endDate'];
        $start_date     = $start.' 00:00';
        $end_date       = $end.' 23:59';
        $sl_type        = $_REQUEST["slType"];
        $call_ans_in    = $_REQUEST["callAnsIn"];
        $call_not_after = $_REQUEST["callNotAfter"];
        $source         = 1;

        $reports = $this->callData($start_date, $end_date, $queue, $agent, $source);
        $kendoEmulator = array();

        array_push($kendoEmulator, array(   "mizezi" => "აბონენტმა გათიშა",
                                            "raodenoba" => $reports["row_abadon"],
                                            "_procenti" => round($reports["row_abadon"] / $reports["row_abadon"] * 100, 2).'%'));


        array_push($kendoEmulator, array(   "mizezi" => "დრო ამოიწურა",
                                            "raodenoba" => 0,
                                            "_procenti" => '0%'));



        array_push($kendoEmulator, array(   "mizezi" => "ტექ. ხარვეზი",
                                            "raodenoba" => 0,
                                            "_procenti" => '0%'));

        return $kendoEmulator;

    }

    public function unanswered_calls_by_queue(){
        $queue          = rawurldecode($_REQUEST["queues"]);
        $agent          = rawurldecode($_REQUEST["exts"]);
        
        $start          = $_REQUEST['startDate'];
        $end 	        = $_REQUEST['endDate'];
        $start_date     = $start.' 00:00';
        $end_date       = $end.' 23:59';
        $sl_type        = $_REQUEST["slType"];
        $call_ans_in    = $_REQUEST["callAnsIn"];
        $call_not_after = $_REQUEST["callNotAfter"];
        $source         = 1;

        $reports = $this->callData($start_date, $end_date, $queue, $agent, $source);
        $kendoEmulator = array();

        parent::setQuery("	 SELECT   COUNT(*) AS `count`,
                                asterisk_queue.number AS `queue`
                        FROM     asterisk_call_log
                        JOIN     asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                        WHERE    asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date') 
                        AND      asterisk_queue.id IN ($queue)
                        AND      asterisk_call_log.call_type_id = 1
                        AND      asterisk_call_log.call_status_id IN(9)
                        GROUP BY asterisk_call_log.queue_id");
    
        $Unanswered_Calls_by_Queue_r = parent::getResultArray();
        foreach($Unanswered_Calls_by_Queue_r['result'] AS $Unanswered_Calls_by_Queue) {
            array_push($kendoEmulator, array(   "rigi" => $Unanswered_Calls_by_Queue['queue'],
                                                "sul" => $Unanswered_Calls_by_Queue['count'],
                                                "_procenti" => round((($Unanswered_Calls_by_Queue['count'] / $Unanswered_Calls_by_Queue['count']) * 100), 2).'%'));
        }


        

        return $kendoEmulator;

    }

    public function call_distribution_per_day(){
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        $queue          = rawurldecode($_REQUEST["queues"]);
        $agent          = rawurldecode($_REQUEST["exts"]);
        
        $start          = $_REQUEST['startDate'];
        $end 	        = $_REQUEST['endDate'];
        $start_date     = $start.' 00:00';
        $end_date       = $end.' 23:59';
        $sl_type        = $_REQUEST["slType"];
        $call_ans_in    = $_REQUEST["callAnsIn"];
        $call_not_after = $_REQUEST["callNotAfter"];
        $source         = 1;

        $reports = $this->callData($start_date, $end_date, $queue, $agent, $source);
        $all_answer_count = $reports["row_answer"];
        $all_abandon_count = $reports["row_abadon"];
        parent::setQuery("SELECT   DATE(FROM_UNIXTIME(asterisk_call_log.call_datetime)) AS `datetime`,
                                COUNT(*) AS `all_call`,
                                SUM(IF(asterisk_call_log.call_status_id IN(6,7),1,0)) AS `answer_count`,
                                ROUND(SUM(IF(asterisk_call_log.call_status_id IN(6,7),1,0)) / $all_answer_count * 100,2) AS `call_answer_pr`,
                                SUM(IF(asterisk_call_log.call_status_id IN(9),1,0)) AS `unanswer_call`,
                                ROUND(SUM(IF(asterisk_call_log.call_status_id IN(9),1,0)) / $all_abandon_count * 100,2) AS `call_unanswer_pr`,
                                SEC_TO_TIME(ROUND(SUM(talk_time) / SUM(IF(asterisk_call_log.call_status_id IN(6,7),1,0)))) AS `avg_durat`,
                                SEC_TO_TIME(ROUND(SUM(wait_time) / SUM(IF(asterisk_call_log.call_status_id IN(9),1,0)))) AS `avg_hold`
                        FROM     asterisk_call_log
                        JOIN     asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                        WHERE    asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date') 
                        AND      asterisk_queue.id IN ($queue)
                        AND      asterisk_call_log.call_type_id = 1
                        AND      asterisk_call_log.call_status_id IN(6,7,9)
                        GROUP BY DATE(FROM_UNIXTIME(asterisk_call_log.call_datetime))");

        $res = parent::getKendoList($this->colCount, $this->cols);

        return $res;
    }
    
    public function call_distribution_per_hour(){
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        $queue          = rawurldecode($_REQUEST["queues"]);
        $agent          = rawurldecode($_REQUEST["exts"]);
        
        $start          = $_REQUEST['startDate'];
        $end 	        = $_REQUEST['endDate'];
        $start_date     = $start.' 00:00';
        $end_date       = $end.' 23:59';
        $sl_type        = $_REQUEST["slType"];
        $call_ans_in    = $_REQUEST["callAnsIn"];
        $call_not_after = $_REQUEST["callNotAfter"];
        $source         = 1;

        $reports = $this->callData($start_date, $end_date, $queue, $agent, $source);
        $all_answer_count = $reports["row_answer"];
        $all_abandon_count = $reports["row_abadon"];
        parent::setQuery("SELECT   HOUR(FROM_UNIXTIME(asterisk_call_log.call_datetime)) AS `datetime`,
                                                COUNT(*) AS `all_call`,
                            				    SUM(IF(asterisk_call_log.call_status_id IN(6,7),1,0)) AS `answer_count`,
                            				    ROUND(SUM(IF(asterisk_call_log.call_status_id IN(6,7),1,0)) / $all_answer_count * 100,2) AS `call_answer_pr`,
                            				    SUM(IF(asterisk_call_log.call_status_id IN(9),1,0)) AS `unanswer_call`,
                            				    ROUND(SUM(IF(asterisk_call_log.call_status_id IN(9),1,0)) / $all_abandon_count * 100,2) AS `call_unanswer_pr`,
                            				    SEC_TO_TIME(ROUND(SUM(talk_time) / SUM(IF(asterisk_call_log.call_status_id IN(6,7),1,0)))) AS `avg_durat`,
                            				    SEC_TO_TIME(ROUND(SUM(wait_time) / SUM(IF(asterisk_call_log.call_status_id IN(9),1,0)))) AS `avg_hold`
                                       FROM     asterisk_call_log
                                       JOIN     asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                                       WHERE    asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date') 
                                       AND      asterisk_queue.id IN ($queue)
                                       AND      asterisk_call_log.call_type_id = 1
                                       AND      asterisk_call_log.call_status_id IN(6,7,9)
                                       GROUP BY HOUR(FROM_UNIXTIME(asterisk_call_log.call_datetime))");

        $res = parent::getKendoList($this->colCount, $this->cols);

        return $res;
    }
    public function call_distribution_per_day_of_week(){
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        $queue          = rawurldecode($_REQUEST["queues"]);
        $agent          = rawurldecode($_REQUEST["exts"]);
        
        $start          = $_REQUEST['startDate'];
        $end 	        = $_REQUEST['endDate'];
        $start_date     = $start.' 00:00';
        $end_date       = $end.' 23:59';
        $sl_type        = $_REQUEST["slType"];
        $call_ans_in    = $_REQUEST["callAnsIn"];
        $call_not_after = $_REQUEST["callNotAfter"];
        $source         = 1;

        $reports = $this->callData($start_date, $end_date, $queue, $agent, $source);
        $all_answer_count = $reports["row_answer"];
        $all_abandon_count = $reports["row_abadon"];
        parent::setQuery("SELECT   CASE
                    									WHEN DAYOFWEEK(FROM_UNIXTIME(asterisk_call_log.call_datetime)) = 1 THEN 'კვირა'
                    									WHEN DAYOFWEEK(FROM_UNIXTIME(asterisk_call_log.call_datetime)) = 2 THEN 'ორშაბათი'
                    									WHEN DAYOFWEEK(FROM_UNIXTIME(asterisk_call_log.call_datetime)) = 3 THEN 'სამშაბათი'
                    									WHEN DAYOFWEEK(FROM_UNIXTIME(asterisk_call_log.call_datetime)) = 4 THEN 'ოთხშაბათი'
                    									WHEN DAYOFWEEK(FROM_UNIXTIME(asterisk_call_log.call_datetime)) = 5 THEN 'ხუთშაბათი'
                    									WHEN DAYOFWEEK(FROM_UNIXTIME(asterisk_call_log.call_datetime)) = 6 THEN 'პარასკევი'
                    									WHEN DAYOFWEEK(FROM_UNIXTIME(asterisk_call_log.call_datetime)) = 7 THEN 'შაბათი'
                    							END AS `datetime`,
                                                COUNT(*) AS `all_call`,
                            				    SUM(IF(asterisk_call_log.call_status_id IN(6,7),1,0)) AS `answer_count`,
                            				    ROUND(SUM(IF(asterisk_call_log.call_status_id IN(6,7),1,0)) / $all_answer_count * 100,2) AS `call_answer_pr`,
                            				    SUM(IF(asterisk_call_log.call_status_id IN(9),1,0)) AS `unanswer_call`,
                            				    ROUND(SUM(IF(asterisk_call_log.call_status_id IN(9),1,0)) / $all_abandon_count * 100,2) AS `call_unanswer_pr`,
                            				    SEC_TO_TIME(ROUND(SUM(talk_time) / SUM(IF(asterisk_call_log.call_status_id IN(6,7),1,0)))) AS `avg_durat`,
                            				    SEC_TO_TIME(ROUND(SUM(wait_time) / SUM(IF(asterisk_call_log.call_status_id IN(9),1,0)))) AS `avg_hold`
                                       FROM     asterisk_call_log
                                       JOIN     asterisk_queue ON asterisk_queue.id = asterisk_call_log.queue_id
                                       WHERE    asterisk_call_log.call_datetime BETWEEN UNIX_TIMESTAMP('$start_date') AND UNIX_TIMESTAMP('$end_date') 
                                       AND      asterisk_queue.id IN ($queue)
                                       AND      asterisk_call_log.call_type_id = 1
                                       AND      asterisk_call_log.call_status_id IN(6,7,9)
                                       GROUP BY DAYOFWEEK(FROM_UNIXTIME(asterisk_call_log.call_datetime))");

        $res = parent::getKendoList($this->colCount, $this->cols);

        return $res;
    }
}





?>