<?php

use Routers\dbClass;

class call extends dbClass{


/***********
 * 
 */
  function checkCallStatus() {


    $_id = $_REQUEST['_id'];

    parent::setQuery("  SELECT  processing_status, request_id as id
                        FROM    `asterisk_call_log_live_calls`
                        WHERE   `request_id` = '$_id'
                    ");

    $data = parent::getResultArray()['result'][0];
    

    return array("data" => $data);


  }


    function checkExtStatus() {

      $extID  = $_REQUEST['extID'];
      $userID = $_REQUEST['userID'];

      parent::setQuery("UPDATE users SET last_actived_time = NOW() WHERE id = '$userID'");
      parent::execQuery();

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

}