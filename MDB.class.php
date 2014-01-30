<?php

class MDB
{
    
    public static function TableList($filename)
    {
        $result = array();
        exec("mdb-tables -1 '{$filename}'",$result);
        return $result;   
    }
    
    
    public static function Table2Matrix($filename,$tablename) 
    {
        
        $cmd = "mdb-export -d'|' -R'~~' -X'++' '{$filename}' '{$tablename}'";
        // mdb-export -d'|' -R'~~' -X'++' '/home/afakes/Documents/code/EcoBananas/code/ECO Database 2013.mdb' 'Field Record Sheet' | sed 's/++//g' | sed 's/\n//g' | sed 's/~~/\n/g'
        
        //ErrorMessage::Marker($cmd);
        $mdb_result_raw  = array();
        
        exec($cmd,$mdb_result_raw);
        
        $mdb_result  = explode("~~",$mdb_result_raw[1]);
        sort($mdb_result,SORT_NUMERIC);
        
        //ErrorMessage::Marker($mdb_result);
        
        $headers = str_getcsv($mdb_result_raw[0], '|', '"', "\\");
        
        $result = array();
        for ($index1 = 0; $index1 < count($mdb_result); $index1++) 
        {
            
            $split = str_getcsv($mdb_result[$index1], "|", '"', "\\");    
            
            if (count($split) != count($headers)) continue;
            
            $row = array();
            for ($col = 0; $col < count($split); $col++) 
            {
                $row[$headers[$col]] = $split[$col];
            }
            
            $result[] = $row;
            
        }
        
        return $result;
        
        
    }
    
}

?>
