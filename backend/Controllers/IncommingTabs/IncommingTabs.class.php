<?php

header("Content-Type:multipart/form-data");

use Controllers\IncommingTabs\comments;
use Controllers\IncommingTabs\detail;
use Controllers\IncommingTabs\file;
use Controllers\IncommingTabs\history;
use Controllers\IncommingTabs\logs;
use Controllers\IncommingTabs\mail;
use Controllers\IncommingTabs\news;
use Controllers\IncommingTabs\question;
use Controllers\IncommingTabs\records;
use Controllers\IncommingTabs\sms;
use Controllers\IncommingTabs\task;
use Routers\dbClass;

class IncommingTabs extends dbClass
{

    public $colCount;
    public $cols;

    public function getHistory()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        $startDate = rawurldecode($_REQUEST['stardDate']);
        $endDate = rawurldecode($_REQUEST['endDate']);
        $phone = $_REQUEST['phone'];
        $clientNum = $_REQUEST['clientNum'];

        return (new history())->GET($this->colCount, $this->cols, $startDate, $endDate, $phone, $clientNum);
    }

    public function getTask()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        return (new task())->GET($this->colCount, $this->cols);
    }

    public function getDetails()
    {
        $inc_id         = $_REQUEST['inc_id'];

        return (new detail())->GET($inc_id);
    }

    public function getQuestion()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        return (new question())->GET($this->colCount, $this->cols);
    }


    public function getSms()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        $inc_id         = $_REQUEST['inc_id'];


        return (new sms())->GET($this->colCount, $this->cols, $inc_id);
    }

    public function insertSms()
    {
        $inc_id         = $_REQUEST['inc_id'];
        $text           = $_REQUEST['text'];
        $phones         = $_REQUEST['phones'];

        return (new sms())->INSERT($text, $inc_id, $phones);
    }


    public function getMail()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        $inc_id         = $_REQUEST['inc_id'];
        return (new mail())->GET($this->colCount, $this->cols, $inc_id);
    }

    public function sendMail()
    {

        $inc_id = $_REQUEST['inc_id'];
        $email = $_REQUEST['email'];
        $cc = $_REQUEST['cc'];
        $bcc = $_REQUEST['bcc'];
        $subject = $_REQUEST['subject'];
        $text = $_REQUEST['text'];
        $attachments = $_REQUEST['attachment'];
        $send_record = $_REQUEST['send_record'];

        return (new mail())->INSERT($inc_id, $email, $cc, $bcc, $subject, $text, $attachments, $send_record);
    }



    public function getSignature()
    {

        parent::setQuery("SELECT id, name FROM mail_signature WHERE active = 1");

        $extResult = parent::getResultArray();

        return  $extResult['result'];
    }

    public function getShablon()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        parent::setQuery("SELECT id, 'text' as text,name FROM mail_signature WHERE active = 1");

        $callList = parent::getKendoList($this->colCount, $this->cols);

        return $callList;
    }

    public function getShablonMail()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        parent::setQuery("SELECT id, name,text FROM mail_template WHERE actived = 1");

        $callList = parent::getKendoList($this->colCount, $this->cols);

        return $callList;
    }

    public function getShablonSMS()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        parent::setQuery("SELECT id,name,text FROM sms_template WHERE actived = 1");

        $callList = parent::getKendoList($this->colCount, $this->cols);

        return $callList;
    }

    public function getCnobar()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        parent::setQuery("SELECT id, 'text' as text,name FROM mail_signature WHERE active = 1");

        $callList = parent::getKendoList($this->colCount, $this->cols);

        return $callList;
    }

    public function getFile()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        $inc_id         = $_REQUEST['id'];

        return (new file())->GET($this->colCount, $this->cols, $inc_id);
    }

    public function addFile()
    {
        $files = [];
        $inc_id = $_REQUEST['inc_id'];
        $route = $_REQUEST['uploadedfrom'];

        foreach ($_FILES as $file) {
            array_push($files, $file);
        }

        return (new file())->ADD($files, $inc_id,$route);
    }

    public function getRecordsInc()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        $phone     = $_REQUEST['phone'];

        return (new records())->GETInc($this->colCount, $this->cols, $phone);
    }

    public function getRecordsOut()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        $phone     = $_REQUEST['phone'];

        return (new records())->GETOut($this->colCount, $this->cols, $phone);
    }

    public function getComments()
    {
        $inc_id         = $_REQUEST['inc_id'];
        return (new comments())->GET($inc_id);
    }

    public function insertComments()
    {
        $inc_id         = $_REQUEST['inc_id'];
        $text         = $_REQUEST['text'];
        $parent_id         = $_REQUEST['parent_id'];
        return (new comments())->INSERT($text, $parent_id, $inc_id);
    }

    public function deleteComments()
    {
        $id         = $_REQUEST['id'];
        return (new comments())->DELETE($id);
    }

    public function editComments()
    {
        $id         = $_REQUEST['id'];
        $text         = $_REQUEST['text'];
        return (new comments())->UPDATE($id, $text);
    }

    public function getLogs()
    {
        $inc_id         = $_REQUEST['inc_id'];
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        return (new logs())->GET($this->colCount, $this->cols, $inc_id);
    }

    public function getNews()
    {
        $status_id      = $_REQUEST['status_id'];
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        return (new news())->GET($this->colCount, $this->cols, $status_id);
    }

    public function GetServiceCenters()
    {
        $name = $_REQUEST['name'];
        $parent_id = $_REQUEST['parent_id'];
        $id = $_REQUEST['id'];

        $filter = "";
        if (!empty($name)) {
            $filter = ' AND parent_id > 0 ';
            if ($parent_id > 0) {
                $filter .= " AND parent_id = $parent_id ";
            }
        }

        parent::setQuery("  SELECT    id,
                                    name
                            FROM  service_center
                            WHERE actived = 1 $filter ");

        $result = parent::getResultArray()['result'];

        return $result;
    }

    public function GetServiceCentersParentID()
    {
        $id = $_REQUEST['id'];

        parent::setQuery("  SELECT    parent_id as id
                            FROM  service_center
                            WHERE id = $id ");

        $result = parent::getResultArray()['result'];

        return $result;
    }

    public function GetBranchID()
    {
        $id = $_REQUEST['id'];

        parent::setQuery("  SELECT    branch_id as id
                            FROM  service_center
                            WHERE id = $id ");

        $result = parent::getResultArray()['result'];

        return $result;
    }
}
