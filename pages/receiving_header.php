<?php
include '../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$functions = new TheFunctions;
$file_name = $_POST['pagename'];
$title = strtoupper($file_name);

$branch = $functions->AppBranch();
$transdate = $functions->GetSession('branchdate');
$shift = $functions->GetSession('shift');
$table = "store_" . $file_name . "_data";

$dateLockChecker = $functions->dateLockChecker($branch, $transdate, $db);
$dateLockCheckerRM = $functions->dateLockCheckerRM($branch, $transdate, $db);
$lock_by = $functions->analystVal($branch, $transdate, $db);


$GET_OUT_DATA = $functions->CheckTransferData($table, $functions->AppBranch(), $functions->GetSession('branchdate'), $functions->GetSession('shift'), 'OUT', $db);
if ($GET_OUT_DATA == 1) {
	$summary_btn = '';
	if ($functions->GetSession('userlevel') == 50 || $functions->GetSession('userlevel') >= 80) {
		$unlock_btn = '';
	} else {
		$unlock_btn = 'style="display: none"';
		$summary_btn = 'disabled';
	}
} else {
	$summary_btn = 'disabled';
	$unlock_btn = 'disabled';
}
if ($file_name == 'cashcount') {
	$post_to = 'CASH COUNT';
} else {
	$post_to = 'TO SUMMARY';
}
$summary_btn = $functions->detechPostedData($table, $branch, $transdate, $db) === 1 ? '' : 'disabled';
$tablePosted = $functions->tableDataCheckingForPosted($table, $branch, $transdate, $shift, $db);

?>
<style>
.pagemenu {
	border: 1px solid var(--text-grey);
	background: #cecece;
	padding:5px 15px 5px 15px;
	border-radius:7px;
	cursor: pointer;
	color: var(--text-grey);
}
.pagemenu:hover {
	background: #f1f1f1;
	border: 1px solid #f1f1f1;
}
</style>
<table style="width: 100%;border-collapse:collapse;white-space:nowrap" cellpadding="0" cellspacing="0">
	<tr>
		<td style="width:350px;position:relative">
			<i class="fa-solid fa-magnifying-glass searchicon"></i>
			<span class="tm" onclick="clearSearch('itemsearch')"></span>
			<input id="itemsearch" type="text" class="form-control" style="padding-left:35px;padding-right:57px" placeholder="Search Item" autocomplete="no">
		</td>
		<td style="width:0.5em" class="branch-info"></td>
		<td style="width:150px">
			<button id="additembtn" class="btn btn-success btn-sm" onclick="syncReceived()">Sync Received</button>
		</td>
		<td style="text-align:right">
			<button id="previewdatabtn" class="btn btn-primary btn-sm" onclick="previewData()">Preview Data</button>


		</td>
	</tr>
</table>
<div class="Results"></div>
<script>
const RAW_MATERIAL_PAGES = ['rm_receiving', 'rm_transfer', 'rm_badorder', 'rm_pcount'];

function showDateLockAlert(userAnalyst) {
	app_alert("System Message", "The date is already locked, if there are any changes, please contact " + userAnalyst + " the Data Analysts", "warning", "Ok", "", "");
}

function isDateLocked(filename) {
	const userAnalyst = '<?php echo $lock_by; ?>';
	const dateLockChecker = '<?php echo $dateLockChecker; ?>';
	const dateLockCheckerRM = '<?php echo $dateLockCheckerRM; ?>';

	if (RAW_MATERIAL_PAGES.includes(filename)) {
		if (dateLockCheckerRM == 1) {
			showDateLockAlert(userAnalyst);
			return true;
		}
		return false;
	}

	if (dateLockChecker == 1) {
		showDateLockAlert(userAnalyst);
		return true;
	}

	return false;
}

$(function() {
	$('#itemsearch').keyup(function() {
		const branch = '<?php echo $branch; ?>';
		const transdate = '<?php echo $transdate; ?>';
		const pagename = '<?php echo $file_name; ?>';
		const shift = '<?php echo $shift; ?>';
		const search = $('#itemsearch').val();

		$.post("./includes/" + pagename + "_data.php", { pagename: pagename, branch: branch, transdate: transdate, shift: shift, search: search }, function(data) {
			$("#contentdata").html(data);
		});
	});
});

$(document).ready(function() {
	const statBut = '<?php echo $tablePosted; ?>';
	if (statBut == 1) {
		$("#additembtn").hide();
		$("#posttosummarybtn").hide();
	}
});

function previewData() {
	$.post("./includes/preview_data.php", {}, function(data) {
		$("#previewData_page").html(data);
	});
	$('#previewData').fadeIn();
}

function syncReceived()
{
	swal({
		title: "Sync Received",
		text: "Are you sure you want to sync received items now?",
		icon: "warning",
		buttons: ["Cancel", "Yes, Sync"],
		dangerMode: true
	}).then(function(willSync) {
		if (willSync) {
			executeSyncReceived();
		}
	});

	return false;
}

function executeSyncReceived()
{
	if (typeof navigator !== 'undefined' && navigator.onLine === false) {
		app_alert("System Message", "No internet connection detected. Please connect and try again.", "warning", "Ok", "", "");
		return false;
	}

	const $btn = $('#additembtn');
	$btn.prop('disabled', true).html('Syncing <i class="fa fa-spinner fa-spin"></i>');

	$.post("./actions/actions.php", { mode: 'sync_received_items' }, function(data) {
		$('.Results').html(data);
	}).fail(function() {
		app_alert("System Message", "Sync request failed. Please try again.", "warning", "Ok", "", "");
	}).always(function() {
		$btn.prop('disabled', false).html('Sync Received');
	});

	return false;
}



function clearSearch(params)
{
	$('#' + params).val('');
	const pagename = '<?php echo $file_name; ?>';
	$.post("./includes/" + pagename + "_data.php", { pagename: pagename }, function(data) {
		$("#contentdata").html(data);
	});
}



</script>