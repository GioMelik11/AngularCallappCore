<?php

namespace Routers;
use Routers\dbClass;
use Exception;


class Router extends dbClass{


	/**
	 * get VIEW PAGE from DB
	 *
	 * @param [Number] $user_id
	 * @param [Number] $page_id
	 * @return void
	 */
	public function reqPage($user_id, $page_id) {
        
		parent::setQuery("	SELECT page_group.name AS dest, pages.name
							FROM   users 
                            JOIN   group_permission ON users.group_id = group_permission.group_id
							JOIN   pages ON group_permission.page_id = pages.id
							JOIN   page_group ON pages.page_group_id = page_group.id 
							WHERE  users.id = $user_id AND pages.id = $page_id");
		
		$row = parent::getResultArray();
		
		if($row['count'] > 0){
			$_SESSION['PAGEID'] = $page_id;
			$page_name = $row['result'][0]['dest'];
			$name = $row['result'][0]['name'];
			if(file_exists('Views/pages/'.$page_name.DIRECTORY_SEPARATOR."index.php")){

				print "<section content>";

				$this->runCss($name);

				require_once 'Views/pages/'.$page_name.DIRECTORY_SEPARATOR."index.php";
				
				$this->runJs($name);

				print "</section>";

			}else{
				require_once 'Views/pages/404.html';
			}
			
		}else{

			require_once 'Views/pages/404.html';
			
		}
	}

/**
 * Run Js for View Page
 *
 * @param [String] $name
 * @return void
 */
	static function runJs($name){
		if(file_exists('Assets/js/pages/'.$name.DIRECTORY_SEPARATOR.$name.'.class.js')){

			print '<script type="module">
					import '.$name.' from "./Assets/js/pages/'.$name.'/'.$name.'.class.js"
					new '.$name.'()
			</script>';

		}
	}

	static function runCss($name){
		if(file_exists('Assets/css/page/'.$name.DIRECTORY_SEPARATOR.$name.'.css')){

			print '<link rel="stylesheet" href="Assets/css/page/'.$name.DIRECTORY_SEPARATOR.$name.'.css">';

		}
	}


	public function getWelcomePage() {

		parent::setQuery("	SELECT 	`page_id`
							FROM 	`group_wellcome_page`
							WHERE 	`group_id` = 1");

		$result = parent::getResultArray();

		return $result['result'][0];
	}

    public function getAuthPage(){
		return include("Views/pages/authorization/index.php");
    }


}