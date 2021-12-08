<?php


use Routers\dbClass;

class User extends dbClass
{

  public function get()
  {

    $id = $_REQUEST['id'];


    $query = "SELECT    `users`.`id`, `user_info`.`name` 
                FROM      `users` 
                LEFT JOIN `user_info` ON `user_info`.user_id = `users`.id
                WHERE     `users`.id == '$id'";

    parent::setQuery($query);
    $response = parent::getResultArray();

    return $response['result'];
  }

  public function getAuthorizedUser()
  {

    $id = $_SESSION['USERID'];


    $query = "SELECT    `users`.`id`, `user_info`.`name`, `group`.`name` AS `group`,`user_info`.`work_status_id`
                FROM      `users` 
                LEFT JOIN `user_info` ON `user_info`.`user_id` = `users`.id
                LEFT JOIN `group` ON `group`.id = `users`.group_id
                WHERE     `users`.id = '$id'";

    parent::setQuery($query);
    $response = parent::getResultArray();

    return $response['result'][0];
  }

  public function setUserWorkStatus()
  {

    $id = $_SESSION['USERID'];
    $work_status_id = $_REQUEST['work_status_id'];

    $query = "SELECT    person_id
                FROM      `users` 
                WHERE     `users`.id = '$id'";

    parent::setQuery($query);

    $person_id = parent::getResultArray()['result'][0]['person_id'];

    $query = "UPDATE    user_info
                SET       work_status_id = '$work_status_id'
                WHERE     id = '$person_id'";

    parent::setQuery($query);
    parent::execQuery();

    $response = ["status" => "OK"];

    return $response;
  }

  public function getWorkStatus()
  {

    $id = $_REQUEST['selected_status_id'];

    $query = "SELECT    `id`, 
                          `name`,
                          `color` 
                FROM      `work_status` 
                WHERE     `actived` = 1 AND id != $id";

    parent::setQuery($query);
    $response = parent::getResultArray();

    return $response['result'];
  }

  public function getSelectedWorkStatus()
  {

    $id = $_REQUEST['id'];

    $query = "SELECT    `id`, 
                          `name`,
                          `color` 
                FROM      `work_status` 
                WHERE     `actived` = 1 AND id = $id";

    parent::setQuery($query);
    $response = parent::getResultArray();

    return $response['result'][0];
  }

  public function getSource()
  {

    $id = $_SESSION['USERID'];

    $query = "SELECT   source. `id`, 
                          source.`name`,
                          source.`key`,
                        IFNULL(`source_control`.actived,0) as actived 
              FROM      `source` 
              LEFT JOIN source_control ON `source_control`.source_id = `source`.id AND source_control.user_id = '$id'
              WHERE     source.`actived` = 1";

    parent::setQuery($query);
    $response = parent::getResultArray();

    return $response['result'];
  }

  public function setSourceControl()
  {

    $user_id = $_SESSION['USERID'];
    $actived = $_REQUEST['actived'];
    $source_id = $_REQUEST['source_id'];

    $query = "INSERT INTO source_control(`user_id`, `source_id`, `actived`)
                                  VALUES ($user_id, $source_id, $actived)
                                  ON DUPLICATE KEY UPDATE actived=$actived;";

    parent::setQuery($query);
    parent::execQuery();

    $response = ["status" => "OK"];

    return $response;
  }

  public function setQueueControl()
  {

    $actived = $_REQUEST['actived'];
    $ext_id = $_REQUEST['ext_id'];
    $queue_name = $_REQUEST['queue_name'];
    $dt = [];

    $query = "SELECT    `number`
      FROM      `asterisk_extension` 
      WHERE     id = '$ext_id'";

    parent::setQuery($query);
    $ext_name = parent::getResultArray()['result'][0]['number'];
    $ext_name = 'Local/' . $ext_name . '@from-queue/n';


    parent::connect();

    if ($actived == 1) {
      $dt = parent::LocalCallOff($ext_name, $queue_name);
    } else {
      $dt =  parent::LocalCall($ext_name, $queue_name);
    }


    $response = ["status" => "OK", $dt];

    return $response;
  }


  public function getOperators()
  {

    $query = "SELECT    `users`.`id`, `user_info`.`name` 
                FROM      `users` 
                LEFT JOIN `user_info` ON `user_info`.id = `users`.id
                WHERE     `users`.group_id != 1 AND NOT ISNULL(`user_info`.`name`)";

    parent::setQuery($query);
    $response = parent::getResultArray();

    return $response['result'];
  }
}
