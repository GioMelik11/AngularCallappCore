<?php

namespace IO;

use Routers\dbClass;

class Request extends dbClass {
    
    protected $clients;
    protected $response;
    protected $msg;

    protected $id;

    public function __construct($msg = '') {
        $this->msg = json_decode($msg);
    }

    public function sendRequest() {
        
        $postdata = http_build_query($this->msg);
        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        $context  = stream_context_create($opts);
        
        $result = file_get_contents(APP_PATH.'/index.php', false, $context);

        if($result == '') $result = array();
        $this->response = array('result' => json_decode($result));
        
    }

    public function getResponse() {

        return $this->response;

    }
  
    
}