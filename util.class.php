<?php
class util {

    // some change
    
    /**
     * Characters that are not letters or numbers 
     * - may not be wanted in inputs
     * 
     * @var type 
     */
    public static $EXTRA_CHARS = '@##$%^&*()_+-={}[]\|:";\'\\<>,.?/`~';

    public static function country_code_array()
    {
    
$text = <<<COUNTRY
AF,Afghanistan
AL,Albania
DZ,Algeria
AS,American Samoa
AD,Andorra
AO,Angola
AI,Anguilla
AQ,Antarctica
AG,Antigua and Barbuda
AR,Argentina
AM,Armenia
AW,Aruba
AU,Australia
AT,Austria
AZ,Azerbaijan
BS,Bahamas
BH,Bahrain
BD,Bangladesh
BB,Barbados
BY,Belarus
BE,Belgium
BZ,Belize
BJ,Benin
BM,Bermuda
BT,Bhutan
BO,Bolivia, Plurinational State of
BQ,Bonaire, Sint Eustatius and Saba
BA,Bosnia and Herzegovina
BW,Botswana
BV,Bouvet Island
BR,Brazil
IO,British Indian Ocean Territory
BN,Brunei Darussalam
BG,Bulgaria
BF,Burkina Faso
BI,Burundi
KH,Cambodia
CM,Cameroon
CA,Canada
CV,Cape Verde
KY,Cayman Islands
CF,Central African Republic
TD,Chad
CL,Chile
CN,China
CX,Christmas Island
CC,Cocos (Keeling) Islands
CO,Colombia
KM,Comoros
CG,Congo
CD,Congo, the Democratic Republic of the
CK,Cook Islands
CR,Costa Rica
HR,Croatia
CU,Cuba
CW,Curaçao
CY,Cyprus
CZ,Czech Republic
CI,Côte d&#x27;Ivoire
DK,Denmark
DJ,Djibouti
DM,Dominica
DO,Dominican Republic
EC,Ecuador
EG,Egypt
SV,El Salvador
GQ,Equatorial Guinea
ER,Eritrea
EE,Estonia
ET,Ethiopia
FK,Falkland Islands (Malvinas)
FO,Faroe Islands
FJ,Fiji
FI,Finland
FR,France
GF,French Guiana
PF,French Polynesia
TF,French Southern Territories
GA,Gabon
GM,Gambia
GE,Georgia
DE,Germany
GH,Ghana
GI,Gibraltar
GR,Greece
GL,Greenland
GD,Grenada
GP,Guadeloupe
GU,Guam
GT,Guatemala
GG,Guernsey
GN,Guinea
GW,Guinea-Bissau
GY,Guyana
HT,Haiti
HM,Heard Island and McDonald Islands
VA,Holy See (Vatican City State)
HN,Honduras
HK,Hong Kong
HU,Hungary
IS,Iceland
IN,India
ID,Indonesia
IR,Iran, Islamic Republic of
IQ,Iraq
IE,Ireland
IM,Isle of Man
IL,Israel
IT,Italy
JM,Jamaica
JP,Japan
JE,Jersey
JO,Jordan
KZ,Kazakhstan
KE,Kenya
KI,Kiribati
KP,Korea, Democratic People&#x27;s Republic of
KR,Korea, Republic of
KW,Kuwait
KG,Kyrgyzstan
LA,Lao People&#x27;s Democratic Republic
LV,Latvia
LB,Lebanon
LS,Lesotho
LR,Liberia
LY,Libya
LI,Liechtenstein
LT,Lithuania
LU,Luxembourg
MO,Macao
MK,Macedonia, The Former Yugoslav Republic of
MG,Madagascar
MW,Malawi
MY,Malaysia
MV,Maldives
ML,Mali
MT,Malta
MH,Marshall Islands
MQ,Martinique
MR,Mauritania
MU,Mauritius
YT,Mayotte
MX,Mexico
FM,Micronesia, Federated States of
MD,Moldova, Republic of
MC,Monaco
MN,Mongolia
ME,Montenegro
MS,Montserrat
MA,Morocco
MZ,Mozambique
MM,Myanmar
NA,Namibia
NR,Nauru
NP,Nepal
NL,Netherlands
NC,New Caledonia
NZ,New Zealand
NI,Nicaragua
NE,Niger
NG,Nigeria
NU,Niue
NF,Norfolk Island
MP,Northern Mariana Islands
NO,Norway
OM,Oman
PK,Pakistan
PW,Palau
PS,Palestinian Territory, Occupied
PA,Panama
PG,Papua New Guinea
PY,Paraguay
PE,Peru
PH,Philippines
PN,Pitcairn
PL,Poland
PT,Portugal
PR,Puerto Rico
QA,Qatar
RO,Romania
RU,Russian Federation
RW,Rwanda
RE,Réunion
BL,Saint Barthélemy
SH,Saint Helena, Ascension and Tristan da Cunha
KN,Saint Kitts and Nevis
LC,Saint Lucia
MF,Saint Martin (French part)
PM,Saint Pierre and Miquelon
VC,Saint Vincent and the Grenadines
WS,Samoa
SM,San Marino
ST,Sao Tome and Principe
SA,Saudi Arabia
SN,Senegal
RS,Serbia
SC,Seychelles
SL,Sierra Leone
SG,Singapore
SX,Sint Maarten (Dutch part)
SK,Slovakia
SI,Slovenia
SB,Solomon Islands
SO,Somalia
ZA,South Africa
GS,South Georgia and the South Sandwich Islands
SS,South Sudan
ES,Spain
LK,Sri Lanka
SD,Sudan
SR,Suriname
SJ,Svalbard and Jan Mayen
SZ,Swaziland
SE,Sweden
CH,Switzerland
SY,Syrian Arab Republic
TW,Taiwan, Province of China
TJ,Tajikistan
TZ,Tanzania, United Republic of
TH,Thailand
TL,Timor-Leste
TG,Togo
TK,Tokelau
TO,Tonga
TT,Trinidad and Tobago
TN,Tunisia
TR,Turkey
TM,Turkmenistan
TC,Turks and Caicos Islands
TV,Tuvalu
UG,Uganda
UA,Ukraine
AE,United Arab Emirates
GB,United Kingdom
US,United States
UM,United States Minor Outlying Islands
UY,Uruguay
UZ,Uzbekistan
VU,Vanuatu
VE,Venezuela, Bolivarian Republic of
VN,Viet Nam
VG,Virgin Islands, British
VI,Virgin Islands, U.S.
WF,Wallis and Futuna
EH,Western Sahara
YE,Yemen
ZM,Zambia
ZW,Zimbabwe
AX,Åland Islands        
COUNTRY;
    

    }

    public static function dbq($str,$forceCharacter = false)
    {
        if ($forceCharacter) return "E'".  str_replace("'", "\'", $str)."'";
        
        if (is_numeric($str)) return $str;
        
        if (strtolower($str) == "null") return $str;
        
        return "E'".  str_replace("'", "\'", $str)."'";
    }
    

    public static function dbqKeyedArray($array,$operator = "=",$delim = ",") 
    {
    
        $results = array();
        foreach ($array as $key => $value) 
        {
            if (is_numeric($value))
                $results[] = $key." ".$operator." ".$value;    
            else
                $results[] = $key." ".$operator." ".util::dbq($value);
        }
        

        $result = implode($delim, $results);
        
        return $result;
        
    }
    
    public static function boolean2string($src) 
    {
        if ($src == true) return "true";
        return "false";
        
    }

    public static function string2boolean($src) 
    {
        if (strtolower($src) == "true") return true;
        return false;
        
    }
    
    
    public static function isPublicMethod($obj,$methodName)
    {
        $rm = new ReflectionMethod($obj, $methodName);

        return array_util::Contains(Reflection::getModifierNames($rm->getModifiers()), 'public');

    }
    
    
    public static function Log($from,$str) 
    {
        error_log("APPLICATION ERROR:: ".$from."::".$str);
    }
    
    
/**
* Extract the value of a commandline option that looks like --option=value
*
* @param type $array - usually $argv
* @param type $optionName - the name of the commandline option whose value you want e.g. --userid=fred, then ask for "userid"
* @param type $default - if the commandline option asked for does not exists on the command line then return this value
* @return type string
*/
    public static function  CommandLineOptionValue($array,$optionName,$default = null)
    {
        
        $result = $default;
        foreach ($array as $value) 
        {
            if (util::contains($value, "--{$optionName}="))
                $result = str_replace("--{$optionName}=", '', $value);
        }
        
        $result = str_replace("'", '', $result);
        
        if (trim($result) == "true") return true;
        if (trim($result) == "false") return false;
        
        return $result;

    }
    
    
    
    /**
    * @method isWebBrowser
    * @return mixed
    */
    public static function isWebBrowser()
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) return true;
        if (trim($_SERVER['HTTP_USER_AGENT']) != "") return true;
        
        return false;
    }

    /**
    * @method hostname 
    * @return mixed
    */
    public static function hostname()
    {
        return exec("hostname");
    }



    /**
    * @method scriptName 
    * @return mixed
    */
    public static function scriptName()
    {
        if (isset($_SERVER['PHP_SELF'])) return $_SERVER['PHP_SELF'];
        return $argv[0];
    }


    /**
    * @method visualWait 
    * @param $Time 
    * @return mixed
    */
    public static function visualWait($seconds)
    {

        for ($index = 0; $index <= $seconds; $index++)
        {
            //echo "$index ";
            sleep(1);
        }

       // echo "\n";
    }

    /**
     * @method get number of days in month
     * @return array - if nothing passed
     * @return single value  - if passed year and month
    */
    public static function daysInMonth($year,$month)
    {
        $months = array();
        $months[1] = 31;
        $months[2] = date('L', strtotime("$year-01-01")) ? 29 : 28;
        $months[3] = 31;
        $months[4] = 30;
        $months[5] = 31;
        $months[6] = 30;
        $months[7] = 31;
        $months[8] = 31;
        $months[9] = 30;
        $months[10] = 31;
        $months[11] = 31;
        $months[12] = 31;

        return $months[$month];
    }



    /**
    * @method removeString 
    * @param $src 
    * @param $remove 
    * @return mixed
    */
    public static function removeString($src,$remove)
    {
        return str_replace($remove, "", $src);
    }






    /**
    * @method uniqueColumnNames 
    * @param $src 
    * @return mixed
    */
    public static function uniqueColumnNames($src)
    {
        // go thru each cell value for each column if this column does not have the value then add it.,

        if (!is_array( util::first_element($src) ))
        {
            //echo "##Error util::uniqueColumnNames  src does not look like a matrix\n";
            return NULL;
        }

        $columns = array();
        foreach ($src as $rowID => $row)
            foreach ($row as $columnName => $cellValue)
                $columns[$columnName] = "";

        return array_keys($columns);

    }




    /**
    * @method sqlPivot 
    * @param $array 
    * @param $columnID 
    * @param $rowID 
    * @param $cellID 
    * @param $nullValue 
    * @return mixed
    */
    public static function sqlPivot($array,$columnID,$rowID,$cellID, $nullValue = null, $operation = '+',$sort_columns = false)
    {

    	$msg = "\n
    	columnID......$columnID
    	rowID.........$rowID
    	cellID........$cellID
    	nullValue.....$nullValue
    	operation.....$operation
    	sort_columns..$sort_columns
    	\n";

        ErrorMessage::Marker( __METHOD__." {$msg}");
        
    	ErrorMessage::Marker(__METHOD__." Marker 1");
    	
        // get unique values for $columnID,$rowID
        
        $cols = array();
        $cols[] = $columnID;
        $cols[] = $rowID;
        
        $unique = util::sqlUniqueColumnValues($array,$cols);

    	ErrorMessage::Marker(__METHOD__." Marker 2");
        
        $uniqueColumnValues = $unique[$columnID];
        $uniqueRowValues    = $unique[$rowID];

        
        if ($sort_columns) sort($uniqueColumnValues);
        ErrorMessage::Marker(__METHOD__." Marker 3");


        // make a matrix that is from these unique Values $columnID,$rowID

        // initalise matrix
        $matrix = array();

        $matrix_counts = array(); // hold how many values will make op this cell - used for avg and stddev ...

        ErrorMessage::Marker(__METHOD__." Marker 4");

        
        foreach ($uniqueRowValues as $rowValue)
        {
            $matrix[$rowValue] = array();

            foreach ($uniqueColumnValues as $columnValue)
            {
                $matrix[$rowValue][$columnValue] = $nullValue;
                $matrix_counts[$rowValue][$columnValue] = 0;
            }
        }

        ErrorMessage::Marker(__METHOD__." Marker 5");

        

        foreach ($array as $srcID => $srcRow)
        {

            $col = $srcRow[$columnID];
            $row = $srcRow[$rowID];

            
            $matrix[$row][$col] = null;
            
            if (!is_null($srcRow[$cellID]))  // only write data to matrix if it is not null
            {
                switch ($operation) {
                    case '+': $matrix[$row][$col] =  $matrix[$row][$col] + $srcRow[$cellID]; break; // sum
                    case '-': $matrix[$row][$col] =  $matrix[$row][$col] - $srcRow[$cellID]; break; // subtract
                    case '/': $matrix[$row][$col] =  $matrix[$row][$col] / $srcRow[$cellID]; break; // divide
                    case '*': $matrix[$row][$col] =  $matrix[$row][$col] * $srcRow[$cellID]; break; // product
                    case 'mean':
                    case 'avg':
                        $matrix[$row][$col] =  $matrix[$row][$col] + $srcRow[$cellID]; break; // m,ean

                    default:
                        break;
                }
                
                $matrix_counts[$row][$col]++; // only count if the value is not null
                
            }
            
        }

        ErrorMessage::Marker(__METHOD__." Marker 6");

        
        ksort($matrix);

        ErrorMessage::Marker(__METHOD__." Marker 7");

        
        // check to see if operation requires matrix counts
        $use_matrix_counts = FALSE;
        switch ($operation) {
            case 'mean':
            case 'avg':
                $use_matrix_counts = TRUE; break; // sum
        }
        
        ErrorMessage::Marker(__METHOD__." Marker 8");


        // if we have asked for a function that uses the $matrix_counts process it here
        if ($use_matrix_counts)
        {
            ErrorMessage::Marker(__METHOD__." Marker 9");
            
            // process matrix
            foreach ($matrix as $row_id => $row)
            {
                foreach ($row as $column_id => $cell_value)
                {
                    switch ($operation) {
                        case 'mean':
                        case 'avg':
                            

                            if (!$matrix[$row_id][$column_id] == $nullValue || !is_null($matrix[$row_id][$column_id]) )
                            {
                                if ($matrix_counts[$row_id][$column_id] == 0)
                                {
                                    $matrix[$row_id][$column_id] =  null;  // asked for average and we have zero count
                                }
                                else
                                {
                                    $matrix[$row_id][$column_id] =  ($matrix[$row_id][$column_id] / $matrix_counts[$row_id][$column_id]) ;
                                }
                                
                                
                            }
                                
                        break; // average

                    }
                }
            }

        }

        ErrorMessage::Marker(__METHOD__." Marker 10");
        
        return $matrix;

    }



    /**
    * @method sqlInfo 
    * @param $sqlResult 
    * @return mixed
    */
    public static function sqlInfo($sqlResult)
    {

        displayMatrix($sqlResult);

        $unique = sqlUniqueColumnValues($sqlResult);
        foreach ($unique as $key => $value)
            echo "\n\n$key: " . join("\t",$value);

    }




    /**
    * @method sqlUniqueColumnValues 
    * @param $sqlResult 
    * @return mixed
    */
    public static function sqlUniqueColumnValues($sqlResult,$names = null)
    {        
        ErrorMessage::Marker(__METHOD__." Start ");
        
        $columns = array();
        if (is_array($names)) 
        {
            ErrorMessage::Marker(__METHOD__." getting for array ".  join(", ", $names));
            
            foreach ($names as $columnName) 
                $columns[$columnName] = array();
        } else 
            foreach (array_keys(util::first_element($sqlResult)) as $columnName) 
                $columns[$columnName] = array();            
        
        
        foreach ($sqlResult as $row)
        {
            // go thru each cell value for each column
            // if this column does not have the value then add it.,
            foreach ($row as $columnName => $cellValue)
                $columns[$columnName][$cellValue] = $cellValue;
            
        }

        ErrorMessage::Marker(__METHOD__." End ");
        
        return $columns;


    }

    // failed  - array of rows and counts
    // success - cell count

    /**
    * @method checkCellCount 
    * @param $link 
    * @param $tableName 
    * @return mixed
    */
    public static function checkCellCount($link, $tableName)
    {

        $bandColumn = 'band';
        $valueCountColumn = 'ValueCount';
        $valueCountSumColumn = 'ValueCountSum';

        $sqlResult = groupSum($link, $tableName, $bandColumn, $valueCountColumn);  // returns Column as ValueCountSum

        $avg = arrayAverage($sqlResult, $valueCountSumColumn);

        // check that each ValueCountSum ==  $avg
        $checkArray = array();
        foreach ($sqlResult as $bandNum => $row)
            $checkArray[$bandNum] = $row[$valueCountSumColumn] / $avg;

        $shouldBeZero = array_sum($checkArray) - count($sqlResult);

        if ($shouldBeZero == 0) return $avg;

        return $sqlResult;


    }







    /**
    * @method groupSum 
    * @param $link 
    * @param $tableName 
    * @param $groupOn 
    * @param $sumOn 
    * @return mixed
    */
    public static function groupSum($link, $tableName, $groupOn, $sumOn)
    {
    // check that the cell count is the same for each band
$sql = <<<SQL
select $groupOn, sum($sumOn) as "$sumOn\Sum"
from $tableName
group by $groupOn
order by $groupOn
;
SQL;

        $sqlResult = query($sql,$link, $groupOn);

        return $sqlResult;

    }




    /**
    * @method extractFileToDatabase 
    * @param $filename 
    * @param $tableName 
    * @return mixed
    */
    public static function extractFileToDatabase($filename, $tableName)
    {

        $statsFile = file_get_contents($filename);

        $rawHistogramData = explode('Histogram',$statsFile);
        $result = processAllHistograms($rawHistogramData);
        $uniqueValues = getUniqueValues($result);
        $table = createTable($uniqueValues, $result);

        $link = connect();
        writeHistogramToDatabase($table, $tableName);    // Add histogram data to database
        disconnect($link);

    }



    /**
    * @method createTable 
    * @param $uniqueValues 
    * @param $result 
    * @return mixed
    */
    public static function createTable($uniqueValues, $result)
    {

        // create table array where unique values are the keys
        // and the band names are the columns
        $table = array();
        foreach ($uniqueValues as $uniqueValue)
        {

            $tableRowId = $uniqueValue;

            $table[$tableRowId] = array();

            // each band name
            foreach ($result as $bandName => $bandCountArray)
            {
                $table[$tableRowId][$bandName] = 0;

                // if this unique values exsists for a band then get it's count
                if (array_key_exists($uniqueValue, $bandCountArray))
                    $table[$tableRowId][$bandName] = $bandCountArray[$uniqueValue];

            }

        }

        return $table;

    }


    /**
    * @method deleteTable 
    * @param $tableName 
    * @return mixed
    */
    public static function deleteTable($tableName)
    {
        $result = mysql_query("delete from $tableName");
        return $result;
    }


    /**
    * @method explodeTree 
    * @param $array 
    * @param $delimiter = '_' 
    * @param $baseval = false 
    * @return mixed
    */
    public static function explodeTree($array, $delimiter = '_', $baseval = false)
    {
        if(!is_array($array)) return false;

        if(!is_array($array)) return false;
        $splitRE   = '/' . preg_quote($delimiter, '/') . '/';
        $returnArr = array();
        foreach ($array as $key => $val) {
            // Get parent parts and the current leaf
            $parts    = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
            $leafPart = array_pop($parts);

            // Build parent structure
            // Might be slow for really deep and large structures
            $parentArr = &$returnArr;
            foreach ($parts as $part) {
                if (!isset($parentArr[$part])) {
                    $parentArr[$part] = array();
                } elseif (!is_array($parentArr[$part])) {
                    if ($baseval) {
                        $parentArr[$part] = array('__base_val' => $parentArr[$part]);
                    } else {
                        $parentArr[$part] = array();
                    }
                }
                $parentArr = &$parentArr[$part];
            }

            // Add the final part to the structure
            if (empty($parentArr[$leafPart])) {
                $parentArr[$leafPart] = $val;
            } elseif ($baseval && is_array($parentArr[$leafPart])) {
                $parentArr[$leafPart]['__base_val'] = $val;
            }
        }
        return $returnArr;
    }



    /**
    * @method replaceInKey 
    * @param $srcArray 
    * @param $find_str 
    * @param $replace_str 
    * @return mixed
    */
    public static function replaceInKey($srcArray,$find_str,$replace_str)
    {
        $result = array();

        foreach ($srcArray as $index => $filename)
        {

            $newKey = str_replace($find_str, $replace_str, $index);
            $result[$newKey] = $filename;
        }

        return $result;
    }



    /**
    * @method toLastSlash 
    * @param $src 
    * @param $slashType = "" 
    * @return mixed
    */
    public static function toLastSlash($src, $slashType = "/")
    {
        return self::toLastChar($src, $slashType);
    }



    /**
    * @method toLastChar 
    * @param $src 
    * @param $charType = "" 
    * @return mixed
    */
    public static function toLastChar($src, $charType = "")
    {

        $pos = strrpos($src, $charType);
        if ($pos === false) return $src;
        $result = substr($src,0,$pos);
        return $result;
    }


    /**
    * @method fromLastSlash 
    * @param $src 
    * @param $slashType = "/**" 
    * @return mixed
    */
    public static function fromLastSlash($src, $slashType = "/")
    {
        return self::fromLastChar($src, $slashType);
    }



    /**
    * @method fromLastChar 
    * @param $src 
    * @param $charType = "" 
    * @return mixed
    */
    public static function fromLastChar($src, $charType = "")
    {

        if (is_array($src)) return self::fromLastCharArray($src, $charType);
        
        
        $pos = strrpos($src, $charType);
        if ($pos === false) return $src;

        return substr($src,$pos + 1);
    }

    
    /**
    * @method fromLastChar 
    * @param $src 
    * @param $charType = "" 
    * @return mixed
    */
    public static function fromLastCharArray($src, $charType = "")
    {

        $result = array();
        
        foreach ($src as $key => $value) 
            $result[$key] = self::fromLastChar($value, $charType);

        return $result;
    }
    

/**
 * @method contains      - search a string for another string ans return TRUE if it's there
 * @param $in            - the string to search
 * @param $find          - to string to look for
 * @param $caseSensitive - case sensitive search  true/false
 * @return boolean
*/
    public static function contains($in, $find, $caseSensitive = TRUE )
    {
        $pos = strpos( $in, $find);
        if (!$caseSensitive) $pos = stripos( $in, $find);

        if ($pos === false) return FALSE;
        return TRUE;
    }




    /**
    * @method URL Value
    * @param $array
    * @param $findIn
    * @return mixed
    */
    public static function urlValue($key,$default)
    {
        return self::arrayValue($_GET, $key,$default);
    }





    /**
    * @method trim_end 
    * @param $toTrim 
    * @param $trimOff 
    * @return mixed
    */
    public static function trim_end($toTrim,$trimOff)
    {
        $lastChar = substr($toTrim,count($toTrim) - 2,1);
        if ($lastChar != $trimOff) return $toTrim;
        return substr($toTrim,0,count($toTrim) - 2);
    }


    /**
    * @method last_char 
    * @param $str 
    * @return mixed
    */
    public static function last_char($str)
    {
        $lastChar = substr($str,count($str) - 1,1);
        return $lastChar;
    }


    /**
    * @method first_char 
    * @param $str 
    * @return mixed
    */
    public static function first_char($str)
    {
        return substr($str,0,1);
    }



    /**
    * @method last_element 
    * @param $array 
    * @return mixed
    */
    public static function last_element($array)
    {
        $lastIndex = count($array);

        $vals = array_values($array);

        return $vals[$lastIndex - 1];
    }

    /**
    * @method last_element
    * @param $array
    * @return mixed
    */
    public static function last_key($array)
    {
        $lastIndex = count($array);

        $keys = array_keys($array);

        return $keys[$lastIndex - 1];
    }



    /**
    * @method first_element 
    * @param $array 
    * @return mixed
    */
    public static function first_element($array)
    {        
        return reset($array);
    }

    /**
    * @method first_element
    * @param $array
    * @return mixed
    */
    public static function first_key($array)
    {
        $keys = array_keys($array);
        return $keys[0];
    }



    /**
    * @method midStr 
    * @param $src 
    * @param $from 
    * @param $to 
    * @return mixed
    */
    public static function midStr($src,$from = null,$to = null,$must_contain = false, $include_delim = true)
    {
        
        if (is_array($src)) 
            return self::midStrArray($src,$from,$to,$must_contain);
        
        // if it must conatin to and from then check them
        if ($must_contain)
        {
            if (is_null($from)) return NULL;
            if (is_null($to)  ) return NULL;
            if (strpos($src, $from) === false) return NULL;
            if (strpos($src, $to)   === false) return NULL;
        }
        
        $posFrom = (is_null($from)) ? 0            :  strpos($src, $from) + strlen($from); 
        $posTo   = (is_null($to))   ? strlen($src) :  strpos($src, $to,$posFrom + 1);
        
        if ($posFrom === FALSE) $posFrom = 0;          // if we can't find from then set to start of string
        if ($posTo   === FALSE) $posTo = strlen($src); // if we can't find to set to end of string
        
        
        
        $result = substr($src, $posFrom, $posTo - $posFrom );

        if (!$include_delim)
        {
            $result = str_replace($from, "", $result);
            $result = str_replace($to, "", $result);
        }
        
        return $result;

    }

    /**
    * @method midStr 
    * @param $src 
    * @param $from 
    * @param $to 
    * @return mixed
    */
    public static function midStrArray($src,$from = null,$to = null,$must_contain = false)
    {
        
        $result = array();
        foreach ($src as $key => $value) 
        {
            $v = self::midStr($value,$from,$to,$must_contain);
            if (!is_null($v)) $result[$key] = $v;
        }
        
        return $result;

    }
    
    


    /**
    * @method leftStrFrom 
    * @param $src 
    * @param $fromChar 
    * @return mixed
    */
    public static function leftStrFrom($src,$fromChar,$includeFromChar = TRUE)
    {
        $pos = strpos($src, $fromChar);
        if ($pos === FALSE) return $src;

        $result = substr($src, $pos);

        if (!$includeFromChar)
            $result = trim($result,$fromChar);
        
        return $result;

    }



    /**
    * @method leftStr 
    * @param $src 
    * @param $toChar 
    * @return mixed
    */
    public static function leftStr($src,$toChar,$includeDelim = TRUE)
    {
        if(is_array($src)) return self::leftStrArray($src, $toChar, $includeDelim);
        
        $pos = strpos($src, $toChar);

        if ($pos === FALSE) return $src;

        $result = substr($src, 0, $pos);

        if (!$includeDelim)
            $result = trim($result,$toChar);

        return $result;

    }

    public static function leftStrArray($src,$toChar,$includeDelim = TRUE)
    {
        
        $result = array();
        foreach ($src as $key => $value) 
            $result[$key] = self::leftStr($value,$toChar,$includeDelim);

        return $result;

    }
    
    

    /**
    * @method rightStr 
    * @param $src 
    * @param $toChar 
    * @return mixed
    */
    public static function rightStr($src,$fromChar,$includeFromChar = true)
    {
        $pos = strrpos($src, $fromChar);
        if ($pos === false) return $src;

        $result =  substr($src, $pos);

        if (!$includeFromChar)
            $result = trim($result, $fromChar);

        return $result;

    }



    /**
    * @method copyFiles 
    * @param $array 
    * @return mixed
    */
    public static function copyFiles($array)
    {

        $result = array();
        foreach ($array as $src => $dest)
            $result[$src] = FALSE;
            if (file_exists($src))
                $result[$src] = copy($src,$dest);

        return $result;

    }


    function lastday($month = '', $year = '') {
       $result = strtotime("{$year}-{$month}-01");
       $result = strtotime('-1 second', strtotime('+1 month', $result));
       return date('Y-m-d', $result);
    }



// return matrix that each row is an accumulation of the previous

    /**
    * @method accumMatrix
    * @param $src
    * @return mixed
    */
    public static function accumMatrix($src)
    {
        return matrix::accumulate($src);
    }

    /**
    * @method accumLimit
    * @param matrix $src
    * @param $limit
    * @return mixed value for each column where the cell value is less than or equial to (but not greater than $limit )
    */
    public static function accumLimit($src, $limit)
    {
        return matrix::accumLimit($src, $limit);
    }



    /**
    * @method MatrixCell
    * @param $matrix
    * @param $rowID
    * @param $column
    * @param $noValue = null
    * @return mixed
    */
    public static function MatrixCell($matrix, $rowID,$column, $noValue = null)
    {
        return matrix::Cell($matrix, $rowID, $column) ;
    }

    /**
    * @method displayMatrix
    * @param $src
    * @param $delim = "\t"
    * @return mixed
    */
    public static function displayMatrix($src, $delim = "\t")
    {
        echo matrix::display($src, $delim);
    }


    /**
    * @method printableMatrix
    * @param $src
    * @param $delim = "\t"
    * @return mixed
    */
    public static function printableMatrix($src, $delim = "\t")
    {
        return matrix::printable($src, $delim);
    }


    /**
    * @method matrix2HTMLTable
    * @param $src
    * @return mixed
    */
    public static function matrix2HTMLTable($src, $style="")
    {
        return matrix::toHTML($src, $style);
    }

    /**
    * @method loadMatrix
    * @param $filename
    * @param $delim = "
    * @param "
    * @param $rowID = ""
    * @return mixed
    */
    public static function loadMatrix($filename,$delim = ",",$rowID = "")
    {
        return matrix::load($filename, $delim, $rowID);
    }

    /**
    * @method saveMatrix
    * @param $src
    * @param $filename
    * @param $delim = "
    * @param "
    * @return mixed
    */
    public static function saveMatrix($src,$filename,$delim = ",")
    {
        return matrix::save($src, $filename, $delim);
    }


    /**
    * @method orderMatrixByRow
    * @param $src
    * @return mixed
    */
    public static function orderMatrixByRow($src)
    {
        return matrix::sortRows($src);
    }



    public static function TimeDifference($date1, $date2)
    {
        $date1 = is_int($date1) ? $date1 : strtotime($date1);
        $date2 = is_int($date2) ? $date2 : strtotime($date2);
        
        if (($date1 !== false) && ($date2 !== false)) {
            if ($date2 >= $date1) {
                $diff = ($date2 - $date1);

                if ($days == intval((floor($diff * 86400))))
                    $diff %= 86400;
                if ($hours == intval((floor($diff * 3600))))
                    $diff %= 3600;
                if ($minutes == intval((floor($diff * 60))))
                    $diff %= 60;

                return array($days, $hours, $minutes, intval($diff));
            }
        }

        return false;
    }

    public static function TimeDifferenceSeconds($date1, $date2)
    {
        $date1 = is_int($date1) ? $date1 : strtotime($date1);
        $date2 = is_int($date2) ? $date2 : strtotime($date2);

        if (($date1 !== false) && ($date2 !== false))
            if ($date2 >= $date1)
                $diff = ($date2 - $date1);

        return $diff;
    }


    // convert Bigendian (NASA) written floats to PC level
    public static function NASAtoFloat($val )
    {
        $a = unpack("N",$val);
        $b = unpack("f",pack( "I",$a[1]));
        return $b[1];
    }

    /**
     *
     * Remove "Extra chacters from streing"
     * 
     * @param type $str
     * @param type $delim if it's just a simple clean out of delims. i.e. replace with empty char
     * @param string|array $otherChars  Charcaters you want to replace in string
     * @param type $replace_wtih what to replace them with (only a simgle replace string for all chars)
     * @return type 
     */
    public static function CleanStr($str,$delim = NULL,$otherChars = NULL,$replace_wtih = "_")
    {
        if (!is_null($delim))
        {
            $str = str_replace($delim ,'',$str);
        }

        $str = str_replace(chr(10),'',$str);
        $str = str_replace(chr(13),'',$str);
        
        if (!is_null($otherChars) )
        {
            if (is_string($otherChars)) $otherChars = str_split($otherChars);
            
            foreach ($otherChars as $toCleanOut) {
                $str = str_replace($toCleanOut,$replace_wtih,$str);
            }
        }

        return $str;
    }

    
    public static function CleanString($str)
    {
        return self::CleanStr($str,NULL,self::$EXTRA_CHARS,"");   
    }
    
    
    public static function toString($src)
    {
        if (!is_array($src)) return "$src";
        
        return print_r($src,true);
        
    }


   /**
    * @method scriptName 
    * @return mixed
    */
    public static function string($src,$default = "")
    {
        if (!is_null($src)) return $src;
        return $default;
    }


    public static function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        $start  = $length * -1; //negative
        return (substr($haystack, $start) === $needle);
    }
    
}
?>