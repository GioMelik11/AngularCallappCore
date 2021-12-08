<?php


use Routers\dbClass;


class Authorization extends dbClass{
    
    private  $user_id;
    private  $group_id;
    private  $username;
    private  $password;
    private  $extension_id;
    private  $session_id;
    private  $date;
    private  $ip;
    
    
    /**
     * constructor
     */
    public function login() {

        $this->set_username($_REQUEST['username']);
        $this->set_password($_REQUEST['password']);
        $this->set_ext($_REQUEST['ext']);
        return $this->checklogin();
        
    }
    
    /**
     * @param string $username სისტემის მომხმარებელი
     */
    public function set_username($username) {
        $this->username = $username;
    }
    
    /**
     * @param string $password მომხმარებლის პაროლი
     */
    public function set_password($password) {
        if($password != ''){
            $this->password = md5($password);
        }else{
            $this->password = '';
        }
    }
    
    public function set_ext($ext) {
        $this->extension_id = $ext;
    }

    /**
     * სისტემის მომხარებლის შემოწმება
     * @return boolean
     */
    public function checklogin() {
        if ($this->username != '' && $this->password != '' ) {
            
            parent::setQuery("  SELECT       `users`.`id`, `users`.`group_id`
                                FROM	     `users`
                                WHERE        `users`.`password` = '$this->password' AND `users`.`username` = '$this->username' AND `users`.`actived` = 1");
            
            $num_row= parent::getNumRow();

            if ($num_row == 1) {
                
                $uid = parent::getResultArray();
                
                $this->user_id      = $uid['result'][0]['id'];
                $this->group_id     = $uid['result'][0]['group_id'];
                $this->ip();
                $this->savelogin();

                return array("message" => "ავტორიზაცია წარმატებით დასრულდა", "status" => 1, "userId" => $this->user_id);
                
            }else{
                    return array("message" => "მომხმარებელი ვერ მოიძებნა", "status" => 2);
            }
        }else{
            return array("message" => "მომხმარებლის სახელი ან პაროლი ცარიელია", "status" => 3);
        }
        
    }
    
    /**
     * სესიის მონაცემების შენახვა
     */
    public function savelogin() {
        
        $_SESSION['USERID']     = $this->user_id;
        $_SESSION['EXTID']      = $this->extension_id;
        $_SESSION['IP']         = $this->ip;
        $_SESSION["GROUPID"]    = $this->group_id;

        session_regenerate_id();

        $this->session_id = session_id();
        $this->date	   = date("Y-m-d H:i:s");
        
        if($this->extension_id == ''){
            $this->extension_id = 0;
        }

        parent::setQuery("INSERT INTO `user_log`
                                  (`user_id`, `datetime`, `extension_id`, `ip`, `session_id`, `type`)
                            VALUES
                                  ($this->user_id, NOW(), $this->extension_id, '$this->ip', '$this->session_id', 1)");
        parent::execQuery();

        parent::setQuery("  UPDATE  `users` 
                            SET     `logged`          = 1,
                                    `login_date` 	  = '$this->date',
                                    `ip`              = '$this->ip',
                                    `extension_id`    = '$this->extension_id'
                            WHERE   `id`              =  $this->user_id");

        parent::execQuery();
        
    }
    
    public function expire($time){
        $this->expire = $time;
        session_cache_limiter('private');
        session_cache_expire($time / 60);
    }
    

    public function getExt(){

        parent::setQuery("SELECT id, name FROM asterisk_extension WHERE actived = 1");

        $extResult = parent::getResultArray();

        return  $extResult['result'];

    }
    public function getQueue(){

        parent::setQuery("SELECT id, number AS name FROM asterisk_queue WHERE actived = 1");

        $queueResult = parent::getResultArray();

        return  $queueResult['result'];

    }

    /**
     * LOG OUT
     *
     * @return void
     */
    public function logout(){
       
        $user_id    = $_SESSION['USERID'];
        $ext_id     = $_SESSION['EXTID'];
        $ip         = $_SESSION['IP'];
        $session_id = session_id();

        if(empty($ext_id)){
            $ext_id = 0;
        }

        if(!isset($_SESSION)) session_start();

        parent::setQuery("  INSERT INTO `user_log`
                                    (`user_id`, `datetime`, `extension_id`, `ip`, `session_id`, `type`)
                            VALUES
                                    ($user_id, NOW(), $ext_id, '$ip', '$session_id', 2)");
        parent::execQuery();
        
        parent::setQuery("  UPDATE  `users` 
                            SET     `logged`        = 0, 
                                    `extension_id`  = 0 
                            WHERE   `id`            = '$user_id'");
        parent::execQuery();


        session_destroy();

        unset($_SESSION['USERID']);
        unset($_SESSION['EXTID']);
        unset($_SESSION['IP']);

        return array("messsage" => "სესია წარმატებით  დასრულდა", "status" => true);
        
    }


/**
 * GET CLIENT IP
 *
 * @return void
 */
    public function ip(){
        global $REMOTE_ADDR;
        global $HTTP_X_FORWARDED_FOR, $HTTP_X_FORWARDED, $HTTP_FORWARDED_FOR, $HTTP_FORWARDED;
        global $HTTP_VIA, $HTTP_X_COMING_FROM, $HTTP_COMING_FROM;
        if (empty($REMOTE_ADDR)) {
            if (!empty($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) {
                $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
            }
            else if (!empty($_ENV) && isset($_ENV['REMOTE_ADDR'])) {
                $REMOTE_ADDR = $_ENV['REMOTE_ADDR'];
            }
            else if (@getenv('REMOTE_ADDR')) {
                $REMOTE_ADDR = getenv('REMOTE_ADDR');
            }
        }
        if (empty($HTTP_X_FORWARDED_FOR)) {
            if (!empty($_SERVER) && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $HTTP_X_FORWARDED_FOR = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            else if (!empty($_ENV) && isset($_ENV['HTTP_X_FORWARDED_FOR'])) {
                $HTTP_X_FORWARDED_FOR = $_ENV['HTTP_X_FORWARDED_FOR'];
            }
            else if (@getenv('HTTP_X_FORWARDED_FOR')) {
                $HTTP_X_FORWARDED_FOR = getenv('HTTP_X_FORWARDED_FOR');
            }
        }
        if (empty($HTTP_X_FORWARDED)) {
            if (!empty($_SERVER) && isset($_SERVER['HTTP_X_FORWARDED'])) {
                $HTTP_X_FORWARDED = $_SERVER['HTTP_X_FORWARDED'];
            }
            else if (!empty($_ENV) && isset($_ENV['HTTP_X_FORWARDED'])) {
                $HTTP_X_FORWARDED = $_ENV['HTTP_X_FORWARDED'];
            }
            else if (@getenv('HTTP_X_FORWARDED')) {
                $HTTP_X_FORWARDED = getenv('HTTP_X_FORWARDED');
            }
        }
        if (empty($HTTP_FORWARDED_FOR)) {
            if (!empty($_SERVER) && isset($_SERVER['HTTP_FORWARDED_FOR'])) {
                $HTTP_FORWARDED_FOR = $_SERVER['HTTP_FORWARDED_FOR'];
            }
            else if (!empty($_ENV) && isset($_ENV['HTTP_FORWARDED_FOR'])) {
                $HTTP_FORWARDED_FOR = $_ENV['HTTP_FORWARDED_FOR'];
            }
            else if (@getenv('HTTP_FORWARDED_FOR')) {
                $HTTP_FORWARDED_FOR = getenv('HTTP_FORWARDED_FOR');
            }
        }
        if (empty($HTTP_FORWARDED)) {
            if (!empty($_SERVER) && isset($_SERVER['HTTP_FORWARDED'])) {
                $HTTP_FORWARDED = $_SERVER['HTTP_FORWARDED'];
            }
            else if (!empty($_ENV) && isset($_ENV['HTTP_FORWARDED'])) {
                $HTTP_FORWARDED = $_ENV['HTTP_FORWARDED'];
            }
            else if (@getenv('HTTP_FORWARDED')) {
                $HTTP_FORWARDED = getenv('HTTP_FORWARDED');
            }
        }
        if (empty($HTTP_VIA)) {
            if (!empty($_SERVER) && isset($_SERVER['HTTP_VIA'])) {
                $HTTP_VIA = $_SERVER['HTTP_VIA'];
            }
            else if (!empty($_ENV) && isset($_ENV['HTTP_VIA'])) {
                $HTTP_VIA = $_ENV['HTTP_VIA'];
            }
            else if (@getenv('HTTP_VIA')) {
                $HTTP_VIA = getenv('HTTP_VIA');
            }
        }
        if (empty($HTTP_X_COMING_FROM)) {
            if (!empty($_SERVER) && isset($_SERVER['HTTP_X_COMING_FROM'])) {
                $HTTP_X_COMING_FROM = $_SERVER['HTTP_X_COMING_FROM'];
            }
            else if (!empty($_ENV) && isset($_ENV['HTTP_X_COMING_FROM'])) {
                $HTTP_X_COMING_FROM = $_ENV['HTTP_X_COMING_FROM'];
            }
            else if (@getenv('HTTP_X_COMING_FROM')) {
                $HTTP_X_COMING_FROM = getenv('HTTP_X_COMING_FROM');
            }
        }
        if (empty($HTTP_COMING_FROM)) {
            if (!empty($_SERVER) && isset($_SERVER['HTTP_COMING_FROM'])) {
                $HTTP_COMING_FROM = $_SERVER['HTTP_COMING_FROM'];
            }
            else if (!empty($_ENV) && isset($_ENV['HTTP_COMING_FROM'])) {
                $HTTP_COMING_FROM = $_ENV['HTTP_COMING_FROM'];
            }
            else if (@getenv('HTTP_COMING_FROM')) {
                $HTTP_COMING_FROM = getenv('HTTP_COMING_FROM');
            }
        }
        
        if (!empty($REMOTE_ADDR)) {
            $direct_ip = $REMOTE_ADDR;
        }
        
        $proxy_ip	 = '';
        if (!empty($HTTP_X_FORWARDED_FOR)) {
            $proxy_ip = $HTTP_X_FORWARDED_FOR;
        } else if (!empty($HTTP_X_FORWARDED)) {
            $proxy_ip = $HTTP_X_FORWARDED;
        } else if (!empty($HTTP_FORWARDED_FOR)) {
            $proxy_ip = $HTTP_FORWARDED_FOR;
        } else if (!empty($HTTP_FORWARDED)) {
            $proxy_ip = $HTTP_FORWARDED;
        } else if (!empty($HTTP_VIA)) {
            $proxy_ip = $HTTP_VIA;
        } else if (!empty($HTTP_X_COMING_FROM)) {
            $proxy_ip = $HTTP_X_COMING_FROM;
        } else if (!empty($HTTP_COMING_FROM)) {
            $proxy_ip = $HTTP_COMING_FROM;
        }
        
        if (empty($proxy_ip)) {
            $this->ip =  $direct_ip;
        } else {
            $is_ip = preg_match('|^([0-9]{1,3}\.){3,3}[0-9]{1,3}|', $proxy_ip, $regs);
            if ($is_ip && (count($regs) > 0)) {
                $this->ip =  $regs[0];
            } else {
                $this->ip =  'unknow';
            }
        }
    }


}

?>