<?php

use Routers\dbClass;

class test extends dbClass{
    
    private $response = Array();
    
    function test(){
        
        global $db;
        $db = new dbClass();
        
        $db->setQuery("SELECT  pages.id,
                               menu_detail.`name`,
                    		   pages.`name` AS `page`,
                    		   menu_detail.icon
                       FROM   `users`
                       JOIN   `group` ON users.group_id = `group`.id
                       JOIN    group_permission ON group.id = group_permission.group_id
                       JOIN    pages ON pages.id = group_permission.page_id
                       JOIN    page_group ON pages.page_group_id = page_group.id
                       JOIN    menu_detail ON menu_detail.page_id = pages.id
                       WHERE   menu_detail.actived = 1 AND menu_detail.parent = 0 AND users.actived = 1 AND users.id = 1 AND menu_detail.menu_id = 1");
        
        $result = $db->getResultArray();
        
        foreach ($result['result'] AS $row){
            $sub_array = array();
            $data = array("name" => $row['name'], "page" => $row['page'], "icon" => $row['icon'], "active" => true, "sub"=>"", "param" => array("id"=>$row['id']));
            
            $sub_array = $this->GetSubMenu(1, $row['id']);
            if (count($sub_array)>0) {
                $data['sub'] = $sub_array;
            }
            array_push($this->response, $data);
        }
        
        return $this->response;
        
    }
    
    function GetSubMenu($user_id, $page_id){
        global $db;
        $db = new dbClass();
        $sub_arr = array();
        $db->setQuery("SELECT  pages.id,
                               menu_detail.`name`,
                    		   pages.`name` AS `page`
                       FROM   `users`
                       JOIN   `group` ON users.group_id = `group`.id
                       JOIN    group_permission ON group.id = group_permission.group_id
                       JOIN    pages ON pages.id = group_permission.page_id
                       JOIN    page_group ON pages.page_group_id = page_group.id
                       JOIN    menu_detail ON menu_detail.page_id = pages.id
                       WHERE   menu_detail.actived = 1 AND users.id = 1 AND menu_detail.parent = $page_id");

        $result = $db->getResultArray();
        
        if ($db->getNumRow()) {
            foreach ($result['result'] AS $row){
                $sub_array = array();
                $data = array("name" => $row['name'], "page" => $row['page'], "id" => $row['id'], "sub" => "");
                $sub_array = $this->GetSubMenu(1, $row['id']);
                if (count($sub_array)>0) {
                    $data['sub'] = $sub_array;
                }
                array_push($sub_arr, $data);
            }
        }
        return $sub_arr;
    }
    
}




?>