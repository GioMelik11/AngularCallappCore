<?php

use Services\Custom\searchByNumber\searchByNumber;

use Routers\dbClass;

class Incomming extends dbClass
{

    private $colCount;
    private $cols;


    public function checkDialogOpenData()
    {
        $extID = $_REQUEST['extID'];


        parent::setQuery("  SELECT      asterisk_extension.id,
                                        IFNULL(user_info.name, '') AS operator,
                                        queueMembers.name AS used_ext,
                                        (SELECT incomming_request.id FROM asterisk_call_log JOIN incomming_request ON incomming_request.asterisk_call_log_id = asterisk_call_log.id WHERE asterisk_call_log.source = IFNULL(main_chan.connectedLineNum, IF(chan_out.channelStateDesc = 'Up',chan_out.connectedLineNum,'')) AND extension_id = asterisk_extension.id ORDER BY asterisk_call_log.id DESC LIMIT 1) AS inc_id,
                                        main_chan.uniqueid,
                                        IFNULL(main_chan.connectedLineNum, IF(chan_out.channelStateDesc = 'Up',chan_out.connectedLineNum,'')) AS `phone`,
                                        CASE
                                                        WHEN `main_chan`.`context` = 'macro-dial-one' OR `main_chan`.`context` = 'macro-dial' THEN 'in'
                                                        WHEN `chan_out`.`context` = 'macro-dialout-trunk' THEN 'out'
                                                        WHEN (SELECT context FROM asterisk.channels WHERE bridgeID = `main_chan`.`bridgeID` AND application='Dial' LIMIT 1) = 'autodialer' THEN 'in-autodialer'
                                        END  AS `type`,
                                        IF(queueMembers.paused = 1 OR (UNIX_TIMESTAMP(NOW()) - `queueMembers`.`lastCall`) < `queues`.`wrapUpTime`, 'paused', queueMemberStatus.`icon`) AS `status`


                            FROM 				asterisk.queueMembers
                            LEFT JOIN   asterisk.`queues` ON `queues`.`queue` = `queueMembers`.`queue`
                            JOIN        asterisk.queueMemberStatus ON queueMembers.`status` = queueMemberStatus.id
                            JOIN        asterisk_extension ON asterisk_extension.number = queueMembers.`name`
                            LEFT JOIN   users ON users.extension_id = asterisk_extension.id AND users.logged = 1
                            LEFT JOIN   user_info ON user_info.id = users.person_id
                            LEFT JOIN   asterisk.channels AS main_chan ON main_chan.callerIDName = queueMembers.`name` AND REPLACE(SUBSTRING_INDEX(queueMembers.location,'@',1),'Local','SIP') = SUBSTRING_INDEX(main_chan.channel,'-',1)
                            LEFT JOIN   asterisk.channels AS chan ON chan.linkedid = main_chan.linkedid AND chan.context = 'from-queue'
                            LEFT JOIN   asterisk.channels AS `chan_out` ON REPLACE(SUBSTRING_INDEX(queueMembers.location,'@',1),'Local','SIP') = SUBSTRING_INDEX(chan_out.channel,'-',1) AND chan_out.context = 'macro-dialout-trunk'
                            WHERE 		queueMembers.queue IN(SELECT asterisk_queue.number FROM asterisk_queue WHERE actived = 1) AND asterisk_extension.id = '$extID'
                            LIMIT 1
                            ");

        $dialog = parent::getResultArray();
        $dialogData = $dialog['result'][0];

        if ($dialog['count'] > 0) {
            if ($dialogData['status'] == 'busy') {
                return array("status" => true, "incommingId" => $dialogData['inc_id'], "phone" => $dialogData['phone']);
            } else {
                return array("status" => false);
            }
        } else {
            return array("status" => false);
        }
    }



    function checkExtStatus()
    {

        $extID  = $_REQUEST['extID'];
        $userID = $_REQUEST['userID'];

        parent::setQuery("  SELECT      asterisk_extension.id,
                                        IFNULL(user_info.name, '') AS operator,
                                        queueMembers.name AS used_ext,
                                        (SELECT incomming_request.id FROM asterisk_call_log JOIN incomming_request ON incomming_request.asterisk_call_log_id = asterisk_call_log.id WHERE asterisk_call_log.source = IFNULL(main_chan.connectedLineNum, IF(chan_out.channelStateDesc = 'Up',chan_out.connectedLineNum,'')) AND extension_id = asterisk_extension.id ORDER BY asterisk_call_log.id DESC LIMIT 1) AS inc_id,
                                        main_chan.uniqueid,
                                        IFNULL(main_chan.connectedLineNum, IF(chan_out.channelStateDesc = 'Up',chan_out.connectedLineNum,'')) AS `phone`,
                                        CASE
                                                        WHEN `main_chan`.`context` = 'macro-dial-one' OR `main_chan`.`context` = 'macro-dial' THEN 'in'
                                                        WHEN `chan_out`.`context` = 'macro-dialout-trunk' THEN 'out'
                                                        WHEN (SELECT context FROM asterisk.channels WHERE bridgeID = `main_chan`.`bridgeID` AND application='Dial' LIMIT 1) = 'autodialer' THEN 'in-autodialer'
                                        END  AS `type`,
                                        IF(queueMembers.paused = 1 OR (UNIX_TIMESTAMP(NOW()) - `queueMembers`.`lastCall`) < `queues`.`wrapUpTime`, 'paused', queueMemberStatus.`icon`) AS `status`,
                                        users.id AS `user_id`
  
                            FROM 				asterisk.queueMembers
                            LEFT JOIN   asterisk.`queues` ON `queues`.`queue` = `queueMembers`.`queue`
                            JOIN        asterisk.queueMemberStatus ON queueMembers.`status` = queueMemberStatus.id
                            JOIN        asterisk_extension ON asterisk_extension.number = queueMembers.`name`
                            LEFT JOIN   users ON users.extension_id = asterisk_extension.id AND users.logged = 1
                            LEFT JOIN   user_info ON user_info.user_id = users.id
                            LEFT JOIN   asterisk.channels AS main_chan ON main_chan.callerIDName = queueMembers.`name` AND REPLACE(SUBSTRING_INDEX(queueMembers.location,'@',1),'Local','SIP') = SUBSTRING_INDEX(main_chan.channel,'-',1)
                            LEFT JOIN   asterisk.channels AS chan ON chan.linkedid = main_chan.linkedid AND chan.context = 'from-queue'
                            LEFT JOIN   asterisk.channels AS `chan_out` ON REPLACE(SUBSTRING_INDEX(queueMembers.location,'@',1),'Local','SIP') = SUBSTRING_INDEX(chan_out.channel,'-',1) AND chan_out.context = 'macro-dialout-trunk'
                            WHERE 		queueMembers.queue IN(SELECT asterisk_queue.number FROM asterisk_queue WHERE actived = 1) AND asterisk_extension.id = '$extID'
                            LIMIT 1
                            ");

        $dialog = parent::getResultArray();
        $dialogData = $dialog['result'][0];

        if ($dialog['count'] > 0) {
            if ($dialogData['status'] == 'busy') {
                return array("status" => true, "incommingId" => $dialogData['inc_id'], "phone" => $dialogData['phone'], "collection" => "calls", "userID" => $dialogData['user_id']);
            } else {
                return array("status" => false);
            }
        } else {
            return array("status" => false);
        }
    }

    function get_source_info()
    {


        $id = $_REQUEST['id'];

        parent::setQuery("  SELECT  did as number
                        FROM    `asterisk_call_log`
                        WHERE   `id` = '$id'");

        $data = parent::getResultArray()['result'][0];


        return $data;
    }

    function insertData()
    {

        $user_id = $_SESSION['USERID'];

        parent::setQuery("INSERT INTO incomming_request(`processing_start`,`datetime`,`user_id`,`status`,`source_id`,`actived`)
                                                                 VALUES (NOW(),NOW(),'$user_id','0','0','0')");
        parent::execQuery();

        parent::setQuery("  SELECT	    *,
                                        'phone' as source,
                                        '' as telefoni
                            FROM 		incomming_request
                            WHERE 	    id = '" . parent::getLastId() . "'
                            ORDER BY    id DESC");
        $data = parent::getResultArray()['result'][0];

        return $data;
    }

    function updateData()
    {

        $id = $_REQUEST['id'];

        parent::setQuery(" UPDATE incomming_request 
                            SET actived = '1',
                                source_id = '16'    
                            WHERE id = '$id'");
        parent::execQuery();


        return array("status" => "OK");
    }


    function checkCallStatus()
    {


        $_id = $_REQUEST['_id'];

        parent::setQuery("  SELECT  processing_status, request_id as id
                            FROM    `asterisk_call_log_live_calls`
                            WHERE   `id` = '$_id'
                        ");

        $data = parent::getResultArray()['result'][0];


        return array("data" => $data);
    }


    public function getList()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        $startDate      = rawurldecode($_REQUEST['startDate']);
        $endDate        = rawurldecode($_REQUEST['endDate']);
        $operator       = $_REQUEST['operator'];
        $sources_multi  = rawurldecode($_REQUEST['sources']);
        $allstatus_multi  = rawurldecode($_REQUEST['allstatus']);

        $filter = "";
        $filter = " AND incomming_request.datetime BETWEEN '$startDate' AND '$endDate' ";

        if (!empty($operator)) {
            $filter .= " AND incomming_request.user_id = '$operator'";
        }
        if (!empty($sources_multi)) {
            $filter .= " AND incomming_request.`source_id` IN($sources_multi)";
        }

        if (!empty($allstatus_multi)) {
            $filter .= " AND  (incomming_request.status IN($allstatus_multi) OR incomming_request.asterisk_call_status_id IN($allstatus_multi)) ";
        }

        parent::setQuery("   SELECT	    incomming_request.id,
                                        source.key,
                                        incomming_request.chat_id,
                                        incomming_request.datetime,
                                        user_info.name,
                                        CASE
                                            WHEN NOT ISNULL(incomming_request.asterisk_call_log_id) THEN IFNULL(processing.`value`->>'$.inputs.telefoni___987',incomming_request.request_name) 
                                            WHEN NOT ISNULL(incomming_request.chat_id) THEN chat.sender_name
                                            WHEN NOT ISNULL(incomming_request.mail_id) THEN incomming_request.request_name
                                        END AS client,
                                        processing.`value`->>'$.inputs.input_003',
                                        processing.`value`->>'$.inputs.input_002',
                                        info_category.name AS cat,
                                        dir_momartvis_info_newinputID_136.`name`,

                                        CONCAT(TIME_FORMAT(SEC_TO_TIME(asterisk_call_log.talk_time),'%i:%s'), ' (', TIME_FORMAT(SEC_TO_TIME(asterisk_call_log.wait_time),'%i:%s'), ')') as `time`,
                                        CONCAT(DATE_FORMAT( FROM_UNIXTIME( asterisk_call_log.call_datetime ), '%Y/%m/%d/' ), record.NAME, '.', record.format ) as `record`,

                                        incomming_request.status,
                                        asterisk_call_log.call_type_id as asterisk_call_type_id,
                                        asterisk_call_log.call_status_id as asterisk_call_status_id,
                                        '' as status,
                                        IF(NOT ISNULL(incomming_request.processing_start),0,1),
                                        IF(NOT ISNULL(incomming_request.processing_end),0,1),
                                        incomming_request.source_id,
                                        incomming_request.asterisk_call_log_id,
                                        incomming_request.mail_id
                                FROM 		incomming_request
                                LEFT JOIN   asterisk_call_log  ON `asterisk_call_log`.id = incomming_request.asterisk_call_log_id
                                LEFT JOIN   asterisk_call_record AS `record` ON record.asterisk_call_log_id = incomming_request.asterisk_call_log_id
                                LEFT JOIN	users ON users.id = incomming_request.user_id
                                LEFT JOIN	user_info ON user_info.user_id = users.id
                                LEFT JOIN	chat ON chat.id = incomming_request.chat_id
                                LEFT JOIN   processing ON processing.processing_page_id = 1 AND processing.row_id = incomming_request.id
                                LEFT JOIN   dir_momartvis_info_newinputID_136 ON dir_momartvis_info_newinputID_136.id = processing.`value`->>'$.selectors.reagireba_statusi_'
                                LEFT JOIN   info_category ON info_category.id = processing.`value`->>'$.multilevel.zaris_kategoriebi___594___1'
                                LEFT JOIN   source ON source.id = incomming_request.source_id
                                WHERE 	    incomming_request.actived = 1 $filter
                                ORDER BY    id DESC");
        $callList = parent::getKendoList($this->colCount, $this->cols);

        return $callList;
    }

    public function closeSession()
    {

        $source = $_REQUEST['source'];
        $chatID = $_REQUEST['chatId'];

        switch ($source) {

            case 'mail':
                parent::setQuery("UPDATE mail SET mail_status_id = 3 WHERE id = '$chatID'");
                parent::execQuery();
                break;
            default:
                parent::setQuery("UPDATE chat SET chat_status_id = 4 WHERE id = '$chatID'");
                parent::execQuery();
        }

        return array("message" => "სესია წარმატებით დაიხურა");
    }

    public function getAllStatus()
    {
        parent::setQuery("  SELECT  status_id as id,
                                    name
                            FROM  allstatus
                            WHERE actived = 1");

        $result = parent::getResultArray()['result'];

        return $result;
    }

    public function setProcesingdate()
    {
        $proc_start = $_REQUEST['start'];
        $proc_end = $_REQUEST['end'];
        $inc_id = $_REQUEST['inc_id'];

        if ($proc_start > 0) {
            parent::setQuery("UPDATE incomming_request SET processing_start = NOW() WHERE id = '$inc_id'");
            parent::execQuery();

            return array("Status", "OK");
        }

        if ($proc_end > 0) {
            parent::setQuery("UPDATE incomming_request SET processing_end = NOW() WHERE id = '$inc_id'");
            parent::execQuery();

            return array("Status", "OK");
        }

        return array("Status", "Failed");
    }

    public function get_emojis()
    {
        parent::setQuery("SELECT `emojis`.`code` FROM `emojis` WHERE `emojis`.actived = 1");
        $result = parent::getResultArray()['result'];

        return $result;
    }

    public function getSearchData()
    {

        $client_number = $_REQUEST['clientNumber'];

        $service =  new searchByNumber();
        $service->SET($client_number);
        $data = $service->GET();

        return array("Data" => $data);
    }

    public function get_parent_id()
    {
        $parend_ids = [];
        $id = $_REQUEST['id'];
        parent::setQuery("SELECT parent_id FROM `info_category` WHERE id = '$id'");
        $parend_ids['second'] = parent::getResultArray()['result'][0]['parent_id'];

        if ($parend_ids['second'] > 0) {
            parent::setQuery("SELECT parent_id FROM `info_category` WHERE id = '$parend_ids[second]'");
            $parend_ids['first'] = parent::getResultArray()['result'][0]['parent_id'];
        }


        return $parend_ids;
    }

    public function get_call_types()
    {
        $id = $_REQUEST['id'];
        parent::setQuery("SELECT id,name FROM `info_category` WHERE parent_id = '$id'");
        $data = parent::getResultArray()['result'];

        return $data;
    }
}
