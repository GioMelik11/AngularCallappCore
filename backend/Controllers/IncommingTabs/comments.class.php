<?php

namespace Controllers\IncommingTabs;

use Routers\dbClass;

class comments extends dbClass{

    private $response = Array();
    
    function GET($inc_id){
        
        
        
        parent::setQuery(" SELECT	comment.id as id,
                                    user_info.`name` as name,
                                    comment.datetime as date,
                                    `comment`.text as text,
                                    comment.parent_id
                            FROM 		`comment`
                            LEFT JOIN   users ON users.id = comment.user_id
                            LEFT JOIN   user_info  ON user_info.id = users.person_id
                            WHERE comment.actived = 1 AND comment.parent_id = 0 AND row_id = '$inc_id' AND processing_page_id = '1' AND `comment`.actived = 1
                            ORDER BY id ASC");
        
        $result = parent::getResultArray();
        
        foreach ($result['result'] AS $row){
            $sub_array = array();
            $data = array("id" => $row['id'], "name" => $row['name'], "date" => $row['date'], "text" => $row['text'], "parent_id"=> $row['parent_id'], "answer"=> "");
            
            
            $sub_array = $this->GetSubComment(1, $row['id']);
            if (count($sub_array)>0) {
                $data['answer'] = $sub_array;
            }
            
            array_push($this->response, $data);
        }
        
        return $this->response;
        
    }
    
    function GetSubComment($user_id, $page_id){
        
        $sub_arr = array();
        parent::setQuery("  SELECT	comment.id as id,
                                    user_info.`name` as name,
                                    comment.datetime as date,
                                    `comment`.text as text,
                                    comment.parent_id
                            FROM 		`comment`
                            LEFT JOIN   users ON users.id = comment.user_id
                            LEFT JOIN   user_info  ON user_info.id = users.person_id
                            WHERE `comment`.actived = 1 AND `comment`.parent_id = '$page_id' AND `comment`.actived = 1
                            ORDER BY id ASC");
        
        $result = parent::getResultArray();
        
        if (parent::getNumRow()) {
            foreach ($result['result'] AS $row){
                $sub_array = array();
                $data = array("id" => $row['id'], "name" => $row['name'], "date" => $row['date'], "text" => $row['text'], "parent_id"=> $row['parent_id'], "answer"=> "");
                $sub_array = $this->GetSubComment(1, $row['id']);
                if (count($sub_array)>0) {
                    $data['answer'] = $sub_array;
                }
                array_push($sub_arr, $data);
            }
        }
        return $sub_arr;
    }

    function INSERT($text, $parent_id ,$inc_id){

        $user_id = $_SESSION['USERID'];

        parent::setQuery("INSERT INTO comment(`user_id`,`datetime`,`processing_page_id`,`row_id`,`parent_id`,`text`) 
                                    VALUES ('$user_id',NOW(),1,'$inc_id','$parent_id','$text'); ");
        parent::execQuery();

        parent::setQuery("  SELECT `comment`.id,
                                    `user_info`.`name`,
                                    `comment`.text,
                                    `comment`.datetime,
                                    `comment`.parent_id
                            FROM `comment` 
                            JOIN users ON users.id = `comment`.user_id
                            JOIN user_info ON `users`.`person_id` = `user_info`.id
                            WHERE users.`id` = '$user_id' AND `comment`.actived = 1
                            ORDER BY id DESC LIMIT 1");
        $res = parent::getResultArray();

        $item = $res['result'][0];

        $response = array("status" => "OK", "result" => array("id" => $item['id'],"name" => $item['name'],"text" => $item['text'],"date" => $item['datetime'],"parent_id" => $item['parent_id']));
         
        return $response;
    }

    function DELETE($id){
        parent::setQuery("  UPDATE comment 
                            SET actived = 0
                            WHERE id = '$id' OR parent_id = '$id'");
        parent::execQuery();
         
        return array("status" => "OK");
    }

    function UPDATE($id,$text){
        parent::setQuery("  UPDATE comment 
                            SET text = '$text'
                            WHERE id = '$id'");
        parent::execQuery();

        return array("status" => "OK");
    }






 
}