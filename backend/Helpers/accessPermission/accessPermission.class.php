
<?php


use Routers\dbClass;


class accessPermission extends dbClass{


    private $accessTo = '';

    function initial(){

        $this->setAccessTo();

        parent::setQuery("  SELECT * FROM `group_permission`
                            WHERE  `page_id` = '$_SESSION[PAGEID]' AND `group_id` = '$_SESSION[GROUPID]'
                            ");


        $count = parent::getNumRow();
        $result = parent::getResultArray();

        if($count > 0){
            if($result['result'][0][$this->accessTo]){
                return array("status" => true, "message" => "OK");
            }else{
                return array("status" => false, "message" => "წვდომა შეზღუდულია");
            };
        }
        
    }

    function setAccessTo(){
        $this->accessTo = $_REQUEST['accessTo'];
    }


}