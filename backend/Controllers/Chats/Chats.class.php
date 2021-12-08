<?php

use Routers\dbClass;
use Services\Plugins\Messenger\Messenger;
use Services\Plugins\Mail\Mail;

class Chats extends dbClass
{

    public function fetchMessages()
    {
        $chatID = $_REQUEST['chat_id'];
        $source = $_REQUEST['source'];
        $allMessageData = array();

        if ($source == "mail") {
            parent::setQuery("  SELECT 			mail_detail.id as id,
                                                IF(ISNULL(mail_detail.user_id), 'client', 'operator') AS type,
                                                mail_detail.datetime,
                                                IF(ISNULL(mail_detail.user_id), mail.sender_name, user_info.name) AS name,
                                                CASE
                                                    WHEN mail_attachment.id > 0 THEN
                                                        CONCAT(mail_detail.body,GROUP_CONCAT('<a href=\"','http://172.16.50.153/files/',mail_attachment.name,'\">',mail_attachment.name,'</a>'))
                                                    ELSE
                                                        mail_detail.body
                                                END  as message,
                                                'Assets/images/no-image.png' AS avatar
            
                                FROM 				mail
                                LEFT JOIN		mail_detail ON mail.id = mail_detail.mail_id
                                LEFT JOIN       mail_attachment on mail_attachment.mail_detail_id = mail_detail.id
                                LEFT JOIN		users ON users.id = mail_detail.user_id
                                LEFT JOIN		user_info ON user_info.user_id = users.id
                                WHERE 			mail.id = '$chatID'
                                GROUP BY        mail_detail.id
                                ORDER BY 		mail.id DESC");

            $messages = parent::getResultArray();
        } else {
            parent::setQuery("  SELECT 		chat_details.id as id,
            IF(chat_details.user_id = 0, 'client', 'operator') AS type,
            chat_details.datetime,
            IF(chat_details.user_id = 0, chat.sender_name, user_info.name) AS name,
            chat_details.message,
            'Assets/images/no-image.png' AS avatar

            FROM 		chat_details
            JOIN		chat ON chat.id = chat_details.chat_id
            LEFT JOIN	users ON users.id = chat_details.user_id
            LEFT JOIN	user_info ON user_info.id = users.person_id
            WHERE 		chat_details.chat_id = '$chatID' AND chat_details.archived = 0
            ORDER BY 	chat_details.id ASC");

            $messages = parent::getResultArray();
        }

        foreach ($messages['result'] as $message) {
            array_push($allMessageData, array(
                "id" => $message['id'],
                "type" => $message['type'],
                "datetime" => $message['datetime'],
                "name" => $message['name'],
                "message" => $message['message'],
                "avatar" => $message['avatar']
            ));
        }

        parent::setQuery("UPDATE chat_details SET archived = 1 WHERE archived = 0 AND chat_id = '$chatID'");
        parent::execQuery();


        return $allMessageData;
    }

    public function getMessages()
    {
        $chatID = $_REQUEST['chat_id'];
        $source = $_REQUEST['source'];
        $detail_id = $_REQUEST['detail_id'];
        $allMessageData = array();

        if ($source == "mail") {

            parent::setQuery("  SELECT 		mail_detail.id as id,
                                            IF(ISNULL(mail_detail.user_id), 'client', 'operator') AS type,
                                            mail_detail.datetime,
                                            IF(ISNULL(mail_detail.user_id), mail.sender_name, user_info.name) AS name,
                                            CASE
                                                WHEN mail_attachment.id > 0 THEN
                                                    CONCAT(mail_detail.body,GROUP_CONCAT('<a href=\"','http://172.16.50.153/files/',mail_attachment.name,'\">',mail_attachment.name,'</a>'))
                                                ELSE
                                                    mail_detail.body
                                            END  as message,    
                                            'Assets/images/no-image.png' AS avatar

                                FROM 		mail
																LEFT JOIN		mail_detail ON mail.id = mail_detail.mail_id
                                LEFT JOIN       mail_attachment on mail_attachment.mail_detail_id = mail_detail.id
                                LEFT JOIN		users ON users.id = mail_detail.user_id
                                LEFT JOIN		user_info ON user_info.user_id = users.id
                                WHERE 		ISNULL(mail_detail.user_id) AND	mail_detail.mail_id = '$chatID' AND mail_detail.id > '$detail_id'
                                GROUP BY        mail_detail.id
                                ORDER BY 		mail.id DESC");

            $messages = parent::getResultArray();
        } else {
            parent::setQuery("  SELECT 		chat_details.id as id,
            IF(chat_details.user_id = 0, 'client', 'operator') AS type,
            chat_details.datetime,
            IF(chat_details.user_id = 0, chat.sender_name, user_info.name) AS name,
            chat_details.message,
            'Assets/images/no-image.png' AS avatar

            FROM 		chat_details
            JOIN		chat ON chat.id = chat_details.chat_id
            LEFT JOIN	users ON users.id = chat_details.user_id
            LEFT JOIN	user_info ON user_info.id = users.person_id
            WHERE 		chat_details.chat_id = '$chatID' AND chat_details.archived = 0
            ORDER BY 	chat_details.id ASC");

            $messages = parent::getResultArray();
        }


        foreach ($messages['result'] as $message) {
            array_push($allMessageData, array(
                "id" => $message['id'],
                "type" => $message['type'],
                "datetime" => $message['datetime'],
                "name" => $message['name'],
                "message" => $message['message'],
                "avatar" => $message['avatar']
            ));
        }


        parent::setQuery("UPDATE chat_details SET archived = 1 WHERE archived = 0 AND chat_id = '$chatID'");
        parent::execQuery();

        return $allMessageData;
    }


    public function sendMessage()
    {
        $chatID = $_REQUEST['chat_id'];
        $userID = $_SESSION['USERID'];
        $isOperator = (bool)$_REQUEST['isOperator'];
        $source = $_REQUEST['source'];

        $message = $_REQUEST["message"];
        $message = stripslashes($message);

        $fileLinks = $_REQUEST['fileLinks'];

        if (!$isOperator) {
            $userID = 0;
        }

        parent::setQuery("INSERT INTO chat_details SET  user_id = '$userID',
                                                        datetime = NOW(),
                                                        chat_id = '$chatID',
                                                        message = '$message',
                                                        archived = 1");
        parent::execQuery();



        $lastMessageID = parent::getLastId();

        parent::setQuery("  SELECT 		chat_details.id,
                                        IF(chat_details.user_id = 0, 'client', 'operator') AS type,
                                        chat_details.datetime,
                                        IF(chat_details.user_id = 0, chat.sender_name, user_info.name) AS name,
                                        chat_details.message,
                                        'Assets/images/no-image.png' AS avatar

                            FROM 		chat_details
                            JOIN		chat ON chat.id = chat_details.chat_id
                            LEFT JOIN	users ON users.id = chat_details.user_id
                            LEFT JOIN	user_info ON user_info.id = users.person_id
                            WHERE 		chat_details.id = '$lastMessageID'
                            ORDER BY 	chat_details.id ASC");

        $message_result = parent::getResultArray();
        $message_result = $message_result['result'][0];

        parent::setQuery("  UPDATE chat SET chat_status_id = 2,
                                            first_user_id = '$userID'
                            WHERE   chat_status_id = 1 AND id = '$chatID'");
        parent::execQuery();


        $sendResponse = null;

        switch ($source) {
            case 'mail':
                $addresses = [];
                $addresses['address']  = array();
                if (!empty($fileLinks)) {
                    $fileLinks = substr_replace($fileLinks, "", -1);
                    $fileLinks = explode(",", $fileLinks);
                } else {
                    $fileLinks = [];
                }

                if (!empty($_REQUEST['bcc'])) {
                    $addresses['bcc'] = explode(",", $_REQUEST['bcc']);
                }

                if (!empty($_REQUEST['cc'])) {
                    $addresses['cc'] = explode(",", $_REQUEST['cc']);
                }

                parent::setQuery("  SELECT      `mail`.`subject` AS subject,
                                                `mail`.sender_address as sender
                                    FROM        `mail` 
                                    WHERE       `mail`.id = '$chatID'");
                $response = parent::getResultArray()['result'][0];


                array_push($addresses['address'], $response['sender']);

                parent::setQuery("INSERT mail_detail 
                    SET datetime     =   NOW(),                  
                        mail_id =   '" . $chatID . "',
                        `mail_type_id`      = 2,
                        `mail_send_status_id` = 1,
                        `user_id`       = '" . $userID . "',
                        `body`         =   '$message'");
                parent::execQuery();

                $lastMessageID = parent::getLastId();

                parent::setQuery("  SELECT 			mail_detail.id as id,
                                                    IF(ISNULL(mail_detail.user_id), 'client', 'operator') AS type,
                                                    mail_detail.datetime,
                                                    IF(ISNULL(mail_detail.user_id), mail.sender_name, user_info.name) AS name,
                                                    CASE
                                                        WHEN mail_attachment.id > 0 THEN
                                                            CONCAT(mail_detail.body,GROUP_CONCAT('<a href=\"','http://172.16.50.153/files/',mail_attachment.name,'\">',mail_attachment.name,'</a>'))
                                                        ELSE
                                                            mail_detail.body
                                                    END  as message,
                                                    'Assets/images/no-image.png' AS avatar

                                    FROM 				mail
                                    LEFT JOIN		mail_detail ON mail.id = mail_detail.mail_id
                                    LEFT JOIN       mail_attachment on mail_attachment.mail_detail_id = mail_detail.id
                                    LEFT JOIN		users ON users.id = mail_detail.user_id
                                    LEFT JOIN		user_info ON user_info.user_id = users.id
                                    WHERE 			mail_detail.id = '$lastMessageID'");

                $message_result = parent::getResultArray();
                $message_result = $message_result['result'][0];

                $mail = new Mail();
                $mail->set($response['subject'], $message, $fileLinks, $addresses, 1);
                $sendResponse = $mail->send();



                break;
            case 'messenger':

                if ($fileLinks != '') {
                    $fileLinks = explode(",", $fileLinks);
                } else {
                    $fileLinks == array();
                }

                $sendResponse = (new Messenger())->send(2, $chatID, $message, $fileLinks);

                break;
        }

        $messageStatus = array(
            "status" => "OK",
            "user_id" => $_SESSION['USERID'],
            "response" => $sendResponse,
            "result" => array(
                "id" => $message_result['id'],
                "type" => $message_result['type'],
                "datetime" => $message_result['datetime'],
                "name" => $message_result['name'],
                "message" => $message_result['message'],
                "avatar" => $message_result['avatar']
            )
        );


        return $messageStatus;
    }
}
