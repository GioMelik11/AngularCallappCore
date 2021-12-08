<?php 
    use Routers\dbClass;
    
    class Task_zeinkals extends dbClass
    {
        public function service_center()
        {
            $where = "";
            
            if(isset($_REQUEST["onlyActives"]) && $_REQUEST["onlyActives"] != "")
                $where = " actived = 1 ";

            if(isset($_REQUEST["id"]) && $_REQUEST["id"] != "")
                $where .= $where == "" ? " id = $_REQUEST[id] " : " AND id = $_REQUEST[id] ";

            parent::setQuery("SELECT `id`, `name` FROM service_center WHERE  $where");
            return parent::getResultArray()["result"];
        } 

    }
    