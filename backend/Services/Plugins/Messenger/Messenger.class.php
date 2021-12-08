<?php

namespace Services\Plugins\Messenger;

use CURLFile;
use Exception;
use Helpers\URLRequest;
use Routers\dbClass;


/**
 * CALLAPP MESSENGER SENDER
 */

class Messenger extends dbClass
{

  private $url;

  // FACEBOOK ACCESS TOKEN
  private $access_token;

  // SENDER ID
  private $sender_id;
  // SENDER ID FOR FACEBOOK
  private $recipient;

  // CHAT ID
  private $chat_id;
  // MESSAGE 
  private $message;

  /**
   * @var array $file
   */
  private $file;

  /**
   *  @var array $data
   */
  private $data;

  private $Request;

  private $debug = false;

  private $header;



  /**
   * SET FACEBOOK URL
   * @see https://developers.facebook.com/docs/messenger-platform/reference/send-api#request
   */
  private function setURL()
  {

    $this->url = "https://graph.facebook.com/v2.6/me/messages?access_token=$this->access_token";
  }

  /**
   * SET CHAT ID FROM CALLAPP
   * @param int $chat_id
   */
  private function setChatId($chat_id)
  {

    $this->chat_id = $chat_id;
  }

  /**
   * SET MESSAGE FROM CALLAPP
   * @param string $message
   */
  private function setMessage($message)
  {

    $this->message = strip_tags($message);
  }

  /**
   * SET SENDER ID FROM CALLAPP
   */
  private function setSenderId()
  {

    parent::setQuery("  SELECT  `sender_id` 
                        FROM    `chat` 
                        WHERE   `id`  = '$this->chat_id' 
                        LIMIT   1 ");

    $this->sender_id = parent::getResultArray()['result'][0]['sender_id'];
  }

  /**
   * @param array $file
   * 
   * @return [type]
   */
  private function setFileLinks($file)
  {

    $this->file = $file;
  }


  /**
   * @param int $account_id
   * 
   * @return [type]
   */
  private function getAccessToken($account_id)
  {

    parent::setQuery("SELECT  `token` 
                        FROM    `chat_account`
                        WHERE   `id` = '$account_id'
                        LIMIT 1
                      ");

    $this->access_token = parent::getResultArray()['result'][0]['token'];
  }


  /**
   * SEND MESSAGE
   * @param int $account_id
   * @param int $chat_id
   * @param string $message
   * @param array $file
   * @return array
   */
  public function send($account_id, $chat_id, $message = '', $file)
  {

    // GET AND SET ACCOUNT TOKEN
    $this->getAccessToken($account_id);

    // SET REQUEST URL
    $this->setURL();

    // SET CHAT ID
    $this->setChatId($chat_id);

    // SET MESSAGE TEXT
    $this->setMessage($message);

    // SET SENDER ID
    $this->setSenderId();

    // SET FILE
    $this->setFileLinks($file);

    // SET RECIPIENT
    $this->setRecipient();


    if (!empty($this->file)) {

      try {
        $this->buildFileArray();
      } catch (Exception $e) {
        new Exception($e->getMessage(), $e->getCode());
      }
    } else {

      try {
        $this->buildMessageArray();
      } catch (Exception $e) {
        new Exception($e->getMessage(), $e->getCode());
      }
    }


    // SEND IMAGE FROM LINK
    // $data = array('recipient' => 
    // array('id' => $this->sender_id), 
    // 'message' => 
    // array(
    //   'attachment' => array("type" => "image", 
    //   "payload" => array("is_reusable" => true, "url" => "https://scontent.ftbs10-1.fna.fbcdn.net/v/t1.6435-9/232444452_1200766273669631_5032331822531603696_n.jpg?_nc_cat=111&ccb=1-5&_nc_sid=e3f864&_nc_ohc=xVyAJcRGaZIAX-BNfQf&tn=rOsgo7XyDBU4RIKv&_nc_ht=scontent.ftbs10-1.fna&oh=c2fdd53ca1054e929287c456effe8647&oe=616C8656"))));

  }

  private function sendRequest()
  {

    try {

      $this->Request = new URLRequest($this->url, $this->data);
      $this->Request->exec();

      if($this->debug){
        print $this->Request->getResponse();
      }

    } catch (Exception $e) {
      new Exception($e->getMessage());
    }
  }


  /**
   * 
   */
  private function setRecipient()
  {

    $this->recipient = array('recipient' => json_encode(array('id' => $this->sender_id)) );
  }


  /**
   * BUILD MESSAGE ARRAY FOR MESSENGER API
   * @see 
   */
  private function buildMessageArray()
  {

    $this->data = array_merge($this->recipient, array('message' => array('text' => $this->message)));

    $this->sendRequest();

  }


  private function buildFileArray()
  {

    foreach ($this->file as $link) {

      $this->uploadFile($link);
      

      $this->sendRequest();
      
      

    }

  }

  private function uploadFile($link)
  {

    $path = realpath($link);
    $type = mime_content_type($link);
    
      $this->data = array_merge(
        $this->recipient,
        array(
          'message' => json_encode(array('attachment'  => array('type' => 'file', 'payload' => array('is_reusable' => true)))), 
          'filedata'    =>  fopen($path, 'r')
        )
      );

      $this->header = array('Content-Type: application/json');


  }
}
