<?php

namespace Controllers\IncommingTabs;

use Routers\dbClass;

class logs extends dbClass{

    public function GET($colCount,$cols,$inc_id){

        parent::setQuery("  SELECT	incomming_request.id,
                                    incomming_request.datetime,
                                    source.key,
                                    processing.`value`->>'$.inputs.abonenti___079' AS abonenti,
                                    processing.`value`->>'$.inputs.abonentis_nomeri___019' AS ab_number,
                                    CASE
                                            WHEN NOT ISNULL(incomming_request.asterisk_call_log_id) THEN IFNULL(processing.`value`->>'$.inputs.telefoni___987',incomming_request.request_name)
                                            WHEN NOT ISNULL(incomming_request.chat_id) THEN chat.sender_name
                                    END AS client

                            FROM 		incomming_request
                            LEFT JOIN	users ON users.id = incomming_request.user_id
                            LEFT JOIN	user_info ON user_info.user_id = users.id
                            LEFT JOIN	chat ON chat.id = incomming_request.chat_id
                            LEFT JOIN   processing ON processing.processing_page_id = 1 AND processing.row_id = incomming_request.id
                            LEFT JOIN   dir_momartvis_info_newinputID_136 AS statusi ON statusi.id = processing.`value`->>'$.selectors.reagireba_statusi_'
                            LEFT JOIN   info_category ON info_category.id = processing.`value`->>'$.multilevel.zaris_kategoriebi___594___1'
                            LEFT JOIN   source ON source.id = incomming_request.source_id
                            WHERE 	incomming_request.actived = 1

                            ORDER BY id DESC");

        $callList = parent::getKendoList($colCount, $cols);
        
        return $callList;
    }
 
}