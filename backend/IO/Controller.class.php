<?php

namespace IO;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use IO\Request;

use Ratchet\WebSocket\WsServer;
use React\EventLoop\TimerInterface;

use IO\Routes\Queue;
use IO\Routes\incommingPanel;

class Controller extends Request implements MessageComponentInterface {
    
    protected $clients;
    protected $response;
    protected $request;
    protected $msg;
    protected $class;
    protected $method;
    protected $loop;

    public $FPStatus = false;
    public $liveData = array();


    public $receivers = [];

    public $dataCollection = [];

    public function __construct($loop) {
        $this->clients = new \SplObjectStorage;
        $this->loop = $loop;
        $that = $this; 
        $this->loop->addPeriodicTimer(1, function() use ($that) {

            $queue = new Queue();
            $this->liveData['flashPanel'] = $queue->GETTER();

            $incommingPanel = new incommingPanel();
            $this->liveData['incommingPanel'] = $incommingPanel->GETTER();

        });
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }


    public function onMessage(ConnectionInterface $from, $msg) {
        
        $this->msg = json_decode($msg);

        $removeloop = (isset($this->msg->removeLoop) ? $this->msg->removeLoop : '');

        if(isset($this->receivers[$from->resourceId][$removeloop])){
            $this->loop->cancelTimer($this->receivers[$from->resourceId][$removeloop]);
            unset($this->receivers[$from->resourceId][$removeloop]);
        }

        if(isset($this->msg->wsRoute)){
            $this->receivers[$from->resourceId] = array();
            if(!isset($this->receivers[$from->resourceId][$this->msg->wsRoute])){
                $this->makeLoop($from, $this->msg, 1);
            }
        }else{
            if($this->msg->act && !$this->msg->loop){
                $this->class    = new Request($msg);
                $this->request  = $this->class->sendRequest();
                $this->response = $this->class->getResponse();

                
                // if($this->response['result']->collection == 'calls'){
                    
                //     if(!in_array($this->response['result']->userID, $this->dataCollection, true)){
                //         array_push($this->dataCollection, $this->response['result']->userID);
                //     }

                //     $from->send(json_encode($this->dataCollection));
                // }else{
                    $from->send(json_encode($this->response));
                // }

            }elseif($this->msg->loop){
                $from->send(json_encode(array("message" => "CREATE LOOP")));
            }
        }
    }


    public function makeLoop($from, $msg,  $period = 1){
        $that = $this; 

        $data = $this->liveData[$this->msg->live][$this->msg->wsRoute];

        $from->send(json_encode(array("result" => $data, "id" => $msg->id, "wsRoute" => $msg->wsRoute)));

        $this->loop->addPeriodicTimer($period, function(TimerInterface $timer) use ($that,  $from, $msg) {
           
            $from->send(json_encode(array("result" => $this->liveData[$msg->live][$msg->wsRoute], "id" => $msg->id, "wsRoute" => $msg->wsRoute)));
            $this->receivers[$from->resourceId][$msg->wsRoute] = $timer;
            // var_dump(array_keys($this->receivers[$from->resourceId]));
        });
    }


    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        foreach($this->receivers[$conn->resourceId] as $loop){
            $this->loop->cancelTimer($loop);
        }
        unset($this->receivers[$conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        parent::sLog($e->getMessage(), "EPRO");
        $conn->close();
    }

}