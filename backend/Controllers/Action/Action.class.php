<?php

use Controllers\IncommingTabs\news;
use Routers\dbClass;

class Action extends dbClass
{

    public function getList()
    {

        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];

        $status_id = urldecode($_REQUEST['status_id']);
        $filter = "";

        if(empty($status_id) || strpos($status_id,"1") !== false){
            $filter = " WHERE news.actived = 1 ";
        }else{
            $filter = " WHERE news.actived = 0 ";
        }

        parent::setQuery("   SELECT	    news.id,
                                        news.start_date,
                                        news.end_date,
                                        news.name,
                                        news.description,
                                        user_info.name
                                FROM 		news
                                LEFT JOIN   users ON users.id = news.user_id
                                LEFT JOIN   `user_info` ON `user_info`.user_id = `users`.id
                                $filter
                                ORDER BY    id DESC ");

        $callList = parent::getKendoList($this->colCount, $this->cols);

        return $callList;
    }

    public function ADD()
    {
        $name = $_REQUEST['name'];
        $start_date = $_REQUEST['start_date'];
        $end_date = $_REQUEST['end_date'];
        $description = $_REQUEST['description'];
        $news_type = $_REQUEST['news_type'];
        $region = $_REQUEST['region'];
        $user_id = $_SESSION['USERID'];
        $id = $_REQUEST['id'];

        parent::setQuery("INSERT INTO news(`id`,`user_id`,`start_date`,`end_date`,`name`,`description`,`news_type`,`region`)
                                VALUES ('$id','$user_id','$start_date','$end_date','$name','$description','$news_type','$region')");
        parent::execQuery();

        return array("status" => "OK","news_id" => parent::getLastId());
    }

    public function getFiles(){
        $colCount = $_REQUEST['count'];
        $cols     = $_REQUEST['cols'];
        $route = $_REQUEST['uploaded_from'];
        $row_id = $_REQUEST['row_id'];

        if($route == "voice"){
            parent::setQuery("  SELECT	`id`,
                                `original_name`,
                                positionVC as rig,
                                concat('Uploads/','asterisk-records/',hash,'.',type),
                                '' as download
                        FROM    upload_files
                        WHERE 	actived = 1 AND row_id = '$row_id' AND route = '$route'
                        ORDER BY id DESC
                        LIMIT 200");
        }else{
            parent::setQuery("  SELECT	`id`,
                                `datetime`,
                                `original_name`,
                                concat('Uploads/',DATE(datetime),'/',hash,'.',type),
                                '' as download
                        FROM    upload_files
                        WHERE 	actived = 1 AND row_id = '$row_id' AND route = '$route'
                        ORDER BY id DESC
                        LIMIT 200");
        }
       

        $callList = parent::getKendoList($colCount, $cols);

        return $callList;
    }

    public function updateVoices(){

        $value = $_REQUEST["position"];
        $id = $_REQUEST['id'];

        parent::setQuery("UPDATE upload_files
                             SET positionVC = '$value'
                             WHERE id = '$id'");
        parent::execQuery();

        return array("status" => "OK");

    }

    public function removeFile(){
        $id = $_REQUEST['id'];

        parent::setQuery("UPDATE upload_files
                             SET actived = '0'
                             WHERE id = '$id'");
        parent::execQuery();
        
        return array("status" => "OK");

    }

    public function GETID()
    {
        parent::setQuery("INSERT INTO news(`user_id`)
                                       VALUES ('0')");
        parent::execQuery();

        $id = parent::getLastId();

        parent::setQuery("DELETE FROM news 
        WHERE id = '$id'");
        parent::execQuery();

        return array("id" => $id);
    }

    public function DELETE()
    {
        $id = $_REQUEST['id'];

        parent::setQuery("UPDATE news 
                          SET  actived = '0'
                          WHERE id = '$id' ");

        parent::execQuery();

        return array("status" => "OK");
    }

    function EDIT(){
        $id = $_REQUEST['id'];

        
        parent::setQuery("  SELECT *
                            FROM   news
                            WHERE  id = '$id'");
        $result = parent::getResultArray()['result'][0];

        return $result;
    }

    public function UPDATE()
    {
        
        $name = $_REQUEST['name'];
        $start_date = $_REQUEST['start_date'];
        $end_date = $_REQUEST['end_date'];
        $description = $_REQUEST['description'];
        $news_type = $_REQUEST['news_type'];
        $region = $_REQUEST['region'];
        $user_id = $_SESSION['USERID'];
        $id = $_REQUEST['id'];

        parent::setQuery("UPDATE news 
                          SET   `start_date`   = '$start_date',
                                `end_date`     = '$end_date',
                                `user_id`      = '$user_id',
                                `description`  = '$description',
                                `news_type`    = '$news_type',
                                `name`         = '$name',
                                `region`       = '$region'
                         WHERE id = '$id' ");

        parent::execQuery();

        return array("status" => "OK");
    }

    public function getTabs()
    {
        parent::setQuery("SELECT    id,
                                    name
                        FROM      `news_selector` 
                        WHERE `news_selector`.actived = 1");

        $result = parent::getResultArray()['result'];

        return $result;
    }
}
