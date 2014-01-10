<?php
class database
{

    public $link = null;
    public $result = null;
    public $debug = false;
    public $progress = false;
   
    public $debug_all = false;
    
    public $open_new_connection = false;
    
    private $db     = '';
    private $host   = '';
    private $userID = '';
    private $pwd    = '';
    
    private $insert_block_size = 40;
    

    public function __construct($db = null, $host = null, $userID = null, $pwd = null)
    {

        $this->db     = (!is_null($db))     ? $db     : $this->db;
        $this->host   = (!is_null($host))   ? $host   : $this->host;
        $this->userID = (!is_null($userID)) ? $userID : $this->userID;
        $this->pwd    = (!is_null($pwd))    ? $pwd    : $this->pwd;
        
        $this->connect();
        
    }
    
    public function DB()
    {
        return $this->db;
    }

    public function HOST()
    {
        return $this->host;
    }
    
    
    public function __destruct()
    {
        $this->disconnect();
    }

    public function selectTable($db, $tableName, $keyColoumn = "",$where = "",$limit = "")
    {
        if ($where != "") $where = " where $where ";
        if ($limit != "") $limit = " limit {$limit} ";
        
        $q = "select * from `$db`.`$tableName` $where $limit";
        
        return $this->query($q,$keyColoumn);
    }


    
    public function table_names_for_databases($database_pattern_array)
    {
        
        $result = array();

        if (is_array($database_pattern_array))
            
            foreach ($database_pattern_array as $database_pattern) 
            {
                foreach ($this->database_names($database_pattern) as $database) 
                {
                    foreach ($this->table_names($database)  as $tabe_name) 
                    {
                        $result[$database][$tabe_name] = "";
                    }
                    
                }
                        
            }
                
                
        else
        {
            
            
            foreach ($this->database_names($database_pattern_array) as $database) 
            {
                foreach ($this->table_names($database)  as $tabe_name) 
                {
                    $result[$database][$tabe_name] = "";
                }
            }
            
        }

        
        return $result;
        
    }
    
    
    public function database_names($like = "")
    {
        $like = $like."%";
        
        $sql_result = $this->query("SELECT S.`SCHEMA_NAME` as database_name FROM information_schema.SCHEMATA S where SCHEMA_NAME != 'information_schema' and SCHEMA_NAME != 'mysql' and SCHEMA_NAME like '$like';",'database_name');
        
        return array_keys($sql_result);
    }

    
    public function table_names($db, $like = "%")
    {
        $sql = "SELECT TABLE_NAME  as table_name FROM information_schema.TABLES where TABLE_SCHEMA = '$db' and TABLE_NAME like('$like');";
        $sql_result = $this->query($sql,'table_name');
        
        $result = array();
        foreach (array_keys($sql_result) as $value)
            $result[$value] = $value;
        
        return $result;
    }
    
    
    public function connect()
    {
        $this->link = mysql_connect($this->host, $this->userID, $this->pwd,$this->open_new_connection);
        
        if (!$this->link) die('Could not connect: ' . mysql_error());
        
        $db_result = mysql_select_db($this->db);
        if ($db_result == false) die('Could not change to database ' . $this->db."\n");
        
        if ($this->debug_all) echo "Connect to {$this->db}\n";
        
    }

    public function change_db($db)
    {
        return  mysql_select_db($db);
    }

    public function disconnect()
    {
        if ($this->isConnected())
            @mysql_close($this->link);
        
        if ($this->debug_all) echo "Disconnect from {$this->db}\n";
        
        unset($this->link);
        $this->link = null;
    }

    public function isConnected()
    {        
        return (!is_null($this->link));   
    }
    
    
    
    //** the key will be unique and the value will be the last value for that key
    public function KeyedColumn($table,$keyColoumn,$valueColumn,$where,$limit)
    {
        if (is_null($table)       || $table == '') return null;
        if (is_null($keyColoumn)  || $keyColoumn == '') return null;
        if (is_null($valueColumn) || $valueColumn == '') return null;

        if ($where != '') $where = " where $where ";
        if ($limit != '') $limit = " limit $limit ";

        $sql = "select $keyColoumn,$valueColumn from $table $where order by $keyColoumn $limit";
        $sqlResult = $this->query($sql,$keyColoumn);

        $result = array();
        foreach ($sqlResult as $key => $row)  $result[$key] = $row[$valueColumn];

        unset($sqlResult);

        return $result;

    }

    private function clean_query($sql,$above_128 = true) 
    {
        
        //** clean all non typeable chars from query
        for ($index = 0; $index <= 31; $index++) $sql = str_replace(chr($index), ' ', $sql);   
        
        if ($above_128)
            for ($index = 128; $index <= 255; $index++) $sql = str_replace(chr($index), ' ', $sql);   

            
        $sql = trim($sql);
        $sql = util::trim_end($sql, ';');
        
        
        return $sql;
    }
    

    public function query($sql,$keyColoumn = "",$query_names = null)
    {
        
        //$this->connect();
        $sql = $this->clean_query($sql);
        
        if (is_string($query_names)) $query_names = explode(",",$query_names);
        
        $result = array();
        $count = 0;
        foreach (explode(';',$sql) as $single_sql)
        {
            $single_sql = trim($single_sql);
            if ($single_sql == "") continue;
            
            $qname = (is_null($query_names)) ? $count  : $query_names[$count];
            if (substr($single_sql,0,2) == "<<") 
            {
                $singe_query_name = util::midStr($single_sql, '<<', '>>');
                $single_sql = str_replace("<<$singe_query_name>>", " ", $single_sql);
                $qname = $singe_query_name;
            }
            
            $result[$qname] = $this->query_single($single_sql,$keyColoumn);
            
            $count++;
        }
        
        //$this->disconnect();
        if (count($result) == 1) return $result[0]; //** if there was only one result - i.e. one sql then return it's result - current output
        
        return $result;
        
    }
    
    private function query_single($sql,$keyColoumn = "")
    {
        
        $sql = $this->clean_query($sql);

        $sql_result = mysql_query($sql, $this->link);

        if ($sql_result == FALSE) return FALSE;

        try
        {
            $row = @mysql_fetch_assoc($sql_result);
        }
        catch (Exception $exc) {
            return array();
        }
        
        $result = array();
        while ($row)
        {
            if ($keyColoumn == "" )
                $result[] = $row;
            else
                $result[$row[$keyColoumn]] = $row;
            
            
            $row = @mysql_fetch_assoc($sql_result);
        }

        
        $this->result = $result;
        
        return $result;

    }

    public function jagged_query($sql,$key_column)
    {
        //$this->connect();

        $sql = $this->clean_query($sql);

        $sql_result = mysql_query($sql, $this->link);

        if ($sql_result == FALSE) return FALSE;

        try
        {
            $row = @mysql_fetch_assoc($sql_result);
        }
        catch (Exception $exc) {
            return array();
        }
        
        $result = array();
        while ($row)
        {
            $result[$row[$key_column]][] = $row;            
            $row = @mysql_fetch_assoc($sql_result);
        }
        
        $this->result = $result;

        mysql_free_result($sql_result);
        
       // $this->disconnect();
        
        return $result;

    }
    
    //** $array = key field name  value = column type
    public function change_types_of_columns($db,$table, $array)
    {    
        $changes = array();
        foreach ($array as $field => $datatype)
            $changes[] = "CHANGE  `$field`  `$field` $datatype NULL";
        
        $sql = "ALTER TABLE  `{$db}`.`{$table}` ". join(',',$changes).";";
        
        return $this->update($sql);
    }

    
    public function change_column_type($db,$table, $field,$datatype)
    {    
        $sql = "ALTER TABLE  `{$db}`.`{$table}` CHANGE  `$field`  `$field` $datatype NULL";
        return $this->update($sql);
    }
    
    public function change_column_type_to_double($db,$table, $field)
    {       
        return $this->change_column_type($db, $table, $field, 'double');
    }
    
    public function change_column_type_to_varchar($db,$table, $field,$size = 100)
    {            
        return $this->change_column_type($db, $table, $field, "varchar($size)");
    }
    
    
    public function count($table, $field = NULL,$where = NULL)
    {
       // $this->connect();
        $result = -1;

        if (!is_null($field)) $groupby = " group by $field ";
        if (!is_null($where)) $where = " where $where ";

        $result = array();
        if (is_null($field))
            $sql = "select count(*) as count from $table $where";
        else
            $sql = "select $field, count(*) as count from $table $where $groupby";

        $sql = $this->clean_query($sql);
        
        $sql_result = mysql_query($sql, $this->link);

        if ($sql_result == FALSE)
            $result = -1;
        else
        {
            try
            {
                $row = mysql_fetch_assoc($sql_result);
                $result = trim($row['count']);
            }
            catch (Exception $exc) {
                $result = -1;
            }
        }
        
       // $this->disconnect();
        
        return $result;

    }

    public function single_value_query($sql)
    {
        $sql_result = $this->query($sql);
        $first_row = util::first_element($sql_result);        
        return util::first_element($first_row);        
    }
    
    
    public function max($table,$id_field,$value_field,$where = NULL)
    {
        $result = null;

        if (!is_null($where) && $where != '') $where = " where $where ";

        $sql = "select $id_field,max($value_field) as 'max' from $table $where group by $id_field order by $id_field";

        $sql_result = $this->query($sql, $id_field);

        $result = array();
        foreach ($sql_result as $id => $row)
            $result[$id] = $row['max'];

        unset($sql_result);

        return $result;

    }

    public function min($table,$id_field,$value_field,$where = NULL)
    {
        $result = null;

        if (!is_null($where)) $where = " where $where ";

        $sql = "select $id_field,min($value_field) as 'min' from $table $where group by $id_field order by $id_field";


        $sql_result = $this->query($sql, $id_field);

        $result = array();
        foreach ($sql_result as $id => $row)
            $result[$id] = $row['min'];

        unset($sql_result);

        return $result;

    }


    public function insert($sql)
    {
        //$this->connect();
        $sql = $this->clean_query($sql);
        $sql_result = mysql_query($sql,$this->link);
        $affected = mysql_affected_rows();
       // $this->disconnect();
        return $affected;
    }

    public function delete($sql)
    {
      //  $this->connect();
        $sql = $this->clean_query($sql);
        $sql_result = mysql_query($sql,$this->link);
        $affected = mysql_affected_rows();
      //  $this->disconnect();
        return $affected;
    }

    public function update($sql)
    {

        $sql = $this->clean_query($sql);
        
        if (!util::contains($sql, ';'))
        {
            $sql_result = mysql_query($sql, $this->link);
            $affected = mysql_affected_rows();
            return $affected;
        }

        //** multiple quries
        $result_affected_rows = array();
        foreach (explode(';',$sql) as $single_sql)
        {
            $single_sql = trim($single_sql);
            
            if ($single_sql == "") continue;
            
            $sql_result = mysql_query($single_sql.";", $this->link);
            $result_affected_rows[$sql_result] = mysql_affected_rows();
        }
            
        return $result_affected_rows;
    }


    public function Index($db,$table,$column)
    {
        if (is_null($db) || $db == "") return null;
        if (is_null($table) || $table == "") return null;
        if (is_null($column) || $column == "") return null;

        $sql = "ALTER TABLE `$db`.`$table` ADD INDEX (  `$column` );";

        $update_rows = $this->update($sql);

        return $update_rows;
    }

    //** $column_array = column names to index
    public function IndexColumns($db,$table,$column_array)
    {
        if (is_null($db) || $db == "") return null;
        if (is_null($table) || $table == "") return null;
        
        $adds = array();
        foreach ($column_array as $column)
            $adds[] = "ADD INDEX (`$column` )";
        
        $sql = "ALTER TABLE `$db`.`$table` ".join(',',$adds).";";
        
        $update_rows = $this->update($sql);

        return $update_rows;
    }
    
    
    
    public function AddTextColumn($db,$table,$column,$size = 100)
    {
        if (is_null($db) || $db == "") return null;
        if (is_null($table) || $table == "") return null;
        if (is_null($column) || $column == "") return null;
        
        
        echo __METHOD__." db =  $db, table =$table, column = $column \n";
        
        
        if ($this->hasColumn($db,$table,$column))
        {
            return 1;
        }
        

        $sql  = "ALTER TABLE  `$db`.`$table` ADD `$column` VARCHAR( $size ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;\n";
        
        echo "$sql\n";
        
        $add_column_ok = $this->update($sql);

        if(is_null($add_column_ok))
        {
            return null;
        }

        return $this->Index($db, $table, $column);
    }

    public function AddDateColumn($db,$table,$column)
    {
        if (is_null($db) || $db == "") return null;
        if (is_null($table) || $table == "") return null;
        if (is_null($column) || $column == "") return null;

        if ($this->hasColumn($db,$table,$column))
        {
            return 1;            
        }
        

        $sql  = "ALTER TABLE  `$db`.`$table` ADD `$column` DATETIME NULL;\n";
        $add_column_ok = $this->update($sql);

        
        if(is_null($add_column_ok))
        {
            return null;
        }

        return $this->Index($db, $table, $column);
    }


    public function hasColumn($db,$table,$column)
    {
        
        echo __METHOD__." db =  $db, table =$table, column = $column \n";

        $sql = "select * from `$db`.`$table` limit 1";
        
        echo "$sql\n";
        
        $short_table = $this->query($sql);
        
        print_r($short_table);
        
        echo  "\n";
        
        $first_row = util::first_element($short_table);

        return (array_key_exists($column, $first_row));

    }

    public function ColumnNames($db,$table)
    {   
        $short_table = $this->query("select COLUMN_NAME as columns FROM `information_schema`.`COLUMNS` where TABLE_SCHEMA = '{$db}' and table_name = '{$table}';",'columns');
        return array_keys($short_table);
    }


    public function AddNumericColumn($db,$table,$column)
    {
        

        if (is_null($db) || $db == "") return null;
        if (is_null($table) || $table == "") return null;
        if (is_null($column) || $column == "") return null;

        if ($this->hasColumn($db,$table,$column))
        {
            return 1;            
        }
        

        $sql  = "ALTER TABLE  `$db`.`$table` ADD `$column` DOUBLE NULL;\n";
        $add_column_ok = $this->update($sql);

        if(is_null($add_column_ok))
        {
            return null;
        }
        

        return $this->Index($db, $table, $column);
    }

    public function Set($db,$table,$column,$value)
    {
        

        if (is_null($db) || $db == "") return null;
        if (is_null($table) || $table == "") return null;
        if (is_null($column) || $column == "") return null;
        if (is_null($value) || $value == "") return null;

        if (!$this->hasColumn($db,$table,$column))
        {
            return null;
        }


        $sql  = "update `$db`.`$table` set `$column` = $value;";


        $update_rows = $this->update($sql);


        return $update_rows;
    }


    public function DropTable($db,$table)
    {

       if (is_null($db) || $db == "") return null;
        if (is_null($table) || $table == "") return null;

        $update_rows = $this->update("drop table if exists `$db`.`$table`;");

        return $update_rows;
    }

    public function CreateTable($db,$table,$sql,$drop_table_first = false, $full_index = false)
    {

        if (is_null($db) || $db == "") return null;
        if (is_null($table) || $table == "") return null;
        if (is_null($sql) || $sql == "") return null;

        if ($drop_table_first) $this->DropTable($db, $table);
        
        $create_sql = "create table `$db`.`$table` \n".str_replace(';', '', $sql).";";
        
        ErrorMessage::Marker(__METHOD__." create_sql = \n$create_sql");
        
        $row_count = $this->update($create_sql);
        
        
        if ($full_index)
            $this->IndexColumns($db,$table,$this->ColumnNames($db,$table));

        return $row_count;
    }

    
    
    public function CreateTableFromArray($db,$table_name,$column_name_array,$drop_table_first = false, $full_index = false,$indexes = NULL)
    {

        if (is_null($db)) return null;
        if (is_null($table_name)) return null;
        if (is_null($column_name_array)) return null;
        if ($db == "") return null;
        if ($table_name == "") return null;
        if (count($column_name_array) == 0) return null;

        $tableName = $this->cleanColumnName($table_name);

        if ($drop_table_first) $this->DropTable($db, $table_name);

        
        $result  = "\nCREATE TABLE  `$db`.`$tableName`  (";
        $result .= "\n `ID` int(11) NOT NULL auto_increment,";

        $colNames = array();
        foreach ($column_name_array as $column_name => $column_type)  //** expect $column_name_array to be   ['column_name'] = db_type
        {
            $cleanName = $this->cleanColumnName($column_name,"C");
            if ($cleanName == "") continue;
            $colNames[$column_name] = $cleanName;

            $result .= "\n `$cleanName` $column_type default NULL";

            if (util::contains(strtoupper($column_type), 'VARCHAR')) $result .= " COLLATE utf8_unicode_ci";

            $result .= ",";

        }

        $result .= "\n `updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,";
        
        $result .= "\n PRIMARY KEY  (`ID`)";

        
        if ($full_index)
        {
            $index = ",";
            
            if (is_null($indexes))
            {
                foreach ($colNames as $column_name)
                    $index .= "\n KEY `$column_name` (`$column_name`),";

                $result .= substr($index,0,strlen($index) - 1); //** drop last comma                
            }
            else
            {
                foreach (array_keys($indexes) as $column_name)
                    $index .= "\n KEY `$column_name` (`$column_name`),";

                $result .= substr($index,0,strlen($index) - 1); //** drop last comma                
                
            }
            

        }

        $result .= "\n ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;";

        
        ErrorMessage::Marker( __METHOD__." result = $result");
        
        
        $this->update($result);

        return $tableName;
    }



    public function matrixToTable($matrix,$rowID_name, $to_db,$tableName,$numeric_prefix = 'F',$indexes = NULL,$drop_table = false)
    {
         
        ErrorMessage::Marker(" to_db = $to_db,  tableName = $tableName");
        
        
        $sql_create_db = "create database if not exists $to_db;";
        $this->update($sql_create_db);
        
        if ($drop_table) $this->DropTable($to_db, $tableName);

       
        $column_name_lookup = array(); //** store keyed array of old name to new name
       
        $create_table_sql = $this->matrixToTable_CreateTable( $matrix,$rowID_name, $to_db,$tableName,$column_name_lookup,$numeric_prefix, $indexes);
        
        
        $this->update($create_table_sql); //** create table
        
        if ($this->progress) 
            $P = new progress(1, count($matrix), 1, 10);
        
        //** loop thru matrix row by row and buoild an SQL for that row.
        $count = 1;
        $block_count = 1;
        $block_insert = array();
        foreach ($matrix as $row_id => $row)
        {            
            
            if ($this->progress) 
            {
                $P->step_to($count);
                $P->display_percent();
            }
            
            $insert = "";
            $insert_column_names = array();
            $insert_column_values = array();

            $row_insert =  array();
            
            //** get data from matrix under old name and write new name in to "$insert_column_values"
            foreach ($column_name_lookup as $old_name => $name_info)
            {
                $insert_column_names[] = $name_info['new'];  //** get column name 'new cleaned name'

                //** if current column is the row id field then get data from row'sID'
                $cell_value = ($name_info['isRowID'] ) ? $row_id : $row[$name_info['old']];
                $cell_value = trim((is_array($cell_value)) ?  print_r($cell_value,true) : trim($cell_value));
                
                if ($cell_value == '' || is_null($cell_value))
					$db_cell_value = 'NULL';
                else
                {
                	$db_cell_value = ($name_info['type'] == "DOUBLE") ?  $cell_value : "'".$cell_value."'"; //** data type is varchar so wrap in quotes
                }
                	
                $insert_column_values[] = $db_cell_value;

            }

            for ($index = 0; $index < count($insert_column_values); $index++) 
                $row_insert[$insert_column_names[$index]] = $insert_column_values[$index];

            $block_insert[] = $row_insert;
            
            if ($block_count >= $this->insert_block_size)
            {                
                
                $inserted_count = $this->InsertArrayBulk($to_db, $tableName, $block_insert);                
                unset($block_insert);
                $block_insert = array();
                $block_count = 1;
            }
            
            $count++;
            $block_count++;
            
        }

        if (count($block_insert) != 0)
        {
            $inserted_count = $this->InsertArrayBulk($to_db, $tableName, $block_insert); //** write anything left in block
        }
        
        unset($block_insert);
        
        return $this->count("`$to_db`.`$tableName`");
    }


    
    
    
    public function matrixToTable_CreateTable( $matrix,$rowID_name, $to_db,$tableName,&$column_name_lookup,$numeric_prefix = 'F',$indexes = NULL)
    {
        //** SQL to create Table
        

        $result = "";
        
        $result .= "\nCREATE TABLE  `{$to_db}`.`$tableName`  (";
        $result .= "\n `ID` int(11) NOT NULL auto_increment,";

        $matrix_column_names = matrix::ColumnNames($matrix);

        
        $rowID_newname = $this->cleanColumnName($rowID_name,$numeric_prefix); //** add the "row_ID column"
        $rowID_Type = matrix::ColumnTypeForDB($matrix);
        
        
        $result .= "\n `$rowID_newname` $rowID_Type default NULL";        
        if ($rowID_Type != "DOUBLE") $result .= " COLLATE utf8_unicode_ci";
        $result .= ",";

        $column_name_lookup[$rowID_name]['old']  = $rowID_name;
        $column_name_lookup[$rowID_name]['new']  = $rowID_newname;
        $column_name_lookup[$rowID_name]['type'] = $rowID_Type;
        $column_name_lookup[$rowID_name]['isRowID'] = true;


        foreach ($matrix_column_names  as $rawColumnName)
        {
            $cleanName = $this->cleanColumnName($rawColumnName,$numeric_prefix);
            if ($cleanName == "") continue;

            $columnType = matrix::ColumnTypeForDB($matrix,$rawColumnName);
            $result .= "\n `$cleanName` $columnType default NULL";
            
            if ($columnType != "DOUBLE") $result .= " COLLATE utf8_unicode_ci";
            
            $result .= ",";

            $column_name_lookup[$rawColumnName]['old']  = $rawColumnName;
            $column_name_lookup[$rawColumnName]['new']  = $cleanName;
            $column_name_lookup[$rawColumnName]['type'] = $columnType;
            $column_name_lookup[$rawColumnName]['isRowID'] = false;

        }

        $result .= "\n `updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,";
        
        $result .= "\n PRIMARY KEY  (`ID`),";
        $result .= "\n KEY `Index_$rowID_name` (`$rowID_name`)";

        if (!is_null($indexes))
        {
            //** create index for columns

            $index = "";
            foreach ($matrix_column_names  as $rawColumnName)
            {
                $cleanName = $this->cleanColumnName($rawColumnName,$numeric_prefix);
                if ($cleanName == "") continue;
                $index .= "\n KEY `Index_$cleanName` (`$cleanName`),";
            }

            if ($index != "")
                $result .= ",".substr($index,0,strlen($index) - 1); //** add index code and drop last comma

        }


        $result .= "\n ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;";

        return $result;

    }


    public function fromDelimited($filename,$db,$tableName = null, $delim = ",",$column_types = null,$index = true,$index_these = null)
    {        
        return $this->fromCSV($filename,$db,$tableName, $delim,false,$column_types,$index,$index_these);
    }

    public function fromCSV($filename,$db,$tableName = null, $delim = ",",$add_date_columns = false,$column_types = null,$index = true,$index_these = null)
    {

       if (!file_exists($filename))
        {
            return NULL;
        }

        if (is_null($tableName))
            $tableName = $this->cleanColumnName(str_replace(".".file::getFileExtension($filename),"",util::fromLastSlash($filename)));

        $sql = $this->getCreateTableText($db,$filename,$tableName,$delim,'F',$add_date_columns,$column_types,$index,$index_these);
        
        $temp_filename = file::random_filename().".sql";
        
        file_put_contents($temp_filename, $sql."\n");
        
        $cmd = "mysql -u{$this->userID} -p{$this->pwd} -e 'source {$temp_filename};' {$this->db} ";
        
        exec($cmd);

        $table_count = $this->count("`{$db}`.`{$tableName}`");
        
        ErrorMessage::Marker(__METHOD__."table_count = $table_count");
        
        file::delete($temp_filename);
        
        return $table_count;

    }



    private function getCreateTableText($db,$filename,$tableName = NULL,$delim = ",",$numeric_prefix = 'F',$add_date_columns = false,$column_types = null,$index = true,$index_these = null)
    {
          

      if (!file_exists($filename))
        {
            return NULL;
        }

        if (file::lineCount($filename) <= 1)
        {
            return NULL;
        }


        $file = file($filename);

        if (is_null($tableName))
            $tableName = $this->cleanColumnName(util::toLastChar(util::fromLastSlash($filename), '.'));

        
        $split = str_getcsv($file[0], $delim, '"');


        $result  = "";
        $result .= "\nDROP TABLE IF EXISTS `$db`.`$tableName`;";
        $result .= "\nCREATE TABLE  `$db`.`$tableName`  (";
        $result .= "\n `ID` int(11) NOT NULL auto_increment,";

        $colCount = 0;
        
        $colNames = array();
        foreach ($split as $rawColumnName)
        {
            //ErrorMessage::Marker("Checking name  [{$rawColumnName}]");
             
            if (!is_null($column_types))
                $columnType = $column_types[$rawColumnName];
            else
                $columnType = $this->getColumnType($file, $colCount,$delim);
            
            
            $cleanName = $this->cleanColumnName($rawColumnName,$numeric_prefix);
            if ($cleanName == "") continue;

            
            // check to see if the name of the column has "date" somewhere
            // if so then we will make this a datetime column
            if ($add_date_columns && util::contains(strtolower($cleanName), "date"))  $columnType = "DATE";
            
            $colNames[] = $cleanName;

            if ($columnType == "DOUBLE")
                $result .= "\n `$cleanName` $columnType default NULL,";
            else
                $result .= "\n `$cleanName` $columnType default NULL COLLATE utf8_unicode_ci ,";


            $colCount++;
        }

        $result .= "\n `updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,";
        
        $result .= "\n PRIMARY KEY  (`ID`)";

        if ($index)
        {        
            $index = "";
            
            $index_count = 0;
            
            
            if (is_null($index_these))
            {
                foreach ($split as $rawColumnName)
                {
                    if ($index_count > 30) continue; //** if we have more than 30 columns only index the first 30

                    $cleanName = $this->cleanColumnName($rawColumnName,$numeric_prefix);
                    if ($cleanName == "") continue;

                    $index .= "\n KEY `Index_$cleanName` (`$cleanName`),";

                    $index_count++;

                }
                
            }
            else
            {
                // index only specific columns
                foreach ($index_these as $rawColumnName)
                {
                    if ($index_count > 30) continue; //** if we have more than 30 columns only index the first 30

                    $cleanName = $this->cleanColumnName($rawColumnName,$numeric_prefix);
                    if ($cleanName == "") continue;

                    $index .= "\n KEY `Index_$cleanName` (`$cleanName`),";

                    $index_count++;

                }
                
            }
            
            $result .= ",".substr($index,0,strlen($index) - 1); //** drop last comma
            
        }
        

        $result .= "\n ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;";

        $fullPath = realpath($filename);

        $result .= "\n\nload data local infile '$fullPath' into table `$db`.`$tableName`";
        $result .= "\nfields terminated by '$delim'";
        $result .= "\noptionally enclosed by '\"' ";
        $result .= "\nlines terminated by '\\n'";
        $result .= "\nIGNORE 1 LINES";
        $result .= "\n(`".join("`,`",$colNames)."`);";

        $lastCol = util::last_element($colNames);

        $trim_sql = "\n\nupdate `$db`.`$tableName` set `$lastCol` = trim(char(13) from `$lastCol`);";

        $result .= $trim_sql;

        echo "$result\n";
        
        

        return $result;

    }

    public function InfileImport($fullPath,$db,$tableName,$delim,$colNames)
    {
        $sql = "";
        
        $sql .= "\nload data local infile '$fullPath' into table `$db`.`$tableName`";
        $sql .= "\nfields terminated by '$delim'";
        $sql .= "\noptionally enclosed by '\"' ";
        $sql .= "\nlines terminated by '\\n'";
        $sql .= "\nIGNORE 1 LINES";
        $sql .= "\n(`".join("`,`",$colNames)."`);\n";
        
        $temp_filename = file::random_filename().".sql";
        
        file_put_contents($temp_filename, $sql."\n");
        
        $cmd = "mysql -u{$this->userID} -p{$this->pwd} -e 'source {$temp_filename};' {$this->db} ";
        
        echo "cmd = $cmd\n";
        
        exec($cmd);
        
        file::delete($temp_filename);
        
        return true;
        
    }

    
    public function cleanColumnName($rawColumnName,$numeric_prefix = 'F')
    {
          

          $rawColumnName = trim($rawColumnName);

            $rawColumnName = str_replace('"','',$rawColumnName);
            $rawColumnName = str_replace("'",'',$rawColumnName);
            $rawColumnName = str_replace(";",'',$rawColumnName);
            $rawColumnName = str_replace("(",'',$rawColumnName);
            $rawColumnName = str_replace(")",'',$rawColumnName);
            $rawColumnName = str_replace("[",'',$rawColumnName);
            $rawColumnName = str_replace("]",'',$rawColumnName);
            $rawColumnName = str_replace("{",'',$rawColumnName);
            $rawColumnName = str_replace("}",'',$rawColumnName);
            $rawColumnName = str_replace("%",'',$rawColumnName);
            $rawColumnName = str_replace("$",'',$rawColumnName);
            $rawColumnName = str_replace("*",'',$rawColumnName);
            $rawColumnName = str_replace(':','',$rawColumnName);
            $rawColumnName = str_replace('&','',$rawColumnName);
            $rawColumnName = str_replace('?','',$rawColumnName);
            $rawColumnName = str_replace('<','',$rawColumnName);
            $rawColumnName = str_replace('>','',$rawColumnName);
            $rawColumnName = str_replace('^','',$rawColumnName);
            $rawColumnName = str_replace('#','',$rawColumnName);

            $rawColumnName = str_replace("-",'_',$rawColumnName);
            $rawColumnName = str_replace(' ','_',$rawColumnName);
            $rawColumnName = str_replace("/**",'_',$rawColumnName);
            $rawColumnName = str_replace('\\','_',$rawColumnName);
            $rawColumnName = str_replace('.','_',$rawColumnName);

            $rawColumnName = trim($rawColumnName);

            //$rawColumnName = strtolower($rawColumnName);

            $first_char = util::first_char($rawColumnName);

            switch ($first_char) {
                case '0':
                case '1':
                case '2':
                case '3':
                case '4':
                case '5':
                case '6':
                case '7':
                case '8':
                case '9':
                    $rawColumnName = $numeric_prefix.$rawColumnName; //** if the name starts with a number  then we need to mak it start with a letter
                    break;

            }

            $rawColumnName = trim(util::trim_end($rawColumnName, "_"));
            
            return $rawColumnName;
    }


    private function getColumnType($fileArray, $colCount,$delim)
    {
        


        $rowCount = 0;
        $numberCount = 0;

        $maxStringLength = 0;

        foreach ($fileArray as $fileLine)
        {
            
            if ($rowCount > 0)
            {
                $split = str_getcsv($fileLine, $delim);
                
                $cell = trim(array_util::Value($split,$colCount,""));

                if ($cell == "") continue;

                $maxStringLength = max(strlen($cell),$maxStringLength);

                if (is_numeric($cell)) $numberCount++;    
                
            }
            
            $rowCount++;

        }

        if ( $numberCount >  ( ($rowCount -1 ) * 0.8) ) return "DOUBLE"; //** if 80% of values are numbers its a number column

        if ($maxStringLength == 0) $maxStringLength = 1;
        
        $maxStringLength = round($maxStringLength * 3,0); //**make it 3 times the max length

        $result_type = "varchar($maxStringLength)";
        
        return $result_type;

    }

    public function toCSV($sql,$outputFilename)
    {
        
        matrix::Save(self::query($sql), $outputFilename);        
        return file_exists($outputFilename);
    }

    public function pivotToCSV($db, $sql,$outputFilename,$pivotFieldsStr)
    {
        
        $pivotFields = explode(',',$pivotFieldsStr);

        if (file_exists($sql)) $sql = file_get_contents($sql); //** if they passed a filename get the sql from there

        $sqlResult = $this->quickQuery($db,$sql);
                           //**sqlPivot($array,    $columnID,      $rowID,         $cellID,         $nullValue)
        $pivotResult = util::sqlPivot($sqlResult,$pivotFields[0],$pivotFields[1],$pivotFields[2], "");

        util::saveMatrix($pivotResult,$outputFilename);
    }


    public function uniqueValues($db, $table, $field, $where = '',$limit = '')
    {

        if ($where != '') $where = " where $where ";
        if ($limit != '') $limit = " limit $limit ";

        if (is_null($db))
        {
            $sql = "SELECT `$field`  FROM $table $where group by `$field` order by `$field` $limit;";
        }
        else
        {
            $sql = "SELECT `$field`  FROM `$db`.`$table` $where group by `$field` order by `$field` $limit;";    
        }
        
        


        return matrix::Column($this->query($sql), $field);
    }


    //** $db              : name of database to connect to
    //** $table_name      : table name to create
    //** $field_prefix    : if column name is numeric then prefix it with this.
    //** $sql             : the SQL to select data
    //** $pivot_column    : What column of SQL result will we look at for Unique values to create
    //**                    columns of pivot table.    MUST EXIST IN SQL result
    //** $pivot_row       : What column of SQL result will we look at for Unique values to create
    //**                    rows id's of pivot table   MUST EXIST IN SQL result
    //** $pivot_value     : What column of SQL result will we look at for Unique values to retive
    //**                    value. ie the cell values  MUST EXIST IN SQL result
    //** $pivot_operation : how do we summarise the values +,-,*,/**, avg may be more see   $this->sqlPivot
    //**
    //** $null_value      : default null value for table

    public function PivotQuery($to_db,$table_name,$field_prefix,$sql, $pivot_column, $pivot_row, $pivot_value,$pivot_operation,$null_value = null, $get_stats = false,$min_row_count = null)
    {
        $add_db_result = $this->query("create database if not exists {$to_db};");

        ErrorMessage::Marker("add_db_result = ".print_r($add_db_result,true));
        
        
        $msg  = " TEST 1213
        to_db...........{$to_db}
	table_name......{$table_name}
	field_prefix....{$field_prefix}
	sql.............{$sql}
	pivot_column....{$pivot_column}
	pivot_row.......{$pivot_row}
	pivot_value.....{$pivot_value}
	pivot_operation.{$pivot_operation}
	null_value......{$null_value}
	get_stats.......{$get_stats}
	min_row_count...{$min_row_count}
	\n     		
        	  ";
        
        ErrorMessage::Marker(__METHOD__."PivotQuery\n{$msg}");
        
        ErrorMessage::Marker(__METHOD__." get first row");
        $first_sql_result = $this->query(str_replace(";", " limit 1;", $sql));
        if (count($first_sql_result) <= 0) return;

        
        ErrorMessage::Marker(__METHOD__. "Pre First Element");
        
        //** check to see if Pivot columns exists in result set
        $first_row = util::first_element($first_sql_result);
        ErrorMessage::Marker(__METHOD__. "Post First Element");
        ErrorMessage::Marker("first_row = " .print_r($first_row,true));
        if (!array_key_exists($pivot_column, $first_row) ||
            !array_key_exists($pivot_row,    $first_row) ||
            !array_key_exists($pivot_value,  $first_row)
            ) 
        {
            ErrorMessage::Marker(__METHOD__. "First Row NOT OK");    
            return null;
        }
        
        
        ErrorMessage::Marker(__METHOD__. "First Row GOOD - getting full query");
        
        $sql_result = $this->query($sql);
        
        ErrorMessage::Marker(__METHOD__. "PivotQuery return count = ".count($sql_result));
        
        
        ErrorMessage::Marker(__METHOD__." Marker 1 ");
        
        if (!is_null($min_row_count))
        {
            //** need to count the number of rows assign to each  $pivot_row
            //** $pivot_row is the name of the column that holds the unique values to assign to the row header

            ErrorMessage::Marker(__METHOD__." Marker 2 ");

            
            //** unique value counts for $pivot_row
            $histogram = matrix::ColumnHistogram($sql_result, $pivot_row);  //** count per unique value
            $sql_row_ids_to_remove = array();
            foreach ($histogram as $histogram_row_id => $count)
            {
                if ($count >= $min_row_count) continue;
                //** here we need to get a list of keys from $sql_result where $row_id  the value in  result[$pivot_row] =  $histogram_row_id
                foreach ($sql_result as $sql_row_id => $sql_row)
                {
                    //** if the column from the sql result we have chosen for the pivrot row id
                    //** has the same value as the $histogram_row_id we are going to mark it for removal
                    if ($sql_row[$pivot_row] == $histogram_row_id)
                        $sql_row_ids_to_remove[] = $sql_row_id;
                }

            }

            ErrorMessage::Marker(__METHOD__." Marker 3 ");
            
            foreach ($sql_row_ids_to_remove as $id) unset($sql_result[$id]);

            unset($histogram);
            unset($sql_row_ids_to_remove);
        }


        ErrorMessage::Marker(__METHOD__." Marker 4 ");
        
        $pivot = util::sqlPivot($sql_result,$pivot_column,$pivot_row,$pivot_value, $null_value, $pivot_operation,false);
        
        ErrorMessage::Marker(__METHOD__." Marker 5");
        
        unset($sql_result);

        if ($get_stats)
        {
            ErrorMessage::Marker(__METHOD__." Marker 6 ");
            $stats = matrix::RowStatistics($pivot,$null_value);
            
            ErrorMessage::Marker(__METHOD__." Marker 7 ");
            
            // Add Stats to Table
            foreach ($pivot as $row_id => $row)
                foreach ($stats[$row_id] as $stats_column => $stats_value)
                    $pivot[$row_id][$stats_column] = $stats_value;

            ErrorMessage::Marker(__METHOD__." Marker 8 ");
            
        }
        
        ErrorMessage::Marker(__METHOD__." Marker 9 ");
        
        $row_count = $this->matrixToTable($pivot,$pivot_row,$to_db, $table_name,$field_prefix ,NULL);
        ErrorMessage::Marker("Wrote {$row_count} rows to {$to_db}.{$table_name} ");
        unset($pivot);
        return $row_count;
        
    }

    
    public function PivotAverage($to_db,$table_name,$field_prefix,$where,$from_table, $pivot_column, $pivot_row, $pivot_value,$null_value = null, $get_stats = false,$min_row_count = null)
    {

        $delim = "|";
        
        $msg  = __METHOD__." 
        to_db...........{$to_db}
	table_name......{$table_name}
	field_prefix....{$field_prefix}
	where...........{$where}
	from_table......{$from_table}
	pivot_column....{$pivot_column}
	pivot_row.......{$pivot_row}
	pivot_value.....{$pivot_value}
	null_value......{$null_value}
	get_stats.......{$get_stats}
	min_row_count...{$min_row_count}
	\n     		
        ";
        
        ErrorMessage::Marker(__METHOD__."{$msg}");
        
        
        $create_db = $this->update("create database if not exists {$to_db};");
        
        ErrorMessage::Marker(__METHOD__."create_db =  {$create_db}");
        
        
        $uniqueColumnValues = $this->uniqueValues(null, $from_table, $pivot_column,$where);
        $uniqueRowValues    = $this->uniqueValues(null, $from_table, $pivot_row   ,$where);

        $template1 = "  select `{$pivot_column}`,
                                {$pivot_value}
                          from  {$from_table}
                         where `{$pivot_row}` = ";
        $template2 = "     and  {$where}
                           and  `{$pivot_column}` in "."(".join(",",$uniqueColumnValues).")"."
                      order by  `{$pivot_column}`";
        
                      
        $column_types = array();
        $column_types[$pivot_row] = "varchar(50)";
        
        foreach ($uniqueColumnValues as $column_name)  $column_types[$field_prefix.str_replace(".","_",$column_name)] = "DOUBLE";

        if ($get_stats)
            foreach (matrix::RowStatisticsNames() as $column_name)  $column_types[$column_name] = "DOUBLE";
                      
        $tempfilename = file::random_filename();
        
        file_put_contents($tempfilename,$pivot_row.$delim.$field_prefix.str_replace(".","_",join("{$delim}{$field_prefix}",$uniqueColumnValues)).$delim.join($delim,matrix::RowStatisticsNames()). "\n", FILE_APPEND);
        
        
        
        $count = 1;
        foreach ($uniqueRowValues as $rowID) 
        {
            //if ($count > 5) continue;
            
            $sql = $template1."'{$rowID}'".$template2;
            
            if ($count % 1000 == 0)
                ErrorMessage::Marker(__METHOD__." {$count} / ".count($uniqueRowValues) );
            
            //ErrorMessage::Marker(__METHOD__."{$rowID} sql = $sql");
            $qresult = $this->query($sql,$pivot_column);
            
            $line_array = array();
            
            foreach ($uniqueColumnValues as $column_name) 
                $line_array[$column_name] = "NULL";

            foreach ($qresult as $column_name => $row_values) 
                $line_array[$column_name] = $row_values[$pivot_value];
            
            
            if ($get_stats)
            {
                $little_matrix = array();
                foreach ($line_array as $column_name => $cell_value) 
                    $little_matrix[$rowID][$column_name] = $cell_value;

                $stats = matrix::RowStatistics($little_matrix,"NULL");

                foreach ($stats[$rowID] as $stats_key => $stats_value) 
                    $line_array[$stats_key] = $stats_value;
                
                unset($stats);
                unset($little_matrix);
                
            }
            
            file_put_contents($tempfilename,$rowID.$delim.join($delim,$line_array)."\n",FILE_APPEND);
            $count++;
            
            unset($line_array);
        }
        
        
        ErrorMessage::Marker(__METHOD__."$tempfilename = cat {$tempfilename}");
        
        $index_these = array();
        $index_these[] = $pivot_row;
        
        $row_count = $this->fromDelimited($tempfilename, $to_db, $table_name, $delim,$column_types,true,$index_these); // when reloading tablefrom delimited don't fully index
        
        unset($index_these);
        
        ErrorMessage::Marker("Wrote {$row_count} rows to {$to_db}.{$table_name} ");
        
        file::Delete($tempfilename);
        
        
        unset($column_types);
        
        return $row_count;
        
        
    }

    public function add_column_varchar($db,$table,$column_name,$size = 100)
    {
        
        return $this->add_column($db,$table,$column_name,"VARCHAR($size)");
    }

    public function add_column_decimal($db,$table,$column_name,$size = 15,$decimal_places = 5)
    {
          
      return $this->add_column($db,$table,$column_name,"double");
    }

    public function add_column_double($db,$table,$column_name)
    {
         
       return $this->add_column($db,$table,$column_name,"double");
    }

    public function add_column($db,$table,$column_name,$db_column_type = "VARCHAR(100)")
    {
        
        $collate = "";
        if (util::contains(strtoupper($db_column_type), 'VARCHAR'))
            $collate = "CHARACTER SET utf8 COLLATE utf8_unicode_ci";

        $sql  = "ALTER TABLE  `$db`.`$table` ADD  `$column_name` $db_column_type $collate NULL;";
        $sql .= "ALTER TABLE  `$db`.`$table` ADD INDEX (  `$column_name` ) ;";

        return $this->update($sql);

    }


    public function hasTable($db,$table)
    {


      $sql = "SELECT count(*) as count FROM information_schema.`TABLES` where TABLE_SCHEMA = '$db'   and TABLE_NAME   = '$table';";
        $sql_result = $this->query($sql);

        $first_row = util::first_element($sql_result);

        if ($first_row['count'] > 0) return true;

        return false;

    }

    public function InsertArray($db,$table,$array)
    {
            

    $useable = array();
        foreach ($array as $key => $value)
            $useable[$key] = (is_null($value) || trim($value) == "") ? 'NULL' : $value;

        $sql   = "insert into `$db`.`$table` (".join(',',array_keys($useable)).") values (".join(',',array_values($useable)).");";


        return $this->insert($sql);

    }

    //** create one (or more) large insert statements
    //** $array = two levels 
    //** [row_index] =  row (column_name => [cell],column_name => [cell],column_name => [cell],column_name => [cell])
    public function InsertArrayBulk($db,$table,$array)
    {
          
      
        if (count($array) == 0) return 0;
        
        $useable = array();

        $column_names = array_keys(util::first_element($array));
        
        $sql   = "insert into `$db`.`$table` (`".join('`,`',$column_names)."`) values ";

        
        foreach ($array as $row_index => $row) 
        {
            foreach ($row as $column_name => $value)
                $useable[$column_name] = (is_null($value) || trim($value) == "") ? 'NULL' : $value;
            
            
            $values = array();
            foreach (array_values($useable) as $single_value) 
                $values[] = is_numeric($single_value) ? $single_value : "'{$single_value}'";
            
            $sql  .= "(".  str_replace("''", "'", join(",",$values))."),";
            
            unset($values);
        }
        
        $sql = util::trim_end(trim($sql), ",").";";
        
        $sql = str_replace("'NULL'", 'NULL', $sql);

        //echo __METHOD__." sql = $sql\n\n";

        
        return $this->insert($sql);

    }
    
    
    public function GroupBy($db,$table,$key_column,$value_column,$where = "")
    {
        

        $where = ($where == "") ?  "" : " where $where ";
        
        $sql = "SELECT {$key_column},{$value_column} FROM `$db`.$table $where group by {$key_column},{$value_column} order by {$key_column},{$value_column};";

        $sql_result = $this->query($sql, $key_column);

        $result = array();
        foreach ($sql_result as $row_id => $row)
            $result[$row[$key_column]] = $row[$value_column];

        unset($sql_result);

        return $result;

    }

    public function Grant($user = null,$db = null,$table = "*",$privilege = "ALL")
    {
        

        if (is_null($db)) $db = $this->db;
        if (is_null($user)) $user = $this->userID;
        
        $sql = "GRANT $privilege PRIVILEGES ON {$db}.{$table} TO '{$user}'@'%' WITH GRANT OPTION;";
        return $this->update($sql);
    }
    

    private function GrantFullAccess()
    {      
        $sql = "GRANT ALL PRIVILEGES ON *.* TO '{$this->user}'@'%' WITH GRANT OPTION;";
        return $this->update($sql);
    }
    

    public function AndClauseFromKeyedArray($src)
    {      
       
        $result_array = array();
        foreach ($src as $key => $value)
            $result_array[] =  (is_numeric($value)) ?  "`$key` = $value" :  "`$key` = '$value'";
        
        return join(' and ',$result_array);
    }
    
    public function CopyTable($fromDB,$fromTable,$toDB,$toTable,$drop_table_first = false, $full_index = false)
    {      
        $row_count = $this->CreateTable($toDB, $toTable, "select * from `$fromDB`.`$fromTable` ",$drop_table_first, $full_index);
        return $row_count;
    }
    
    public function Table2File($db, $tableName,$filename,$delim = ",",$replace_delim = null,$write_row_limit = 5000)
    {      
        $row_count = $this->count("`{$db}`.`{$tableName}`");

        $matrix = $this->selectTable($db, $tableName, "", "", "0,1");
        
        $matrix = matrix::ReplaceStringValue($matrix, '"', '');
        $matrix = matrix::ReplaceStringValue($matrix, "'", '');
        
        
        
        if (!is_null($replace_delim))
            $matrix = matrix::ReplaceStringValue($matrix, $delim, $replace_delim);
        
        matrix::Save($matrix, $filename,$delim, null, null, true, false); //** write the headers and the first row
         
        
        for ($row_number = 1; $row_number < $row_count; $row_number += $write_row_limit) {
            $matrix = $this->selectTable($db, $tableName, "", "", "{$row_number},{$write_row_limit}");

            $matrix = matrix::ReplaceStringValue($matrix, '"', '');
            $matrix = matrix::ReplaceStringValue($matrix, "'", '');
            
            
            if (!is_null($replace_delim))
                $matrix = matrix::ReplaceStringValue($matrix, $delim, $replace_delim);
            
            matrix::Save($matrix, $filename,$delim, null, null, false, true);
            unset($matrix);                
        }
        
        if (!file_exists($filename)) return null; //** if file does not exists then it's wrong -- failed
        
        $file_row_count = file::lineCount($filename); //** check the number of rows in the file
        
        return  (($file_row_count - 1) == $row_count); //** return true if the number of rows in the file macthes the number of rows from the file.
    }
    
    
    public function queryFileSimple($sql,$filename,$delim = ",")
    {
        
        $sql = str_replace("\n"," ", $sql);
        
        $cmd = "mysql --max_join_size=4294967295  --quick -C -u{$this->userID} -p{$this->pwd} --batch --raw --execute='$sql' | tr \"{$delim}\" \" \" | tr '\\t' \"{$delim}\" | sed 's/NULL//g' - > $filename";
        
        ErrorMessage::Marker( __METHOD__."cmd = $cmd");
        
        $output = array();
        $result = exec($cmd,$output);
        
        ErrorMessage::Marker( __METHOD__."result = $result \n".print_r($output,true));
        
        
        return file_exists($filename);
        
        
    }
    
    
    public function table2FileSimple($db, $tableName,$filename,$delim = ",")
    {
        $cmd = "mysql -u{$this->userID} -p{$this->pwd} --batch --raw --execute='select * from `{$db}`.`{$tableName}`' | tr \"{$delim}\" \" \" | tr '\\t' \"{$delim}\" | sed 's/NULL//g' - > $filename";
        
        ErrorMessage::Marker( __METHOD__."cmd = $cmd");
        
        $output = array();
        $result = exec($cmd,$output);
        
        ErrorMessage::Marker( __METHOD__."result = $result \n".print_r($output,true));
        
        
        return file_exists($filename);
        
        
    }
    
    public function table2Zip($db, $tableName,$filename = null ,$delim = ",")
    {
        
        if (is_null($filename)) $filename = "{$db}_{$tableName}.csv";
        
        $cmd = "mysql -h{$this->host} -u{$this->userID} -p{$this->pwd} --batch --raw --execute='select * from `{$db}`.`{$tableName}` ' | tr \"{$delim}\" \" \" | tr '\\t' \"{$delim}\" | sed 's/NULL//g' > $filename";
        exec($cmd);
     
        if (!file_exists($filename)) 
        {
            ErrorMessage::Marker("FAILED to create CSV file from table");
            return false;
        }
        
        $zipped = file::move_to_zip_file($filename);
       
        
        if (is_null($zipped)) 
        {
            ErrorMessage::Marker("FAILED to zip CSV file");
            return false;            
        }
        
        return $filename;
        
    }

    
    public function query2Zip($sql,$filename,$delim = ",")
    {
        
        if (!util::contains($filename, '.csv') ) $filename = $filename.".csv";
        
        $cmd = "mysql --max_join_size=4294967295  --quick -u{$this->userID} -p{$this->pwd} --batch --raw --execute='{$sql}' | tr \"{$delim}\" \" \" | tr '\\t' \"{$delim}\" | sed 's/NULL//g' > $filename";
        exec($cmd);
     
        if (!file_exists($filename)) 
        {
            ErrorMessage::Marker("FAILED to create CSV file from query = {$sql}");
            return false;
        }
        
        $zipped = file::move_to_zip_file($filename);
        
        if (is_null($zipped)) 
        {
            ErrorMessage::Marker("FAILED to zip CSV file");
            return false;            
        }
        
        return $zipped;
        
    }
    

    public function dump_database_table_to_zip($db,$table,$filename = "")
    {
        
        if ($filename == "") $filename = "$db-$table.sql";
        
        $cmd  = "mysqldump -h{$this->HOST()} -u{$this->userID} -p{$this->pwd} $db $table  > $filename" ;
        exec($cmd);
     
        if (!file_exists($filename)) 
        {
            ErrorMessage::Marker("FAILED to create SQL file from $db .. $table");
            return false;
        }
        
        $zipped = file::move_to_zip_file($filename);
        
        if (is_null($zipped)) 
        {
            ErrorMessage::Marker("FAILED to zip SQL file");
            return false;            
        }
        
        file::Delete($filename);
        
        return $zipped;
        
    }
    
    
    
    
    
    
}
?>