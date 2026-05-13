<?php
require '../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$function = new TheFunctions;

$branch_name = $functions->AppBranch();
$branch_date = $functions->GetSession('branchdate');
$branch_shift = $functions->GetSession('shift');
$ulevel = $functions->getSession('userlevel');
if(isset($_POST['search']))
{
	$item_name = $_POST['search'];
	$q = "WHERE report_date='$branch_date' AND branch='$branch_name' AND item_name LIKE '%$item_name%'";
} 
else
{
	if(isset($_SESSION['session_shift'])) 
	{
		$shift = $_SESSION['session_shift'];
		$q = "WHERE report_date='$branch_date' AND branch='$branch_name'";
	} else {
		$shift = '';
		$q = "WHERE report_date='$branch_date' AND branch='$branch_name'";
	}
}

$total_variance_amount = 0;
$total_variance_qty    = 0;
$total_variance_short  = 0; // negative values
$total_variance_over   = 0; // positive values
?>

<table id="summarydatatable" style="width: 100%" class="table table-hover table-striped table-bordered">
	<thead>
		<tr>
			<th rowspan="2" style="vertical-align:middle">#</th>
			<th rowspan="2" style="vertical-align:middle">ITEMCODE</th>
			<th rowspan="2" style="vertical-align:middle">MATERIALS</th>
			<th rowspan="2" style="vertical-align:middle">BEG</th>
			<th rowspan="2" style="vertical-align:middle">DELIVERY</th>
			<th colspan="2">TRANSFER</th>
			<th rowspan="2" style="vertical-align:middle">C-IN</th>
			<th rowspan="2" style="vertical-align:middle">VAR-IN</th>
			<th rowspan="2" style="vertical-align:middle">VAR-OUT</th>
			<th rowspan="2" style="vertical-align:middle">BO</th>
			<th rowspan="2" style="vertical-align:middle">TOTAL</th>
			<th rowspan="2" style="vertical-align:middle">ACTUAL USAGE</th>
			<th rowspan="2" style="vertical-align:middle">EXP.TOTAL</th>
			<th rowspan="2" style="vertical-align:middle">PHYSICAL COUNT</th>
			<th rowspan="2" style="vertical-align:middle">VARIANCE (KL)</th>
			<th rowspan="2" style="vertical-align:middle">PRICE/KG</th>
			<th rowspan="2" style="vertical-align:middle">VAR AMOUNT</th>
		</tr>
		<tr>
			<th>IN</th>
			<th>OUT</th>
		</tr>
	</thead>
<?php
$amounttotal=0;
$total_variance_qty = 0;
$total_variance_short = 0;
$total_variance_over = 0;
// Main grouped query for per-item summary
$query = "
	SELECT 
	    MIN(id) AS id, -- representative ID
	    MAX(branch) AS branch,
	    MAX(report_date) AS report_date,
	    item_id,
	    MAX(item_name) AS item_name,
	    MAX(category) AS category,
	    
	    MAX(CASE WHEN shift = 'FIRST SHIFT' THEN beginning END) AS beginning,
	    
	    SUM(stock_in) AS stock_in,
	    SUM(transfer_in) AS transfer_in,
	    SUM(counter_out) AS counter_out,
	SUM(var_in) AS var_in,
	SUM(var_out) AS var_out,
	    SUM(sub_total) AS sub_total,
	    SUM(transfer_out) AS transfer_out,
	    SUM(bo) AS bo,
	    SUM(total) AS grand_total,
	    SUM(actual_usage) AS actual_usage,
	    
	    MAX(CASE WHEN shift = 'SECOND SHIFT' THEN actual_count END) AS actual_count,
	    
	    SUM(difference) AS difference,
	    MAX(price_kg) AS price_kg, 
	    SUM(amount) AS total_amount

	FROM store_rm_summary_data
	$q
	GROUP BY item_id
	ORDER BY MIN(id) DESC;  
";

$result = mysqli_query($db, $query);  
// Compute whole-day totals (sum from raw rows matching $q) to ensure correct OVER/SHORT totals
$totals_sql = "SELECT 
	COALESCE(SUM(difference),0) AS total_variance_qty,
	COALESCE(SUM(amount),0) AS total_variance_amount,
	COALESCE(SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END),0) AS total_variance_short,
	COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS total_variance_over
FROM store_rm_summary_data
" . $q . ";";

$totals_res = mysqli_query($db, $totals_sql);
if ($totals_res) {
	$totals_row = mysqli_fetch_assoc($totals_res);
	$total_variance_qty = (float)$totals_row['total_variance_qty'];
	$total_variance_amount = (float)$totals_row['total_variance_amount'];
	$total_variance_short = (float)$totals_row['total_variance_short'];
	$total_variance_over = (float)$totals_row['total_variance_over'];
} else {
	$total_variance_qty = 0;
	$total_variance_amount = 0;
	$total_variance_short = 0;
	$total_variance_over = 0;
}
if($result->num_rows > 0)
{
	$i=0;
	$total=0;
	$amounttotal=0;
	$breadsAmount =0;
	$total=0;$transfer_out=0;$should_be=0;$sold;	
	while($ROW = mysqli_fetch_array($result))  
	{
		$i++;
		$item_id = $ROW['item_id'];
		$item_name = $ROW['item_name'];
		$beginning = $ROW['beginning'];
		$stock = $ROW['stock_in'];
		$transfer_in = $ROW['transfer_in'];
		$transfer_out = $ROW['transfer_out'];
		$cout = $ROW['counter_out'];
		$var_in = $ROW['var_in'];
		$var_out = $ROW['var_out'];
		$bad_order = $ROW['bo'];
		$sub_total = $ROW['sub_total'];
		$actual_count = $ROW['actual_count'];
		$total = $ROW['grand_total'];
		$price_kg = $ROW['price_kg'];
		$actual_usage = $ROW['actual_usage'];
		$difference = $ROW['difference'];
		$total_amount = $ROW['total_amount'];


		$variance_amt = (float)$total_amount;

?>
	<tr>		
		<td style="text-align:center;"><?php echo $i; ?></td>
						
		<td class="al-right" style="text-align:center"><?php echo $item_id; ?></td>
		<td style="text-align:left;white-space:nowrap"><?php echo $item_name; ?></td>
		<td class="al-right"><?php echo $beginning; ?></td>						
		<td class="al-right"><?php echo $stock; ?></td>
		<td class="al-right"><?php echo $transfer_in; ?></td>
		<td class="al-right"><?php echo $transfer_out; ?></td>
		<td class="al-right"><?php echo $cout; ?></td>
		<td class="al-right"><?php echo $var_in; ?></td>
		<td class="al-right"><?php echo $var_out; ?></td>
		<td class="al-right"><?php echo $bad_order; ?></td>

		<td class="al-right"><?php echo $sub_total; ?></td>
		<td class="al-right"><?php echo $actual_usage; ?></td>
		<td class="al-right"><?php echo $total; ?></td>
		<td class="al-right"><?php echo $actual_count; ?></td>
		
		
		<td class="al-right" style="background-color: <?= ($difference < 0) ? '#d4edda' : (($difference > 0) ? '#fff3cd' : '') ?>"><?= number_format($difference,3) ?></td>
		<td class="al-right"><?php echo $price_kg; ?></td>
		<td style="text-align:right; background-color: <?= ($total_amount < 0) ? '#d4edda' : (($total_amount > 0) ? '#fff3cd' : '') ?>"><?php echo number_format($total_amount,2)?></td>
	</tr>
<?php 
} } else { ?>	
<?php } ?>	
	<tr>
		<td style="text-align:center;">&nbsp;</td>
		<td colspan="14" style="text-align:center;padding-right:30px;" ><strong>TOTAL</strong></td>
		<td class="text-center" style="background-color: <?= ($total_variance_qty < 0) ? '#d4edda' : (($total_variance_qty > 0) ? '#fff3cd' : '') ?>"><?= number_format($total_variance_qty,3) ?></td>
		<td class="text-center"></td>
		<td style="text-align:right;border-top:3px solid #232323;">
			<span style="background-color:#d4edda;padding:5px;display:inline-block;"><?= number_format($total_variance_short,2) ?></span>
			<span style="background-color:#fff3cd;padding:5px;display:inline-block;float:right;"><?= number_format($total_variance_over,2) ?></span>
		</td>
	</tr>
</table>
<div id="sumdata">
<div id="bottom" class="sales-breakdown" style="width:100%">	
</div>
</div>
<script>


