<?php

namespace Controllers\IncommingTabs;

use Routers\dbClass;

class records extends dbClass
{

    public function GETInc($colCount, $cols, $phone)
    {

        parent::setQuery("  SELECT  asterisk_call_log.id,
                                    FROM_UNIXTIME(asterisk_call_log.call_datetime),
                                    CONCAT(TIME_FORMAT(SEC_TO_TIME(asterisk_call_log.talk_time),'%i:%s'), ' (', TIME_FORMAT(SEC_TO_TIME(asterisk_call_log.wait_time),'%i:%s'), ')'),
                                    CONCAT(DATE_FORMAT( FROM_UNIXTIME( asterisk_call_log.call_datetime ), '%Y/%m/%d/' ), asterisk_call_record.NAME, '.', asterisk_call_record.format )
                            FROM asterisk_call_log 
                            JOIN asterisk_call_record ON asterisk_call_log.id = asterisk_call_record.asterisk_call_log_id
                            WHERE asterisk_call_log.call_type_id = 1 AND asterisk_call_log.source = '$phone'");
        $callList = parent::getKendoList($colCount, $cols);

        return $callList;
    }

    public function GETOut($colCount, $cols, $phone)
    {

        parent::setQuery("  SELECT  asterisk_call_log.id,
                                    FROM_UNIXTIME(asterisk_call_log.call_datetime),
                                    CONCAT(TIME_FORMAT(SEC_TO_TIME(asterisk_call_log.talk_time),'%i:%s'), ' (', TIME_FORMAT(SEC_TO_TIME(asterisk_call_log.wait_time),'%i:%s'), ')'),
                                    CONCAT( DATE_FORMAT( FROM_UNIXTIME( asterisk_call_log.call_datetime ), '%Y/%m/%d/' ), asterisk_call_record.NAME, '.', asterisk_call_record.format )
                            FROM asterisk_call_log 
                            JOIN asterisk_call_record ON asterisk_call_log.id = asterisk_call_record.asterisk_call_log_id
                            WHERE asterisk_call_log.call_type_id = 2 AND asterisk_call_log.destination = '$phone'");;
        $callList = parent::getKendoList($colCount, $cols);

        return $callList;
    }
}
