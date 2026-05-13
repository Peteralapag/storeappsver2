<?php
include '../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$functions = new TheFunctions;
$trans_date = $functions->GetSession('branchdate');
$store_branch = $functions->AppBranch();
$file_name = $_POST['pagename'];
$title = strtoupper($file_name);

if(isset($_POST['search']))
{
	$item_name = $_POST['search'];
	$shift = $_SESSION['session_shift'];
	$q = "WHERE report_date='$trans_date' AND shift='$shift' AND branch='$store_branch' AND item_name LIKE '%$item_name%'";
} 
else
{
	if(isset($_SESSION['session_shift'])) 
	{
		$shift = $_SESSION['session_shift'];
		$q = "WHERE report_date='$trans_date' AND shift='$shift' AND branch='$store_branch'";
	} else {
		$shift = '';
		$q = "WHERE report_date='$trans_date' AND branch='$store_branch'";
	}
}
?>
<div class="tableFixHead">
<table style="width: 3000px" class="table table-hover table-striped table-bordered">
	<thead>
		<tr>
			<th style="text-align:center">#</th>
			<th>ITEM NAME</th>
			<th>QUANTITY</th>
			<th>UNIT OF MEASURE</th>
			<th>SUPPLIER</th>
			<th>PREFIX</th>
			<th>STATUS</th>
			<!--th>ACTIONS</th-->
		</tr>
	</thead>
<?php
$query ="SELECT * FROM store_receiving_data $q ORDER BY status,id DESC";  
$result = mysqli_query($db, $query);  
if($result->num_rows > 0)
{
	$x=0;
	while($ROWS = mysqli_fetch_array($result))  
	{
		$x++;
		$rowid = $ROWS['id'];
		$item_name = $ROWS['item_name'];
		if($ROWS['item_id'] == '' || $ROWS['item_id'] == NULL || $ROWS['item_id'] == Null || $ROWS['item_id'] == 0)
		{
			$noid_class = 'class="noid"';
			$noid_text = '<i class="fa-solid fa-triangle-exclamation icon-color-orange pull-right" onclick="showError()"></i>';
		} else {
			$noid_class = '';
			$noid_text = '';
		}
		if($ROWS['posted'] == 'Posted')
		{
			$status = '<strong>Posted <i class="fa-solid fa-check text-success"></i></strong>';
			$tr_command = '';
		} else {
			$status = 'Open';
			$tr_command = 'ondblclick=editItem("edit","'.$file_name.'","'.$title.'","'.$rowid.'")';
		}
?>	
	<tbody>	
		<tr <?php echo $tr_command; ?> <?php echo $noid_class; ?>>
			<td style="text-align:center"><?php echo $x; ?></td>
			<td><?php echo $ROWS['item_name']." ".$noid_text;; ?></td> <!-- ITEM NAME -->
			<td style="text-align:right; padding-right:30px !important"><?php echo $ROWS['quantity']; ?></td> <!-- ITEM NAME -->
			<td style="text-align:center"><?php echo $ROWS['units']; ?></td> <!-- ACTUAL YIELD -->
			<td style="text-align:center"><?php echo $ROWS['supplier']; ?></td>
			<td style="text-align:center"><?php echo $ROWS['invdr_no']; ?></td>
			<td style="text-align:center"><?php echo $status; ?></td>
		</tr>
	</tbody>
<?php } } else {?>
	<tbody>
		<tr>
			<td colspan="10" style="text-align:center;font-size:16px;"><i class="fa fa-bell"></i>&nbsp;&nbsp;&nbsp;Records</td>
		</tr>
	</tbody>
<?php } ?>
</table>
<div class="results"></div>
</div>
<script>
function deleteItem(rowid,filename,itemname)
{
	app_confirm("Delete Item","Are you sure to delete " + itemname + "?","warning",filename,rowid);
	return false;
}
function deleteItemYes(rowid,filename)
{
	rms_reloaderOn('Deleting Data....');
	var mode = 'deleteitem';
	$.post("./actions/actions.php", { mode: mode, rowid: rowid, filename: filename },
	function(data) {
		$('.results').html(data);
	//	$('#' + sessionStorage.navcount).trigger('click');
		rms_reloaderOff();
	});
}

function detectbarcode(barcodeValue){
	alert('Punch detecting: ' + barcodeValue);
}

(function initReceivingBarcodeTestDetector(){
	if(window.__receivingBarcodeDetectorBound === true)
	{
		return;
	}
	window.__receivingBarcodeDetectorBound = true;

	var barcodeBuffer = '';
	var lastKeyTime = 0;
	var maxIntervalMs = 60;
	var minBarcodeLength = 6;

	$(document).on('keydown.receivingBarcodeTest', function(e)
	{
		var activeTag = (document.activeElement && document.activeElement.tagName) ? document.activeElement.tagName.toLowerCase() : '';
		if(activeTag === 'textarea')
		{
			return;
		}

		var now = Date.now();
		if(now - lastKeyTime > 300)
		{
			barcodeBuffer = '';
		}
		lastKeyTime = now;

		if(e.key === 'Enter')
		{
			if(barcodeBuffer.length >= minBarcodeLength)
			{
				detectbarcode(barcodeBuffer);
			}
			barcodeBuffer = '';
			return;
		}

		if(e.key.length === 1)
		{
			barcodeBuffer += e.key;
		}
		else if(e.key !== 'Shift')
		{
			barcodeBuffer = '';
		}

		if(barcodeBuffer.length > 50)
		{
			barcodeBuffer = barcodeBuffer.slice(-50);
		}
	});
})();
</script>
