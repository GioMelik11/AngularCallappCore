<?php

use Routers\dbClass;

class Flashpanel extends dbClass{

    

    public function getQueue(){
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        parent::setQuery("SET @rownum=0;");
        parent::execQuery();
        parent::setQuery(" SELECT       @`rownum` := @`rownum` + 1 AS `num`,
                                        channels.`callerIDNum` AS `number`, 
                                        channels.`duration` AS `time`
                                    
                                    
                            FROM  	    asterisk.channels_copy  AS channels
                            LEFT JOIN		asterisk.channels_copy AS channels2 ON channels2.context = 'from-internal-xfer' AND channels2.callerIDNum = channels.callerIDNum AND channels2.`application` = 'Queue' 
                            WHERE  	    (channels.`context` = 'ext-queues' OR channels.context = 'from-internal-xfer') AND channels.`application` = 'Queue' 
                            AND         channels.callerIDNum NOT IN(SELECT callerIDNum FROM asterisk.channels_copy AS `chan` WHERE (chan.context = 'macro-dial-one' OR chan.context = 'macro-dial') AND chan.channelStateDesc = 'Up')
                            AND         channels.exten IN(SELECT number FROM CallappCoreNEW.`asterisk_queue` WHERE actived = 1)
                            GROUP BY		channels.`callerIDNum`
                            ORDER BY    channels.`duration` DESC");
        
        $queueData = parent::getKendoList($this->colCount, $this->cols);

        return $queueData;
    }

}

?>