<?php
class grass_output
{
    
    private $GRASS = null;
    private $debug = false;
    
    private $setup_commands = array();
    private $raster_commands = array();
    private $vector_commands = array();
    private $text_commands   = array();
    
     public static function connect($GRASS)
     {
         $GO = new grass_output($GRASS);
         return $GO;
     }
    
     public function __destruct()
     {
        unset($this->setup_commands);
        unset($this->raster_commands);
        unset($this->vector_commands);
        unset($this->text_commands);
     }
     
    
    public function __construct(GRASS $GRASS)
    {        
        $this->GRASS = $GRASS;
        $this->debug = $GRASS->debug;
               
        $this->setup_commands = array();
        $this->raster_commands = array();
        $this->vector_commands = array();
        $this->text_commands   = array();
        
        if ($this->debug) echo "init: grass_output\n";
    }
    
    public function Monitor()
    {
        if (func_num_args() == 0 ) return $this->monitor;
        $this->monitor = func_get_arg(0);
    }
    private $monitor = "PNG";
    
    public function AddPolygonLines($name = null, $width = 2, $color = "black")
    {        
        if (is_null($name)) return null;
        
        $cmd = "d.vect map={$name} type=boundary width=$width color=$color";
        
        if ($this->debug)  echo "Add polygon $cmd\n";
        
        $this->vector_commands[] = $cmd;
        return $cmd;
    }

    public function AddPolygonArea($name = null, $width = 2, $color = "black",$fill_color = "orange")
    {        
        if (is_null($name)) return null;
        
        $cmd = "d.vect map={$name} type=area width=$width color=$color fcolor=$fill_color";
        
        if ($this->debug)  echo "Add polygon $cmd\n";
        
        $this->vector_commands[] = $cmd;
        return $cmd;
    }    
    
    public function AddPolygon($name = null, $width = 2, $color = "black")
    {        
        if (is_null($name)) return null;
        
        $cmd = "d.vect map={$name} type=boundary width=$width color=$color";
        
        if ($this->debug)  echo "Add polygon $cmd\n";
        
        $this->vector_commands[] = $cmd;
        return $cmd;
    }
    
    
    public function AddPoints($name, $size = 4, $color = "red", $fill_color = null, $icon = null )
    {        
        if (is_null($name)) return null;                
        if (is_null($icon)) $icon = "basic/circle";        
        if (is_null($fill_color)) $fill_color = $color;
        if (is_null($size)) $size = 4;
        
        
        $cmd = "d.vect map={$name} icon={$icon} size=$size color=$color fcolor=$fill_color";
        
        if ($this->debug)  echo "Add points $cmd\n";
        
        $this->vector_commands[] = $cmd;
        return $cmd;
    }

    public function AddRaster($name = null)
    {        
        if (is_null($name)) return null;
        $cmd = "d.rast map={$name}";
        
        if ($this->debug)  echo "Add rast $cmd\n";
        
        $this->raster_commands[] = $cmd;
        return $cmd;
    }
    
    public function AddRasterLegend($name = null)
    {        
        $this->rasterLegendName  = $name;
    }
    private $rasterLegendName = null;
    
    public function AddText($text, $point_size = 16,$color = "black" ,$from_left = 10, $from_top = 10,  $rotation = 0)
    {        
        if (is_null($text)) return null;
        
        if (is_array($text))
        {
            $line_count = 0;
            foreach ($text as $key => $text_line)
            {
                $to_write = (is_numeric($key)) ? $text_line : "$key: $text_line";
                $to_write = str_replace("-", " ", $to_write);
                
                $adjusted_from_top = $from_top + ($line_count * ($point_size * 1.8));
                $cmd = "d.text.freetype  path='/usr/share/fonts/truetype/ttf-dejavu/DejaVuSansMono.ttf' -p -s 'text={$to_write}' at=$from_left,{$adjusted_from_top} color=$color size={$point_size} align=ul rotation={$rotation} linespacing=1.1";
                $this->text_commands[] = $cmd;
                $line_count++;
            }            
        }
        else
        {
            $cmd = "d.text.freetype  path='/usr/share/fonts/truetype/ttf-dejavu/DejaVuSansMono.ttf' -p -s 'text={$text}' at=$from_left,$from_top color=$color size={$point_size} align=ul rotation={$rotation} linespacing=1.1";
            $this->text_commands[] = $cmd;
        }
        
          
        echo "Add text ".print_r($this->text_commands,true);
        
        return $cmd;
    }
    
    
    public function Grid()
    {
        if (func_num_args() == 0 ) return $this->addGrid;
        $this->addGrid = func_get_arg(0);
    }
    private $addGrid = 1;

    
    public function Save($filename = null,$width = 1024, $height=768)
    {
        if (is_null($filename)) return null;

        // clean of extension
        if (util::contains($filename, ".")) $filename = util::toLastChar($filename, ".");
        $filename = $filename .".png";
        
        $cmd = array();
        file::Delete($filename);
        
        $cmd[] = "export GRASS_WIDTH={$width}";
        $cmd[] = "export GRASS_HEIGTH={$height}";
        $cmd[] = "export GRASS_PNGFILE={$filename}";
        $cmd[] = "export GRASS_BACKGROUNDCOLOR=FFFFFF";
        $cmd[] = "export GRASS_TRANSPARENT=TRUE";
        $cmd[] = "export GRASS_TRUECOLOR=TRUE";
        $cmd[] = "export GRASS_PNG_AUTO_WRITE=false";
        $cmd[] = "export GRASS_RENDER_IMMEDIATE=false";
        
        $cmd[] = "d.mon start=PNG";
        //$cmd[] = "d.erase";
        
        foreach ($this->raster_commands as $command) 
                $cmd[] = $command;
        
        foreach ($this->vector_commands as $command) 
                $cmd[] = $command;        
        
        if (!is_null($this->Grid())) 
                $cmd[] = "d.grid -b size={$this->Grid()}";        
        
        if (!is_null($this->rasterLegendName))
                $cmd[] = "d.legend -s map={$this->rasterLegendName}";
        
        foreach ($this->text_commands as $command) 
                $cmd[] = $command;
        
        $cmd[] = "d.mon stop=PNG";
        
        $grass_cmd = join(";\n",$cmd);
        
        if ($this->debug) echo "Save output of Grass monitor\n";
        if ($this->debug) echo "$grass_cmd\n";
        
        $this->GRASS->GRASS_COMMAND($grass_cmd);
        
        if (!file_exists($filename)) return false;
        
        return $filename;
        
    }
    
}

?>