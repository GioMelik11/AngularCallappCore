<?php

namespace Controllers\IncommingTabs;

use Routers\dbClass;

class news extends dbClass{

    public function GET($colCount,$cols,$status_id){

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

        $callList = parent::getKendoList($colCount, $cols);
        
        return $callList;
    }
 
}