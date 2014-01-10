<?php
include_once 'includes.php';

class ImageUtil {
    
    
    public function color_grid_from_jpg($filename)
    {
        
        $height = 0;
        $width = 0;
        
        list($height,$width) = getimagesize($filename);
        
        
        
        
        $im = imagecreatefromjpeg("$filename");
        
        $rgb = imagecolorat($im, 10, 15);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        var_dump($r, $g, $b);        

    }
    

    public function Animate($filenames, $output_path = null)
    {
        
        if (is_null($output_path)) $output_path = file::random_filename().".gif";
        
        $cmd  = "convert -delay 50 -dispose Background " . "'".join("' '",$filenames)."'"." '{$outputFilename}'" ;
        exec($cmd);
        
        if (!file_exists($output_path)) return null;
        return $output_path;
        
    }
   
    
    
    
}
?>
