<?php


/**
 * CALLAPP
 * MAIL PLUGINS
 */

namespace Services\Plugins\Mail;

use Exception;
use Libraries\PHPMailer\PHPMailer;
use Routers\dbClass;

class Mail extends dbClass
{

  private $Mailer;

  // DEBUG OPTIONS
  private $SMTPDebug    = false;
  private $Debugoutput  = 'html';

  // AUTHORIZATION OPTIONS
  private $SMTPAuth     = true;
  private $SMTPSecure   = true;
  private $SMTPAutoTLS  = true;

  // CREDENTIALS AND LOGIN
  private $accountID;
  private $accountDetail = array();
  private $Username;
  private $Password;
  private $Host;

  /**
   * IMAP SEND SSL:  465
   * 
   * IMAP RECEIVE SSL: 993  
   * IMAP RECEIVE: 143
   * 
   * POP3 RECEIVE SSL: 995
   * POP3 RECEIVE: 110;
   */
  private $Port         = 465;

  /**
   * DEFAULT CHARSET: UTF-8;
   * UTF-8, ASCII
   */
  private $CharSet      = 'UTF-8';
  private $IsHTML       = true;

  // MESSAGE
  public $Subject;
  public $Message;
  public $Attachments   = array();
  public $Addresses     = array();


  /**
   * @param string  $subject
   * @param string  $message
   * @param array   $attachments
   * @param array   $addresses
   * @param int     $accountID;
   */
  public function set($subject, $message, $attachments, $addresses, $accountID)
  {

    $this->Subject      = $subject;
    $this->Message      = $message;
    $this->Attachments  = $attachments;
    $this->Addresses    = $addresses;
    $this->accountID    = $accountID;

    $this->getAccountDetail();
  }


  private function setOptions()
  {

    $this->Mailer->SMTPDebug    =   $this->SMTPDebug;
    $this->Mailer->SMTPAuth     =   $this->SMTPAuth;

    $this->Mailer->SMTPSecure   =   $this->SMTPSecure;
    $this->Mailer->SMTPAutoTLS  =   $this->SMTPAutoTLS;
    $this->Mailer->Debugoutput  =   $this->Debugoutput;

    $this->Mailer->AuthType = 'LOGIN';

    $this->Mailer->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

    $this->Mailer->CharSet = $this->CharSet;
    $this->Mailer->IsHTML($this->IsHTML);
    $this->Mailer->isSMTP();

    $this->Mailer->Username   =   $this->Username;
    $this->Mailer->Password   =   $this->Password;
    $this->Mailer->Host       =   $this->Host;
    $this->Mailer->Port       =   $this->Port;
  }


  /**
   * @param int $accountID
   */
  private function getAccountDetail()
  {

    parent::setQuery("SELECT  * 
                        FROM    mail_account 
                        WHERE   id = '$this->accountID'");

    $this->accountDetail = parent::getResultArray()['result'][0];

    $this->setCredentials();
  }

  private function setCredentials()
  {

    $this->Username   =   $this->accountDetail['mail'];
    $this->Password   =   $this->accountDetail['password'];
    $this->Host       =   $this->accountDetail['domain'];
  }

  public function setPort($type)
  {

    if (empty($this->accountDetail)) {
      throw new Exception("Account Details is empty");
    }

    switch ($type) {
      case "send":

        if (empty($this->accountDetail['smtp_port'])) {
          throw new Exception("Port is empty");
        }

        // SET PORT
        $this->Port = $this->accountDetail['smtp_port'];

        break;
      default:
        throw new Exception("Type is not Found");
    }
  }


  public function send()
  {

    $this->setPort("send");

    $this->Mailer = new PHPMailer();
    $this->Mailer->isSMTP();
    $this->setOptions();

    if (!empty($this->Addresses['address'])) {
      foreach ($this->Addresses['address'] as $address) {
        $this->Mailer->addAddress($address);
      }
    }

    if (!empty($this->Addresses['bcc'])) {
      foreach ($this->Addresses['bcc'] as $bcc) {
        $this->Mailer->addBCC($bcc);
      }
    }

    if (!empty($this->Addresses['cc'])) {
      foreach ($this->Addresses['cc'] as $cc) {
        $this->Mailer->addCC($cc);
      }
    }

    if (!empty($this->Attachments)) {
      foreach ($this->Attachments as $file) {
        $this->Mailer->AddAttachment($file);
      }
    }

    $this->Mailer->setFrom($this->Username);

    $this->Mailer->Subject    = $this->Subject;
    $this->Mailer->msgHTML($this->Message);

    $status = $this->Mailer->send();

    return array("status" => $status, "ErrorMessage" => $this->Mailer->ErrorInfo);
  }
}
