<?php

namespace Controllers\IncommingTabs;

use Mpdf\Tag\Em;
use Routers\dbClass;
use Services\Plugins\Mail\Mail as MailSender;

class mail extends dbClass
{

    public function GET($colCount, $cols, $inc_id)
    {

        parent::setQuery("SELECT	sent_mail.id,
                                        sent_mail.date,
                                        sent_mail.address,
                                        mail_account.mail,
                                        sent_mail_status.`name` 
                            FROM 		sent_mail
                            JOIN    sent_mail_status ON sent_mail.`status` = sent_mail_status.id
                            JOIN    mail_account ON sent_mail.`account_id` = mail_account.id
                            WHERE 	sent_mail.actived = 1 AND sent_mail.incomming_call_id = '$inc_id'
                            ORDER BY id DESC");
        $callList = parent::getKendoList($colCount, $cols);

        return $callList;
    }

    function INSERT($inc_id, $email, $cc, $bcc, $subject, $text, $attachments, $send_record, $insert = true)
    {
        $addresses   = [];
        $mails = "";
        $ccs = "";
        $bccs = "";
        $account_id  = 1;

        if (!empty($attachments)) {
            $attachments = substr_replace($attachments, "", -1);
            $attachments = explode(",", $attachments);
        } else {
            $attachments = [];
        }


        if (!empty($email)) {
            $addresses['address'] = explode(",", $email);
            foreach ($addresses['address'] as $m) {
                $mails .= $m . ",";
            }
        }

        if (!empty($bcc)) {
            $addresses['bcc'] = explode(",", $bcc);
            foreach ($addresses['bcc'] as $b) {
                $bccs .= $b . ",";
            }
        }

        if (!empty($cc)) {
            $addresses['cc'] = explode(",", $cc);
            foreach ($addresses['cc'] as $c) {
                $ccs .= $c . ",";
            }
        }

        if ($send_record > 0) {

            parent::setQuery("  SELECT  CONCAT('http://172.16.50.152/records/',DATE_FORMAT( FROM_UNIXTIME( asterisk_call_log.call_datetime ), '%Y/%m/%d/' ), asterisk_call_record.NAME, '.', asterisk_call_record.format ) as record
                                FROM incomming_request
                                LEFT JOIN asterisk_call_log  ON incomming_request.asterisk_call_log_id = asterisk_call_log.id
                                LEFT JOIN asterisk_call_record ON asterisk_call_log.id = asterisk_call_record.asterisk_call_log_id
                                WHERE incomming_request.id = '$inc_id'");
            $records = parent::getResultArray()['result'];

            if (!empty($records)) {
                foreach ($records as $item) {
                    $url = $item['record'];

                    $ch = curl_init($url);
                    $dir = './Uploads/records/';
                    $file_name = basename($url);
                    $save_file_loc = $dir . $file_name;
                    $fp = fopen($save_file_loc, 'wb');
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    if (!empty($save_file_loc)) {
                        array_push($attachments, substr($save_file_loc, 2));
                    }
                }
            }
        }


        $text = htmlspecialchars($text, ENT_QUOTES);

        $user_id = $_SESSION['USERID'];

        $mail = new MailSender();
        $mail->set($subject, $text, $attachments, $addresses, $account_id);
        $res = $mail->send();

        if ($insert) {
            parent::setQuery("INSERT INTO sent_mail(`incomming_call_id`,`user_id`,`date`,`address`,`cc_address`,`bcc_address`,`subject`,`body`,`status`,`account_id`) 
                                          VALUES ('$inc_id','$user_id',NOW(),'$mails','$ccs','$bccs','$subject','$text',1,$account_id)");
            parent::execQuery();
        }


        $response = array("status" => "OK", $res);

        return $response;
    }
}
