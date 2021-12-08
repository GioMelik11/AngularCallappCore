<?php

namespace Controllers\IncommingTabs;

use Routers\dbClass;

class history extends dbClass
{

    public function GET($colCount, $cols, $startDate, $endDate, $phone, $clientNum)
    {

        $startDate = $startDate . " 00:00:00";
        $endDate = $endDate . " 23:59:59";

        $filter = "";
        $filter = " AND incomming_request.datetime BETWEEN '$startDate' AND '$endDate' ";
        if (!empty($phone)) {
            $filter .= " AND (incomming_request.request_name = '$phone' OR  processing.`value`->>'$.inputs.telefoni___987' = '$phone')";
        }

        if (!empty($clientNum)) {
            $filter .= " AND processing.`value`->>'$.inputs.abonentis_nomeri___019' = '$clientNum'";
        }

        if (!empty($phone) && !empty($clientNum)) {
            return false;
        }

        parent::setQuery("  SELECT	incomming_request.id,
                                    incomming_request.datetime,
                                    source.key,
                                    processing.`value`->>'$.inputs.abonenti___079' AS abonenti,
                                    processing.`value`->>'$.inputs.abonentis_nomeri___019' AS ab_number,
                                    CASE
                                            WHEN NOT ISNULL(incomming_request.asterisk_call_log_id) THEN IFNULL(processing.`value`->>'$.inputs.telefoni___987',incomming_request.request_name)
                                            WHEN NOT ISNULL(incomming_request.chat_id) THEN chat.sender_name
                                    END AS client,
                                    processing.`value`->>'$.inputs.komentari___766' AS comment,
                                    (SELECT result FROM task WHERE task_source_row_id = incomming_request.id ORDER BY task.id DESC LIMIT 1) AS shedegi,
                                    statusi.`name` AS statusi

                            FROM 		incomming_request
                            LEFT JOIN	users ON users.id = incomming_request.user_id
                            LEFT JOIN	user_info ON user_info.user_id = users.id
                            LEFT JOIN	chat ON chat.id = incomming_request.chat_id
                            LEFT JOIN   processing ON processing.processing_page_id = 1 AND processing.row_id = incomming_request.id
                            LEFT JOIN   dir_momartvis_info_newinputID_136 AS statusi ON statusi.id = processing.`value`->>'$.selectors.reagireba_statusi_'
                            LEFT JOIN   info_category ON info_category.id = processing.`value`->>'$.multilevel.zaris_kategoriebi___594___1'
                            LEFT JOIN   source ON source.id = incomming_request.source_id
                            WHERE 	incomming_request.actived = 1 $filter 

                            ORDER BY incomming_request.id DESC");

        $callList = parent::getKendoList($colCount, $cols);

        return $callList;
    }
}
