<?php

namespace IO\Routes;

use IO\Request;

class incommingPanel extends Request {

    public $req;
    public $class;
    public $request;
    public $response;
    public $reqResponse;
    public $incommingPanel = array();
    
    private function buildRequestParam($route, $act, $source) {

        $this->req          = array('route' => $route, 'act' => $act, 'source' => $source);
        $this->class        = new Request(json_encode($this->req));
        $this->request      = $this->class->sendRequest();
        $this->reqResponse  = $this->class->getResponse()['result'];

        return $this->reqResponse;
        
    }

    public function __construct()
    {

        $this->incommingPanel['phone']  = $this->buildRequestParam('Queue', 'getQueue', 'phone');
        $this->incommingPanel['chat']  = $this->buildRequestParam('Queue', 'getQueue', 'chat');
        $this->incommingPanel['messenger']  = $this->buildRequestParam('Queue', 'getQueue', 'messenger');
        $this->incommingPanel['mail']  = $this->buildRequestParam('Queue', 'getQueue', 'mail');

    }

    public function GETTER() {

        return $this->incommingPanel;
        
    }


}