<?php

namespace Controllers\IncommingTabs;

use Routers\dbClass;

class sms extends dbClass
{

    public function GET($colCount, $cols, $inc_id)
    {

        $filter  = "";
        if ($inc_id > 0) {
            $filter = " AND sms.row_id = '$inc_id' ";
        }

        parent::setQuery("  SELECT	sms_detail.id,
                                    sms_detail.sent_datetime,
                                    user_info.name,
                                    sms_detail.receiver,
                                    sms_detail.message,
                                    sms_status.`name` 

                            FROM 		sms
                            JOIN 		sms_detail on sms_detail.sms_id = sms.id
                            LEFT JOIN   users ON users.id = sms.user_id
                            LEFT JOIN   user_info ON user_info.user_id = users.id
                            JOIN 		sms_status on sms_detail.`status` = sms_status.id
                            WHERE 	sms.actived = 1 $filter 
                            ORDER BY id DESC");
        $callList = parent::getKendoList($colCount, $cols);

        return $callList;
    }

    function INSERT($text, $inc_id, $phones)
    {
        $phones = explode(',', $phones);
        $values = "";
        $encodedtxt = urlencode($text);
        $user_id    = $_SESSION['USERID'];

        parent::setQuery("INSERT INTO sms(`user_id`,`datetime`,`processing_page_id`,`row_id`,`status`,`client_account`) 
                                     VALUES ('$user_id',NOW(),0,'$inc_id',1,'1') ON DUPLICATE KEY UPDATE datetime = NOW() ");
        parent::execQuery();
        $lastid = parent::getLastId();

        foreach ($phones as $phone) {
            if (strlen($phone) == (9 || 12)) {
                if (strlen($phone) == 12) {
                    $phone = substr($phone, 3);
                }

                if (substr($phone, 0, 1) == 5) {
                    $phone = "995" . $phone;
                    $check     = file_get_contents('http://192.168.0.185/mt/oneway?username=energopro&password=EPG343&client_id=343&service_id=1&coding=2&to=' . $phone . '&text=' . $encodedtxt);
                    if ($check) {
                        $values .= "('$user_id',NOW(),'$lastid',NOW(),'$text',1,$phone),";
                    }
                } else {
                    $check = false;
                }
            } else {
                $check = false;
            }
        }

        if ($values != "") {
            parent::setQuery("  UPDATE sms 
                                SET  status = '1'
                                WHERE id = '$lastid'");

            parent::execQuery();

            $values = substr_replace($values, "", -1);

            parent::setQuery("INSERT INTO sms_detail(`user_id`,`datetime`,`sms_id`,`sent_datetime`,`message`,`status`,`receiver`) 
                   VALUES " . $values . "");
            parent::execQuery();
        }

        $response = array("status" => $check);

        return $response;
    }
}
