<?php
/**
 * На этот скрипт приходят уведомления от QIWI Кошелька.
 * SoapServer парсит входящий SOAP-запрос, извлекает значения тегов login, password, txn, status,
 * помещает их в объект класса Param и вызывает функцию updateBill объекта класса QiwiServer.
 *
 * Логика обработки магазином уведомления должна быть в updateBill.
 */

$silent = 'true'; 
include 'functions.php';
include '../../../../dbconnect.php';
include '../../../../includes/functions.php';
include '../../../../includes/gatewayfunctions.php';
include '../../../../includes/invoicefunctions.php';
  
if ( !defined('__DIR__') ) define('__DIR__', dirname(__FILE__));

$s = new SoapServer('IShopClientWS.wsdl', array('classmap' => array('tns:updateBill' => 'Param', 'tns:updateBillResponse' => 'Response')));
$s->setClass('QiwiServer');
$s->handle();

 class Response {
  public $updateBillResult;
 }

 class Param {
  public $login;
  public $password;
  public $txn;      
  public $status;
 }

 class QiwiServer {
  function updateBill($param) {

	$emailTo = 'mail@server.com';
	$emailToName = 'Name To';
	$emailFrom = 'noreply@server.com';
	$emailFromName = 'Qiwi Robot';

	$debugreport = '' . date(DATE_RFC822) . "\n";
	foreach ($param as $k => $v)
	{
		$debugreport .= '' . $k . ' => ' . $v . "\n";
	}

	if ($param->status == 60) {
	
		// проверить статус инвоиса
		$result = mysql_query ('SELECT tblinvoices.*, tblpaymentgateways.value FROM tblinvoices INNER JOIN tblpaymentgateways ON  tblpaymentgateways.gateway = tblinvoices.paymentmethod WHERE tblpaymentgateways.setting = \'name\' AND tblinvoices.id = \'' . $param->txn . '\' AND tblinvoices.status = \'Unpaid\'');
		$data = mysql_fetch_array ($result);
		
		if (!empty($data['id']))
		{
			$pay_id = $data['id'];
			$pay_total = $data['total'];
			$debugreport .= (('' . $pay_id . ' => ' . $pay_total . '') . '');
		
		addinvoicepayment ($pay_id,$param->txn, $pay_total, '', 'qiwi');
		logtransaction ('QiwiTransfer', $debugreport, 'Successful');
		}
		else {
			$debugreport .= (('Invoice not found') . '');
			logtransaction ('QiwiTransfer', $debugreport, 'Error');
		}
	} else if ($param->status > 100) {
	
		// заказ не оплачен (отменен пользователем, недостаточно средств на балансе и т.п.)
		// найти заказ по номеру счета ($param->txn), пометить как неоплаченный
		
		send_mime_mail($emailFromName,$emailFrom,$emailToName,$emailTo,'CP1251','UTF8','Заказ не оплачен',"Привет, \r\n клиент сейчас пытается оплатить счет: " . $param->txn . ". Статус платежа в Киви - " . $param->status . ".");
		logtransaction ('QiwiTransfer', $debugreport , 'Error');
		
	} else if ($param->status >= 50 && $param->status < 60) {
		// счет в процессе проведения
		send_mime_mail($emailFromName,$emailFrom,$emailToName,$emailTo,'CP1251','UTF8','Cчет в процессе проведения',"Привет, \r\n клиент сейчас пытается оплатить счет: " . $param->txn . ". Статус платежа в Киви - " . $param->status . ".");
		logtransaction ('QiwiTransfer', $debugreport, 'Error');
	} else {
		// неизвестный статус заказа
		send_mime_mail($emailFromName,$emailFrom,$emailToName,$emailTo,'CP1251','UTF8','Неизвестный статус заказа',"Привет, \r\n клиент сейчас пытается оплатить счет: " . $param->txn . ". Статус платежа в Киви - " . $param->status . ".");
		logtransaction ('QiwiTransfer', $debugreport, 'Error');
	}

	// формируем ответ на уведомление
	// если все операции по обновлению статуса заказа в магазине прошли успешно, отвечаем кодом 0
	// $temp->updateBillResult = 0
	// если произошли временные ошибки (например, недоступность БД), отвечаем ненулевым кодом
	// в этом случае QIWI Кошелёк будет периодически посылать повторные уведомления пока не получит код 0
	// или не пройдет 24 часа
	
	$temp = new Response();
	$temp->updateBillResult = 0;
	return $temp;
  }
 }
?>
