<?PHP
if (session_status() == PHP_SESSION_NONE) { session_start(); }
ini_set('display_errors',0);
ini_set('mysql.connect_timeout', 3);
ini_set('default_socket_timeout', 3);

$defaultServer = '120.28.196.113';
$sessionServer = !empty($_SESSION['ACTIVE_SERVER_IP']) ? $_SESSION['ACTIVE_SERVER_IP'] : '';
$configServer = (isset($functions) && method_exists($functions, 'GetOnlineServer')) ? $functions->GetOnlineServer('server_ip') : '';
$server = $sessionServer ?: ($configServer ?: $defaultServer);

/* ################### MYSQL DATABASE INFORMATION ########################### */
$conhost = $server.":13306";
$conuser = "rbsapps";
$conpass = "admin@rbs.com";
//$conname = "rosebakeshop_data";
$conname = "rosebakeshop_deyta";

/* ################### MYSQL DATABASE INFORMATION ########################### */
define('CON_HOST', $conhost);
define('CON_USER', $conuser);
define('CON_PASSWORD', $conpass);
define('CON_NAME', $conname);
/* ################### MYSQL DATABASE INFORMATION ########################### */
