<?php

namespace Routers;

use Routers\AsteriskManager;
use Exception;
use mysqli;

class dbClass extends AsteriskManager{

    public $mysqli;

    public $host;
    public $port;
    public $user;
    public $dbpassword;
    public $db;

    public $charset;

    public $query;
    public $curTable;
    
    

    /**CONSTRUCTOR
     *
     * @param string  $host
     * @param string  $user
     * @param string  $dbpassword
     * @param string  $db
     * @param integer $port
     * @param string  $charset
     * @throws Exception
     */
    public function __construct($host=MYSQLSERVERIP, $user=MYSQLUSER, $dbpassword=MYSQLPASS, $db=MYSQLDB, $port=MYSQLPORT, $charset=MYSQLCHARSET){
        
        $this->host     = $host;
        $this->port     = $port;
        $this->user     = $user;
        $this->dbpassword = $dbpassword;
        $this->db       = $db;

        $this->charset  = $charset;

        if (empty($this->host) || empty($this->user) || empty($this->dbpassword) || empty($this->db)) {
            throw new Exception('Empty Parameters');
        }

        $this->mysqli = new mysqli($this->host, $this->user, $this->dbpassword, $this->db, $this->port);


        if ($this->mysqli->connect_error) {
            // throw new Exception('Connect Error ' . $this->mysqli->connect_errno . ': ' . $this->mysqli->connect_error);
            die(file_get_contents("Views/pages/dbError.html").$this->mysqli->connect_errno.': '.$this->mysqli->connect_error);
        }

        if ($this->mysqli) {
            $this->mysqli->set_charset($this->charset);
        }else{
            throw new Exception('SET Charset ERROR');
        }
    }

    /**SET QUERY
     *
     * @param string $query
     */
    public function setQuery($query, $table='') {
        
        if($table != '')
		{
			$this->curTable = $table;
		}
        if ($query=='') {
            throw new Exception('MySQLi query empty');
        }else{
			$this->query = $query;
           
        }
    }

    /**Get Last INSERT Id
     *
     *
     */
    public function getLastId() {
        return $this->mysqli->insert_id;
    }

    /**EXECUTE MYSQL QUERY
     *
     * @throws Exception
     */
    public function execQuery() {

        $this->mysqli->query($this->query);

        if ($this->mysqli->error) {
            throw new Exception($this->mysqli->error." ::: Query -> ".$this->query);
        }else {
            return $this->mysqli->info;
        }
     }

     public function executeQuery($parameters) {
        if($stmt = $this->mysqli->prepare($this->query)) {

            $params = array();
            $types = "";

            foreach ($parameters as $value) {
                $types .= $value["type"];
                array_push($params,$value["name"]);
                // print_r($params);
                // echo "<br><br>";
            }

            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return true;
        }
        else
            return false;
        $this->disconnect();
    }

    /**GET QUERY RESULT NUMERIC ARRAY
     *
     * @param INT $type
     * @throws Exception
     * @return array $fetchArray
     */
    public function getResultArray($type = MYSQLI_ASSOC){
    
        $fetchArray = array();
     
        $result     = $this->mysqli->query($this->query);
        
        if ($result) {
            $start = microtime();
            $count = 0;
    
            $fetchArray['result'] = NULL;
            while($row = $result->fetch_array($type)){
                $fetchArray['result'][] = $row;
                $count++;
            }
			
			if(!empty($this->curTable))
			{
				$request = $this->mysqli->query("SHOW COLUMNS FROM ".$this->curTable);
				if($request)
				{
                    $i = 0;
					while($r = $request->fetch_array($type)){
                        
                        $fetchTableColumnNames[] = $r['Field'];
                        
						if(strpos($r['Type'], 'int') !== false)							///
						{																///
							$fetchTableColumns[] = 'number';							/// MAPPING OF DATA TYPES KENDOUI
						}																///
						else if(strpos($r['Type'], 'varchar') !== false){				///
							$fetchTableColumns[] = 'string';
						}
						else if(strpos($r['Type'], 'date') !== false){
							$fetchTableColumns[] = 'date';
						}
						$i++;
					}
				}
				else
				{
					$fetchTableColumns = 'FAILED MYSQLI->QUERY';
				}
				
			}
			
			// $fetchArray['data_columns'] = $fetchTableColumnNames;
			// $fetchArray['data_type'] = $fetchTableColumns;
            // $fetchArray['time']  = round(microtime() - $start, 4);
            $fetchArray['count'] = $count;

            return $fetchArray;
    
        }else {
            $this->disconnect();
            throw new Exception($this->mysqli->error." ::: Query -> ".$this->query);
        }
    }

    /**GET JSON FROM ASSOC ARRAY RESULT
     *
     * @param INT $type
     * @return json $array
     */
    public function getJson($type = 'ASSOC'){

        if ($type == 'ASSOC') {
            $array = $this->getResultArray(MYSQLI_ASSOC);
        }elseif ($type == 'NUM'){
            $array = $this->getResultArray(MYSQLI_NUM);
        }else{
            throw new Exception('Unknow type!');
        }

        $encodedArray   = json_encode($array, JSON_NUMERIC_CHECK);

        if ($encodedArray) {
            return $encodedArray;
        }else{
            throw new Exception('JSON ecode failed!');
        }

    }

    public function getKendoList($count,$cols){
        $result  = $this->mysqli->query($this->query);
        $data = array();
        $bigdata = array();

        foreach($cols AS $col)
        {
            $subcol = explode(':',$col);
            $colName[] = $subcol[0];
        }
        
        if($result){
            $start = microtime();
            $rowcount = 0;
            while($aRow = $result->fetch_array(MYSQLI_NUM)){
                $row = array();
                for ($i = 0 ; $i < $count ; $i++){

                    if(empty($aRow[$i])){
                        $row[$colName[$i]] = null;
                    }
                    else{
                        $row[$colName[$i]] = $aRow[$i];
                    }
                }
                $bigdata[] = $row;
                $rowcount++;
            }
            $data = $bigdata;

            

            return $data;

        }else{
            $this->disconnect();
            throw new Exception($this->mysqli->error." ::: Query -> ".$this->query);
        }
    }

    /**GET RESULT ROW NUMBERS
     *
     * @throws Exception
     * @return integer $row
     */
    public function getNumRow(){

        $result = $this->mysqli->query($this->query);

        if ($result) {
            $row = $result->num_rows;
            return $row;
        }else{
            throw new Exception($this->mysqli->error." ::: Query -> ".$this->query);
        }
    }

    /**GET TABLE CURRENT INCREMENT VALUE
     *
     * @param  string  $table
     * @return integer $increment
     */
    public function getIncrement($table){

        $result    = $this->mysqli->query("SHOW TABLE STATUS LIKE '$table'");
        $row       = $result->fetch_assoc();
        $increment = $row['Auto_increment'];

        return $increment;
    }

    /**SET TABLE INCREMENT VALUE
     *
     * @param  string  $table
     * @return integer $increment
     */
    public function setIncrement($table){

        $increment = $this->getIncrement($table);
        $next_increment = $increment+1;
        $this->mysqli->query("ALTER TABLE $table AUTO_INCREMENT=$next_increment");

        return $increment;

    }


    /**Chosens options
     * @param Int $id - Selected
     * @param String $selects - Options
     * @return Chosen options HTML
     */

    public function getSelect($id,$selects="name"){


        $req = $this->mysqli->query($this->query);

        $data = '<option value="0" >----</option>';


        if ($req){
            while ($res = $req->fetch_array()) {

                if ($res['id'] == $id) {
                    $data .= '<option value="' . $res['id'] . '" selected="selected">' . $res[$selects] . '</option>';
                } else {
                    $data .= '<option value="' . $res['id'] . '">' . $res[$selects] . '</option>';
                }
            }
        }
        else {
            throw new Exception($this->mysqli->error." ::: Query -> ".$req);
        }

        return $data;
    }

    /**
     * disconnect mysql server
     */
    public function disconnect(){

        $this->mysqli->close();

    }

    public function escp($string){
        return $this->mysqli->real_escape_string($string);
    }


    /**
   * რეპორტის შექმნა
   * @param mixed $report
   * @return boolean
   */
  public function sLog($report = '', $group = '')
  {
    $date = date("Y-m-d");

    if(!file_exists("Logs/{$group}-{$date}.log")){ 
        $logfile = fopen("Logs/{$group}-{$date}.log", "w") or die("Permission Denied!");
        fwrite($logfile, "######################################################\n");
        fwrite($logfile, "##                     LOG FILE                     ##\n");
        fwrite($logfile, "######################################################\n");
        fwrite($logfile, "\n\n\n");
        fclose($logfile);
        return true;
      }

      try {
        $logfile = fopen("Logs/{$group}-{$date}.log", "a");

        if($report == ''){
          $report = "\n".date("H:i:s")." | -------- REPORT IS EMPTY ---------- \n";
        }else{

          if(gettype($report) == "array"){
            $report = implode(" ", $report);
          }

          $report = "\n".date("H:i:s")." | $report";
        }
        fwrite($logfile, $report);
        fclose($logfile);
      }catch(Exception $ex){
        die('Exception:: '.$ex->getMessage());
      }

  }



}

?>
