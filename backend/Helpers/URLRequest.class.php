<?php

namespace Helpers;

use Exception;

class URLRequest {


  private $curl;

  private $url;
  private $data;
  
  private $result;
  private $response;

  private $error;

  // options
  private $returnTransfer;
  private $isPOST;
  private $header;


  /***********
   * CONSTRUCT REQUEST
   */
  public function __construct($url, $data = '', $header = 'Content-Type: application/json', $returnTransfer = true, $isPOST = true){

    $this->url = $url;

    $this->data = json_encode($data);

    $this->returnTransfer = $returnTransfer;

    $this->isPOST = $isPOST;

    $this->header = $header;

  }

  public function exec() {

    $this->initialize();

    $this->setParameters();
    
    $this->response = curl_exec($this->curl);

    if(curl_errno($this->curl)){
      $this->error = curl_error($this->curl);
      new Exception($this->error);
    }

    $this->close();

  }

  public function getResponse(){
    return $this->response;
  }

  public function getError(){

    return $this->error;

  }

  private function setParameters() {

    // IF REQUEST METHOD IS POST
    curl_setopt($this->curl, CURLOPT_POST, $this->isPOST);

    // REQUEST DATA
    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->data);

    // REQUEST CONTENT TYPE
    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array($this->header) );

    // IF REQUEST HAVE A RESPONSE
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, $this->returnTransfer);

  }

  private function initialize(){

    $this->curl = curl_init($this->url);

  }

  private function close() {
    
    curl_close($this->curl);

  }


}