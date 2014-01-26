<?php
include_once '../includes.php';

$p = util::CommandLineValues($argv);

if (!array_key_exists('db',$p))
{
    echo "Required parameter [db] missing\n";
    exit();
}

if (!array_key_exists('host',$p))
{
    echo "Required parameter [host] missing\n";
    exit();
}

if (!array_key_exists('user',$p))
{
    echo "Required parameter [user] missing\n";
    exit();
}

if (!array_key_exists('pass',$p))
{
    echo "Required parameter [pass] missing\n";
    exit();
}

if (!array_key_exists('sql',$p))
{
    echo "Required parameter [sql] missing\n";
    exit();
}

$db = new database($p['db'], $p['host'], $p['user'], $p['pass']);

$result = $db->query($p['sql']);

if (array_key_exists('output',$p))
{
    switch ($p['output']) {
        case 'table':
            matrix::display($result, " ", null, 20);
            break;

        case 'json':
            echo json_encode($result)."\n";
            
            break;
        
        
        default:
            print_r($result);
            break;
    }
    
}
else
{
    print_r($result);    
}



unset($db);

?>