<?php

namespace Services\Custom\searchByNumber; 

use Routers\dbClass;

class searchByNumber extends dbClass{

    public $client_number;

    public function GET() {


      $url = "http://payments.energo-pro.ge:8080/callCenter/cust/getCustomerBalance?custNumber="."'".$this->client_number."'";

      $file = file_get_contents($url);
      $fileData = json_decode($file);
    
      return $fileData;
    
    // $cl_addres =  $fileData->address;
    // $cl_debt = $fileData->balance;
    // $cl_ab_num = $fileData->custNumber;
    // $cl_ab = $fileData->custName;
    
    
    // mysql_query("INSERT INTO `service_request_log`
    //             (`cl_addres`, `cl_debt`, `cl_ab`, `cl_ab_num`, `user_id`, `date`, `send_num`)
    //             VALUES
    //             ('$cl_addres', '$cl_debt', '$cl_ab', '$cl_ab_num', '$user_id', NOW(), '$_REQUEST[ab_num]');");
                
    

    }

    public function SET($client_number) {

      $this->client_number = $client_number;

    }


}



?>