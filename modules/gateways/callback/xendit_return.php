<?php

require "../../../init.php";
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");

// check if the module is activated
/*--- start ---*/

if (empty($_POST['status']) || empty($_POST['id']) || empty($_POST['external_id'])) {
	error_log('wrong query string please contact admin.');
}

$xenditId 		= htmlspecialchars($_POST['id']);
$order_id 		= htmlspecialchars($_POST['external_id']);
$status			= htmlspecialchars($_POST['status']);
$paymentMethod	= htmlspecialchars($_POST['payment_method']);
$paymentAmount 	= htmlspecialchars($_POST['adjusted_received_amount']);
$reference 		= strtoupper($paymentMethod.$xenditId);

if ($status == 'PAID') {				
	$url = $CONFIG['SystemURL'] . "/viewinvoice.php?id=" . $order_id . "&paymentsuccess=true";
} else {		
	$url = $CONFIG['SystemURL'] . "/viewinvoice.php?id=" . $order_id . "&paymentfailed=true";		
}				

//redirect to invoice with message status
header('Location: ' . $url);
die();
			