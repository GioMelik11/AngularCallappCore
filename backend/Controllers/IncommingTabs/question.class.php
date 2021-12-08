<?php

namespace Controllers\IncommingTabs;

use Routers\dbClass;

class question extends dbClass{

    public function GET($colCount,$cols){

        parent::setQuery("  SELECT  id,
                                    question,
                                    answer
                            FROM 		faq
                            WHERE 	actived = 1
                            ORDER BY id DESC");

        $callList = parent::getKendoList($colCount, $cols);
        
        return $callList;
    }
 
}