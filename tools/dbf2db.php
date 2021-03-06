<?php
include_once '../includes.php';


$dbf = util::CommandLineOptionValue($argv,'dbf');
if (is_null($dbf ))
{
    echo "Required parameter [dbf] missing\n";
    exit();
}

$db = util::CommandLineOptionValue($argv,'db');
if (is_null($db))
{
    echo "Required parameter [db] missing  format:   DB:HOST:USER:PASS\n";
    exit();
}

$drop = util::CommandLineOptionValue($argv,'drop',true);
$table = util::CommandLineOptionValue($argv,'table',null);
$text = util::CommandLineOptionValue($argv,'text',null);


dbf2db($dbf,$db,$table,$drop,$text);

function dbf2db($dbf,$db,$table = null,$drop = true,$text = null)
{
    
    list($db,$host,$user,$pass) = explode(":",$db);
    
    $mysql = new database($db,$host,$user,$pass);
    
    // get headers only
    $fieldNames = getFieldNamesAndTypes($dbf);
    $fieldNames['Record'] = 'int(10)';
    //print_r($fieldNames);

    if (is_null($table))
        $table = ucwords(str_replace(".dbf", "", strtolower(basename($dbf))));
    
    $table = $mysql->CreateTableFromArray($db, $table, $fieldNames,$drop,true,null,false);
    
    //echo "CReated = [$table]\n";
    
    // export to temp file and process that line by line
    $extracted_filename = "{$dbf}.extracted";
    
    if (!file_exists($extracted_filename))
    {
        exec("dbfdump -m '{$dbf}' > '{$extracted_filename}'");
        if (!file_exists($extracted_filename)) return null;
    }
    
    $raw_record_row_count = count($fieldNames) + 2; // how many rows to read  2 + numbewr of fields 
    
    // process extracted file
    $fh = fopen($extracted_filename, "r");
    
    $lineCount = 0;
    $inserted_result = array();
    $inserted_result['exists'] = 0;
    $inserted_result['failed'] = 0;
    $inserted_result['insert'] = 0;
    
    while (!feof($fh))
    {
        $rawRecord = "";
        for ($r = 0; $r < $raw_record_row_count; $r++)  
            $rawRecord .= fgets($fh);
        
        if (!util::contains($rawRecord, "Record:")) continue; // not a proper record
        
        if (!is_null($text) && !util::contains($rawRecord, $text)) continue; // we ask that it must contain tisa text and it did not
        
        // $array = ;
        // field names and values will be used in database insert array;
        
        $array = DoubleExplode(str_replace('"', "", str_replace("'", "", $rawRecord)),"\n", ":");
        
        $existing_count = 0;
        if ($drop == false)
            $existing_count = $mysql->count($table, null, "Record = {$array['Record']}"); // we did not drop the table so we want to heck that we don't have tis row already
        
        if($existing_count <= 0)
            $insert_array_result = $mysql->InsertArray($db, $table, $array);
        
        
        if ($existing_count > 0)
        {
            echo "E";
            $inserted_result['exists'] += 1;
        }
        else 
        {
            if ($insert_array_result <= 0)
            {
                echo "\nFAILED: {$rawRecord}.\n";
                $inserted_result['failed'] += 1;
            }
            else
            {
                echo "I";
                $inserted_result['insert'] += 1;
            }
            
        }
        
        
         //print_r($array);
        
        $lineCount++;
        
    }
    fclose($fh);
    
    echo "\nrow_count[$table]: {$mysql->count($table)}\n";    
    foreach ($inserted_result as $key => $value) 
        echo "$key:$value\n";
    
    echo "\n";

    unset($mysql);
    
}

function DoubleExplode($str,$record_delim = "\n", $field_delim = ":")
{
    $result = array();
    foreach (explode($record_delim,$str) as $single_line) 
    {
        $pair = explode($field_delim,$single_line);
        if ($pair[0] == '') continue;
        $result[trim($pair[0])] = trim(array_util::Value($pair, 1));
    }
    
    return $result;
}

function getFieldNames($dbf)
{    
    $rawLines = array();
    exec("dbfdump -h '{$dbf}' | grep 'Field '",$rawLines);
    return array_util::Trim(util::midStrArray($rawLines, '`', "'",true));
}

function getFieldNamesAndTypes($dbf)
{    
    $rawLines = array();
    exec("dbfdump -h '{$dbf}' | grep 'Field '",$rawLines);
    
    $result = array();

    foreach ($rawLines as $rawLine) 
    {
        // must contain these to be vaild header details
        if (!util::contains($rawLine, 'Title=')) continue;
        if (!util::contains($rawLine, 'Type=')) continue;
        if (!util::contains($rawLine, 'Width=')) continue;
        if (!util::contains($rawLine, 'Decimals=')) continue;
        
        $name = util::midStr($rawLine, "Title=`", "'", true, false);
        $raw_type = util::midStr($rawLine, "Type=", ",", true, false);
        $raw_width = util::midStr($rawLine, "Width=", ",", true, false);
        $raw_decimals = util::rightStr($rawLine, "Decimals=", false);
        
        $raw_type = strtolower(trim($raw_type));
        
        $raw_width += 2; // make all widths just a bit bigger 
        
        $type = "varchar(500)";
        switch ($raw_type) 
        {
            case 'integer': $type = "int({$raw_width})"; break;
            case 'double' : $type = "decimal({$raw_width},{$raw_decimals})"; break;
            case 'string' : $type = "varchar({$raw_width})"; break;
        }
        
        $result[$name] = $type;
        
    }
    
    
    return $result;
}





?>