<?php
include_once 'grass_output.class.php';
class GRASS {
    
     public $debug = false;
     public $debug_grass_command = false;
     
     public static $DATABASE_NAME = "grass";

     public static function connect($debug = false)
     {
         $G = new GRASS($debug);
         return $G;
     }

     public static function connect2database($debug = false)
     {
         
        if (util::contains(util::hostname(),"vmx" ) )
            $DB = new database("information_schema","localhost","afakes","Volts100.");
        else
            $DB = new database("information_schema","localhost","root","good4you");    
         
         // connect grass as well as PHP
                  
         $DB->debug = $debug;
         return $DB;
     }
     
     
     public function __construct($debug = false)
     {
        $this->debug = $debug;
         

        ErrorMessage::Marker("setup .. Connect to Grass Database");

        if (util::contains(util::hostname(),"vmx" ) )
        {
            $cmd = "db.login driver=mysql database=grass user=afakes password=Volts100.";
            $this->GRASS_COMMAND($cmd);
        }
        else
        {
            $cmd = "db.login driver=mysql database=grass user=root password=good4you";
            $this->GRASS_COMMAND($cmd);
        }
         
         ErrorMessage::Marker("Connect to Grass Database");
         $cmd = "db.connect driver=mysql database=grass";
         $this->GRASS_COMMAND($cmd);
         
     }
        
    public function GRASS_COMMAND($cmd)
    {
        $result = array();
        exec("{$cmd}",$result);
        return $result;
    }
     
    public function matrix2vector($src, $vector_name,$value_column_name,$remove_nulls = true,$id_column_name = null,$lat_column_name = null, $lng_column_name = null) 
    {
        $delim = "|";
        $column_names = matrix::ColumnNames($src, true);
        
        //** detect lat long column names and then numbers
        $lat_column_num = null;
        if (is_null($lat_column_name))
        {
            //** find column number 
            $lat_column_num = (!is_null($lat_column_num)) ? $lat_column_num : array_util::ArrayKey($column_names, 'lat');
            $lat_column_num = (!is_null($lat_column_num)) ? $lat_column_num : array_util::ArrayKey($column_names, 'LAT');
            $lat_column_num = (!is_null($lat_column_num)) ? $lat_column_num : array_util::ArrayKey($column_names, 'latitude');        
            $lat_column_num = (!is_null($lat_column_num)) ? $lat_column_num : array_util::ArrayKey($column_names, 'y');
            $lat_column_num = (!is_null($lat_column_num)) ? $lat_column_num : array_util::ArrayKey($column_names, 'Y');
            
            $lat_column_name = $column_names[$lat_column_num];//** find column name
        }
        else
            $lat_column_num = array_util::ArrayKey($column_names, $lat_column_name);//** given column name - get column number
        
        
        $lng_column_num = null;
        if (is_null($lng_column_name))
        {
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'lng');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'lon');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'long');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'longitude');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'LNG');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'LON');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'lLONG');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'LONGITUDE');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'x');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'X');
            
            $lng_column_name = $column_names[$lng_column_num];
        }
        else
            $lng_column_num = array_util::ArrayKey($column_names, $lng_column_name);
        
        // select first column as id column
        if (is_null($id_column_name))  $id_column_name = $column_names[0];
        
        $columns_for_plot = array();
        $columns_for_plot[$id_column_name] = '';
        $columns_for_plot[$lat_column_name] = '';
        $columns_for_plot[$lng_column_name] = '';
        $columns_for_plot[$value_column_name] = '';

        //** if we have to remove null values then check value column value and remove the row if the value column is null        
        $filtered_src = $src;
        if ($remove_nulls) $filtered_src = matrix::RemoveRowsBasedColumnValue($filtered_src, $value_column_name, null);
        
        $filename = matrix::Save($filtered_src, file::random_filename_memory(), $delim, null, array_keys($columns_for_plot));
        
        foreach ($columns_for_plot as $column_name => $value) 
            $grass_column_types[] = $column_name." ".strtolower(matrix::ColumnTypeForDB($src,$column_name));
        
        $grass_column_type_str = join(",",$grass_column_types);
        
        $CMD = "v.in.ascii  input={$filename} output=$vector_name format=point fs='$delim' skip=1 columns='{$grass_column_type_str}'  x=3  y=2  z=4 cat=0 --overwrite";
        
        $load_ascii_result = $this->GRASS_COMMAND($CMD);
        
        if (!$this->hasVector($vector_name))
        {
        	ErrorMessage::Marker("ERROR loading $filename to $vector_name file ...  $filename has been left for diagnostics ".print_r($load_ascii_result,true));
            return null;
        }
        
        file::Delete($filename);        
        unset($filtered_src);
        
        return $vector_name;
        
    }
    
      
    public function query2vector($sql, $lat_column_name, $lng_column_name, $value_column_name, $vector_name) 
    {
        
        $remove_nulls = true;
        
        $delim = "|";
        $column_names = matrix::ColumnNames($src, true);
        
        //** detect lat long column names and then numbers
        $lat_column_num = null;
        if (is_null($lat_column_name))
        {
            //** find column number 
            $lat_column_num = (!is_null($lat_column_num)) ? $lat_column_num : array_util::ArrayKey($column_names, 'lat');
            $lat_column_num = (!is_null($lat_column_num)) ? $lat_column_num : array_util::ArrayKey($column_names, 'LAT');
            $lat_column_num = (!is_null($lat_column_num)) ? $lat_column_num : array_util::ArrayKey($column_names, 'latitude');        
            $lat_column_num = (!is_null($lat_column_num)) ? $lat_column_num : array_util::ArrayKey($column_names, 'y');
            $lat_column_num = (!is_null($lat_column_num)) ? $lat_column_num : array_util::ArrayKey($column_names, 'Y');
            
            $lat_column_name = $column_names[$lat_column_num];//** find column name
        }
        else
            $lat_column_num = array_util::ArrayKey($column_names, $lat_column_name);//** given column name - get column number
        
        
        $lng_column_num = null;
        if (is_null($lng_column_name))
        {
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'lng');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'lon');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'long');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'longitude');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'LNG');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'LON');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'lLONG');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'LONGITUDE');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'x');
            $lng_column_num = (!is_null($lng_column_num)) ? $lng_column_num : array_util::ArrayKey($column_names, 'X');
            
            $lng_column_name = $column_names[$lng_column_num];
        }
        else
            $lng_column_num = array_util::ArrayKey($column_names, $lng_column_name);
        
        // select first column as id column
        if (is_null($id_column_name))  $id_column_name = $column_names[0];
        
        $columns_for_plot = array();
        $columns_for_plot[$id_column_name] = '';
        $columns_for_plot[$lat_column_name] = '';
        $columns_for_plot[$lng_column_name] = '';
        $columns_for_plot[$value_column_name] = '';

        //** if we have to remove null values then check value column value and remove the row if the value column is null        
        $filtered_src = $src;
        if ($remove_nulls) $filtered_src = matrix::RemoveRowsBasedColumnValue($filtered_src, $value_column_name, null);
        
        $filename = matrix::Save($filtered_src, file::random_filename_memory(), $delim, null, array_keys($columns_for_plot));
        
        foreach ($columns_for_plot as $column_name => $value) 
            $grass_column_types[] = $column_name." ".strtolower(matrix::ColumnTypeForDB($src,$column_name));
        
        $grass_column_type_str = join(",",$grass_column_types);
        
        $CMD = "v.in.ascii  input={$filename} output=$vector_name format=point fs='$delim' skip=1 columns='{$grass_column_type_str}'  x=3  y=2  z=4 cat=0 --overwrite";
        
        $load_ascii_result = $this->GRASS_COMMAND($CMD);
        
        if (!$this->hasVector($vector_name))
        {
        	ErrorMessage::Marker("ERROR loading $filename to $vector_name file ...  $filename has been left for diagnostics ".print_r($load_ascii_result,true));
            return null;
        }
        
        file::Delete($filename);        
        unset($filtered_src);
        
        return $vector_name;
        
    }
    
    
    
    public function vector_list()
    {
        $list = $this->GRASS_COMMAND("g.mlist type=vect");
        return array_util::Trim($list);
    }
    
    public function raster_list()
    {
        $list = $this->GRASS_COMMAND("g.mlist type=rast");
        return array_util::Trim($list);
    }

    public function hasVector($name)
    {        
        return array_util::Contains($this->vector_list(),trim($name));
    }

    public function hasRaster($name)
    {        
        return array_util::Contains($this->raster_list(),trim($name));
    }
    
    public function Resolution()
    {        
        if (func_num_args() == 0) return $this->resolution;
        $this->resolution = func_get_arg(0);
        
        ErrorMessage::Marker("Set resolution to {$this->resolution}");
        
        $this->GRASS_COMMAND("g.region -pg res={$this->resolution}");
    }
    private $resolution = null;
    
    public function ResetResolution()
    {        
        $this->GRASS_COMMAND("g.region -pgd");
    }
    
    
    public function vectorColumnNames($name)
    {       
        if (!$this->hasVector($name)) return null;
        return $this->GRASS_COMMAND("db.columns table=$name");
    }

    public function vectorAddColumn($vector_name,$column_name,$column_type)
    {        
        if (!$this->hasVector($vector_name)) 
        {
            ErrorMessage::Marker(__METHOD__."Add vector column can't find $vector_name");
            return null;
        }

        $output = $this->GRASS_COMMAND("v.db.addcol map=$vector_name layer=1 columns='$column_name $column_type'");
        
    }
    
    public function VectorInVector($to_check,$inside,$indicator_column_name = null,$save_inVector_as = null,$shape_type = "point")
    {
        if (is_null($indicator_column_name)) $indicator_column_name = "in{$inside}";
        
        if (!$this->hasVector($to_check)) 
        {
            ErrorMessage::Marker(__METHOD__."Vector named $to_check not found");
            return null;
        }
                        
        if (!$this->hasVector($inside)) 
        {
            ErrorMessage::Marker(__METHOD__."Vector named $inside not found");
            return null;
        }
        
        
        $this->vectorAddColumn($to_check,$indicator_column_name,'int');
        
        
        //updates  vector=$to_check with a column "basically named in(other polygon) and set's the value to "not null""
        // where this point is inside the other polygon
        
        $cmd = "v.what.vect vector=$to_check qvector=$inside layer=1 qlayer=1 column=$indicator_column_name qcolumn=cat";
        
        $output = $this->GRASS_COMMAND($cmd);
        
        
        // extract from vector=$to_check where $column_name is not null
        $extracted_vector_name = "{$to_check}_in{$inside}";
        
        $this->remove_vector($extracted_vector_name);
        
        
        $output = $this->GRASS_COMMAND("v.extract  --o input=$to_check output={$extracted_vector_name} type=point layer=1 new=-1 where='{$indicator_column_name} is not null'");
                
        
        if (!is_null($save_inVector_as))
        {
        	$shape_output = $this->GRASS_COMMAND("v.out.ogr -e input=$extracted_vector_name type={$shape_type} dsn={$save_inVector_as} format=ESRI_Shapefile");
        }
        
        
        if (!$this->hasVector($extracted_vector_name)) return null;
        
        return $extracted_vector_name;
        
    }

    
    public function vector2raster_1degree($vector_name,$value_column_name,$region = null)
    {    
        $raster_name = $vector_name."_1degree";
        if (!$this->hasVector($vector_name)) return null;
        
        if (!is_null($region)) 
            $this->set_region($region);
        
        $this->Resolution(1);
        
        $output = $this->GRASS_COMMAND("v.to.rast input=$vector_name output=$raster_name use=attr layer=1 column=$value_column_name rows=4096 --overwrite");

        if (!$this->hasRaster($raster_name)) return null;
        
        $this->set_region();
        
        return $raster_name;
    }
    
    
    public function vector2raster($vector_name,$value_column_name = null,$region = null,$resolution = null,$raster_name = null)
    {              
        if (is_null($raster_name)) $raster_name = $vector_name."_raster";
        if (!$this->hasVector($vector_name)) return null;
        
        if (!is_null($region)) $this->set_region($region);
        if (!is_null($resolution)) $this->Resolution($resolution);
        
        $use = (is_null($value_column_name)) ? "use=cat" : "use=attr column=$value_column_name";
        
        $output = $this->GRASS_COMMAND("v.to.rast input=$vector_name output=$raster_name layer=1 $use rows=4096 --overwrite");
                
        if (!$this->hasRaster($raster_name)) return null;
        
        if (!is_null($region)) $this->set_region();
        
        return $raster_name;
    }

    public function vector2raster_bspline($vector_name,$value_column_name,$region = null,$resolution = null, $Interpolation_spline_step = 5)
    {        
        if (!$this->hasVector($vector_name)) return null;
        
        if (is_null($region)) 
        {            
            if (!is_null($resolution)) $this->Resolution($resolution);
            
            $raster_name = $vector_name."_spline";
            $output = $this->GRASS_COMMAND("v.surf.bspline input={$vector_name} raster={$raster_name} layer=1 column={$value_column_name}  sie={$Interpolation_spline_step} sin={$Interpolation_spline_step} --o");    
            
            if (!$this->hasRaster($raster_name)) 
            {
                ErrorMessage::Marker(__METHOD__."ERROR: vector2raster_bspline - failed to create BSPLINE raster from $vector_name");
                return null;
            }
            
            return $raster_name;
        }
        else
        {
            
            $output = $this->GRASS_COMMAND("v.surf.bspline input={$vector_name} raster={$vector_name}_bspline_full layer=1 column={$value_column_name}  sie={$Interpolation_spline_step} sin={$Interpolation_spline_step} --o");    
            
            if (!$this->hasRaster("{$vector_name}_bspline_full")) 
            {
                ErrorMessage::Marker(__METHOD__."ERROR: vector2raster_bspline - failed to create BSPLINE raster from $vector_name");
                return null;
            }
                    
            $this->set_region($region); //** set region
            if (!is_null($resolution)) $this->Resolution($resolution);
            
            $raster_name = "{$vector_name}_bspline";
            
            //** resample  {$vector_name}_bspline_full to the current region required
            $output = $this->GRASS_COMMAND("r.resample input={$vector_name}_bspline_full output={$raster_name} --o");
            
            GRASS::remove_raster("{$vector_name}_bspline_full");
            
            if (!$this->hasRaster($raster_name)) 
            {
                ErrorMessage::Marker(__METHOD__."ERROR: vector2raster_bspline - failed to create resample down BSPLINE raster from {$vector_name}_bspline_full");
                return null;
            }
            
            return "{$vector_name}_bspline";
            
        }
        
        if (!is_null($resolution)) $this->Resolution($resolution);
        
        return null;
    }
    
    public function vector2raster_idw($vector_name,$value_column_name,$region = null,$resolution = null, $number_of_interpolation_points = 4)
    {      
        $raster_name = $vector_name."_idw";
        if (!$this->hasVector($vector_name)) return null;
        
        if (!is_null($region)) $this->set_region($region);
        if (!is_null($resolution)) $this->Resolution($resolution);
        
        $output = $this->GRASS_COMMAND("v.surf.idw input=$vector_name output=$raster_name npoints=$number_of_interpolation_points layer=1 column=$value_column_name");
        
        if (!$this->hasRaster($raster_name)) return null;        
        
        return $raster_name;
    }

    
    //regularized_spline_with_tension
    public function vector2raster_regularized_spline_with_tension($vector_name,$value_column_name,$region = null,$resolution = null,$tension = 40)
    {        
        $raster_name = $vector_name."_rst";
        if (!$this->hasVector($vector_name)) return null;
        
        if (!is_null($region)) $this->set_region($region);
        if (!is_null($resolution)) $this->Resolution($resolution);
        
        $output = $this->GRASS_COMMAND("v.surf.rst input=$vector_name layer=1 zcolumn=$value_column_name elev=$raster_name tension=$tension  --overwrite ");
        
        if (!$this->hasRaster($raster_name)) return null;        
        
        return $raster_name;
    }
    
    
    public function interpolate_vector($interpolation_type,$vector_name,$value_column_name,$region = null,$resolution = 0.05) 
    {
        //** might be nice to be able to save these rasters for viewing later
        ErrorMessage::Marker(__METHOD__."interpolate_vector $vector_name");
        
        $result = null;
        
        switch ($interpolation_type) {
            case self::$INTERPOLATION_TYPE_IDW:
                $result = $this->vector2raster_idw($vector_name,$value_column_name,$region,$resolution);            
                break;

            case self::$INTERPOLATION_TYPE_SPLINE:
                $result = $this->vector2raster_bspline($vector_name, $value_column_name,$region,$resolution);
                break;
            
            case self::$INTERPOLATION_TYPE_POINT:
                $result = $this->vector2raster($vector_name, $value_column_name,$region,$resolution);
                break;
            
            case self::$INTERPOLATION_TYPE_RST:
                $result = $this->vector2raster_regularized_spline_with_tension($vector_name, $value_column_name,$region,$resolution);
                break;

            default:
                break;
            
        }
        
        return $result;
        
    }
    
    
    
    public function set_region($name = null)
    {
        $region_text_result = null;
        if (is_null($name)) 
            $region_text_result = $this->GRASS_COMMAND("g.region -pgd"); 
        else
        {
            if ($this->hasVector($name)) 
                    $region_text_result = $this->GRASS_COMMAND("g.region -pg vect=$name");
            else    
                if ($this->hasRaster($name)) 
                        $region_text_result = $this->GRASS_COMMAND("g.region -pg rast=$name");
            
        }
        
        if (is_null($region_text_result)) return null;

        return array_util::explode($region_text_result);
        
    }
    

    public function remove($name)
    {
        $result[] = $this->remove_raster($name);
        $result[] = $this->remove_vector($name);
        return $result;
    }
    
    public function remove_raster($name)
    {
        $result = $this->GRASS_COMMAND("g.mremove rast={$name} -f");
        return $result;
    }
    
    public function remove_vector($name)
    {
        $result = $this->GRASS_COMMAND("g.mremove vect={$name} -f");
        return $result;
    }
    

    public function raster_stats_bound_by_vector($raster,$bounding_vector,$joining_phrase = 'in')
    {
        
        if ($this->debug) 
            ErrorMessage::Marker(__METHOD__."raster = $raster  bounding_vector = $bounding_vector");
        
        $boundary_vector_name = "{$raster}_{$joining_phrase}_{$bounding_vector}";
        
        $copied_ok = $this->copy_vector($bounding_vector, $boundary_vector_name, true);
        
        if (!$copied_ok) 
        {
            ErrorMessage::Marker(__METHOD__."FAILED:: Copy vector $vector  to  $boundary_vector_name");
            return null;   
        }
        
        
        $prefix = "SS_";
        $result = $this->GRASS_COMMAND("v.rast.stats -c vector=$boundary_vector_name raster=$raster colprefix={$prefix}");
        
        $table = $this->AttributeTable($boundary_vector_name);
        
        $first_row = util::first_element($table);
        
        $collectable_fields = explode(",","n,min,max,range,mean,stddev,variance,cf_var,sum");
        
        $result = array();
        foreach ($collectable_fields as $field_name)
            $result[$field_name] = $first_row[$prefix.'_'.$field_name];    

        $this->remove_vector($boundary_vector_name);

        return $result;
        
    }
    
    public function AttributeTable($vector)
    {
        $DB = new database('grass');
        $result  = $DB->selectTable($DB->DB(), $vector);
        unset($DB);

        return $result;
        
    }
    
    public function raster_mask($name,$set_region = false)
    {
        if (!$this->hasRaster($name)) 
        {
            ErrorMessage::Marker(__METHOD__."ERROR:: raster mask .. can't find $name");
            return null;
        }
                
        $result = $this->GRASS_COMMAND("r.mask -r input=$name");
        $result = $this->GRASS_COMMAND("r.mask -o input=$name");
        
        if ($set_region)
            $this->set_region ($name);
        
        return $name;
    }

    public function raster_stats($name)
    {
        if (!$this->hasRaster($name)) 
        {
            ErrorMessage::Marker(__METHOD__."ERROR:: raster stats .. can't find $name");
            return null;
        }
        
        return array_util::explode($this->GRASS_COMMAND("r.univar -g map=$name"));
    }
    
    
    
    public function copy_raster($from,$to,$overwrite = false)
    {
        $result = null;
        if (!$this->hasRaster($from)) 
        {
            ErrorMessage::Marker(__METHOD__."ERROR:: copy raster can't find $from");
            return null;
        }

        if ($overwrite) $this->remove_raster($to);
        
        if (!$this->hasRaster($to)) 
        {
            ErrorMessage::Marker(__METHOD__."ERROR:: copy raster $to already exists!");
            return null;
        }
                
        $result = $this->GRASS_COMMAND("g.copy rast=$from,$to");
        
        return $this->hasRaster($to);
    }
    
    
    
    public function copy_vector($from,$to,$overwrite = false)
    {
        $result = null;
        if (!$this->hasVector($from)) 
        {
            ErrorMessage::Marker(__METHOD__."ERROR:: copy vector can't find $from");
            return null;
        }

        if ($overwrite) $this->remove_vector($to);
        
        if ($this->hasVector($to)) 
        {
            ErrorMessage::Marker(__METHOD__."ERROR:: copy vector $to already exists!");
            return null;
        }
                
        $result = $this->GRASS_COMMAND("g.copy vect=$from,$to");
        
        if (!$this->hasVector($to))
        {
            ErrorMessage::Marker(__METHOD__."ERROR:: failed to copy vector $from to $to !");
            return false;
        }
        
        return true;
    }
    
    
    
    public function color_list()
    {
        return explode(",",$this->GRASS_COMMAND("d.colorlist"));
    }
    
    
    
    
    public function RowCount($table_name)
    {
        $DB = new database('grass');
        $count = $DB->count($table_name);        
        unset($DB);
        
        return $count;
    }
    
    
    public function vector2shapefile_point($vector_name, $shapefile_path,$overwrite = false)
    {
        ErrorMessage::Marker(__METHOD__."$vector_name  ---> $shapefile_path");
        
        if ($overwrite) $this->remove_shapefile($shapefile_path);
        
        $SHP = '.shp';
        
        if (!$this->hasVector($vector_name)) 
        {
            ErrorMessage::Marker(__METHOD__."ERROR:: vector2shapefile_point can't find vector $vector_name");
            return null;            
        }
        
        $shapefile_path = trim($shapefile_path);
        
        if (!util::contains(strtolower($shapefile_path), $SHP))
            $shapefile_path = $shapefile_path .$SHP;
        
        $layer_name = str_replace($SHP, '', util::fromLastSlash($shapefile_path));
        
        $this->GRASS_COMMAND("v.out.ogr input={$vector_name} type=point dsn='{$shapefile_path}' olayer={$layer_name} layer=1 format=ESRI_Shapefile");
        
        return file_exists($shapefile_path); //** will return true if we have successfully exported a shape file
        
    }
    
    public function remove_shapefile($name)
    {
        $shapefile_path = trim($name);
        
        $shapefile_path = str_replace('.shp', '', $shapefile_path);
        $shapefile_path = str_replace('.SHP', '', $shapefile_path);

        file::reallyDelete($shapefile_path.".shp");
        file::reallyDelete($shapefile_path.".prj");
        file::reallyDelete($shapefile_path.".dbf");
        file::reallyDelete($shapefile_path.".shx");

    }

    public function vector2KML($vector,$kml_filename)
    {
        $result = null;
        if (!$this->hasVector($vector)) 
        {
            ErrorMessage::Marker(__METHOD__."ERROR:: vector2KML can't find $vector");
            return null;
        }
        
        $output = $this->GRASS_COMMAND("v.out.ogr input={$vector} type=boundary dsn='{$kml_filename}' olayer={$vector} layer=1 format=KML"); 
        
        return (file_exists($kml_filename));
        
    }

    public function Raster2Ascii($raster,$filename,$null_value = -9999.00)
    {
        $raster = trim($raster);
        $filename = trim($filename);
        
        if (!$this->hasRaster($raster)) 
        {
            ErrorMessage::Marker(__METHOD__."can't find $raster");
            return null;
        }

        $output = $this->GRASS_COMMAND("r.out.ascii input='{$raster}' null={$null_value} dp=6 output='{$filename}'");
        
        return (file_exists($filename));
        
    }

    public function Ascii2Raster($filename,$raster)
    {
        if (!file_exists($filename))
        {
            ErrorMessage::Marker(__METHOD__."File does not exist $filename");
            return null;
        }

        $output = $this->GRASS_COMMAND("r.in.ascii -f -d input='{$filename}' output='{$raster}' --o -o");
        if (!$this->hasRaster($raster)) return null;
        return $raster;
    }

    
    public function Raster2Tiff($raster,$filename,$use_divisor = true,$divisor = 1000000)
    {
    	$raster = trim($raster);
    	$filename = trim(str_replace(".asc", ".tif", $filename));
    	
    	if (!$this->hasRaster($raster))
    	{
    		ErrorMessage::Marker(__METHOD__."can't find $raster");
    		return null;
    	}
    	
    	if ($use_divisor)
    	{
    		$output = $this->GRASS_COMMAND("r.out.tiff --quiet -t input='{$raster}' output='{$filename}'");
    	}
    	else
    	{
    		$output = $this->GRASS_COMMAND("r.mapcalc '{$raster}_for_tiff = {$raster} * {$divisor}'");
    		$output = $this->GRASS_COMMAND("r.out.tiff --quiet -t input='{$raster}_for_tiff' output='{$filename}'");
    		$output = $this->remove_raster("{$raster}_for_tiff");
    	}
    	
    	return (file_exists($filename));
    
    }
    
    public function Tiff2Raster($filename,$raster,$use_divisor = true,$divisor = 1000000)
    {
    	$filename = str_replace(".asc", ".tif", $filename);
    	
    	if (!file_exists($filename))
    	{
    		ErrorMessage::Marker(__METHOD__."can't find $raster");
    		return null;
    	}
    
    	if ($use_divisor)
    	{
    		$output = $this->GRASS_COMMAND("r.in.gdal --o -o input='{$filename}' output='{$raster}' ");
    	}
    	else
    	{
    		$output = $this->GRASS_COMMAND("r.in.gdal -k band=1 --o -o input='{$filename}' output='{$raster}_from_tiff' ");
    		$output = $this->GRASS_COMMAND("r.mapcalc '{$raster}  = {$raster}_from_tiff / {$divisor}'");
    		$output = $this->remove_raster("{$raster}_from_tiff");
    	}
    	
    	if (!$this->hasRaster($raster)) return null;
    
    	return $raster;
    }
    
    
    public function Raster2png($raster,$filename)
    {
        
        $raster = trim($raster);
        $filename = trim(str_replace(".asc", ".png", $filename));
        
        if (!$this->hasRaster($raster)) 
        {
            ErrorMessage::Marker(__METHOD__."can't find $raster");
            return null;
        }
        
        $output = $this->GRASS_COMMAND("r.out.png -t -w input='{$raster}' output='{$filename}'"); 
        
        return (file_exists($filename));
        
    }

    
    public function Table2File($tableName,$filename)
    {   
        $DB = self::connect2database($this->debug);
        matrix::Save($DB->selectTable(self::$DATABASE_NAME, $tableName), $filename);
        unset($DB);
        return file_exists($filename);
    }

    public function TableCount($table)
    {   
       
        $DB = self::connect2database($this->debug);
        $result = $DB->count($table);
        unset($DB);
        return $result;
    }
    
    //** GRASS colors 
    public static $COLOR_AQUA     = "aqua";
    public static $COLOR_BLACK    = "black";
    public static $COLOR_BLUE     = "blue";
    public static $COLOR_BROWN    = "brown";
    public static $COLOR_CYAN     = "cyan";
    public static $COLOR_GRAY     = "gray";
    public static $COLOR_GREEN    = "green";
    public static $COLOR_GREY     = "grey";
    public static $COLOR_INDIGO   = "indigo";
    public static $COLOR_MAGENTA  = "magenta";
    public static $COLOR_ORANGE   = "orange";
    public static $COLOR_PURPLE   = "purple";
    public static $COLOR_RED      = "red";
    public static $COLOR_VIOLET   = "violet";
    public static $COLOR_WHITE    = "white";
    public static $COLOR_YELLOW   = "yellow";

    public static $INTERPOLATION_TYPE_POINT  = "POINT";
    public static $INTERPOLATION_TYPE_IDW    = "IDW";
    public static $INTERPOLATION_TYPE_SPLINE = "SPLINE";
    public static $INTERPOLATION_TYPE_RST    = "RST";
    
}
?>