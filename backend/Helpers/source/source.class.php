<?php


use Routers\dbClass;

class Source extends dbClass{

    public function get(){

        $query = "SELECT `id`, `name`, `key`, `color` FROM source WHERE actived = 1 AND id != 7";

        parent::setQuery($query);
        $response = parent::getResultArray();

        return $response['result'];

    }

    public function getSubMenu(){
        $response = [];
        $response['ext_id'] = $_SESSION['EXTID'];

        if($response['ext_id'] > 0){
            $query = "SELECT $response[ext_id] as id,
                        `queue`.queue as name,
                        'phone' as `key`, 
                        if(queue.paused = 1,0,1) as `actived`,
                        '1' as `source_id`
                FROM asterisk_extension 
                JOIN `asterisk`.queueMembers AS queue ON queue.name = asterisk_extension.number
                WHERE asterisk_extension.id = $_SESSION[EXTID]";

            parent::setQuery($query);
            $response['1'] = parent::getResultArray()['result'];

            $query = "SELECT `id`, 
                        `name` as `name`, 
                        'chat' as `key`, 
                        '1' as `actived`,
                        '2' as `source_id` 
                FROM chat_account WHERE actived = 1 AND source_id = 2";

            parent::setQuery($query);
            $response['2'] = parent::getResultArray()['result'];

            $query = "SELECT `id`, 
                    `name` as `name`, 
                    'messenger' as `key`, 
                    '1' as `actived`,
                    '3' as `source_id` 
            FROM chat_account WHERE actived = 1 AND source_id = 3";

            parent::setQuery($query);
            $response['3'] = parent::getResultArray()['result'];

            $query = "SELECT `id`, 
                    `name` as `name`, 
                    'mail' as `key`, 
                    '1' as `actived`,
                    '4' as `source_id` 
            FROM chat_account WHERE actived = 1 AND source_id = 4";

            parent::setQuery($query);
            $response['4'] = parent::getResultArray()['result'];
        }
        


        return $response;

    }

}