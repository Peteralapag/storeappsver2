<?php
require '../init.php'; 
require '../includes/config.php'; 
if($_SESSION['OFFLINE_MODE'] == 0)
{
	$arrays = @file_get_contents($remote);
	if ($arrays === false) {
		echo '<div style="color:red">Unable to connect to update server. Please check your internet connection.</div>';
		return;
	}
	$datas = json_decode($arrays, true);
	$arays = $datas;
	for ($i = 0; $i < count($arays); $i++) {
		$remote_update_no = ($arays[$i]['updateno']);
		$remote_lastdate = ($arays[$i]['lastdate']);
	}
	/*   LOCAL  */
	$array = @file_get_contents("../updates/data/update.json", "r");
	if ($array === false) {
		echo '<div style="color:red">Unable to read local update data.</div>';
		return;
	}
	$data = json_decode($array, true);
	$aray = $data;
	for ($t = 0; $t < count($aray); $t++) {    
		$local_update_no = ($aray[$t]['updateno']);
	}
	if($local_update_no < $remote_update_no)
	{
		echo 1;
	} else {
		echo 0;
	}
}