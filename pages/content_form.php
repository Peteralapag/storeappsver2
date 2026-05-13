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
			<button id="additembtn" class="btn btn-success btn-sm" onclick="addItem('new','<?php echo $file_name; ?>','<?php echo $title; ?>')"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;ADD ITEM</button>
		</td>
		<td style="text-align:right">
			<button id="previewdatabtn" class="btn btn-primary btn-sm" onclick="previewData()">Preview Data</button>
			<button id="posttosummarybtn" class="btn btn-info btn-sm" <?php echo $summary_btn; ?> onclick="postItemModule('<?php echo $file_name; ?>')"><i class="fa-solid fa-bring-forward"></i>&nbsp;&nbsp;POST <?php echo $post_to; ?></button>
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

function postItemModule(filename)
{
	if (isDateLocked(filename)) {
		return false;
	}

	app_confirm("Post to Summary", "Once you've post this, it's final; it can't be altered again.", "warning", 'postItemModule', filename, '');
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

function unLock(params) {
	const userAnalyst = '<?php echo $lock_by; ?>';
	const dateLockChecker = '<?php echo $dateLockChecker; ?>';
	if (dateLockChecker == 1) {
		showDateLockAlert(userAnalyst);
		return false;
	}

	const title = '<?php echo $title; ?>';
	$('#posttosummarybtn,#unlockbutton,#additembtn').attr("disabled", true);
	$('#unlockbutton').html('<i class="fa-solid fa-bring-forward"></i>&nbsp;&nbsp;Unlocking... data &nbsp;&nbsp;<i class="fa fa-spinner fa-spin"></i>');
	const mode = "unlockpost";
	setTimeout(function() {
		$.post("./actions/actions.php", { mode: mode, database: params }, function(data) {
			$(".Results").html(data);
			$('#' + sessionStorage.navcount).click();
			app_alert("System Message", "Summary Successfuly Report Unlock", "success", "Ok", "", "");
		});
	}, 1000);
}

function postToSummary(params)
{
	if (isDateLocked(params)) {
		return false;
	}

	rms_reloaderOn('Ensure uninterrupted processing of data for secure calculations....');

	$('#posttosummarybtn,#unlockbutton,#additembtn').attr("disabled", true);
	$('#posttosummarybtn').html('<i class="fa-solid fa-bring-forward"></i>&nbsp;&nbsp;Posting to Summary&nbsp;&nbsp;<i class="fa fa-spinner fa-spin"></i>');
	const mode = params;
	setTimeout(function() {
		$.post("./actions/post_summary_process.php", { mode: mode }, function(data) {
			$(".Results").html(data);
			rms_reloaderOff();
		});
	}, 1000);
}

function addItem(transmode,file_name,title)
{
	if (isDateLocked(file_name)) {
		return false;
	}

	$('#additem_title').html("ADD NEW " + title + " DATA");
	$.post("./apps/" + file_name + "_form.php", { file_name: file_name, transmode: transmode }, function(data) {
		$("#additem_page").html(data);
	});
	$('#additem').fadeIn();
}

function editItem(transmode,file_name,title,rowid)
{
	$('#additem_title').html("ADD NEW " + title + " DATA");
	$.post("./apps/" + file_name + "_form.php", { file_name: file_name, transmode: transmode, rowid: rowid }, function(data) {
		$("#additem_page").html(data);
	});
	$('#additem').fadeIn();
}

function EditUserChecking(transmode,file_name,title,rowid)
{
	const mode = 'confirmUserEncoder';
	const sendFrom = 'edit';
	$.post("./actions/actions.php", { mode: mode, transmode: transmode, file_name: file_name, title: title, rowid: rowid, sendFrom: sendFrom }, function(data) {
		$('.results').html(data);
	});
}

function DeleteUserChecking(rowid,filename,itemname)
{
	const mode = 'confirmUserEncoder';
	const sendFrom = 'delete';
	$.post("./actions/actions.php", { mode: mode, rowid: rowid, file_name: filename, itemname: itemname, sendFrom: sendFrom }, function(data) {
		$('.results').html(data);
	});
}
</script>