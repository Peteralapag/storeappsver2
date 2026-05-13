<?php
include '../init.php';

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$items = new TheFunctions;

$store_branch = $_SESSION['appstore_branch'] ?? '';
$trans_date   = $_SESSION['session_date'] ?? $date->get_date();
$store_shift  = $_SESSION['session_shift'] ?? '';

$total_variance_amount = 0;
$total_variance_qty    = 0;
$total_variance_short  = 0; // negative values
$total_variance_over   = 0; // positive values

$query = "SELECT 
            item_name,
            beginning,
            stock_in,
            transfer_in,
            transfer_out,
            counter_out,
            var_in,
            var_out,
            bo,
            sub_total,
            actual_usage,
            total,
            actual_count,
            difference,
            price_kg,
            amount,
            variances
          FROM store_rm_summary_data 
          WHERE branch='$store_branch' 
          AND shift='$store_shift' 
          AND report_date='$trans_date'
          ORDER BY item_name ASC";

$result = mysqli_query($db, $query);
?>

<table id="dumdatatable" class="table table-bordered table-sm">
    <thead>
        <tr>
            <th rowspan="2" style="vertical-align:middle">#</th>
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
    <tbody>

<?php
if ($result && $result->num_rows > 0):

    $n = 0;
    while ($row = mysqli_fetch_assoc($result)):

        $n++;

        $variance_amt = (float)$row['amount'];
        $total_variance_amount += $variance_amt;
        $total_variance_qty    += (float)$row['difference'];
        
        if ($variance_amt < 0) {
            $total_variance_short += $variance_amt;
        } elseif ($variance_amt > 0) {
            $total_variance_over += $variance_amt;
        }


		$variance_qty = (float)$row['difference'];
		$variance_amt = (float)$row['amount'];

		$bgColor = '';
		$textColor = '';

		if ($variance_qty < 0) {
			// SHORT
			$bgColor = '#d4edda';
		} elseif ($variance_qty > 0) {
			// OVER
			$bgColor = '#fff3cd';
		}

?>
        <tr>
            <td><?= $n ?></td>
            <td><?= htmlspecialchars($row['item_name']) ?></td>
            <td class="text-center"><?= number_format($row['beginning'],3) ?></td>
            <td class="text-center"><?= number_format($row['stock_in'],3) ?></td>
            <td class="text-center"><?= number_format($row['transfer_in'],3) ?></td>
            <td class="text-center"><?= number_format($row['transfer_out'],3) ?></td>
            <td class="text-center"><?= number_format($row['counter_out'],3) ?></td>
            <td class="text-center"><?= number_format($row['var_in'],3) ?></td>
            <td class="text-center"><?= number_format($row['var_out'],3) ?></td>
            <td class="text-center"><?= number_format($row['bo'],3) ?></td>
            <td class="text-center"><?= number_format($row['sub_total'],3) ?></td>
            <td class="text-center"><?= number_format($row['actual_usage'],3) ?></td>
            <td class="text-center"><?= number_format($row['total'],3) ?></td>
            <td class="text-center"><?= number_format($row['actual_count'],3) ?></td>
            <td class="text-center" style="background-color: <?= ($row['difference'] < 0) ? '#d4edda' : (($row['difference'] > 0) ? '#fff3cd' : '') ?>"><?= number_format($row['difference'],3) ?></td>
            <td class="text-center"><?= number_format($row['price_kg'],2) ?></td>
            <td class="text-center" style="background-color: <?= ($row['amount'] < 0) ? '#d4edda' : (($row['amount'] > 0) ? '#fff3cd' : '') ?>"><?= number_format($row['amount'],3) ?></td>
        </tr>

<?php
    endwhile;
else:
?>

        <tr>
            <td colspan="16" class="text-center text-muted">No data available</td>
        </tr>

<?php endif; ?>

    </tbody>

    <tfoot>
        <tr>
            <td colspan="14" style="text-align: right; font-weight: bold;">TOTAL</td>
            <td class="text-center" style="background-color: <?= ($total_variance_qty < 0) ? '#d4edda' : (($total_variance_qty > 0) ? '#fff3cd' : '') ?>"><?= number_format($total_variance_qty,3) ?></td>
            <td></td>
            <td>
                <table style="width: 100%; border-collapse: collapse;">
                    <td style="background-color: #d4edda; padding: 5px;"><?= number_format($total_variance_short,2) ?></td>
                    <td style="background-color: #fff3cd; padding: 5px; text-align: right"><?= number_format($total_variance_over,2) ?></td>
                </table>
            </td>
            
        </tr>
    </tfoot>
</table>
