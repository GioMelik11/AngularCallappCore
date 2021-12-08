<?php


namespace Routers;

class AsteriskManager{
    
    var $socket = NULL;
    var $server;
    var $port;
    
    function AsteriskManager() { }
    
    function send_request($action, $parameters=array()) {
        $command = "Action: $action\r\n";
        foreach($parameters as $var=>$val) {
            $command .= "$var: $val\r\n";
        }
        $command .= "\r\n";
      
        fwrite($this->socket, $command);

        return $this->get_response(true);
    }
    
    function get_response($allow_timeout=false) {
        $timeout = false;
        do {
            $type = NULL;
            $parameters = array();
        
            if (feof($this->socket)) {
                return false;
            }
            $buffer = trim(fgets($this->socket, 4096));
            while($buffer != '') {
                $a = strpos($buffer, ':');
                if($a) {
                    if(!count($parameters)) { // first line in a response?
                        $type = strtolower(substr($buffer, 0, $a));
                        if(substr($buffer, $a + 2) == 'Follows') {
                            // A follows response means there is a multiline field that follows.
                            $parameters['data'] = '';
                            $buff = fgets($this->socket, 4096);
                            while(substr($buff, 0, 6) != '--END ') {
                                $parameters['data'] .= $buff;
                                $buff = fgets($this->socket, 4096);
                            }
                        }
                    }
        
                // store parameter in $parameters
                $parameters[substr($buffer, 0, $a)] = substr($buffer, $a + 2);
                }
                $buffer = trim(fgets($this->socket, 4096));
            }
        
            // process response
            switch($type) {
                case '': // timeout occured
                    $timeout = $allow_timeout;
                    break;
                case 'event':
                    // Process event with $parameters ?
                    break;
                case 'response':
                    break;
                default:
                    // $this->log('Unhandled response packet from Manager: ' . print_r($parameters, true));
                    break;
            }
        } while($type != 'response' && !$timeout);
        return $parameters;
    }
    
    function connect() {
        // Extract port if specified
        if(strpos(MANAGERHOST, ':') !== false) {
            $parts = explode(':', MANAGERHOST);
            $this->server = $parts[0];
            $this->port   = $parts[1];
        } else {
            $this->server = MANAGERHOST;
            $this->port   = MANAGERPORT;
        }
        
        $errno = $errstr = NULL;
        $this->socket = @fsockopen($this->server, $this->port, $errno, $errstr);
        if(!$this->socket) {
            $this->log("Unable to connect to manager {$this->server}:{$this->port} ($errno): $errstr");
            return false;
        }
        
        // read the header
        $str = fgets($this->socket);
        if($str == false) {
            $this->log("Asterisk Manager header not received.");
            return false;
        } 
        // login
        $res = $this->send_request('login', array('Username'=>MANAGERUSER, 'Secret'=>MANAGERPASS));

        if(false) {
            $this->log("Failed to login.");
            $this->disconnect();
            return false;
        }
        return true;
    }
    
    function disconnect() {
        $this->logoff();
        fclose($this->socket);
    }
    
    function Command($command) {    
        return $this->send_request('Command', array('Command'=>$command));
    }
    
    function ExtensionState($exten, $context='', $actionid='') {
        return $this->send_request('ExtensionState', array('Exten'=>$exten, 'Context'=>$context, 'ActionID'=>$actionid));
    }

    function LocalCall($exten, $queue) {
        return $this->send_request('QueuePause', array('ActionID'=>'test', 'Interface'=>$exten, 'Paused'=>true, 'Queue' => $queue, 'Reason' => 'test'));
    }
    
    function LocalCallOff($ext, $queue){
        return $this->send_request('QueuePause', array('ActionID'=>'test', 'Interface'=>$ext, 'Paused'=>'false', 'Queue' => $queue, 'Reason' => 'test'));
    }
    
    function Hangup($channel) {
        return $this->send_request('Hangup', array('Channel'=>$channel));
    }

    function DNDON($extension){
        return $this->send_request('DBPut', array('Family'=>'DND', 'Key'=>$extension, 'Val'=>'YES'));
    }

    function DNDOFF($extension){
        return $this->send_request('DBDel', array('Family'=>'DND', 'Key'=>$extension));
    }
    
    function Logoff() {
        return $this->send_request('Logoff');
    }
    
    function log($message, $level=1){
        error_log(date('r') . ' - ' . $message);
    }
}

function get_channels($am) {

    $res=$am->Command('core show version');
    preg_match('/Asterisk (\d\.?\d)/', $res['data'], $arr);
    $version=$arr[1];
    if(!preg_match("/\./",$version)) {
        $version = "1.".$version;
    }
    $version = preg_replace("/\./","",$version);
    $version = intval($version);

    $res=$am->Command("core show channels concise");
    $res=$res['data'];
    $responselines=explode("\n",$res);
    $lines=array();

    foreach($responselines as $l) {
        if (preg_match("/^Response/",$l)) continue;
        if (preg_match("/^Privilege/",$l)) continue;
        if (preg_match("/^$/",$l)) break;

        $lines[]=$l;
    }

    $channels=array();
    foreach($lines as $l) {
        $chan=explode("!",$l);
        if (count($chan)==1)
            $chan=explode(":",$l);

        //if($chan[9] != ''){
            $ci=array();
            if(substr($chan[0], -2) != ';1'){
                var_dump($chan); 
                $explode = explode("@", $chan[0]);
                $channelID = $explode[0];
            
                $ci['channel']=$chan[0];
                //$ci['context']=$chan[1];
                //$ci['exten']=$chan[2];
                //$ci['priority']=$chan[3];
                $ci['state']=$chan[4];
                //$ci['application']=$chan[5];
                //$ci['applicationdata']=$chan[6];
                $ci['callerid']=$chan[7];
                //$ci['accountcode']=$chan[8];
                //$ci['amaflags']=$chan[9];
                $ci['duration']=$chan[($version >= 18 ? 11 : 10)];
                //$ci['bridgedto']=$chan[($version >= 18 ? 12 : 11)];

                $dur=$ci['duration']+0;
                $durstr=sprintf("%d:%02d",$dur/60,$dur%60);
                $ci['duration_str']=$durstr;

                $channels[$channelID]=$ci;

            }
        //}
        
    }

    return $channels;    
}

function get_queues($am,$channels) {

    $res=$am->Command("queue show");
    $res=$res['data']; 
    $lines=explode("\n",$res);

    $queue=null;
    $data=null;
    $reading=null;
      
    foreach ($lines as $l) {

        if (is_null($queue) && preg_match("/^(\S+)\s+has\s(\d+)/",$l,$matches)) {
            $queue=$matches[1];
            $data=array();
            $data['ncalls']=$matches[2];
            continue;
        } 
        if (!is_null($queue) && $l=="") {
            //Grabamos esta cola
            $queuelist[$queue]=$data;
            $queue=null;
            $data=null;
            $reading=null;
            continue;
        } 

        if (is_null($reading) && preg_match("/Members:/",$l)) {
            $reading="members";
            continue;
        }

        if ($reading=="members" && preg_match("/^([^\(]*)\(([^\)]*)\).*/",$l,$matches)) {

            $name=trim($matches[1]);
            $member=$matches[2];

            if(preg_match('/^SIP|^IAX|^AGENT|^DAHDI|^LOCAL/i',$name)) {
                $member = $name;
            } 

            $member = explode("@", $member);
            $member = $member[0];

            if(preg_match("/\(Unavailable\)/",$l)) {
                $tipo="unavailable";
            } elseif(preg_match("/\(in call\) \(Busy\)/",$l) || preg_match("/\(in call\) \(In use\)/",$l) || preg_match("/\(In use\)/",$l)) {
                $tipo="busy";
            } elseif(preg_match("/\(in call\) \(On Hold\)/",$l)) {
                $tipo="onhold";
            } elseif(preg_match("/\(Ringing\)/",$l)) {
                $tipo="ringing";
            } elseif(preg_match("/\(Busy\)/",$l)) {
                $tipo="dnd";
            } elseif(preg_match("/\(Not in use\)/",$l) || preg_match("/\(in use\)/",$l)) {
                $tipo="not in use";
            } 

            if(preg_match("/paused/",$l)) {
                $status="paused";
            }

            $mem['id']                  =   $member;
            $mem['name']                =   $name;
            $mem['type']                =   $tipo;
            $mem['status']              =   $status;
            // $mem['lastcall']            =   $seconds;
            $data['members'][$member]   =   $mem;
            continue;
	}

        if (preg_match("/Callers:/",$l)) {
            $reading="callers";
            continue;
        }
        if ($reading=="callers" && preg_match("/^\s+\d+\.\s+(\S+)\s+\(wait:\s*([\d:]+),\s*prio:\s*(\d+)/",$l,$matches)) {
            $callinfo=array();
            $callinfo['channel']=$matches[1];
            $callinfo['chaninfo']=$channels[$matches[1]];
            $callinfo['waittime']=$matches[2];
            $callinfo['prio']=$matches[3];
            $data['calls'][]=$callinfo;
            continue;
        } else if ($reading=="callers") {
            $reading=null;
        }
    }

    return $queuelist;
}

function get_peer_states($am){

    $res    = $am->Command("sip show peers");
    $lines  = explode("\n",$res['data']);
    $extStateArray = array();

    foreach ($lines as $line) {

        $ext = substr($line, 0, 3);

        if(strpos($line,"Unmonitored")){
            $extStateArray[$ext] = 'Unmonitored';
        }elseif (strpos($line,"OK")) {
            $extStateArray[$ext] = 'OK';
        }elseif (strpos($line,"UNKNOWN")) {
            $extStateArray[$ext] = 'UNKNOWN';
        }
       
    }

    return $extStateArray;

}

?>