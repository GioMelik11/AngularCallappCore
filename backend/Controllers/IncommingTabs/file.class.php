<?php

namespace Controllers\IncommingTabs;

use Routers\dbClass;

class file extends dbClass
{

    public function GET($colCount, $cols, $inc_id)
    {

        parent::setQuery("  SELECT	`id`,
                                    `datetime`,
                                    `original_name`

                            FROM    upload_files
                            WHERE 	actived = 1 AND row_id = '$inc_id' AND route = 'IncommingTabs'
                            ORDER BY id DESC
                            LIMIT 200");

        $callList = parent::getKendoList($colCount, $cols);

        return $callList;
    }

    public function ADD($files, $inc_id,$route)
    {
        $user_id = $_SESSION['USERID'];
        $data = [];


        $foldername = date("Y-m-d");

        if($route == "voice"){
            $foldername = "asterisk-records";
        }

        if (!file_exists('Uploads/' . $foldername . '')) {
            mkdir('Uploads/' . $foldername . '', 0777, true);
        }
        $uploadTo = "Uploads/" . $foldername . "/";

        foreach ($files as $file) {
            // $allowImageExt = array('jpg', 'png', 'jpeg', 'gif');
            $imageName = $file['name'];
            $tempPath = $file['tmp_name'];
            // $imageQuality = 60;
            // $originalPath = $uploadTo . basename($imageName);
            // $imageExt = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));

            $original_name = $imageName;
            $hashed_name = md5_file($tempPath);
            $type = end((explode(".", $imageName)));
            $new = $hashed_name . "." . $type;
            $pathto = $uploadTo . $new;

            if (empty($imageName)) {
                $error = "Please Select files..";
                return $error;
            } else {

                move_uploaded_file($tempPath, $pathto);
                // if (in_array($imageExt, $allowImageExt)) {

                // }
            }
            parent::setQuery(" INSERT INTO `upload_files`
                                        (`user_id`, `datetime`, `hash`, `original_name`, `type`, `route`, `row_id`)
                                    VALUES
                                        ('$user_id', NOW(), '$hashed_name', '$original_name', '$type', '$route', '$inc_id')");
            parent::execQuery();
            $lastId = parent::getLastId();

            array_push($data, array(
                "id"        => $lastId,
                "location"  => $uploadTo,
                "hashed"    => $hashed_name,
                "original"  => $original_name,
                "full_name" => $new,
                "type"      => $type,
                "link"      =>  $uploadTo . $new
            ));
        }


        return array("status" => "OK", $data, $files);
    }

    // function compress_image($tempPath, $originalPath, $quality)
    // {
    //     $imgInfo = getimagesize($tempPath);
    //     $mime = $imgInfo['mime'];
    //     strtolower($mime);

    //     switch ($mime) {
    //         case 'image/jpeg':
    //             $image = imagecreatefromjpeg($tempPath);
    //             break;
    //         case 'image/png':
    //             $image = imagecreatefrompng($tempPath);
    //             break;
    //         case 'image/gif':
    //             $image = imagecreatefromgif($tempPath);
    //             break;
    //         default:
    //             $image = imagecreatefromjpeg($tempPath);
    //     }

    //     imagejpeg($image, $originalPath, $quality);
    //     return $originalPath;
    // }
}
