<?php

use Routers\dbClass;

ini_set("display_errors", true);
error_reporting(E_ERROR);

class Handbook extends dbClass
{
    private $result;
    private $request;
    private $geoName;
    private $handbook;
    private $colCount;
    private $cols;

    public function __construct()
    {
        $this->request  = $this->convertArray($_REQUEST["requestData"]);
        $this->geoName  = $this->selectHandbook($_REQUEST["handbook"]);
        $this->result   = array('error' => null, "message" => null);
        $this->handbook = $_REQUEST["handbook"];
        parent::__construct(); // fix mysqli null
    }

    public function getList()
    {
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        $Maintable = $this->handbook;

        $joinData = explode(',', urldecode($_REQUEST["JoinData"]));
        $colData  = explode(',', urldecode($_REQUEST["columnNames"]));

        // convert 1D array to 2D array
        $joinData = array_chunk($joinData, 2);

        $lastColumn = COUNT($colData) - 1;
        $tableColumns = "";
        $joins = "";


        // check if this request has joins
        if (isset($_REQUEST["JoinData"]) && $_REQUEST["JoinData"] != "") {
            foreach ($joinData as $value) {
                $joinType  = current(explode('@', $value[0]));
                $connecter = next(explode('@', $value[0]));
                $joinTable = current(explode('.', $connecter));
                $connector = $value[1];

                $joins .= "$joinType JOIN $joinTable ON $connecter = $connector ";
            }
            // echo $joins;
        }

        foreach ($colData as $key => $column) {
            $tableColumns .= $key == $lastColumn ? " $column " : " $column, ";
        }

        parent::setQuery("  SELECT 	$tableColumns
                                FROM 	$Maintable
                                $joins
                                WHERE 	$Maintable.actived = 1; ");

        $departmentList =  parent::getKendoList($this->colCount, $this->cols);
        return $departmentList;
    }

    public function delete()
    {
        if ($_REQUEST['list'] == '' || $_REQUEST['list'] == null)
            return array('error' => true, 'message' => 'მონიშნეთ წასაშლელად');

        $table = $this->handbook;
        $geoName = $this->geoName;
        $list = implode(',', $_REQUEST["list"]);

        parent::setQuery(" UPDATE $table  SET `actived` = 0 ,  `user_id` = ? WHERE id IN($list); ");
        $params = array(array('name' => $_SESSION["USERID"], 'type' => 's'));

        $this->result["error"]   = parent::executeQuery($params);
        $this->result["message"] = $this->result["error"] ? $geoName . ' წარმატებით წაიშალა' : ' ვერ წაიშალა!';
        return $this->result;
    }

    public function update()
    {
        $table = $this->handbook;
        $geoName = $this->geoName;
        $params = array();
        $tableColumns = "";
        $lastColumn = COUNT($_REQUEST["requestData"]) - 1;
        $catch_id = 0;

        foreach ($_REQUEST["requestData"] as $key  => $column) {
            if ($key == "id")
                $catch_id = $column["value"];
            else {
                $tableColumns .=  " $column[fieldie] =  ?, ";
                array_push($params, array('name' => $column["value"], 'type' => 's'));
            }
        }

        array_push($params, array('name' => $_SESSION["USERID"], 'type' => 's'));
        array_push($params, array('name' => $catch_id, 'type' => 's'));

        parent::setQuery(" UPDATE $table SET $tableColumns `user_id` = ? WHERE id = ?; ");
        $this->result["error"]   = parent::executeQuery($params);
        $this->result["message"] = $this->result["error"] ? $geoName . ' წარმატებით ჩასწორდა' : ' ვერ ჩასწორდა!';
        return $this->result;
    }

    public function create()
    {
        $table = $this->handbook;
        $geoName = $this->geoName;
        $this->result = $this->validation();

        if ($this->activateIfExists() && $this->result["error"] == false) {
            // თუ არ არსებობს დაამატე
            $params = array();
            $tableColumns = "";
            $lastColumn = COUNT($this->request) - 1;

            foreach ($this->request as $key => $column) {
                $tableColumns .= " $key =  ?, ";
                array_push($params, array('name' => $column, 'type' => 's'));
            }

            array_push($params, array('name' => $_SESSION["USERID"], 'type' => 's'));
            parent::setQuery(" INSERT  INTO  $table SET $tableColumns `user_id` = ?; ");
            $this->result["error"]   = parent::executeQuery($params);
            $this->result["message"] = $this->result["error"] ? $geoName . ' წარმატებით დაემატა!' : ' განყოფილება ვერ დაემატა!';
        }

        return $this->result;
    }

    private function activateIfExists()
    {
        $table = $this->handbook;
        $geoName = $this->geoName;

        $this->result = $this->validation();
        if ($this->result == false) return false;

        $sqlColumns = $_REQUEST["requestData"];
        $lastColumn = COUNT($sqlColumns) - 1;
        $tableColumns = "";
        $equals = "";
        $joins = "";


        $ind = 0;
        foreach ($this->request as $key => $columnie) {
            $tableColumns .= " $key, ";
            if ($key != "$table.id") {
                $equals .= " $key " . " = '$columnie' ";
                $equals .= ($lastColumn != $ind) ? " AND " : "";
            }
            $ind++;
        }

        // check if this request has joins
        if (isset($_REQUEST["JoinData"]) && $_REQUEST["JoinData"] != "") {
            foreach ($_REQUEST["JoinData"] as $key => $value) {
                $joinType  = current(explode('@', $value["join"]));
                $connecter = next(explode('@', $value["join"]));
                $joinTable = current(explode('.', $connecter));
                $connector = $value["to"];

                $joins .= "$joinType JOIN $joinTable ON $connecter = $connector ";
            }
        }

        // echo "<br><br><br> " . $joins . "<br><br><br>";

        parent::setQuery("SELECT  $tableColumns $table.actived FROM $table $joins WHERE $equals ;");
        $searchExist = parent::getResultArray();
        $searchRes   = $searchExist["result"][0];

        // შეამოწმე არსებობს თუ არა უკვე ამ სახელით 
        if (COUNT($searchExist["result"]) > 0) {
            // თუ მოიძებნა და აქტიურია არაფერი ქნას, თუ დეაქტიურია გააქტიუროს
            if ($searchRes["actived"] == 1)
                $this->result = array('error' => true, 'message' => $geoName . ' უკვე არსებობს!');
            else {
                $params = array();

                parent::setQuery(" UPDATE $table SET `user_id` = ? , `actived` = 1 WHERE id = ?; ");
                $params =  array(
                    array('name' => $_SESSION["USERID"], 'type' => 's'),
                    array('name' => $searchRes["id"],   'type' => 's')
                );

                $this->result["error"] = parent::executeQuery($params);
                $this->result["message"] = $geoName . ($this->result["error"]) ? ' წარმატებით გააქტიურდა!' : ' ვერ გააქტიურდა!';
            }

            return false;
        }
        return true;
    }

    public function validation()
    {
        $ind = 0;
        foreach ($this->request as $key => $value) {
            $geoName = $_REQUEST["requestData"];
            
            if ($_REQUEST["act"] == "create" && $key == "id") {
            } else {
                if (!isset($this->request[$key]) || $this->request[$key] == '')
                    return array('error' => true, 'message' => 'გთხოვთ შეავსოთ ' . $geoName[$ind]["geoTitle"]);
            }
            $ind++;
        }
        return array('error' => false, 'message' => '');
    }

    private function selectHandbook($handbook)
    {
        $geoNames = array();
        $geoNames["department"]        = "განყოფილებები";
        $geoNames["call_status"]       = "ზარების სტატუსი";
        $geoNames["call_type"]         = "ზარის ტიპები";
        $geoNames["priority"]          = "პრიორიტეტები";
        $geoNames["object"]            = "ობიექტები";
        $geoNames["disconnect_reason"] = "წყვეტის მიზეზი";
        $geoNames["action_worker"]     = "სამუშაოს შემსრულებელი";
        $geoNames["category"]          = "კატეგორიები";
        $geoNames["task_type"]         = "დავალების ტიპები";
        $geoNames["template"]          = "ინფორმაციის წყარო";
        $geoNames["sms_template"]      = "სმს შაბლონი";
        $geoNames["task_setting"]      = "პარამეტრები";
        $geoNames["chat_template"]     = "ჩატის შაბლონი";
        $geoNames["chat_setting"]      = "ჩატის პარამეტრები";
        $geoNames["chat_temes"]        = "ჩატის თემები";
        $geoNames["faq"]               = "კითხვა-პასუხი";
        $geoNames["task_zeinkals"]     = "ზეინკალი";
        $geoNames["mail_template"]     = "Mail შაბლონი";

        $geoNames["branch"]            = "ფილიალი";
        $geoNames["persons_phone"]     = "სატელეფონო ცნობარი";
        $geoNames["position"]          = "თანამდებობა";
        $geoNames["in_type"]           = "ტიპი";
        $geoNames["service_center_handbook"]     = "მომსახურების ცენტრი";

        if (array_key_exists($handbook, $geoNames) != 1) {
            $error = 'არჩეული ცნობარი:' . $handbook . ' არ არსებობს';
            $this->result = array('error' => true, 'message' => $error);
            return null;
        }
        return $geoNames[$handbook];
    }

    public function convertArray($arr)
    {
        $res = array();
        for ($i = 0; $i < COUNT($arr); $i++)
            $res[$arr[$i]["fieldie"]] = $arr[$i]["value"];

        return ($res);
    }

    public function getSelectOpts()
    {
        $table = $this->handbook;
        $where = "";

        if (isset($_REQUEST["onlyActives"]) && $_REQUEST["onlyActives"] != "" && $_REQUEST["onlyActives"] != "false")
            $where = " actived = 1 ";

        if (isset($_REQUEST["id"]) && $_REQUEST["id"] != "")
            $where .= $where == "" ? " id = $_REQUEST[id] " : " AND id = $_REQUEST[id] ";

        if (isset($_REQUEST["name"]) && $_REQUEST["name"] != "" && $_REQUEST["name"] != "null")
            $where .= $where == "" ? " name = '$_REQUEST[name]' " : " AND `name` = '$_REQUEST[name]' ";

        $where = ($where == "") ? "" : " WHERE " . $where;

        parent::setQuery("SELECT `id`, `name` FROM $table $where");
        return parent::getResultArray()["result"];
    }
}
