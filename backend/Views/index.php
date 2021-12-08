<?php

use Routers\Router;
$Router = new Router();

if(!isset($_SESSION["USERID"]) || !isset($_SESSION["GROUPID"])) return $Router->getAuthPage();

require_once "layouts/header.php";

$res = $Router->getWelcomePage();

if(isset($_REQUEST['id'])){
	$page_id = $_REQUEST['id'];
}

	if (empty($page_id)) {
		if($res['page_id'] == ''){
			$page_id = 3;
		}else{
			$page_id = $res['page_id'];
		}
	}

$Router->reqPage(1, $page_id);



?>