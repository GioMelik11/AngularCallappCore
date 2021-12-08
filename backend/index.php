<?php

require_once "Configs/config.php";

if(isset($_REQUEST['route']) || isset($_REQUEST['act'])){

    $className  = $_REQUEST['route'];
    $MethodName = $_REQUEST['act'];
    
    isset($_REQUEST['ns']) ? ($ns = $_REQUEST['ns']) : ($ns = 'Controllers');
        
    include $ns.DIRECTORY_SEPARATOR.$className.DIRECTORY_SEPARATOR.$className.".class.php";
    
    $obj = new $className;
    
    $res = $obj->$MethodName();
    
    if(!is_null($res)){
        echo json_encode($res);
    }

}else{
    require_once("Views/index.php");
}





?>