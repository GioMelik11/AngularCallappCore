<?php

namespace Controllers\IncommingTabs;

use Routers\dbClass;

class task extends dbClass
{

    public function GET($colCount, $cols)
    {

        $inc_id = $_REQUEST['id'];

        parent::setQuery("  SELECT	`task`.id,
                            `task`.datetime,
                            `task`.start_date,
                            `task`.end_date,
                            CONCAT(`Parent_status`.name, ',' ,`Child_status`.name),
                            GROUP_CONCAT(IFNULL(`resp_name`.name,resp_username.username))
                            
                    FROM    task 
                    LEFT JOIN task_status AS Parent_status ON `Parent_status`.id = `task`.task_status_parent_id AND `Parent_status`.actived = 1
                    LEFT JOIN task_status AS Child_status ON `Child_status`.id = `task`.task_status_child_id AND `Child_status`.actived = 1
                    LEFT JOIN task_responsible_user ON task_responsible_user.task_id = task.id
                    LEFT JOIN users AS resp_username ON task_responsible_user.task_responsive_user_id = resp_username.id
                    LEFT JOIN user_info AS resp_name ON resp_username.person_id = resp_name.id
                    WHERE   `task`.actived = 1 AND `task`.task_source_row_id = '$inc_id'
                    GROUP BY `task`.id
                    ORDER BY `task`.id DESC");

        $callList = parent::getKendoList($colCount, $cols);

        return $callList;
    }
}
