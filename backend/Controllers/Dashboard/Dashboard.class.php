<?php

use Routers\dbClass;
//s
class Dashboard extends dbClass{


    private $block;
    private $blockSet = array();
    private $blockId;
    private $response = array();

    private $dateFilter = 'CURDATE()';

    function getDashboardData() {
        $filter = '';
        if(isset($_REQUEST['id'])){
            $this->blockId = $_REQUEST['id'];
            $filter = ' AND dashboard.id = '.$this->blockId;
        }
        if(isset($_REQUEST['filter'])){
            if($_REQUEST['filter'] == 'day'){
                $this->dateFilter = 'CURDATE()';
            }
            else if($_REQUEST['filter'] == 'week'){
                $this->dateFilter = 'DATE_SUB(CURDATE(), INTERVAL 1 WEEK)';
            }
            else if($_REQUEST['filter'] == 'month'){
                $this->dateFilter = 'DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
            }
            else if($_REQUEST['filter'] == 'year'){
                $this->dateFilter = 'DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
            }
        }
        
        parent::setQuery("  SELECT  * 
                            FROM    dashboard
                            WHERE   dashboard.actived = 1 $filter
                            ORDER BY dashboard.order ASC");
        
        $resultArr = parent::getResultArray();

        $data = $resultArr['result'];

        foreach($data as $item){

            if(!empty($item['parent_id'])) $this->getBlockSet($item);
            // CHECK IF BLOCKS HAVE A PARENT_ID
            if(empty($item['parent_id'])) $this->getBlock($item);

            // CHECK AND PUSH BLOCK ARRAY
            if($this->block != null) array_push($this->response, $this->block);
            if($this->blockSet != null) array_push($this->response, $this->blockSet);

        }
        if(!isset($_REQUEST['id'])){
            $operators = $this->response[0];
            $calls = $this->response[1];
    
            array_shift($this->response);
            array_shift($this->response);
    
            $massive_array = array();
            array_push($massive_array,$operators);
            array_push($massive_array,$calls);
            array_unshift($this->response,$massive_array);
        }
        
        return $this->response;

    }


    /**
     * Create block
     *
     * @param [Array] $item
     * @return Array blockArr
     */
    public function getBlock($item){
            $sda = array();
            $blockArr = array();
            $blockArr['id'] = (int)$item['id'];
            $blockArr['title'] = $item['title'];
            $blockArr['type'] = $item['type'];
            $blockArr['boxSize'] = (int)$item['boxSize'];
            $blockArr['live'] = (boolean)$item['live'];
            $blockArr['draggable'] = (boolean)$item['draggable'];
            $blockArr['search'] = (boolean)$item['search'];
            $blockArr['filter'] = (boolean)$item['filter'];
            
            if($item['chart'] != ''){
                $blockArr['chart'] = $item['chart'];
            }

            if($item['label'] == 1){
                $blockArr['label']['text'] = $item['labelText'];
                $blockArr['label']['color'] = $item['labelColor'];
            }

            // $arr['colors'] = array("#AD00FF","#008FFB","#4A4A4A");
            // $arr['header'] = array("შესამოწმ","შემოწმ","შეფასება");
            // $arr['data'][] = array("ქრისტინა კაჭარავა","ცოტნე გუგუშვილი","ნინი კაცაძე");
            // $arr['data'][] = array(15, 10, 10);
            // $arr['data'][] = array(15, 10, 10);
            // $arr['data'][] = array(15, 10, 10);

            switch($item['name']){
                case "operatorsLive":
                    $dataRes = $this->operatorsLive();
                    $blockArr['header'] = $dataRes['header'];
                    $blockArr['colors'] = $dataRes['colors'];
                    $blockArr['data'] = $dataRes['data'];
                    break;
                case "calls":
                    $dataRes = $this->calls();
                    $blockArr['header'] = $dataRes['header'];
                    $blockArr['colors'] = $dataRes['colors'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['footer'] = $dataRes['footer'];
                    break;
                case "SL_LEVEL":
                    $dataRes = $this->sl_level();
                    $blockArr['header'] = $dataRes['header'];
                    $blockArr['colors'] = $dataRes['colors'];
                    $blockArr['labelData'] = $dataRes['labelData'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['dataTitle'] = $dataRes['dataTitle'];
                    $blockArr['footer'] = $dataRes['footer'];
                    break;
                case "fcr":
                    $dataRes = $this->fcr();
                    $blockArr['header'] = $dataRes['header'];
                    $blockArr['colors'] = $dataRes['colors'];
                    $blockArr['labelData'] = $dataRes['labelData'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['dataTitle'] = $dataRes['dataTitle'];
                    $blockArr['footer'] = $dataRes['footer'];
                    break;
                case "ASA":
                    $dataRes = $this->asa();
                    $blockArr['header'] = $dataRes['header'];
                    $blockArr['labelData'] = $dataRes['labelData'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['colors'] = $dataRes['colors'];
                    break;
                case "incomming_today":
                    $dataRes = $this->incomming_today();
                    $blockArr['header'] = $dataRes['header'];
                    $blockArr['labelData'] = $dataRes['labelData'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['colors'] = $dataRes['colors'];
                    $blockArr['dataTitle'] = $dataRes['dataTitle'];
                    $blockArr['labelStack'] = $dataRes['labelStack'];
                    break;
                case "queue_buttons":
                    // $dataRes = $this->queue_buttons();
                    // $blockArr['header'] = $dataRes['header'];
                    // $blockArr['labelData'] = $dataRes['labelData'];
                    // $blockArr['data'] = $dataRes['data'];
                    // $blockArr['colors'] = $dataRes['colors'];
                    // $blockArr['dataTitle'] = $dataRes['dataTitle'];
                    //$blockArr['labelStack'] = $dataRes['labelStack'];
                    break;
                case "monitoring":
                    $dataRes = $this->monitoring();
                    $blockArr['header'] = $dataRes['header'];
                    //$blockArr['footer'] = $dataRes['footer'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['colors'] = $dataRes['colors'];
                    break;
                case "monitoring_rate":
                    $dataRes = $this->monitoring_rate();
                    $blockArr['header'] = $dataRes['header'];
                    //$blockArr['footer'] = $dataRes['footer'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['colors'] = $dataRes['colors'];
                    break;
                case "queue":
                    $dataRes = $this->queue();
                    $blockArr['header'] = $dataRes['header'];
                    //$blockArr['footer'] = $dataRes['footer'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['colors'] = $dataRes['colors'];
                    break;
                case "talk_duration":
                    $dataRes = $this->talk_duration();
                    //$blockArr['header'] = $dataRes['header'];
                    //$blockArr['footer'] = $dataRes['footer'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['colors'] = $dataRes['colors'];
                    break;
                case "unanswered_waittime":
                    $dataRes = $this->unanswered_waittime();
                    //$blockArr['header'] = $dataRes['header'];
                    //$blockArr['footer'] = $dataRes['footer'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['colors'] = $dataRes['colors'];
                    break;
                case "talk_duration_by_user":
                    $dataRes = $this->talk_duration_by_user();
                    $blockArr['header'] = $dataRes['header'];
                    //$blockArr['footer'] = $dataRes['footer'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['colors'] = $dataRes['colors'];
                    $blockArr['footer'] = $dataRes['footer'];
                    break;
                case "operators_live":
                    $dataRes = $this->operators_live();
                    $blockArr['header'] = $dataRes['header'];
                    //$blockArr['footer'] = $dataRes['footer'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['colors'] = $dataRes['colors'];
                    $blockArr['footer'] = $dataRes['footer'];
                    break;
                case "answeredVSunanswered":
                    $dataRes = $this->answeredVSunanswered();
                    $blockArr['header'] = $dataRes['header'];
                    $blockArr['colors'] = $dataRes['colors'];
                    $blockArr['data'] = $dataRes['data'];
                    break;
                case "ATT":
                    $dataRes = $this->ATT();
                    $blockArr['header'] = $dataRes['header'];
                    $blockArr['labelData'] = $dataRes['labelData'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['colors'] = $dataRes['colors'];
                    break;
                case "AWT":
                    $dataRes = $this->AWT();
                    $blockArr['header'] = $dataRes['header'];
                    $blockArr['labelData'] = $dataRes['labelData'];
                    $blockArr['data'] = $dataRes['data'];
                    $blockArr['colors'] = $dataRes['colors'];
                    break;
            }
            
            $this->block = $blockArr;

    }

/**
 * Create blockSet
 *
 * @param [Array] $item
 * @return Array BlockSet
 */
    public function getBlockSet($item){

        $this->getBlock($item);

        // echo count($this->blockSet);
        // array_push($this->response, $this->blockSet);

    }

    public function operatorsLive(){
        $dateRes = array();
        $dateRes['header'] = array("შემოსული","თავისუფალი","საუბრობს","შესვენებაზე");
        $dateRes['colors'] = array("#444444","#02BB62","#FF005C","#FEB019");
        
        parent::setQuery("  SELECT 	IFNULL(SUM(1),0) AS logged,
                                    IFNULL(SUM(IF(dashboard_user_state.state = 1,1,0)),0) AS free,
                                    IFNULL(SUM(IF(dashboard_user_state.state = 3,1,0)),0) AS talking,
                                    IFNULL(SUM(IF(dashboard_user_state.state = 2,1,0)),0) AS `break`
                                                    

                            FROM 	dashboard_user_state
                            JOIN	users ON users.id = dashboard_user_state.user_id
                            JOIN 	asterisk_extension ON asterisk_extension.id = users.extension_id
                            JOIN    asterisk.queueMembers ON queueMembers.name = asterisk_extension.number
                            WHERE	users.extension_id > 0 AND queueMembers.status IN (1,2,3) 
                            AND     users.logged = 1
                            AND     queueMembers.queue IN (SELECT number FROM asterisk_queue WHERE actived = 1)");
        $data = parent::getResultArray();
        foreach($data['result'] AS $item){
            $dateRes['data'] = array($item['logged'],$item['free'],$item['talking'],$item['break']);
        }

        return $dateRes;


    }

    public function calls(){
        $dateRes = array();
        $dateRes['header'] = array("ნაპასუხები","უპასუხო");
        $dateRes['colors'] = array("#02BB62","#FF005C");
        $dateRes['footer'] = array();
        
        parent::setQuery("  SELECT 	SUM(dashboard_request_count.answered) AS 'answered',
                                    (SELECT COUNT(*) FROM dashboard_request_durations WHERE DATE(FROM_UNIXTIME(datetime)) BETWEEN $this->dateFilter AND CURDATE() AND sum_wait_time < 80 AND source_id = 1 AND status = 1) AS '80_seconds',
                                    SUM(dashboard_request_count.`all` - dashboard_request_count.answered) AS 'missed'
                            FROM 	dashboard_request_count
                            WHERE 	DATE(dashboard_request_count.date) BETWEEN $this->dateFilter AND CURDATE() AND dashboard_request_count.source_id = 1");
        $data = parent::getResultArray();
        if($data['count'] == 0){
            $dateRes['data'] = array(0,0);
        }
        else{
            foreach($data['result'] AS $item){
                $dateRes['data'] = array((int)$item['answered'],(int)$item['missed']);
            }
        }
        $dateRes['footer'] = array('show' => true, 'toggle' => false, 'stack' => array('label' => "middle",'percent' => "80 წამი: ".$item['80_seconds']." ზარი"));

        return $dateRes;


    }
    public function fcr(){
        $dateRes                = array();
        $dateRes['header']      = array();
        $dateRes['footer']      = array();
        $dateRes['colors']      = array("#feb019");
        $dateRes['labelData']   = array();
        $dateRes['data']        = array();
        $dateRes['dataTitle']   = array();
        $dayBeforePosition      = 'up';
        parent::setQuery("  SELECT	source_live.name,
                                    CONCAT(ROUND((dashboard_request_count.completed-dashboard_request_count.fcr)/dashboard_request_count.answered * 100,2),'%') AS 'fcr'

                            FROM 	dashboard_request_count
                            JOIN 	source_live ON source_live.source_id = dashboard_request_count.source_id
                            WHERE 	DATE(dashboard_request_count.date) = CURDATE();");
        $data = parent::getResultArray();
        if($data['count'] == 0){
            array_push($dateRes['header'],'');
            array_push($dateRes['labelData'],'');
        }
        else{
            foreach($data['result'] AS $item){
                array_push($dateRes['header'],$item['name']);
                array_push($dateRes['labelData'],$item['fcr']);
            }
        }
        

        parent::setQuery("  SELECT		ROUND(SUM(dashboard_request_count.completed-dashboard_request_count.fcr)/SUM(dashboard_request_count.answered) * 100,2) AS 'percent'

                            FROM 		dashboard_request_count
                            WHERE 		DATE(dashboard_request_count.date) = CURDATE()
                            GROUP BY 	DATE(dashboard_request_count.date)");
        $fcr_percent = parent::getResultArray();
        // $fcr_percent = $fcr_percent['result'][0]['percent'];
        array_push($dateRes['data'],(int)$fcr_percent);


        array_push($dateRes['dataTitle'],'');

        parent::setQuery("  SELECT	ROUND(SUM(IF(DATE(dashboard_request_count.date) = DATE_SUB(CURDATE(),INTERVAL 1 DAY),dashboard_request_count.completed-dashboard_request_count.fcr,0))/SUM(IF(DATE(dashboard_request_count.date) = DATE_SUB(CURDATE(),INTERVAL 1 DAY),dashboard_request_count.answered,0)) * 100,2) AS 'day_before',
                                    ROUND(SUM(IF(DATE(dashboard_request_count.date) = CURDATE(),dashboard_request_count.completed-dashboard_request_count.fcr,0))/SUM(IF(DATE(dashboard_request_count.date) = CURDATE(),dashboard_request_count.answered,0)) * 100,2) AS 'today'

                            FROM 	dashboard_request_count
                            WHERE 	DATE(dashboard_request_count.date) >= DATE_SUB(CURDATE(),INTERVAL 1 DAY)");

        $check_day_before = parent::getResultArray();

        if($check_day_before['result'][0]['today'] < $check_day_before['result'][0]['day_before']){
            $dayBeforePosition = 'down';
        }
        $percentDifference = abs($check_day_before['result'][0]['today'] - $check_day_before['result'][0]['day_before']).'%';
        $dateRes['footer'] = array('show' => true, 'toggle' => false, 'stack' => array('label' => $dayBeforePosition,'percent' => $percentDifference));

        return $dateRes;


    }
    public function sl_level(){
        $dateRes                = array();
        $dateRes['header']      = array();
        $dateRes['footer']      = array();
        $dateRes['colors']      = array("#AD00FF");
        $dateRes['labelData']   = array();
        $dateRes['data']        = array();
        $dateRes['dataTitle']   = array();
        $dayBeforePosition      = 'up';
        parent::setQuery("  SELECT 	    source_live.name,
                                        CONCAT(COUNT(*),'/',SUM(IF(ROUND(dashboard_request_durations.sum_wait_time/count) <= 80,1,0))) AS 'sl'
                                    

                            FROM 	    dashboard_request_durations
                            JOIN 	    source_live ON source_live.source_id = dashboard_request_durations.source_id
                            WHERE       dashboard_request_durations.duration > 0 AND DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) = CURDATE()
                            GROUP BY    dashboard_request_durations.source_id");
        $data = parent::getResultArray();
        if($data['count'] == 0){
            array_push($dateRes['header'],'');
            array_push($dateRes['labelData'],'');
        }
        else{
            foreach($data['result'] AS $item){
                array_push($dateRes['header'],$item['name']);
                array_push($dateRes['labelData'],$item['sl']);
            }
        }
        

        parent::setQuery("  SELECT  ROUND((SUM(IF(ROUND(dashboard_request_durations.sum_wait_time/count) <= 80,1,0))/COUNT(*)) * 100,2) AS percent
                            FROM 	dashboard_request_durations
                            WHERE   dashboard_request_durations.duration > 0 AND DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) = CURDATE()");
        $sl_percent = parent::getResultArray();
        $sl_percent = $sl_percent['result'][0]['percent'];
        array_push($dateRes['data'],(int)$sl_percent);


        array_push($dateRes['dataTitle'],'80წმ');

        parent::setQuery("  SELECT  ROUND(SUM(IF(DATE(FROM_UNIXTIME(datetime))=DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND (sum_wait_time/count)<=80,1,0))/SUM(IF(DATE(FROM_UNIXTIME(datetime))=DATE_SUB(CURDATE(), INTERVAL 1 DAY),1,0))*100,2) AS 'day_before',
                                    ROUND(SUM(IF(DATE(FROM_UNIXTIME(datetime))=CURDATE() AND (sum_wait_time/count)<=80 ,1,0))/SUM(IF(DATE(FROM_UNIXTIME(datetime))=CURDATE(),1,0))*100,2) AS 'today'
                            FROM 	dashboard_request_durations
                            WHERE   dashboard_request_durations.duration > 0 AND DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)");

        $check_day_before = parent::getResultArray();

        if($check_day_before['result'][0]['today'] < $check_day_before['result'][0]['day_before']){
            $dayBeforePosition = 'down';
        }
        $percentDifference = abs($check_day_before['result'][0]['today'] - $check_day_before['result'][0]['day_before']).'%';
        $dateRes['footer'] = array('show' => true, 'toggle' => false, 'stack' => array('label' => $dayBeforePosition,'percent' => $percentDifference));

        return $dateRes;


    }

    public function asa(){
        $dateRes = array();
        $dateRes['header'] = array();
        $dateRes['colors'] = array('#fff');
        $dateRes['labelData']   = array();
        $dateRes['data']        = array();

        parent::setQuery("  SELECT		source_live.name,
                                        TIME_FORMAT(SEC_TO_TIME(ROUND(SUM(dashboard_request_durations.sum_wait_time/dashboard_request_durations.count)/COUNT(*))), '%i:%s') AS wait_time

                            FROM 		source_live
                            JOIN 		dashboard_request_durations ON dashboard_request_durations.source_id = source_live.source_id
                            WHERE 		DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) = CURDATE() AND dashboard_request_durations.duration > 0 AND NOT ISNULL(dashboard_request_durations.user_id)
                            GROUP BY 	dashboard_request_durations.source_id");
        $data = parent::getResultArray();

        parent::setQuery("  SELECT	TIME_FORMAT(SEC_TO_TIME(ROUND(SUM(dashboard_request_durations.sum_wait_time/dashboard_request_durations.count)/COUNT(*))), '%i:%s') AS all_source_asa

                            FROM 	source_live
                            JOIN 	dashboard_request_durations ON dashboard_request_durations.source_id = source_live.source_id AND dashboard_request_durations.duration > 0 AND NOT ISNULL(dashboard_request_durations.user_id)
                            WHERE 	DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) = CURDATE()");

        $asa = parent::getResultArray();
        $asa = $asa['result'][0]['all_source_asa'];
        if($data['count'] == 0){
            array_push($dateRes['header'],'');
            array_push($dateRes['labelData'],'');
        }
        else{
            foreach($data['result'] AS $item){
                array_push($dateRes['header'],$item['name']);
                array_push($dateRes['labelData'],$item['wait_time']);
            }
        }
        
        array_push($dateRes['data'],$asa);
        return $dateRes;


    }
    public function ATT(){
        $dateRes = array();
        $dateRes['header'] = array();
        $dateRes['colors'] = array('#fff');
        $dateRes['labelData']   = array();
        $dateRes['data']        = array();

        parent::setQuery("  SELECT		source_live.name,
                                        TIME_FORMAT(SEC_TO_TIME(ROUND(SUM(dashboard_request_durations.duration)/COUNT(*))), '%i:%s') AS wait_time

                            FROM 		source_live
                            JOIN 		dashboard_request_durations ON dashboard_request_durations.source_id = source_live.source_id
                            WHERE 		DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) = CURDATE() AND dashboard_request_durations.duration > 0
                            GROUP BY 	dashboard_request_durations.source_id");
        $data = parent::getResultArray();

        parent::setQuery("  SELECT	TIME_FORMAT(SEC_TO_TIME(ROUND(SUM(dashboard_request_durations.duration)/COUNT(*))), '%i:%s') AS all_source
                            FROM 	source_live
                            JOIN 	dashboard_request_durations ON dashboard_request_durations.source_id = source_live.source_id
                            WHERE 	DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) = CURDATE() AND dashboard_request_durations.duration > 0");

        $asa = parent::getResultArray();
        $asa = $asa['result'][0]['all_source'];
        if($data['count'] == 0){
            array_push($dateRes['header'],'');
            array_push($dateRes['labelData'],'');
        }
        else{
            foreach($data['result'] AS $item){
                array_push($dateRes['header'],$item['name']);
                array_push($dateRes['labelData'],$item['wait_time']);
            }
        }
        
        array_push($dateRes['data'],$asa);
        return $dateRes;


    }
    public function AWT(){
        $dateRes = array();
        $dateRes['header'] = array();
        $dateRes['colors'] = array('#fff');
        $dateRes['labelData']   = array();
        $dateRes['data']        = array();

        parent::setQuery("  SELECT		source_live.name,
                                        TIME_FORMAT(SEC_TO_TIME(ROUND(SUM(dashboard_request_durations.sum_wait_time/dashboard_request_durations.count)/COUNT(*))), '%i:%s') AS wait_time

                            FROM 		source_live
                            JOIN 		dashboard_request_durations ON dashboard_request_durations.source_id = source_live.source_id
                            WHERE 		DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) = CURDATE() AND dashboard_request_durations.duration = 0 AND ISNULL(dashboard_request_durations.user_id)
                            GROUP BY 	dashboard_request_durations.source_id");
        $data = parent::getResultArray();

        parent::setQuery("  SELECT		source_live.name,
                                        TIME_FORMAT(SEC_TO_TIME(ROUND(SUM(dashboard_request_durations.sum_wait_time/dashboard_request_durations.count)/COUNT(*))), '%i:%s') AS all_source

                            FROM 		source_live
                            JOIN 		dashboard_request_durations ON dashboard_request_durations.source_id = source_live.source_id
                            WHERE 		DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) = CURDATE() AND dashboard_request_durations.duration = 0 AND ISNULL(dashboard_request_durations.user_id)");

        $asa = parent::getResultArray();
        $asa = $asa['result'][0]['all_source'];
        if($data['count'] == 0){
            array_push($dateRes['header'],'');
            array_push($dateRes['labelData'],'');
        }
        else{
            foreach($data['result'] AS $item){
                array_push($dateRes['header'],$item['name']);
                array_push($dateRes['labelData'],$item['wait_time']);
            }
        }
        
        array_push($dateRes['data'],$asa);
        return $dateRes;


    }
    public function incomming_today(){
        $dateRes = array();
        $dateRes['header'] = array();
        $dateRes['colors'] = array("#3ABE82", "#FF005C", "#2196F3", "#FBD300", "#1BD741", "#BA34B4", "#1976D2", "#FBD300", "#FF005C");
        $dateRes['labelData']   = array();
        $dateRes['data']        = array();
        $dateRes['dataTitle']   = array();
        $dateRes['labelStack']  = array();

        parent::setQuery("  SELECT	COUNT(*),
                                    SUM(IF(DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) = CURDATE(),1,0)) AS total,
                                    SUM(IF(DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) < CURDATE(),1,0)) AS date_before
                                    
                            FROM 	source_live
                            JOIN	dashboard_request_durations ON dashboard_request_durations.source_id = source_live.source_id
                            WHERE 	DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
        $totales = parent::getResultArray();
        $totales = $totales['result'][0];

        parent::setQuery("  SELECT		source_live.name,
                                        SUM(IF(DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) = CURDATE(),1,0)) AS total,
                                        ROUND(SUM(IF(DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) = CURDATE(),1,0))/$totales[total]*100,2) AS total_p,
                                        ROUND(SUM(IF(DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) < CURDATE(),1,0))/$totales[date_before]*100,2) AS date_before_p
                                    
                            FROM 		source_live
                            JOIN		dashboard_request_durations ON dashboard_request_durations.source_id = source_live.source_id
                            WHERE 		DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                            GROUP BY 	dashboard_request_durations.source_id");

        $data = parent::getResultArray();
        foreach($data['result'] AS $item){
            $dayBeforePosition = 'up';
            array_push($dateRes['header'],$item['name']);
            array_push($dateRes['dataTitle'],$item['name']);
            array_push($dateRes['labelData'],$item['total']);
            array_push($dateRes['data'],(float)$item['total_p']);

            if($item['total_p'] < $item['date_before_p']){
                $dayBeforePosition = 'down';
            }
            $percentDifference = round(abs($item['total_p'] - $item['date_before_p']), 2).'%';
            array_push($dateRes['labelStack'],array('label' => $dayBeforePosition, 'percent' => $percentDifference));
        }
        
        return $dateRes;


    }
    public function queue_buttons(){
        $dateRes = array();
        $dateRes['header'] = array();
        $dateRes['colors'] = array("#3ABE82", "#FF005C", "#2196F3", "#FBD300", "#1BD741");
        $dateRes['labelData']   = array();
        $dateRes['data']        = array();
        $dateRes['dataTitle']   = array();
        //$dateRes['labelStack']  = array();

        parent::setQuery("  SELECT	SUM(IF(DATE(ivr_buttons_log.datetime) = CURDATE(),1,0)) AS total
				
                            FROM 	ivr_button
                            JOIN	callapp.ivr_buttons_log ON ivr_buttons_log.button = ivr_button.ivr_button
                            WHERE 	DATE(ivr_buttons_log.datetime) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
        $totales = parent::getResultArray();
        $totales = $totales['result'][0];

        parent::setQuery("  SELECT		CONCAT('IVR',ivr_button.ivr_button) AS name,
                                        SUM(IF(DATE(ivr_buttons_log.datetime) = CURDATE(),1,0)) AS total,
                                        ROUND(SUM(IF(DATE(ivr_buttons_log.datetime) = CURDATE(),1,0))/$totales[total]*100,2) AS total_p

                            FROM 		ivr_button
                            JOIN		callapp.ivr_buttons_log ON ivr_buttons_log.button = ivr_button.ivr_button
                            WHERE 		DATE(ivr_buttons_log.datetime) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                            GROUP BY 	ivr_button.ivr_button");

        $data = parent::getResultArray();
        foreach($data['result'] AS $item){
            array_push($dateRes['header'],$item['name']);
            array_push($dateRes['dataTitle'],$item['name']);
            array_push($dateRes['labelData'],$item['total']);
            array_push($dateRes['data'],(float)$item['total_p']);
        }
        
        return $dateRes;


    }
    public function monitoring(){
        $dateRes = array();
        $dateRes['header']  = array("შესამოწმებელი","შემოწმებული");
        $dateRes['colors']  = array("#AD00FF", "#008FFB");
        $dateRes['data']    = array();
        $dateRes['data'][0] = array();
        $dateRes['footer']  = array();
        $columnCount        = 2;

        parent::setQuery("  SELECT		dashboard_user_state.`name` AS 'name',
                                        SUM(IF(dashboard_request_durations.monitored IN (1,2),1,0)) AS '1',
                                        SUM(IF(dashboard_request_durations.monitored = 2,1,0)) AS '2'
                            FROM 		dashboard_request_durations
                            JOIN		dashboard_user_state ON dashboard_user_state.user_id = dashboard_request_durations.monitored_user_id
                            WHERE 		DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) BETWEEN $this->dateFilter AND CURDATE()
                            GROUP BY	dashboard_request_durations.monitored_user_id");

        $data = parent::getResultArray();
        if($data['count'] == 0){
            
        }
        else{
            foreach($data['result'] AS $item){
                array_push($dateRes['data'][0], $item['name']);
            }
            $i = 1;
            foreach($data['result'] AS $item){
                for($c=1;$c<=$columnCount;$c++){
                    if(!isset($dateRes['data'][$c])){
                        $dateRes['data'][$c] = array();
                    }
                    array_push($dateRes['data'][$c],$item[$c]);
                }
            }
        }
        
        
        return $dateRes;


    }

    public function monitoring_rate(){
        $dateRes = array();
        $dateRes['header']  = array("შესამოწ.","შემოწ.","შეფასება");
        $dateRes['colors']  = array("#AD00FF", "#008FFB", "#4A4A4A");
        $dateRes['data']    = array();
        $dateRes['data'][0] = array();
        $dateRes['footer']  = array();
        $columnCount        = 3;



        parent::setQuery("  SELECT		dashboard_user_state.`name` AS 'name',
                                        SUM(IF(dashboard_request_durations.monitored IN (1,2),1,0)) AS '1',
                                        SUM(IF(dashboard_request_durations.monitored = 2,1,0)) AS '2',
                                        IFNULL(ROUND(SUM(dashboard_request_durations.monitored_rate)/SUM(IF(dashboard_request_durations.monitored = 2,1,0)),1),'0.0') AS '3'

                            FROM 		dashboard_request_durations
                            JOIN		dashboard_user_state ON dashboard_user_state.user_id = dashboard_request_durations.monitored_user_id
                            WHERE 		DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) BETWEEN $this->dateFilter AND CURDATE()
                            GROUP BY	dashboard_request_durations.monitored_user_id");

        $data = parent::getResultArray();
        if($data['count'] == 0){
            
        }
        else{
            foreach($data['result'] AS $item){
                array_push($dateRes['data'][0], $item['name']);
            }
            $i = 1;
            foreach($data['result'] AS $item){
                for($c=1;$c<=$columnCount;$c++){
                    if(!isset($dateRes['data'][$c])){
                        $dateRes['data'][$c] = array();
                    }
                    array_push($dateRes['data'][$c],$item[$c]);
                }
            }
        }
        
        
        return $dateRes;


    }

    public function queue(){
        $dateRes = array();
        $dateRes['header']  = array("ოპერატორი","აყვანილი","რიგი");
        $dateRes['colors']  = array("#3ABE82", "#FA5993", "#2196F3", "#FF005C", "#8E24AA", "#FBD300");
        $dateRes['data']    = array();
        $dateRes['data'][0] = array();
        $columnCount        = 3;

        parent::setQuery("  SELECT source_live.name,
                                    CASE
                                        WHEN source_live.source_id = 1 THEN (   SELECT 	IFNULL(SUM(1),0)
                                                                                FROM 	dashboard_user_state
                                                                                JOIN	users ON users.id = dashboard_user_state.user_id
                                                                                JOIN 	asterisk_extension ON asterisk_extension.id = users.extension_id
                                                                                JOIN    asterisk.queueMembers ON queueMembers.name = asterisk_extension.number
                                                                                WHERE	users.extension_id > 0 AND queueMembers.status IN (1,2,3) 
                                                                                AND     users.logged = 1
                                                                                AND     queueMembers.queue IN (SELECT number FROM asterisk_queue WHERE actived = 1))

                                        WHEN source_live.source_id = 4 THEN (	SELECT  COUNT(*) 
                                                                                FROM    users_cumunication
                                                                                JOIN    users ON users.id = users_cumunication.user_id
                                                                                WHERE   users_cumunication.chat = 1 AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(users.last_actived_time)) < 15)

                                        WHEN source_live.source_id = 6 THEN (	SELECT  COUNT(*) 
                                                                                FROM    users_cumunication
                                                                                JOIN	users ON users.id = users_cumunication.user_id
                                                                                WHERE   users_cumunication.messenger = 1 AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(users.last_actived_time)) < 15)

                                        WHEN source_live.source_id = 7 THEN (	SELECT  COUNT(*) 
                                                                                FROM    users_cumunication
                                                                                JOIN	users ON users.id = users_cumunication.user_id
                                                                                WHERE   users_cumunication.mail = 1 AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(users.last_actived_time)) < 15)

                                        
                                        WHEN source_live.source_id = 11 THEN (	SELECT  COUNT(*) 
                                                                                FROM    users_cumunication
                                                                                JOIN	users ON users.id = users_cumunication.user_id
                                                                                WHERE   users_cumunication.cf = 1 AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(users.last_actived_time)) < 15)
                                                                                
                                    END AS '1',
                                    SUM(IF(dashboard_user_live.`status` = 1,1,0)) AS '2',
                                    SUM(IF(dashboard_user_live.`status` = 0,1,0)) AS '3'

                            FROM source_live
                            LEFT JOIN dashboard_user_live ON dashboard_user_live.source_id = source_live.source_id
                            WHERE source_live.source_id NOT IN (5,8,10,12,9,11)
                            GROUP BY 	source_live.source_id");

        $data = parent::getResultArray();
        
        if($data['count'] == 0){
            
        }
        else{
            foreach($data['result'] AS $item){
                array_push($dateRes['data'][0], $item['name']);
            }
            $i = 1;
            foreach($data['result'] AS $item){
                for($c=1;$c<=$columnCount;$c++){
                    if(!isset($dateRes['data'][$c])){
                        $dateRes['data'][$c] = array();
                    }
                    array_push($dateRes['data'][$c],$item[$c]);
                }
            }
        }
        
        
        return $dateRes;


    }

    public function talk_duration(){
        $dateRes = array();
        $dateRes['colors']  = array('#AD00FF', '#02BB62', '#FFA903');
        $dateRes['data']    = array();

        parent::setQuery("  SELECT  `short`,
                                    `long`
                            FROM    call_durations
                            WHERE   id = 1");
        $durations = parent::getResultArray();

        $short = $durations['result'][0]['short'];
        $long = $durations['result'][0]['long'];

        parent::setQuery("  SELECT	SUM(IF(dashboard_request_durations.duration < $short,1,0)) AS 'short',
                                    SUM(IF(dashboard_request_durations.duration BETWEEN $short AND $long,1,0)) AS 'medium',
                                    SUM(IF(dashboard_request_durations.duration > $long,1,0)) AS 'long'
                            FROM 	dashboard_request_durations
                            WHERE 	DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) BETWEEN $this->dateFilter AND CURDATE() AND dashboard_request_durations.source_id = 1 AND dashboard_request_durations.duration > 0");

        $data = parent::getResultArray();
        
        $dateRes['data']['მოკლე'] = (int)$data['result'][0]['short'];
        $dateRes['data']['საშუალო'] = (int)$data['result'][0]['medium'];
        $dateRes['data']['დიდი'] = (int)$data['result'][0]['long'];
        
        
        return $dateRes;


    }

    public function unanswered_waittime(){
        $dateRes = array();
        $dateRes['colors']  = array('#AD00FF', '#02BB62', '#FFA903');
        $dateRes['data']    = array();

        parent::setQuery("  SELECT  `short`,
                                    `long`
                            FROM    unanswered_waittime
                            WHERE   id = 1");
        $durations = parent::getResultArray();

        $short = $durations['result'][0]['short'];
        $long = $durations['result'][0]['long'];

        parent::setQuery("  SELECT	SUM(IF(dashboard_request_durations.sum_wait_time < $short,1,0)) AS 'short',
                                    SUM(IF(dashboard_request_durations.sum_wait_time BETWEEN $short AND $long,1,0)) AS 'medium',
                                    SUM(IF(dashboard_request_durations.sum_wait_time > $long,1,0)) AS 'long'
                            FROM 	dashboard_request_durations
                            WHERE 	DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) BETWEEN $this->dateFilter AND CURDATE() AND dashboard_request_durations.source_id = 1 AND dashboard_request_durations.duration = 0 AND ISNULL(dashboard_request_durations.user_id) AND dashboard_request_durations.sum_wait_time > 0");

        $data = parent::getResultArray();
        
        $dateRes['data']['მოკლე'] = (int)$data['result'][0]['short'];
        $dateRes['data']['საშუალო'] = (int)$data['result'][0]['medium'];
        $dateRes['data']['დიდი'] = (int)$data['result'][0]['long'];
        
        
        return $dateRes;


    }

    public function talk_duration_by_user(){
        $dateRes = array();
        $dateRes['header']  = array("პატარა", "საშუალო", "დიდი");
        $dateRes['colors']  = array("#02BB62", "#FFA903", "#FF005C");
        $dateRes['footer']  = array();
        $dateRes['data']    = array();
        $dateRes['data'][0] = array();
        $columnCount        = 3;

        parent::setQuery("  SELECT  `short`,
                                    `long`
                            FROM    call_durations
                            WHERE   id = 1");
        $durations = parent::getResultArray();

        $short = $durations['result'][0]['short'];
        $long = $durations['result'][0]['long'];

        parent::setQuery("  SELECT 	dashboard_user_state.name,
                                    SUM(IF(dashboard_request_durations.duration < $short,1,0)) AS '1',
                                    SUM(IF(dashboard_request_durations.duration BETWEEN $short AND $long,1,0)) AS '2',
                                    SUM(IF(dashboard_request_durations.duration > $long,1,0)) AS '3'
                            FROM 	dashboard_request_durations
                            JOIN 	dashboard_user_state ON dashboard_user_state.user_id = dashboard_request_durations.user_id
                            WHERE 	DATE(FROM_UNIXTIME(dashboard_request_durations.datetime)) BETWEEN $this->dateFilter AND CURDATE() AND dashboard_request_durations.source_id = 1 AND dashboard_request_durations.duration > 0
                            GROUP BY dashboard_request_durations.user_id");

        $data = parent::getResultArray();
        if($data['count'] == 0){
            
        }
        else{
            foreach($data['result'] AS $item){
                array_push($dateRes['data'][0], $item['name']);
            }
            $i = 1;
            foreach($data['result'] AS $item){
                for($c=1;$c<=$columnCount;$c++){
                    if(!isset($dateRes['data'][$c])){
                        $dateRes['data'][$c] = array();
                    }
                    array_push($dateRes['data'][$c],$item[$c]);
                }
            }
        }

        $dateRes['footer'] = array('show' => true, 'toggle' => true, 'stack' => array('label' => 'ssss','percent' => '12'));
        
        
        return $dateRes;


    }

    
    public function operators_live(){
        $dateRes = array();
        $dateRes['header']  = array("Phone", "Chat", "Messenger", "Mail", "Viber");
        $dateRes['colors']  = array("#3ABE82", "#FA5993", "#2196F3", "#FF005C", "#8E24AA");
        $dateRes['footer']  = array();
        $dateRes['data']    = array();
        $dateRes['data'][0] = array();
        $columnCount        = 5;


        parent::setQuery(" SELECT       dashboard_user_state.name,
                                        SUM(IF(dashboard_user_live.source_id = 1 AND dashboard_user_live.`status` = 1,1,0)) AS '1',
                                        SUM(IF(dashboard_user_live.source_id = 4 AND dashboard_user_live.`status` = 1,1,0)) AS '2',
                                        SUM(IF(dashboard_user_live.source_id = 6 AND dashboard_user_live.`status` = 1,1,0)) AS '3',
                                        SUM(IF(dashboard_user_live.source_id = 7 AND dashboard_user_live.`status` = 1,1,0)) AS '4',
                                        SUM(IF(dashboard_user_live.source_id = 9 AND dashboard_user_live.`status` = 1,1,0)) AS '5'
                                        
                            
                            FROM 	    dashboard_user_state
                            JOIN	    users ON users.id = dashboard_user_state.user_id
                            LEFT JOIN   dashboard_user_live ON dashboard_user_live.user_id = users.id
                            WHERE	    (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(users.last_actived_time)) < 15
                            
                            GROUP BY    dashboard_user_state.user_id");

        $data = parent::getResultArray();
        if($data['count'] == 0){
            
        }
        else{
            foreach($data['result'] AS $item){
                array_push($dateRes['data'][0], $item['name']);
            }
            $i = 1;
            foreach($data['result'] AS $item){
                for($c=1;$c<=$columnCount;$c++){
                    if(!isset($dateRes['data'][$c])){
                        $dateRes['data'][$c] = array();
                    }
                    array_push($dateRes['data'][$c],$item[$c]);
                }
            }
        }

        $dateRes['footer'] = array('show' => true, 'toggle' => true, 'stack' => array('label' => 'ssss','percent' => '12'));
        
        
        return $dateRes;


    }
    public function answeredVSunanswered(){
        $dateRes = array();
        $dateRes['header'] = array("დამუშავებული","დაუმუშვებელი");
        $dateRes['colors'] = array("#fbae18","#c158dd");
        
        parent::setQuery("  SELECT 	SUM(dashboard_request_count.completed) AS 'completed',
                                    (dashboard_request_count.`answered` - dashboard_request_count.completed) AS 'uncompleted'

                            FROM 	dashboard_request_count
                            WHERE 	DATE(dashboard_request_count.date) = CURDATE()");
        $data = parent::getResultArray();
        if($data['count'] == 0){
            $dateRes['data'] = array(0,0);
        }
        else{
            foreach($data['result'] AS $item){
                $dateRes['data'] = array((int)$item['completed'],(int)$item['uncompleted']);
            }
        }
        

        return $dateRes;


    }


}


// $json = array();
// $json_data = array();
// $json_data['type'] = 'table';
// $json_data['title'] = 'მონიტორინგი / პრობლემური';
// $json_data['boxSize'] = 3;
// $json_data['draggable'] = true;
// $json_data['search'] = true;
// $json_data['filter'] = true;
// $json_data['colors'] = array("#AD00FF","#008FFB","#4A4A4A");
// $json_data['header'] = array("შესამოწმ","შემოწმ","შეფასება");
// $json_data['data'][] = array("ქრისტინა კაჭარავა","ცოტნე გუგუშვილი","ნინი კაცაძე");
// $json_data['data'][] = array(15, 10, 10);
// $json_data['data'][] = array(15, 10, 10);
// $json_data['data'][] = array(15, 10, 10);
// array_push($json, $json_data);
// return $json;