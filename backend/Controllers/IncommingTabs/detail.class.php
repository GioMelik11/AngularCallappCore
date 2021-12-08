<?php

namespace Controllers\IncommingTabs;

use Routers\dbClass;

class detail extends dbClass
{

    public function GET($inc_id)
    {

        parent::setQuery("  SELECT	incomming_request.id,
                                    incomming_request.datetime,
                                    user_info.name as operator,
                                    CASE 
                                         WHEN FROM_UNIXTIME(UNIX_TIMESTAMP(asterisk_call_log.call_datetime)+wait_time+talk_time) <= incomming_request.processing_start
                    						THEN SEC_TO_TIME(UNIX_TIMESTAMP(incomming_request.processing_end)-UNIX_TIMESTAMP(incomming_request.processing_start))
                    					WHEN FROM_UNIXTIME(UNIX_TIMESTAMP(asterisk_call_log.call_datetime)+wait_time+talk_time) > incomming_request.processing_start
                    						AND FROM_UNIXTIME(UNIX_TIMESTAMP(asterisk_call_log.call_datetime)+wait_time+talk_time) >= incomming_request.processing_end
                    						THEN '00:00:00'
                    					WHEN FROM_UNIXTIME(UNIX_TIMESTAMP(asterisk_call_log.call_datetime)+wait_time+talk_time) > incomming_request.processing_start
                    						AND FROM_UNIXTIME(UNIX_TIMESTAMP(asterisk_call_log.call_datetime)+wait_time+talk_time) < incomming_request.processing_end
                    						THEN SEC_TO_TIME(UNIX_TIMESTAMP(incomming_request.processing_end) - (UNIX_TIMESTAMP(asterisk_call_log.call_datetime)+wait_time+talk_time))
                    			    END AS `processed_time`,
                                SEC_TO_TIME(asterisk_call_log.talk_time) AS `talk_time`,
			                    SEC_TO_TIME(asterisk_call_log.wait_time) AS `wait_time`

                            FROM 		incomming_request
                            LEFT JOIN	users ON users.id = incomming_request.user_id
                            LEFT JOIN	user_info ON user_info.id = users.person_id
                            LEFT JOIN   asterisk_call_log ON asterisk_call_log.id = incomming_request.asterisk_call_log_id
                            WHERE 	 incomming_request.id = '$inc_id'");

        $result = parent::getResultArray()['result'][0];

        return $result;
    }
}
