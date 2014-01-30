<?php
include_once '../includes.php';


$dbf = util::CommandLineOptionValue($argv,'dbf');
if (is_null($dbf ))
{
    echo "Required parameter [dbf] missing\n";
    exit();
}

$csv = util::CommandLineOptionValue($argv,'csv',null);
$delim = util::CommandLineOptionValue($argv,',');


dbf2csv($dbf,$csv);


function dbf2csv($dbf,$csv = null,$delim = ",",$replace_delim_with = ";")
{
    // get headers only
    $fieldNames = getFieldNames($dbf);
    //print_r($fieldNames);
 
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
    while (!feof($fh))
    {
        $rawRecord = "";
        for ($r = 0; $r < $raw_record_row_count; $r++)  $rawRecord .= str_replace($delim,$replace_delim_with,fgets($fh));
        if (!util::contains($rawRecord, "Record:")) continue; // not a proper record
        
        $array = DoubleExplode($rawRecord,"\n", ":");
        
        if (is_null($csv))
        {
            if ($lineCount == 0)  echo join($delim,  array_keys($array))."\n";
            echo toDelimitedString($array)."\n";
        }
        
        if (!is_null($csv))
        {
            if ($lineCount == 0) file_put_contents($csv, join($delim,  array_keys($array))."\n");
            file_put_contents($csv, toDelimitedString($array)."\n",FILE_APPEND);
        }
        
        $lineCount++;
        
    }
    fclose($fh);
    

    
}

function toDelimitedString($array, $delim = ",")
{
    
    $result = array();
    foreach ($array as $key => $value) 
        $result[$key] = (is_numeric($value)) ? $value : '"'.$value.'"';
    
    return join($delim,$result);
}


function DoubleExplode($str,$record_delim = "\n", $field_delim = ":")
{
    $result = array();
    foreach (explode($record_delim,$str) as $single_line) 
    {
        $pair = array_util::Trim(explode($field_delim,$single_line));        
        if ($pair[0] == '') continue;
        $result[$pair[0]] = array_util::Value($pair, 1);
    }
    
    return $result;
}

function getFieldNames($dbf)
{    
    $rawLines = array();
    exec("dbfdump -h '{$dbf}' | grep 'Field '",$rawLines);
    return array_util::Trim(util::midStrArray($rawLines, '`', "'",true));
}



?>