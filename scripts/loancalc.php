<?php

echo "<html><head><style type='text/css'>textarea { margin-bottom: 20px; } input { width: 100px; }legend { background: black; -moz-border-radius: 5px; font-family: sans-serif; font-size: 16px; font-weight: bold; color: white; } fieldset { background: #eee; border: thin solid #ccc; -moz-border-radius: 15px; float: right; }</style><title>Loan and Retirement Calculator</title></head><body><form action='$PHP_SELF' method='get'><fieldset>
<legend>Payment Plan</legend>
loan amount: $<input type='text' name='loan_amount' value='$_GET[loan_amount]' /><br />
loan interest: <input type='text' name='loan_interest' value='$_GET[loan_interest]' />%<br />
monthly loan payment: $<input type='text' name='loan_payment' value='$_GET[loan_payment]' /><br />
monthly IRA/HSA contribution: $<input type='text' name='ira_contribution' value='$_GET[ira_contribution]' /><br />
lump payments: <br /><textarea name='lump_payments'>$_GET[lump_payments]</textarea><br />
lump ira contributions: <br /><textarea name='lump_ira_contributions'>$_GET[lump_ira_contributions]</textarea><br />
<input type='submit' value='calculate' /></fieldset>\n";

if ($_GET['loan_amount'])
{
	$periodic_interest=$_GET['loan_interest']/1200;
	$loan_amount=$_GET['loan_amount'];

	$lump_payments=explode("\n",$lump_payments);
	foreach($lump_payments as $payment_line)
	{
		$payment_line_array=explode(':',$payment_line);
		$lump_payment[$payment_line_array[0]]=$payment_line_array[1];
	}

	$lump_ira_contributions=explode("\n",$lump_ira_contributions);
	foreach($lump_ira_contributions as $contribution_line)
	{
		$contribution_line_array=explode(':',$contribution_line);
		$lump_ira_contribution[$contribution_line_array[0]]=$contribution_line_array[1];
	}

	echo "<table style='border: 1px solid black; background: #eee;' cellpadding='8'>\n";
	echo "<tr><td>month</td><td>month pd.</td><td>total pd.</td><td>balance</td><td>tax savings</td><td>ira contrib</td><td>ira balance</td><td>future value</td></tr>\n";
	echo "<tr><td colspan='8' style='text-align: center;'><hr /><h3>2007</h3></td></tr>\n";
	for($i=1;$i<=120;$i++)
	{
		$loan_amount+=$periodic_interest*$loan_amount;
		if ($lump_payment[$i]) $loan_amount-=$lump_payment[$i];
		if ($loan_amount > $_GET['loan_payment'])
			$loan_amount-=$_GET['loan_payment'];
		else
		{
			$excess_contribution=$_GET['loan_payment']-$loan_amount;
			$loan_amount=0;
		}
		$month_payment=$lump_payment[$i]+$_GET['loan_payment']-$excess_contribution;
		$month_contribution=$lump_ira_contribution[$i]+$_GET['ira_contribution']+$excess_contribution;
		$ira_total+=$ira_total*.005;
		$ira_total+=$_GET['ira_contribution'];
		$ira_total+=$excess_contribution;
		$month_tax_savings=min(.15*($_GET['ira_contribution']+$excess_contribution+$lump_ira_contribution[$i]),100);
		$tax_savings+=$month_tax_savings;
		if ($lump_ira_contribution[$i])
			$ira_total+=$lump_ira_contribution[$i];
		$future_value=$ira_total*pow(1.06,floor(34-($i/12)));
		$total_payment+=$month_payment;
		$total_payment=round($total_payment);
		$month_contribution=round($month_contribution);
		$loan_amount=round($loan_amount);
		$ira_total=round($ira_total);
		$future_value=round($future_value);
		$tax_savings=round($tax_savings);
		$month="<span style='font-size: smaller; font-family: sans-serif; color: #999;'>".date("M",strtotime((12*($i/12-floor($i/12)))."/28/".(2007 + floor ($i/12))." 1:00pm"))."</span>";
		echo "<tr><td>$i $month</td><td>$month_payment</td><td>$total_payment</td><td>$loan_amount</td><td>$tax_savings</td><td>$month_contribution</td><td>$ira_total</td><td>$future_value</td></tr>\n";
		if (floor($i/12)==$i/12 && $i!=120)
		{
			echo "<tr><td colspan='8' style='text-align: center;'><hr /><h3>".(2007 + floor ($i/12))."</h3></td></tr>\n";
			echo "<tr><td>month</td><td>month pd.</td><td>total pd.</td><td>balance</td><td>tax savings</td><td>ira contrib</td><td>ira balance</td><td>value in 2041</td></tr>\n";
		}
	}
	echo "</table>\n";
}

echo "</body></html>";

?>