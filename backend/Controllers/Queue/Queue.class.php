<?php
session_start();
use Routers\dbClass;
class Queue extends dbClass{

    private $source;
    private $userId;

    public function getQueue(){

        $this->source = $_REQUEST['source'];
        $userId = $_REQUEST['userId'];

        switch($this->source){
            case 'phone':
                parent::setQuery("  SELECT	    IF(asterisk_call_log.call_status_id <= 2, 'waiter', 'taken') AS type,
                                                incomming_request.request_name AS name,
                                                '' AS lastMessage,
                                                SEC_TO_TIME(UNIX_TIMESTAMP(NOW()) - asterisk_call_log.call_datetime) AS lastDateTime,
                                                '' AS startDatetime,
                                                'Assets/images/no-image.png' AS imgUrl,
                                                '' AS newMessage,
                                                source.key AS sourceKey,
                                                asterisk_call_log.id AS id,
                                                incomming_request.id AS incommingId

                                    FROM 	    incomming_request
                                    JOIN	    source ON source.id = incomming_request.source_id
                                    JOIN	    asterisk_call_log ON asterisk_call_log.id = incomming_request.asterisk_call_log_id
                                    WHERE 	    incomming_request.source_id = 1 AND asterisk_call_log.call_status_id IN (2,3) AND IF(asterisk_call_log.call_status_id > 2, asterisk_call_log.user_id = '$userId', 1=1)

                                    GROUP BY    asterisk_call_log.id");
                break;
            case 'mail':
                parent::setQuery("  SELECT 	IF(mail.mail_status_id = 1, 'waiter', 'taken') AS type,
                                            mail.sender_name AS name,
                                            mail.`subject` AS lastMessage,
                                            (SELECT DATE_FORMAT(datetime, '%H:%i') FROM mail_detail WHERE mail_detail.mail_id = mail.id ORDER BY id DESC LIMIT 1) AS lastDateTime,
                                            mail.send_datetime AS startDatetime,
                                            'Assets/images/no-image.png' AS imgUrl,
                                            'false' AS newMessage,
                                            'mail' AS sourceKey,
                                            mail.id AS id,
                                            incomming_request.id AS incommingId
                                    FROM 	mail
                                    JOIN	incomming_request ON incomming_request.mail_id = mail.id
                                    WHERE 	mail.mail_status_id IN (1,2) AND mail.mail_type_id = 2");
                break;
            default:
                parent::setQuery("  SELECT		IF(chat.chat_status_id = 1, 'waiter', 'taken') AS type,
                                                chat.sender_name AS name,
                                                (SELECT message FROM chat_details WHERE chat_id = chat.id ORDER BY id DESC LIMIT 1) AS lastMessage,
                                                (SELECT DATE_FORMAT(datetime, '%H:%i') FROM chat_details WHERE chat_id = chat.id ORDER BY id DESC LIMIT 1) AS lastDateTime,
                                                chat.first_datetime AS startDatetime,
                                                'Assets/images/no-image.png' AS imgUrl,
                                                IF((SELECT user_id FROM chat_details WHERE chat_id = chat.id ORDER BY id DESC LIMIT 1) = 0, 'false', 'true') AS newMessage,
                                                source.key AS sourceKey,
                                                chat.id AS id,
                                                incomming_request.id AS incommingId
                                            
                                        

                                    FROM 		chat
                                    JOIN		chat_details ON chat_details.chat_id = chat.id
                                    JOIN		source ON source.id = chat.source_id
                                    JOIN		incomming_request ON incomming_request.chat_id = chat.id
                                    WHERE 		chat.chat_status_id IN (1,2) AND source.key = '$this->source' AND IF(chat.chat_status_id = 2, chat.first_user_id = '$userId', 1=1)
                                    GROUP BY 	chat.id
                                    ORDER BY 	chat.last_datetime DESC");
        }

        $queueList = parent::getResultArray();

        return $queueList['result'];
    }
    
    public function flashPanelQueue(){
        $this->source = $_REQUEST['source'];

        switch($this->source){
            case 'phone':
                parent::setQuery("SET @rownum=0;");
                parent::execQuery();
                parent::setQuery("  SELECT      @`rownum` := @`rownum` + 1 AS `id`,
                                                channels.`callerIDNum` AS `number`, 
                                                channels.`duration` AS `time`,
                                                IFNULL(channels_transfered.callerIDNum,IFNULL(channels2.exten,channels.exten)) AS exten
        
        
                                    FROM  	    `asterisk`.`channels`  AS channels
                                    LEFT JOIN	asterisk.channels AS channels2 ON channels2.context = 'from-internal-xfer' AND channels2.callerIDNum = channels.callerIDNum AND channels2.`application` = 'Queue' 
                                    LEFT JOIN	asterisk.channels AS channels_transfered ON channels_transfered.context = 'from-trunk-sip-EE_Trunk_2484848' AND channels_transfered.connectedLineNum = channels.callerIDNum
                                    WHERE  	    (channels.`context` = 'ext-queues' OR channels.context = 'from-internal-xfer' OR channels.context = 'from-internal') AND channels.`application` = 'Queue' 
                                    AND         channels.callerIDNum NOT IN(SELECT callerIDNum FROM asterisk.channels AS `chan` WHERE (chan.context = 'macro-dial-one' OR chan.context = 'macro-dial') AND chan.channelStateDesc = 'Up')
                                    AND         channels.exten IN(SELECT number FROM `asterisk_queue` WHERE actived = 1)
                                    GROUP BY	channels.`callerIDNum`
                                    ORDER BY    channels.`duration` DESC");
        
                $queueList = parent::getResultArray();
                break;
            case 'mail':
                parent::setQuery("SET @rownum=0;");
                parent::execQuery();
                parent::setQuery("  SELECT  @`rownum` := @`rownum` + 1 AS `id`,
                                            mail.sender_name AS sender_name,
                                            SEC_TO_TIME(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(mail.fetch_datetime)) AS duration
                
                                    FROM 	mail
                                    WHERE 	mail.mail_status_id = 1 AND mail.mail_type_id = 2");
        
                $queueList = parent::getResultArray();
                break;
            default:
                parent::setQuery("SET @rownum=0;");
                parent::execQuery();
                parent::setQuery("  SELECT  @`rownum` := @`rownum` + 1 AS `id`,
                                            chat.sender_name,
                                            SEC_TO_TIME(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(chat.first_datetime)) AS duration

                                    FROM 	chat
                                    JOIN	source ON source.id = chat.source_id
                                    WHERE 	chat.chat_status_id = 1 AND source.`key` = '$this->source'");
        
                $queueList = parent::getResultArray();
        }
        

        return $queueList['result'];
    }

    public function flashPanel(){
        $this->source   = $_REQUEST['source'];
        $panelArray     = array();
        switch($this->source){
            case 'phone':
                parent::setQuery("  SELECT 		asterisk_extension.id,
                                                IFNULL(user_info.name, '') AS operator,
                                                queueMembers.name AS used_ext,
                                                GROUP_CONCAT(queueMembers.queue) AS queues,
                                                IFNULL(chan.exten,queueMembers.queue) AS `queue_now`,
                                                GROUP_CONCAT(source.`key`) AS sources,
                                                IFNULL(main_chan.connectedLineNum, IF(chan_out.channelStateDesc = 'Up',chan_out.connectedLineNum,'')) AS `phone`,
                                                CASE
                                                        WHEN `main_chan`.`context` = 'macro-dial-one' OR `main_chan`.`context` = 'macro-dial' THEN 'in'
                                                        WHEN `chan_out`.`context` = 'macro-dialout-trunk' THEN 'out'
                                                        WHEN (SELECT context FROM asterisk.channels WHERE bridgeID = `main_chan`.`bridgeID` AND application='Dial' LIMIT 1) = 'autodialer' THEN 'in-autodialer'
                                                END  AS `type`,
                                                IFNULL(TIME_FORMAT(IFNULL(main_chan.duration, chan_out.duration), '%i:%s'), '00:00') AS `time`,
                                                IF(queueMembers.paused = 1 OR (UNIX_TIMESTAMP(NOW()) - `queueMembers`.`lastCall`) < `queues`.`wrapUpTime`, 'paused', queueMemberStatus.`icon`) AS `status`


                                    FROM 		asterisk.queueMembers
                                    LEFT JOIN   asterisk.`queues` ON `queues`.`queue` = `queueMembers`.`queue`
                                    JOIN        asterisk.queueMemberStatus ON queueMembers.`status` = queueMemberStatus.id
                                    JOIN        callapp.asterisk_extension ON asterisk_extension.number = queueMembers.`name`
                                    LEFT JOIN   callapp.users ON users.extension_id = asterisk_extension.id AND users.logged = 1
                                    LEFT JOIN   callapp.user_info ON user_info.user_id = users.id


                                    LEFT JOIN   asterisk.channels AS main_chan ON main_chan.callerIDName = queueMembers.`name` AND REPLACE(SUBSTRING_INDEX(queueMembers.location,'@',1),'Local','SIP') = SUBSTRING_INDEX(main_chan.channel,'-',1)
                                    LEFT JOIN   asterisk.channels AS chan ON chan.linkedid = main_chan.linkedid AND chan.context = 'from-queue'
                                    LEFT JOIN   asterisk.channels AS `chan_out` ON REPLACE(SUBSTRING_INDEX(queueMembers.location,'@',1),'Local','SIP') = SUBSTRING_INDEX(chan_out.channel,'-',1) AND chan_out.context = 'macro-dialout-trunk'
                                    LEFT JOIN	source_control ON source_control.user_id = users.id AND source_control.actived = 1
                                    LEFT JOIN	source ON source.id = source_control.source_id

                                    WHERE 		queueMembers.queue IN(SELECT callapp.asterisk_queue.number FROM asterisk_queue WHERE actived = 1) AND !ISNULL(users.id)
                                    GROUP BY    queueMembers.`name`

                                    UNION ALL

                                    SELECT 		asterisk_extension.id,
                                                '' AS operator,
                                                queueMembers.name AS used_ext,
                                                GROUP_CONCAT(queueMembers.queue) AS queues,
                                                IFNULL(chan.exten,queueMembers.queue) AS `queue_now`,
                                                '' AS sources,
                                                IFNULL(main_chan.connectedLineNum, IF(chan_out.channelStateDesc = 'Up',chan_out.connectedLineNum,'')) AS `phone`,
                                                CASE
                                                        WHEN `main_chan`.`context` = 'macro-dial-one' OR `main_chan`.`context` = 'macro-dial' THEN 'in'
                                                        WHEN `chan_out`.`context` = 'macro-dialout-trunk' THEN 'out'
                                                        WHEN (SELECT context FROM asterisk.channels WHERE bridgeID = `main_chan`.`bridgeID` AND application='Dial' LIMIT 1) = 'autodialer' THEN 'in-autodialer'
                                                END  AS `type`,
                                                IFNULL(TIME_FORMAT(IFNULL(main_chan.duration, chan_out.duration), '%i:%s'), '00:00') AS `time`,
                                                IF(queueMembers.paused = 1 OR (UNIX_TIMESTAMP(NOW()) - `queueMembers`.`lastCall`) < `queues`.`wrapUpTime`, 'paused', queueMemberStatus.`icon`) AS `status`


                                    FROM 		asterisk.queueMembers
                                    LEFT JOIN   asterisk.`queues` ON `queues`.`queue` = `queueMembers`.`queue`
                                    JOIN        asterisk.queueMemberStatus ON queueMembers.`status` = queueMemberStatus.id
                                    JOIN        callapp.asterisk_extension ON asterisk_extension.number = queueMembers.`name`
                                    LEFT JOIN   callapp.users ON users.extension_id = asterisk_extension.id AND users.logged = 1


                                    LEFT JOIN   asterisk.channels AS main_chan ON main_chan.callerIDName = queueMembers.`name` AND REPLACE(SUBSTRING_INDEX(queueMembers.location,'@',1),'Local','SIP') = SUBSTRING_INDEX(main_chan.channel,'-',1)
                                    LEFT JOIN   asterisk.channels AS chan ON chan.linkedid = main_chan.linkedid AND chan.context = 'from-queue'
                                    LEFT JOIN   asterisk.channels AS `chan_out` ON REPLACE(SUBSTRING_INDEX(queueMembers.location,'@',1),'Local','SIP') = SUBSTRING_INDEX(chan_out.channel,'-',1) AND chan_out.context = 'macro-dialout-trunk'
                                    WHERE 		queueMembers.queue IN(SELECT callapp.asterisk_queue.number FROM asterisk_queue WHERE actived = 1) AND ISNULL(users.id)
                                    GROUP BY    queueMembers.`name`");

                $panelData = parent::getResultArray();
                
                foreach($panelData['result'] AS $data){

                    $queues = explode(',', $data['queues']);
                    $queuesArray = array();
                    foreach($queues AS $queue){
                        array_push($queuesArray, array("text" => $queue, "background" => "#000", "foreground" => "#FFF"));
                    }

                    array_push($panelArray, array(  "id" => $data['id'],
                                                    "operator" => array("text" => $data['operator'], "avatar" => false, "status" => array("key" => "active", "title" => "აქტიური", "background" => "red"), "extension" => array("text" => $data['used_ext'], "background" => "#B2E9A9", "foreground" => "")),
                                                    "accList" => $queuesArray,
                                                    "account" => array("text" => $data['queue_now'], "background" => "#FFF7CF", "foreground" => ""),
                                                    "sources" => explode(',', $data['sources']),
                                                    "sourceKey" => "phone",
                                                    "status" => $data['status'],
                                                    "author" => array("text" => "", "avatar" => false, "number" => array("text" => $data['phone'], "background" => "", "foreground" => "")),
                                                    "callType" => $data['type'],
                                                    "duration" => $data['time'],
                                                    "totalDuration" => $data['time']));
                }
                break;
            case 'mail':
                parent::setQuery("  SELECT 		mail.id AS id,
                                                user_info.name AS operator_name,
                                                false AS operator_avatar,
                                                mail_account.`name` AS mail_account_name,
                                                mail_account.color AS mail_account_color,
                                                false AS sender_avatar,
                                                mail.sender_name AS sender_name,
                                                mail.sender_address AS sender_address,
                                                SEC_TO_TIME(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(mail.fetch_datetime)) AS duration

                                    FROM 		mail
                                    LEFT JOIN   users ON users.id = mail.user_id
                                    LEFT JOIN   user_info ON user_info.user_id = users.id
                                    JOIN        mail_account ON mail_account.id = mail.account_id
                                    WHERE 		mail.mail_status_id = 2 AND mail.mail_type_id = 2");
                $panelData = parent::getResultArray();

                foreach($panelData['result'] AS $data){
                    array_push($panelArray, array(  "id" => $data['id'],
                                                    "operator" => array("text" => $data['operator_name'], "avatar" => false),
                                                    "account" => array("text" => $data['mail_account_name'], "foreground" => $data['mail_account_color']),
                                                    "sourceKey" => "mail",
                                                    "author" => array("text" => $data['sender_name'], "address" => $data['sender_address'], "avatar" => false),
                                                    "duration" => $data['duration'],
                                                    "totalDuration" => $data['duration']));
                }
                break;
            default:
                parent::setQuery("  SELECT		chat.id,
                                                source.`key`,
                                                user_info.name AS operator_name,
                                                chat_account.`name` AS account_name,
                                                chat_account.color AS account_color,
                                                chat.sender_name AS sender_name,
                                                IF((SELECT user_id FROM chat_details WHERE chat_id = chat.id ORDER BY id DESC LIMIT 1) = 0, 'false', 'true') AS newMessage,
                                                SEC_TO_TIME(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(chat.last_datetime)) AS duration
                                                
                                    FROM 		chat
                                    JOIN	    source on source.id = chat.source_id
                                    LEFT JOIN   users ON users.id = chat.last_user_id
                                    LEFT JOIN   user_info ON user_info.user_id = users.id
                                    LEFT JOIN	chat_account ON chat_account.id = chat.account_id AND chat_account.source_id = chat.source_id
                                    WHERE 		chat.chat_status_id = 2 AND source.`key` = '$this->source'");
                $panelData = parent::getResultArray();

                foreach($panelData['result'] AS $data){
                    array_push($panelArray, array(  "id" => $data['id'],
                                                    "operator" => array("text" => $data['operator_name'], "avatar" => false),
                                                    "account" => array("text" => $data['account_name'], "foreground" => $data['account_color']),
                                                    "sourceKey" => $data['key'],
                                                    "author" => array("text" => $data['sender_name'], "avatar" => false),
                                                    "newMessage" => (bool)$data['newMessage'],
                                                    "duration" => $data['duration'],
                                                    "totalDuration" => $data['duration']));
                }
        }
        

        return $panelArray;
        
    }

    public function getFlashPanelQueueCountArray(){
        parent::setQuery("  SELECT 	    'phone' AS source_key,
                                        COUNT(*) AS 'queue'

                            FROM 	    asterisk_call_log
                            WHERE 	    call_type_id = 1 AND call_status_id = 2

                            UNION ALL

                            SELECT		source.`key` AS source_key,
                                        COUNT(chat.id) AS 'queue'
                            FROM 		source
                            LEFT JOIN	chat ON chat.source_id = source.id AND chat.chat_status_id = 1
                            WHERE 		source.actived = 1 AND source.id NOT IN (1)
                            GROUP BY 	source.id

                            UNION ALL

                            SELECT	    'mail' AS source_key,
                                        COUNT(*) AS 'queue'
                            FROM 	    mail
                            WHERE 	    mail_status_id = 1");
        $sourceQueue = parent::getResultArray();
        
        $queueArray = array();

        foreach($sourceQueue['result'] AS $queue){
            array_push($queueArray, array("source" => $queue['source_key'], "value" => (int)$queue['queue']));
        }

        return $queueArray;
    }

    public function getFlashPanelQueueCountObject(){
        parent::setQuery("  SELECT 	'phone' AS source_key,
                                    COUNT(*) AS 'queue'

                            FROM 	asterisk_call_log
                            WHERE 	call_type_id = 1 AND call_status_id = 2

                            UNION ALL
                            
                            SELECT		source.`key` AS source_key,
                                        COUNT(chat.id) AS 'queue'
                            FROM 		source
                            LEFT JOIN	chat ON chat.source_id = source.id AND chat.chat_status_id = 1
                            WHERE 		source.actived = 1 AND source.id NOT IN (1)
                            GROUP BY 	source.id
                            
                            UNION ALL

                            SELECT	    'mail' AS source_key,
                                        COUNT(*) AS 'queue'
                            FROM 	    mail
                            WHERE 	    mail_status_id = 1");
        $sourceQueue = parent::getResultArray();
        
        $queueArray = array();

        foreach($sourceQueue['result'] AS $queue){
            array_push($queueArray, array($queue['source_key'] => (int)$queue['queue']));
        }

        return $queueArray;
    }
}

?>