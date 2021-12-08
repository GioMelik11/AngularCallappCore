<?php

use Routers\dbClass;

class Automator extends dbClass{

    private $colCount;
    private $cols;

    public function getLevel(){
        $parentID = $_REQUEST['id'];
        $inputKey = $_REQUEST['inputKey'];

        $tempSelectorName = explode("--", $inputKey);
        $selectorID = $tempSelectorName[1];

        parent::setQuery("  SELECT  table_name
                            FROM    processing_setting_detail
                            WHERE   id = '$selectorID'");
        $selectorTableName = parent::getResultArray();
        $selectorTableName = $selectorTableName['result'][0]['table_name'];

        parent::setQuery("  SELECT  id,name
                            FROM    `$selectorTableName`
                            WHERE   actived = 1 AND parent_id = '$parentID'");
        $level_data = parent::getResultArray();

        if($level_data['count'] == 0){
            $level_data['result'][0] = array("id" => 0, "name" => "არ აქვს კატეგორია");
        }

        return $level_data['result'];
        
    }

    public function getFieldsetList(){
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        $pageID         = $_REQUEST['pageId'];
        parent::setQuery("  SELECT	processing_fieldset.id,
                                    processing_fieldset.name,
                                    GROUP_CONCAT(processing_setting_detail.name SEPARATOR ', ') AS inputs

                            FROM    processing_fieldset_by_page
                            JOIN	processing_fieldset ON processing_fieldset.id = processing_fieldset_by_page.fieldset_id
                            JOIN	processing_setting_detail ON processing_setting_detail.processing_fieldset_id = processing_fieldset.id
                            WHERE 	processing_fieldset_by_page.actived = 1 AND processing_fieldset_by_page.processing_page_id = '$pageID' AND processing_fieldset.actived = 1

                            GROUP BY processing_fieldset.id");
        $callList = parent::getKendoList($this->colCount, $this->cols);

        return $callList;
    }

    public function getInputList(){
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        $fieldsetID     = $_REQUEST['fieldsetID'];
        parent::setQuery("  SELECT      processing_setting_detail.id,
                                        processing_setting_detail.name,
                                        processing_field_type.name,
                                        processing_input_tabs.name,
                                        processing_setting_detail.position


                            FROM        processing_setting_detail
                            LEFT JOIN   processing_field_type ON processing_field_type.id = processing_setting_detail.processing_field_type_id
                            LEFT JOIN   processing_input_tabs ON processing_input_tabs.id = processing_setting_detail.tab_id
                            WHERE       processing_setting_detail.actived = '1'  AND processing_setting_detail.processing_fieldset_id = '$fieldsetID'");
        $callList = parent::getKendoList($this->colCount, $this->cols);

        return $callList;
    }

    public function getPageList(){
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        parent::setQuery("  SELECT 	processing_page.id,
                                    processing_page.datetime,
                                    processing_page.`name`,
                                    GROUP_CONCAT(processing_fieldset.name SEPARATOR ', ') AS fieldsets
                                    
                            FROM 	processing_page
                            LEFT JOIN	processing_fieldset_by_page ON processing_fieldset_by_page.processing_page_id = processing_page.id
                            LEFT JOIN	processing_fieldset ON processing_fieldset.id = processing_fieldset_by_page.fieldset_id
                            WHERE 	processing_page.actived = 1
                            GROUP BY processing_page.id");
        $callList = parent::getKendoList($this->colCount, $this->cols);

        return $callList;
    }


    public function getfieldsetName(){
        $fieldsetID     = $_REQUEST['fieldsetID'];
        parent::setQuery("  SELECT	processing_fieldset.name AS 'fieldsetName',
                                    'Fieldset დასახელება3454' AS 'title'

                            FROM 	processing_fieldset
                            WHERE 	processing_fieldset.id = '$fieldsetID'");
        $filedsetName = parent::getResultArray();

        return $filedsetName['result'];
    }

    public function getGeneratedInputs(){
        $field_query = '';
        $pageKey    = $_REQUEST['pageKey'];
        $processing = $_REQUEST['id'];
        $field_id = @$_REQUEST['field_id'];
        
        if($field_id != ''){
            $field_query = " AND processing_fieldset.id = '$field_id'";
        }

        //ჯერ ჯეროით შემომავალი ზარისთვის მოგვაქ დამხსოვრებული ნომერი მომართვისთვის... მერე უნდა გაკეთდეს სხვა გვერდებისთვისაც
        parent::setQuery("  SELECT		processing.value,
                                        IFNULL(processing.`value`->>'$.inputs.telefoni___987',incomming_request.request_name) as phone

                            FROM 		incomming_request
                            LEFT JOIN   processing ON incomming_request.id = processing.row_id
                            WHERE   	incomming_request.id = '$processing'");
        $jsonData = parent::getResultArray();
        $phoneData = $jsonData['result'][0]['phone'];

        $jsonData = json_decode($jsonData['result'][0]['value'],true);


        $inputJson  = array();
        parent::setQuery("  SELECT	processing_fieldset.id,
                                    processing_fieldset.name,
                                    processing_fieldset.multiple_categories_area AS multiData

                            FROM	processing_page
                            JOIN	processing_fieldset_by_page ON processing_fieldset_by_page.processing_page_id = processing_page.id
                            JOIN	processing_fieldset ON processing_fieldset.id = processing_fieldset_by_page.fieldset_id
                            WHERE 	processing_page.`key` = '$pageKey' AND processing_fieldset.actived = 1 $field_query
                            ORDER BY processing_fieldset.position");
        $fieldsets = parent::getResultArray();


        $fieldsetIterator = 0;
        foreach($fieldsets['result'] AS $field){
            $fieldsetData = array("fieldsetName" => $field['name'], "tabs" => array());
            array_push($inputJson, $fieldsetData);
            parent::setQuery("  SELECT 	processing_input_tabs.id,
                                        processing_input_tabs.`name`
                                FROM 	processing_input_tabs
                                WHERE 	processing_input_tabs.field_id = '$field[id]' AND processing_input_tabs.actived = 1");
            $tabs = parent::getResultArray();
            if($tabs['count'] > 0){
                $tabIterator = 0;
                foreach($tabs['result'] AS $tab){
                    $tabData = array("tabName" => $tab['name'], "inputs" => array());
                    array_push($inputJson[$fieldsetIterator]['tabs'], $tabData);
                    parent::setQuery("  SELECT 	processing_setting_detail.id,
                                                CONCAT(processing_setting_detail.`key`,'--',processing_setting_detail.id,'--',processing_field_type.id) AS `key`,
                                                processing_setting_detail.name,
                                                processing_setting_detail.table_name,
                                                processing_field_type.`key` AS type,
                                                processing_setting_detail.input_size,
                                                processing_setting_detail.multilevel_1,
                                                processing_setting_detail.multilevel_2,
                                                processing_setting_detail.multilevel_3

                                        FROM 	processing_setting_detail
                                        JOIN	processing_field_type ON processing_field_type.id = processing_setting_detail.processing_field_type_id
                                        WHERE 	processing_setting_detail.actived = 1 AND processing_setting_detail.tab_id = '$tab[id]' AND processing_field_type.actived = 1
                                        ORDER BY processing_setting_detail.position");

                    $inputs = parent::getResultArray();
                    $inputIterator = 0;
                    if (is_array($inputs['result']) || is_object($inputs['result']))
                    {
                        foreach($inputs['result'] AS $input){
                            $key_cutted = explode('--', $input['key']);

                            $inputData = array("input_id" => (int)$input['id'], "input_key" => $input['key'], "input_name" => $input['name'], "input_type" => $input['type'], "input_size" => (int)$input['input_size']);
                            array_push($inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'], $inputData);

                            if($input['type'] == 'radio' || $input['type'] == 'checkbox' || $input['type'] == 'multiselect' || $input['type'] == 'select'){




                                $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['input_value'] = empty($jsonData['selectors'][$key_cutted[0]]) ? '' : (int)$jsonData['selectors'][$key_cutted[0]];
                                $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['input_parameters'] = array();
                                if(empty($input['table_name'])) $input['table_name'] = 'mail_type';

                                parent::setQuery("  SELECT  id, name
                                                    FROM    `$input[table_name]`
                                                    WHERE   actived = 1");
                                $parameters = parent::getResultArray();
                                foreach($parameters['result'] AS $param){
                                    $DIR_Params = array("id" => $param['id'], "name" => $param['name']);
                                    array_push($inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['input_parameters'], $DIR_Params);
                                }
                            }
                            else if($input['type'] == 'multilevelselect'){

                                $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['level_1_name'] = $input['multilevel_1'];
                                $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['level_2_name'] = $input['multilevel_2'];
                                $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['level_3_name'] = $input['multilevel_3'];

                                $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['level_1_value'] = '';
                                $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['level_2_value'] = '';
                                $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['level_3_value'] = '';

                                $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['level_1_parameters'] = array();
                                $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['level_2_parameters'] = array();
                                $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['level_3_parameters'] = array();
                                
                                if(empty($input['table_name'])) $input['table_name'] = 'mail_type';
                                parent::setQuery("  SELECT  id, name
                                                    FROM    `$input[table_name]`
                                                    WHERE   actived = 1 AND parent_id = 0");
                                $parameters_level_1 = parent::getResultArray();
                                foreach($parameters_level_1['result'] AS $param){
                                    $multilevel_Params = array("id" => $param['id'], "name" => $param['name']);
                                    array_push($inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['level_1_parameters'], $multilevel_Params);
                                }

                                

                                parent::setQuery("  SELECT id,name
                                                    FROM `$input[table_name]`
                                                    WHERE actived = 1 AND parent_id IN (SELECT	id
                                                                                        FROM `$input[table_name]`
                                                                                        WHERE actived = 1 AND parent_id IN (SELECT id FROM `$input[table_name]` WHERE actived = 1 AND parent_id = 0))");
                                $parameters_level_3 = parent::getResultArray();

                                foreach($parameters_level_3['result'] AS $param){
                                    $multilevel_Params = array("id" => $param['id'], "name" => $param['name']);
                                    array_push($inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['level_3_parameters'], $multilevel_Params);
                                }
    
                            }
                            else{
                                //თუ ტელეფონის ნომრის ველია იტერაციაში მოგვაქვს უკვე წინასწარ წამოღებული ნომერი
                                if($key_cutted[0] == 'telefoni___987'){
                                    $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['input_value'] = empty($phoneData) ? '' : $phoneData;
                                }
                                else{
                                    $inputJson[$fieldsetIterator]['tabs'][$tabIterator]['inputs'][$inputIterator]['input_value'] = empty($jsonData['inputs'][$key_cutted[0]]) ? '' : $jsonData['inputs'][$key_cutted[0]];
                                }
                                
                            }
                            $inputIterator++;
                        }
                    }

                    $tabIterator++;
                }
            }
            else{
                parent::setQuery("  SELECT 	processing_setting_detail.id,
                                            CONCAT(processing_setting_detail.`key`,'--',processing_setting_detail.id,'--',processing_field_type.id) AS `key`,
                                            processing_setting_detail.name,
                                            processing_setting_detail.table_name,
                                            processing_field_type.`key` AS type,
                                            processing_setting_detail.input_size,
                                            processing_setting_detail.multilevel_1,
                                            processing_setting_detail.multilevel_2,
                                            processing_setting_detail.multilevel_3

                                    FROM 	processing_setting_detail
                                    JOIN	processing_field_type ON processing_field_type.id = processing_setting_detail.processing_field_type_id
                                    WHERE 	processing_setting_detail.actived = 1 AND processing_setting_detail.processing_fieldset_id = '$field[id]' AND processing_field_type.actived = 1
                                    ORDER BY processing_setting_detail.position");

                $inputs = parent::getResultArray();
                $inputJson[$fieldsetIterator]['inputs'] = array();
                $inputIterator = 0;
                if (is_array($inputs['result']) || is_object($inputs['result']))
                {
                    foreach($inputs['result'] AS $input){
                        $key_cutted = explode('--', $input['key']);

                        
                        $inputData = array("input_id" => (int)$input['id'], "input_key" => $input['key'], "input_name" => $input['name'], "input_type" => $input['type'], "input_size" => (int)$input['input_size']);
                        array_push($inputJson[$fieldsetIterator]['inputs'], $inputData);

                        if($input['type'] == 'radio' || $input['type'] == 'checkbox' || $input['type'] == 'multiselect' || $input['type'] == 'select'){



                            $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['input_value'] = empty($jsonData['selectors'][$key_cutted[0]]) ? '' : (int)$jsonData['selectors'][$key_cutted[0]];
                            $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['input_parameters'] = array();
                            if(empty($input['table_name'])) $input['table_name'] = 'mail_type';
                            parent::setQuery("  SELECT  id, name
                                                FROM    `$input[table_name]`
                                                WHERE   actived = 1");
                            $parameters = parent::getResultArray();
                            foreach($parameters['result'] AS $param){
                                $DIR_Params = array("id" => $param['id'], "name" => $param['name']);
                                array_push($inputJson[$fieldsetIterator]['inputs'][$inputIterator]['input_parameters'], $DIR_Params);
                            }
                        }
                        else if($input['type'] == 'multilevelselect'){
                            $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['level_1_name'] = $input['multilevel_1'];
                            $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['level_2_name'] = $input['multilevel_2'];
                            $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['level_3_name'] = $input['multilevel_3'];


                            $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['level_1_value'] = empty($jsonData['multilevel'][$key_cutted[0].'___1']) ? '' : (int)$jsonData['multilevel'][$key_cutted[0].'___1'];
                            $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['level_2_value'] = empty($jsonData['multilevel'][$key_cutted[0].'___2']) ? '' : (int)$jsonData['multilevel'][$key_cutted[0].'___2'];
                            $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['level_3_value'] = empty($jsonData['multilevel'][$key_cutted[0].'___3']) ? '' : (int)$jsonData['multilevel'][$key_cutted[0].'___3'];





                            $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['level_1_parameters'] = array();
                            $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['level_2_parameters'] = array();
                            $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['level_3_parameters'] = array();

                            $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['additionals'] = array();


                            if(empty($input['table_name'])) $input['table_name'] = 'mail_type';
                            parent::setQuery("  SELECT  id, name
                                                FROM    `$input[table_name]`
                                                WHERE   actived = 1 AND parent_id = 0");
                            $parameters_level_1 = parent::getResultArray();
                            foreach($parameters_level_1['result'] AS $param){
                                $multilevel_Params = array("id" => $param['id'], "name" => $param['name']);
                                array_push($inputJson[$fieldsetIterator]['inputs'][$inputIterator]['level_1_parameters'], $multilevel_Params);
                            }


                            parent::setQuery("  SELECT	id, name
                                                FROM `$input[table_name]`
                                                WHERE actived = 1 AND parent_id IN (SELECT id FROM `$input[table_name]` WHERE actived = 1 AND parent_id = 0)");
                            $parameters_level_2 = parent::getResultArray();

                            foreach($parameters_level_2['result'] AS $param){
                                $multilevel_Params = array("id" => $param['id'], "name" => $param['name']);
                                array_push($inputJson[$fieldsetIterator]['inputs'][$inputIterator]['level_2_parameters'], $multilevel_Params);
                            }


                            parent::setQuery("  SELECT id,name
                                                FROM `$input[table_name]`
                                                WHERE actived = 1 AND parent_id IN (SELECT	id
                                                                                    FROM `$input[table_name]`
                                                                                    WHERE actived = 1 AND parent_id IN (SELECT id FROM `$input[table_name]` WHERE actived = 1 AND parent_id = 0))");
                            $parameters_level_3 = parent::getResultArray();

                            foreach($parameters_level_3['result'] AS $param){
                                $multilevel_Params = array("id" => $param['id'], "name" => $param['name']);
                                array_push($inputJson[$fieldsetIterator]['inputs'][$inputIterator]['level_3_parameters'], $multilevel_Params);
                            }

                            if(!empty($jsonData['additionalLevel'])){
                                $additionalIterator = 0;
                                
                                foreach($jsonData['additionalLevel'] AS $key => $addInput){
                                    $select_count = 1;
                                    array_push($inputJson[$fieldsetIterator]['inputs'][$inputIterator]['additionals'], array("input_key" => $input['key'], "input_number" => $key));
                                    foreach($addInput AS $key => $add){
                                        $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['additionals'][$additionalIterator]["level_value_".$select_count] = $add;
                                        $select_count++;
                                    }
                                    $additionalIterator++;
                                }
                                
                            }



                        }
                        else{
                            
                            if($key_cutted[0] == 'telefoni___987'){

                                $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['input_value'] = empty($phoneData) ? '' : $phoneData;

                            }else{
                                $inputJson[$fieldsetIterator]['inputs'][$inputIterator]['input_value'] = empty($jsonData['inputs'][$key_cutted[0]]) ? '' : $jsonData['inputs'][$key_cutted[0]];
                            }

                            
                        }


                        $inputIterator++;
                    }
                    $inputJson[$fieldsetIterator]['multible'] = $field['multiData'] == 1;
                    if($field['multiData']){
                        array_push($inputJson[$fieldsetIterator]['inputs'], array("multipleButtons" => array("buttonAdd" => true, "buttonAdditionalInputs" => true, "buttonDocuments" => true, "buttonContacts" => true)));
                    }
                }

            }
            
            
            $fieldsetIterator++;
        }

        return $inputJson;

    }
    public function saveParam(){
        $id         = $_REQUEST['input_id'];
        $selector   = $_REQUEST['selector_id'];
        $param_name = $_REQUEST['param_name'];
        $user_id    = $_SESSION['USERID'];
        if($id != ''){
            parent::setQuery("  SELECT  processing_setting_detail.table_name,
                                        processing_setting_detail.key AS 'input_key',
                                        processing_fieldset.key AS 'field_key'
                                FROM    processing_setting_detail
                                LEFT JOIN processing_fieldset ON processing_setting_detail.processing_fieldset_id = processing_fieldset.id
                                WHERE   processing_setting_detail.id = $id");
            $res = parent::getResultArray();
            $tableName = $res['result'][0]['table_name'];
            if($tableName == '')
            {
                $new_tableName = 'dir_'.$res['result'][0]['field_key'].'_'.$res['result'][0]['input_key'];

                //echo $new_tableName;

                parent::setQuery("CREATE TABLE `$new_tableName` (
                                            id INT(11) AUTO_INCREMENT NOT NULL PRIMARY KEY,
                                            user_id INT(4),
                                            datetime datetime,
                                            name VARCHAR(70),
                                            actived INT(2) DEFAULT 1
                                            )");
                parent::execQuery();
                parent::setQuery("INSERT INTO `$new_tableName` (`user_id`,`datetime`,`name`) VALUES('$user_id',NOW(),'$param_name')");
                parent::execQuery();
                parent::setQuery("UPDATE processing_setting_detail SET table_name='$new_tableName' WHERE id = '$id'");
                parent::execQuery();
                $data['response'] = 'suc';
            }
            else{
                if($selector != ''){
                    
                    parent::setQuery("UPDATE $tableName SET name = '$param_name' WHERE id = '$selector' ");
                    $answer = parent::execQuery();
                    if($answer){
                        $data['response'] = 'suc';
                    }
                }
                else{
                    parent::setQuery("INSERT INTO $tableName (`user_id`,`datetime`,`name`) VALUES('$user_id',NOW(),'$param_name')");
                    $answer = parent::execQuery();
                    
                    $data['response'] = 'suc';
                }
            }
            $data['input_id'] = '';
        }
        else{
            $field_id = $_REQUEST['field_id'];
            parent::setQuery(" SELECT `key`
                            FROM processing_fieldset
                            WHERE id = '$field_id'");
            $res = parent::getResultArray();
            parent::setQuery("INSERT INTO processing_setting_detail 
                                            (`user_id`,`datetime`,`name`,`processing_fieldset_id`) VALUES ('$user_id',NOW(),'temporary_name_sys','$field_id')");
            parent::execQuery();

            $increment = parent::getIncrement('processing_setting_detail');
            $increment = $increment - 1;
            $new_tableName = 'dir_'.$res['result'][0]['key'].'_newinputID_'.$increment;



            parent::setQuery("UPDATE processing_setting_detail SET table_name='$new_tableName' WHERE id='$increment'");
            parent::execQuery();



            parent::setQuery("CREATE TABLE `$new_tableName` (
                                        id INT(11) AUTO_INCREMENT NOT NULL PRIMARY KEY,
                                        user_id INT(4),
                                        datetime datetime,
                                        name VARCHAR(70),
                                        actived INT(2) DEFAULT 1
                                        )");
            parent::execQuery();
            parent::setQuery("INSERT INTO `$new_tableName` (`user_id`,`datetime`,`name`) VALUES('$user_id',NOW(),'$param_name')");
            $answer = parent::execQuery();

            $data['response'] = 'suc';
            $data['input_id'] = $increment;
        }

        return $data;
    }
    public function getSelectorParam(){
        $id         = $_REQUEST['input_id'];
        $selector   = $_REQUEST['selector_id'];
        parent::setQuery("  SELECT  table_name
                            FROM    processing_setting_detail
                            WHERE   id = '$id'");
        $res =parent::getResultArray();
        $tableName = $res['result'][0]['table_name'];
        parent::setQuery("  SELECT  name
                            FROM    $tableName
                            WHERE   id = '$selector'");
        $res = parent::getResultArray();
        $val['param'] = $res['result'][0]['name'];

        return $val;

    }
    public function saveInput(){
        $id            = $_REQUEST['input_id'];
        $field_id      = $_REQUEST['field_id'];
        $name          = $_REQUEST['input_name'];
        $type          = $_REQUEST['input_type'];
        $position      = $_REQUEST['input_pos'];
        $key           = $_REQUEST['key'];
        $depth         = $_REQUEST['multilevel_deep'];
        $name_1        = $_REQUEST['name_1'];
        $name_2        = $_REQUEST['name_2'];
        $name_3        = $_REQUEST['name_3'];
        $nec           = $_REQUEST['nec'];
        $input_tab     = $_REQUEST['input_tab'];
        $user_id       = $_SESSION['USERID'];
        if($id != ''){

            parent::setQuery(" SELECT COUNT(*) AS 'cc'
                            FROM processing_setting_detail
                            WHERE id != '$id' AND `name` = '$name' AND processing_fieldset_id = '$field_id' AND tab_id='$input_tab' AND actived=1");
            $key_count = parent::getResultArray();
            if($key_count['result'][0]['cc'] == 0){
                parent::setQuery("UPDATE processing_setting_detail SET name = '$name', necessary_input='$nec', processing_field_type_id = '$type', position='$position', multilevel_1='$name_1', multilevel_2='$name_2', multilevel_3='$name_3', tab_id='$input_tab',depth='$depth' WHERE id='$id'");
                $res = parent::execQuery();
                if($res){
                    $data['response'] = 'suc';
                }
            }
            else{
                $data['response'] = 'not_uniq';
            }
            
        }
        else{
            if($type == 11 or $type == 12){
                $key = rand(100,9999).rand(1234,8975);
            }
            parent::setQuery(" SELECT  COUNT(*) AS 'cc'
                            FROM    processing_setting_detail
                            WHERE   `key` = '$key' AND tab_id = '$input_tab' AND processing_fieldset_id = '$field_id' AND actived = 1");
            $key_count = parent::getResultArray();
            parent::setQuery(" SELECT  COUNT(*) AS 'cc'
                            FROM    processing_setting_detail
                            WHERE   processing_fieldset_id = '$field_id' AND tab_id = '$input_tab' AND position = '$position'");
            $count = parent::getResultArray();
            if($count['result'][0]['cc'] > 0){
                if($key_count['result'][0]['cc'] == 0){
                    parent::setQuery("UPDATE processing_setting_detail SET position = position + 1 WHERE processing_fieldset_id = '$field_id' AND position >= '$position' AND actived = 1 ORDER BY position DESC");
                    parent::execQuery();
                    $key = $key . '___' .rand(100,999);
                    parent::setQuery("INSERT INTO processing_setting_detail 
                                            (`user_id`,`datetime`,`name`,`processing_fieldset_id`,`processing_field_type_id`,`position`,`key`,`multilevel_1`,`multilevel_2`,`multilevel_3`,`depth`,`necessary_input`,`tab_id`) VALUES ('$user_id',NOW(),'$name','$field_id','$type','$position','$key','$name_1','$name_2','$name_3','$depth','$nec','$input_tab')");
                    parent::execQuery();
    
                    $data['response'] = 'suc';
                }
                else{
                    $data['response'] = 'not_uniq';
                }
                
            }
            else{
                
                if($key_count['result'][0]['cc'] == 0){
                    $key = $key . '___' .rand(100,999);
                    parent::setQuery("INSERT INTO processing_setting_detail 
                                            (`user_id`,`datetime`,`name`,`processing_fieldset_id`,`processing_field_type_id`,`position`,`key`,`multilevel_1`,`multilevel_2`,`multilevel_3`,`depth`,`necessary_input`,`tab_id`) VALUES ('$user_id',NOW(),'$name','$field_id','$type','$position','$key','$name_1','$name_2','$name_3','$depth','$nec','$input_tab')");
                    parent::execQuery();
                    $data['response'] = 'suc';
                }
                else{
                    $data['response'] = 'not_uniq';
                }
                
            }
            //$db->setQuery("INSERT INTO processing_setting_detail (`user_id`,`datetime`,`name`,`processing_fieldset_id`,`processing_field_type_id`,`position`) VALUES (1,2,3)");
        }
        return $data;
    }



    public function GetDirData(){
        $this->colCount = $_REQUEST['count'];
        $this->cols     = $_REQUEST['cols'];
        $id             = $_REQUEST['input_id'];
        parent::setQuery(" SELECT  table_name
                        FROM    processing_setting_detail
                        WHERE   id = '$id'");
        $table = parent::getResultArray();
        $tableName = $table['result'][0]['table_name'];

        parent::setQuery(" SELECT  id,
                                name
                        FROM    $tableName
                        WHERE   actived = '1'");
        $result = parent::getKendoList($this->colCount, $this->cols);

        return $result;
    }
    public function getInputEdit(){
        $input_id = $_REQUEST['id'];

        parent::setQuery("  SELECT  id,
                                    name,
                                    processing_field_type_id AS field_type,
                                    necessary_input AS nec,
                                    tab_id,
                                    position,
                                    `key`
                            FROM    processing_setting_detail
                            WHERE   actived = 1 AND processing_setting_detail.id = '$input_id'");
        $inputData = parent::getResultArray();


        return $inputData['result'][0];
    }
    public function GetNecInput(){
        $data = array();

        array_push($data, array('id' => 0,'name' => 'არა'));
        array_push($data, array('id' => 1,'name' => 'კი'));

        return $data;
    }
    public function GetInputTypes(){
        parent::setQuery("  SELECT  id, name
                            FROM    processing_field_type
                            WHERE   actived = 1");
        $result = parent::getResultArray();

        return $result['result'];
    }

    public function GetFieldTabs(){
        parent::setQuery("  SELECT  id, name
                            FROM    processing_input_tabs
                            WHERE   actived = 1");
        $result = parent::getResultArray();

        return $result['result'];
    }

    public function removeData(){
        $input_id = $_REQUEST['input_id'];
        $ids = $_REQUEST['ids'];
        $type = $_REQUEST['type'];

        switch($type){
            case 'param':
                parent::setQuery("  SELECT table_name
                                    FROM processing_setting_detail
                                    WHERE id = '$input_id'");
                $table_name = parent::getResultArray();
                $table_name = $table_name['result'][0]['table_name'];

                foreach($ids AS $id){
                    parent::setQuery("UPDATE $table_name SET actived = 0 WHERE id = '$id'");
                    parent::execQuery();
                }
                $data['status'] = 'success';
                break;
            case 'input':
                foreach($ids AS $id){
                    parent::setQuery("UPDATE processing_setting_detail SET actived = 0 WHERE id = '$id'");
                    parent::execQuery();
                }
                $data['status'] = 'success';
                break;
        }

        return $data;

    }

    public function saveModalData(){

        $pageKey    = $_REQUEST['pageKey'];
        $rowID      = $_REQUEST['id'];
        $user_id    = $_SESSION['USERID'];
 
        $savingDataArray = array();

        $savingDataArray['inputs'] = array();
        $savingDataArray['selectors'] = array();
        $savingDataArray['multilevel'] = array();
        $savingDataArray['additionalLevel'] = array();

        foreach($_REQUEST AS $key => $value){

            if($key == 'route' OR $key == 'act' OR $key == 'pageKey' OR $key == 'id'){
                continue;
            }
            else{
                $tempKey = explode('--', $key); //Exploding key for input parameters
                $inputID = $tempKey[1]; //Input ID in DB
                $inputTypeID = $tempKey[2]; //Getting input type id

                switch ($inputTypeID){
                    case 1: //Text input
                        $savingDataArray['inputs'][$tempKey[0]] = $value;
                        break;
                    case 2: //TextArea
                        $savingDataArray['inputs'][$tempKey[0]] = $value;
                        break; 
                    case 4://Date
                    
                        break;
                    case 5://Datetime
                
                        break;
                    case 6://Radio
                
                        break;
                    case 7://Checkbox
                
                        break;
                    case 8://Selector
                        if($tempKey[3] != ''){
                            $savingDataArray['additionalLevel'][$tempKey[3]][$key] = $value;
                        }
                        else{
                            $savingDataArray['selectors'][$tempKey[0]] = $value;
                        }
                        
                        break;
                    case 10://Multilevel selector
                        if($tempKey[4] != ''){
                            $savingDataArray['additionalLevel'][$tempKey[4]][$tempKey[0].'___'.$tempKey[3].'___'.$tempKey[4]] = $value;
                        }
                        else{
                            $savingDataArray['multilevel'][$tempKey[0].'___'.$tempKey[3]] = $value;
                        }
                        
                        break;
                    
                }
            }
            
        }


        parent::setQuery("  SELECT  COUNT(*) AS cc,
                                    (SELECT id FROM processing_page WHERE `key` = '$pageKey') AS page_id
                            FROM    processing
                            WHERE   row_id = '$rowID'");
        $isAlreadySaved = parent::getResultArray();
        $pageID         = $isAlreadySaved['result'][0]['page_id'];
        $isAlreadySaved = $isAlreadySaved['result'][0]['cc'];
        


        $tempData = json_encode($savingDataArray, JSON_UNESCAPED_UNICODE );


        if($isAlreadySaved == 0){
            parent::setQuery("INSERT INTO processing SET    datetime = NOW(),
                                                            processing_page_id = '$pageID',
                                                            row_id = '$rowID',
                                                            `value` = '$tempData'");
        }
        else{
            parent::setQuery("UPDATE processing SET `value` = '$tempData' WHERE row_id = '$rowID'");
        }
        parent::execQuery();


        //save operator id 
        parent::setQuery("  UPDATE incomming_request 
                            SET      `user_id` = '$user_id',
                                     `status` = '1' 
                            WHERE id = '$rowID'");
        parent::execQuery();


        return array("message" => "OK");
        
        //return $savingDataArray;

    }


}

?>