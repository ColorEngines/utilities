<?php

class ImageUtil {

    public function Animate($filenames, $output_path = null)
    {
        
        if (is_null($output_path)) $output_path = file::random_filename().".gif";
        
        $cmd  = "convert -delay 50 -dispose Background " . "'".join("' '",$filenames)."'"." '{$outputFilename}'" ;
        exec($cmd);
        
        if (!file_exists($output_path)) return null;
        return $output_path;
        
    }
   
    
    
    public static function getGoogleImageURLs($k) {
    
	$url = "http://images.google.it/images?as_q=##query##&hl=it&imgtbs=z&btnG=Cerca+con+Google&as_epq=&as_oq=&as_eq=&imgtype=&imgsz=m&imgw=&imgh=&imgar=&as_filetype=&imgc=&as_sitesearch=&as_rights=&safe=images&as_st=y";

        $web_page = file_get_contents( str_replace("##query##",urlencode($k), $url ));
 
        $web_page = str_replace('?imgurl=', "\n?imgurl=", $web_page);
        $web_page = str_replace('imgrefurl=', "\nimgrefurl=", $web_page);
        
        $lines = array_util::Replace(array_util::Replace(array_util::ElementsThatContain(explode("\n",$web_page), '?imgurl'), '&amp;', ''),'?imgurl=','');
        
        
	return $lines;
    }
    
    
    
    public static function calculateTextBox($text,$fontFile = null ,$fontSize = 200,$fontAngle = 0) { 
        /************ 
        simple function that calculates the *exact* bounding box (single pixel precision). 
        The function returns an associative array with these keys: 
        left, top:  coordinates you will pass to imagettftext 
        width, height: dimension of the image you have to create 
        *************/ 
        
        if (is_null($fontFile))  $fontFile = '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf';
        
        
        $rect = imagettfbbox($fontSize,$fontAngle,$fontFile,$text); 
        $minX = min(array($rect[0],$rect[2],$rect[4],$rect[6])); 
        $maxX = max(array($rect[0],$rect[2],$rect[4],$rect[6])); 
        $minY = min(array($rect[1],$rect[3],$rect[5],$rect[7])); 
        $maxY = max(array($rect[1],$rect[3],$rect[5],$rect[7])); 

        return array( 
         "left"   => abs($minX) - 1, 
         "top"    => abs($minY) - 1, 
         "width"  => $maxX - $minX, 
         "height" => $maxY - $minY, 
         "box"    => $rect 
        ); 
    } 

    public static function textFromImages
    (
         $text_string = 'HELLO WORLD'
        ,$font_size    = 600
        ,$images_to_use = null
        ,$font_ttf     = '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf'
    ) 
    {

        $text_angle   = 0; 
        $text_padding = 10; 
        
        
        $dest_width = 60;
        $dest_height = 60;
        
        if (is_null($images_to_use))
        {
            $images_to_use = array();
            $images_to_use[] = 'running1.jpg';            
        }
        
        
        $the_box        = self::calculateTextBox($text_string, $font_ttf, $font_size, $text_angle); 

        $imgWidth     = $the_box["width"] + $text_padding + ($dest_width * 2); 
        $imgHeight    = $the_box["height"] + $text_padding + ($dest_height * 2); 
        
        
        $image_for_text = imagecreatetruecolor($imgWidth,$imgHeight); 
        imagefill($image_for_text,0,0, imagecolorallocate($image_for_text,200,200,200)); 
        imagecolortransparent($image_for_text, imagecolorallocate($image_for_text,200,200,200));

        $image_for_graphics = imagecreatetruecolor($imgWidth,$imgHeight); 
        imagefill($image_for_graphics,0,0, imagecolorallocate($image_for_text,200,200,200)); 
        imagecolortransparent($image_for_graphics, imagecolorallocate($image_for_text,200,200,200));
        

        $color = imagecolorallocate($image_for_text,0,0,0); 

        imagettftext($image_for_text, 
            $font_size, 
            $text_angle, 
            $the_box["left"] + ($imgWidth / 2) - ($the_box["width"] / 2), 
            $the_box["top"] + ($imgHeight / 2) - ($the_box["height"] / 2), 
            $color, 
            $font_ttf, 
            $text_string); 

        
        $src_images = array();
        $count = 0;
        foreach ($images_to_use as $value) 
        {
            $tmp_img = self::load($value);
            if (is_null($tmp_img))  continue;
            if ($tmp_img === FALSE) continue;
            
            $src_images[$count] = array();
            $src_images[$count]['src'] = $tmp_img;
            list($src_images[$count]['width'],$src_images[$count]['height']) = getimagesize($value);
            
            $count++;
            
        }
        
        
        $count = 0;
        for ($xindex = 0; $xindex < $imgWidth; $xindex++) 
        {

            for ($yindex = 0; $yindex < $imgHeight; $yindex ++) 
            {

                $rgb = imagecolorat($image_for_text, $xindex, $yindex);
                $pixel_color = imagecolorsforindex($image_for_text, $rgb);        

                if ($pixel_color['red'] == 0 && $pixel_color['green'] == 0 && $pixel_color['blue'] == 0)
                {
                    imagecopyresized($image_for_graphics,$src_images[$count]['src'], $xindex, $yindex, 0, 0, $dest_width, $dest_height , $src_images[$count]['width'], $src_images[$count]['height']);
                    $yindex += $dest_height;
                    $count++;
                    if($count >= count($src_images)) $count = 0;
                    
                }

            }

           $xindex += $dest_width;
        }    

        unset($src_images);
        
        return  $image_for_graphics;
        
        
    }

//    public static function ImprintImageWithText
//    (
//         $image_filename = '/home/afakes/Documents/code/tark/FreeFunkyWords.com/one/image2text/Art/image_11.jpg'
//        ,$text_string = 'HELLO WORLD'
//        ,$font_size    = 600
//        ,$images_to_use = null
//        ,$font_ttf     = '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf'
//    ) 
//    {
//
//        $text_angle   = 0; 
//        $text_padding = 10; 
//        
//        $dest_width = 60;
//        $dest_height = 60;
//        
//        if (is_null($images_to_use))
//        {
//            $images_to_use = array();
//            $images_to_use[] = 'running1.jpg';            
//        }
//        
//        
//        $the_box        = self::calculateTextBox($text_string, $font_ttf, $font_size, $text_angle); 
//
//        $imgWidth     = $the_box["width"] + $text_padding + ($dest_width * 2); 
//        $imgHeight    = $the_box["height"] + $text_padding + ($dest_height * 2); 
//        
//        
//        
//        $image_for_text = imagecreatetruecolor($imgWidth,$imgHeight); 
//        imagefill($image_for_text,0,0, imagecolorallocate($image_for_text,200,200,200)); 
//        imagecolortransparent($image_for_text, imagecolorallocate($image_for_text,200,200,200));
//        
//        $image_for_graphics = imagecreatetruecolor($imgWidth,$imgHeight); 
//        imagefill($image_for_graphics,0,0, imagecolorallocate($image_for_text,200,200,200)); 
//        imagecolortransparent($image_for_graphics, imagecolorallocate($image_for_text,200,200,200));
//        
//
//        $color = imagecolorallocate($image_for_text,0,0,0); 
//
//        imagettftext($image_for_text, 
//            $font_size, 
//            $text_angle, 
//            $the_box["left"] + ($imgWidth / 2) - ($the_box["width"] / 2), 
//            $the_box["top"] + ($imgHeight / 2) - ($the_box["height"] / 2), 
//            $color, 
//            $font_ttf, 
//            $text_string); 
//
//        
//        $src_images = array();
//        $count = 0;
//        foreach ($images_to_use as $value) 
//        {
//            $tmp_img = self::load($value);
//            if (is_null($tmp_img))  continue;
//            if ($tmp_img === FALSE) continue;
//            
//            $src_images[$count] = array();
//            $src_images[$count]['src'] = $tmp_img;
//            list($src_images[$count]['width'],$src_images[$count]['height']) = getimagesize($value);
//            
//            $count++;
//            
//        }
//        
//        
//        $count = 0;
//        for ($xindex = 0; $xindex < $imgWidth; $xindex++) 
//        {
//
//            for ($yindex = 0; $yindex < $imgHeight; $yindex ++) 
//            {
//
//                $rgb = imagecolorat($image_for_text, $xindex, $yindex);
//                $pixel_color = imagecolorsforindex($image_for_text, $rgb);        
//
//                if ($pixel_color['red'] == 0 && $pixel_color['green'] == 0 && $pixel_color['blue'] == 0)
//                {
//                    imagecopyresized($image_for_graphics,$src_images[$count]['src'], $xindex, $yindex, 0, 0, $dest_width, $dest_height , $src_images[$count]['width'], $src_images[$count]['height']);
//                    $yindex += $dest_height;
//                    $count++;
//                    if($count >= count($src_images)) $count = 0;
//                    
//                }
//
//            }
//
//           $xindex += $dest_width;
//        }    
//
//        unset($src_images);
//        
//        return  $image_for_graphics;
//        
//        
//    }
//    
    
    
    
    public static function downloadImageFrom($src,$dest_folder = null) 
    {
        
        if (is_null($dest_folder)) $dest_folder = file::random_filename();
        
        $dir = file::mkdir_safe($dest_folder);
        
        if (is_null($dir)) exit();

        $files = file::folder_files($dest_folder,'/',true);
        
        $result = array();
        
        if (count($files) < 3) 
        {
            $sub_result = array();
            if (!is_array($src))
            {
                $sub_result_filename = "{$dest_folder}/image_0.".substr(util::fromLastChar($src, '.'),0,3);
                if (!file_exists($sub_result_filename)) file::wget($src, $sub_result_filename);
            }
            else
            {
                $count = 0;
                foreach ($src as $value) 
                {
                    $sub_result_filename = "{$dest_folder}/image_{$count}.".substr(util::fromLastChar($value, '.'),0,3);
                    $sub_result[$count] = $sub_result_filename;
                    $count++;
                    
                    if (file_exists($sub_result_filename)) continue;
                    file::wget($value, $sub_result_filename);
                }
            }
            
            $files = file::folder_files($dest_folder,'/',true); // re-get files from folder
            
        }

        
        foreach ($files as $filename => $file_path) 
        {
            if (filesize($file_path) < 1000) 
            {
                file::Delete($file_path);
                continue;
            }
                
            if (!self::isKnownType($file_path))
            {
                file::Delete($file_path);
                continue;
            }
            
            $result[$filename] = $file_path;
            
        }
        
        return $result;
        
    }
    
    public static function getImageWidth($filename) 
    {
        $image_info = getimagesize($filename);    
        return  $image_info[0];
    }
    
    public static function getImageHeight($filename) 
    {
        $image_info = getimagesize($filename);    
        return  $image_info[0];
    }
    
    
    public static function getImageType($filename) 
    {
        $image_info = getimagesize($filename);    
        return  $image_info[2];
    }
    
    
    public static function isKnownType($filename) 
    {
        if (!file_exists($filename)) return false;
        
        $image_info = getimagesize($filename);    
        if ($image_info === FALSE) return false;
        
        $image_type = $image_info[2];

        switch ($image_type) {
            case IMAGETYPE_JPEG:
                return true;
                break;

            case IMAGETYPE_GIF:
                return true;
                break;

            case IMAGETYPE_PNG:
                return true;
                break;

        }
      
        return false;
      
   }    
    
    
    
    public static function load($filename) 
    {
        
        if (!file_exists($filename)) return null;
        if (filesize($filename) <= 1000) return null;  // file less than 1000 bytes discard
        
        $image_info = getimagesize($filename);    
        if ($image_info === FALSE) return null;
        
        $image_type = $image_info[2];

        switch ($image_type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filename);
                break;

            case IMAGETYPE_GIF:
                return imagecreatefromgif($filename);
                break;

            case IMAGETYPE_PNG:
                return imagecreatefrompng($filename);
                break;

        }
      
        return null;
      
   }    
    
   
   public static function save($image,$filename, $image_type = IMAGETYPE_PNG) 
   {
      switch ($image_type) {
          case IMAGETYPE_JPEG:
              return imagejpeg($image,$filename,100);
              break;
          
          case IMAGETYPE_GIF:
              return imagegif($image,$filename);
              break;

          case IMAGETYPE_PNG:
              return imagepng($image,$filename);
              break;

      }

   }   
    
   
   
   public static function output($image,$image_type = IMAGETYPE_PNG) 
    {

      switch ($image_type) {
          case IMAGETYPE_JPEG:
              imagejpeg($image);
              break;
          
          case IMAGETYPE_GIF:
              imagegif($image);
              break;

          case IMAGETYPE_PNG:
              imagepng($image);
              break;

      }

   }   

   
   public static function getWidth($image) {
      return intval(imagesx($image));
   }
   
   
   public static function getHeight($image) {
 
      return intval(imagesy($image));
   }
   
   public static function resizeToHeight($image,$height) {
 
      $ratio = $height / self::getHeight($image);
      $width = self::getWidth($image) * $ratio;
      return self::resize($image,$width,$height);
   }
 
   public static function resizeToWidth($image,$width) {
      $ratio = $width / self::getWidth($image);
      $height = self::getheight($image) * $ratio;
      return self::resize($image,$width,$height);
   }
 
   public static function scale($image,$scale) {
      $width = self::getWidth($image) * $scale/100;
      $height = self::getheight($image) * $scale/100;
      
      return self::resize($image,$width,$height);
   }
 
   public static function resize($image,$width,$height,$trans_color = null) {
      $new_image = imagecreatetruecolor($width, $height);
      
      error_log(__METHOD__." width = $width, height = $height");

      if (!is_null($trans_color))
      {
        imagefill($new_image,0,0, $trans_color); 
        imagecolortransparent($new_image, $trans_color);          
      }
      
      imagecopyresampled($new_image, $image, 0, 0, 0, 0, $width, $height, self::getWidth($image), self::getHeight($image));
      return $new_image;
   }      
    
   
   public static function IM_resize($src,$percent = 50,$dest = null,$remove_original = false)
   {
       
        if (is_null($dest) ) $dest = str_replace ('.', "_{$percent}.", $src);
        exec("convert '{$src}' -resize {$percent}% '{$dest}'");

        if (!file_exists($dest))  return null;
        
        if ($remove_original) file::Delete($src);
        
        return $dest;
            
   }
   
   
    
}

?>
