<?php

namespace IO\Routes;

use IO\Request;

class Queue extends Request {

    public $req;
    public $class;
    public $request;
    public $response;
    public $reqResponse;
    public $flashpanel = array();

    public $chatTaken;
    public $chatWaiter;
    
    private function buildRequestParam($route, $act, $source = '') {

        $this->req          = array('route' => $route, 'act' => $act, 'source' => $source);
        $this->class        = new Request(json_encode($this->req));
        $this->request      = $this->class->sendRequest();
        $this->reqResponse  = $this->class->getResponse()['result'];
        
        return $this->reqResponse;
        
    }

    public function __construct()
    {
        $this->flashpanel['counts']         = $this->buildRequestParam('Queue', 'getFlashPanelQueueCountArray');

        $this->flashpanel['phoneTaken']     = $this->buildRequestParam('Queue', 'flashPanel', 'phone');
        $this->flashpanel['phoneWaiter']    = $this->buildRequestParam('Queue', 'flashPanelQueue', 'phone');

        $this->flashpanel['mailTaken']     = $this->buildRequestParam('Queue', 'flashPanel', 'mail');
        $this->flashpanel['mailWaiter']    = $this->buildRequestParam('Queue', 'flashPanelQueue', 'mail');

        $this->flashpanel['chatTaken']     = $this->buildRequestParam('Queue', 'flashPanel', 'chat');
        $this->flashpanel['chatWaiter']    = $this->buildRequestParam('Queue', 'flashPanelQueue', 'chat');

        $this->flashpanel['messengerTaken']     = $this->buildRequestParam('Queue', 'flashPanel', 'messenger');
        $this->flashpanel['messengerWaiter']    = $this->buildRequestParam('Queue', 'flashPanelQueue', 'messenger');

        $this->flashpanel['whatsappTaken']     = $this->buildRequestParam('Queue', 'flashPanel', 'whatsapp');
        $this->flashpanel['whatsappWaiter']    = $this->buildRequestParam('Queue', 'flashPanelQueue', 'whatsapp');

        $this->flashpanel['viberTaken']     = $this->buildRequestParam('Queue', 'flashPanel', 'viber');
        $this->flashpanel['viberWaiter']    = $this->buildRequestParam('Queue', 'flashPanelQueue', 'viber');


    }

    public function GETTER() {

        return $this->flashpanel;
        
    }


}