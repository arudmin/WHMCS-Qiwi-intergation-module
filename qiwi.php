<?
function qiwi_activate ()
{
	definegatewayfield ('qiwi', 'text', 'id', '', 'Qiwi id', '20', 'Qiwi Partner`s ID');
	definegatewayfield ('qiwi', 'text', 'comment', 'Yourcompany name', 'Payment comment', '30', '');

}

function qiwi_link ($params)
{
	$id = $params['id'];
	$comment = $params['comment'];
	$invoiceid = $params['invoiceid'];
	$description = $params['description'];
	$amount = $params['amount'];
	$phone = $params['clientdetails']['phonenumber'];

	$code = '
	<form method="POST" action="http://w.qiwi.ru/setInetBill_utf.do">
	<input type=hidden name="com" value="' . $comment . ' ' . $id . ' - Invoice #' . $invoiceid . '">
	<input type=hidden name="summ" value="' . $amount . '">
	<input type=hidden name="to" value="' . $phone . '">
	<input type=hidden name="from" value="' . $id . '">
	<input type=hidden name="check_agt" value="false">	
	<input type=hidden name="lifetime" value="24">	
	<input type=hidden name="txn_id" value="' . $invoiceid . '">
	<input type=hidden name="hash" value="' . md5 ('' . $comment . '::' . $invoiceid . '::' . sprintf ('%01.2f', $amount) . '') . '">
	<input type="submit" value="' . $params['langpaynow'] . '" class="button">
	</form>';

	return $code;
}

$GATEWAYMODULE['qiwiname'] = 'qiwi';
$GATEWAYMODULE['qiwivisiblename'] = 'QIWI';
$GATEWAYMODULE['qiwitype'] = 'Invoices';
?>