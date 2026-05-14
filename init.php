<?PHP

header('Content-Type: text/html; charset=utf-8');
if (session_status() == PHP_SESSION_NONE) { session_start(); }



include 'db_config.php';
define('DB_HOST', $dbhost);
define('DB_USER', $dbuser);
define('DB_PASSWORD', $dbpass);
define('DB_NAME', $dbname);
define('CENTRALIZED_DB_NAME', $centralizedbname);


require 'class/dropdowns.class.php';
require 'class/encrypted_password_class.php';
require 'class/functions.class.php';
require 'class/summary.class.php';
$summary = new TheSummary;
$functions = new TheFunctions;
$dropdown = new DropDowns;
$pass = new Password;
/* ################# STORE SETTINGS ################################ */
$dbe = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$qSettings = "SELECT * FROM store_settings WHERE id=1";
$res = mysqli_query($dbe, $qSettings);    
if ($res->num_rows > 0) 
{ 
    while($SETTROW = mysqli_fetch_array($res))  
	{
		define("APP_NAME", $SETTROW['app_name']);
		define("COMPANY", $SETTROW['company']);
		define("APP_LOGO", $SETTROW['app_logo']);
		define("VERSION_TEXT", $SETTROW['version_text']);
		// define("VERSION_NUMBER", $SETTROW['version_number']);
		if(isset( $SETTROW['theme_color']))
		{
			define("THEME_COLOR", $SETTROW['theme_color']);
		} else {
			define("THEME_COLOR", 0);
		}
	}
} else {
	echo "No Data";
}
if(THEME_COLOR == 0) {
	define("THEME", 'form_class_green');
} else if(THEME_COLOR == 1) {
	define("THEME", 'form_class_orange');
} else {
	define("THEME", 'form_class_green');
}

$url_host = '120.28.196.113';





/* ################# CHECK AND ADD COLUMNS TO store_rm_summary_data ################################ */
$checkColumns = $dbe->query("SHOW COLUMNS FROM store_rm_summary_data LIKE 'var_in'");
if ($checkColumns->num_rows == 0 || $dbe->query("SHOW COLUMNS FROM store_rm_summary_data LIKE 'var_out'")->num_rows == 0) {
    // Execute all migrations together only once
    if ($checkColumns->num_rows == 0) {
        $dbe->query("ALTER TABLE store_rm_summary_data ADD COLUMN var_in double(11,2) DEFAULT 0.00 AFTER counter_out");
    }
    
    $checkColumnsOut = $dbe->query("SHOW COLUMNS FROM store_rm_summary_data LIKE 'var_out'");
    if ($checkColumnsOut->num_rows == 0) {
        $dbe->query("ALTER TABLE store_rm_summary_data ADD COLUMN var_out double(11,2) DEFAULT 0.00 AFTER var_in");
    }
    
    /* ################# CREATE TABLE store_rm_variance_data ################################ */
    $createVarianceTable = "
        CREATE TABLE IF NOT EXISTS `store_rm_variance_data` (
            `id` bigint NOT NULL AUTO_INCREMENT,
            `branch` varchar(100) DEFAULT NULL,
            `report_date` date DEFAULT NULL,
            `shift` varchar(50) DEFAULT NULL,
            `category` varchar(100) DEFAULT NULL,
            `item_id` bigint DEFAULT NULL,
            `item_name` varchar(255) DEFAULT NULL,
            `var_type` enum('IN','OUT') DEFAULT NULL,
            `quantity` decimal(10,3) DEFAULT NULL,
            `remarks` text DEFAULT NULL,
            `encoded_by` varchar(100) DEFAULT NULL,
            `posted` enum('Yes','No') DEFAULT 'No',
            `status` enum('Closed','Open') DEFAULT 'Open',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        )
    ";
    $dbe->query($createVarianceTable);
}


