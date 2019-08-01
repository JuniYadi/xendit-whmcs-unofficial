<?php
require "../../../init.php";
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");

// check if the module is activated
/*--- start ---*/
$gatewayModuleName  = '';
$paymentCode        = htmlspecialchars($_POST['bank_code']);

switch ($paymentCode) {
	case "BCA":
		$gatewayModuleName = "xendit_bca"; break;
	case "BNI":
		$gatewayModuleName = "xendit_bni"; break;
	case "BRI":
		$gatewayModuleName = "xendit_bri"; break;
	case "MANDIRI":
		$gatewayModuleName = "xendit_mandiri"; break;
    default:
		throw new Exception('payment method not recognize.');
}

$gatewayParams = getGatewayVariables($gatewayModuleName);
if (!$gatewayParams['type']) {
	exit("Module Not Activated");
}

/*--- end ---*/
$xenditId           = htmlspecialchars($_POST['id']);
$xenditUserId       = htmlspecialchars($_POST['user_id']);
$invoiceId          = htmlspecialchars($_POST['external_id']);
$status             = htmlspecialchars($_POST['status']);
$email              = htmlspecialchars($_POST['payer_email']);
$paymentMethod      = htmlspecialchars($_POST['payment_method']);
$paymentAmount      = htmlspecialchars($_POST['adjusted_received_amount']);
$reference          = strtoupper($paymentMethod.$xenditId);

//set parameters for Xendit inquiry
$endpoint       	= $gatewayParams['endpoint'];
$callbackserverkey	= $gatewayParams['callbackserverkey'];
$serverkey      	= $gatewayParams['serverkey'];
$paymentlist    	= $gatewayParams['paymentlist'];
$paymentfee     	= $gatewayParams['paymentfee'];
$expired        	= $gatewayParams['expired'];
$sendemail      	= $gatewayParams['sendemail'];

/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 */

$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

/**
 * Log Transaction.
 *
 * Add an entry to the Gateway Log for debugging purposes.
 *
 * The debug data can be a string or an array. In the case of an
 * array it will be
 *
 * @param string $gatewayName        Display label
 * @param string|array $debugData    Data to log
 * @param string $transactionStatus  Status
 */
logTransaction($gatewayParams['name'], $_POST, $status);


$success = false;
     
if ($status == 'PAID') {
	$success = true;
} else {
	$success = false;
}

if ($success) {
    /**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId         Invoice ID
     * @param string $transactionId  Transaction ID
     * @param float $paymentAmount   Amount paid (defaults to full balance)
     * @param float $paymentFee      Payment fee (optional)
     * @param string $gatewayModule  Gateway module name
     */
    addInvoicePayment(
        $invoiceId,
        $reference,
        $paymentAmount,
        0,
        $gatewayModuleName
	);    
} else {
	//Adopted from paypal to log all the failed transaction
	$orgipn = "";
	foreach ($_POST as $key => $value) {
		$orgipn.= ("" . $key . " => " . $value . "\r\n");		
	}
	logTransaction($gatewayModuleName, $orgipn, "Xendit Handshake Invalid");
}

/**
 * Redirect to invoice.
 *
 * Performs redirect back to the invoice upon completion of the 3D Secure
 * process displaying the transaction result along with the invoice.
 *
 * @param int $invoiceId        Invoice ID
 * @param bool $paymentSuccess  Payment status
 */
callback3DSecureRedirect($invoiceId, $status);