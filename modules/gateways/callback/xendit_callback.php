<?php
require "../../../init.php";
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");

// Check Callback Method
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $logs = "Cannot ".$_SERVER["REQUEST_METHOD"]." ".$_SERVER["SCRIPT_NAME"];
    exit($logs);
    throw new Exception($logs);
}

$callback_data = file_get_contents("php://input");
$data = json_decode($callback_data, TRUE);

// check if the module is activated
/*--- start ---*/
$gatewayModuleName  = '';
$paymentCode        = htmlspecialchars($data['bank_code']);

switch ($paymentCode) {
  case "BCA":
    $gatewayModuleName = "xendit_bca"; break;
  case "BNI":
    $gatewayModuleName = "xendit_bni"; break;
  case "BRI":
    $gatewayModuleName = "xendit_bri"; break;
  case "MANDIRI":
    $gatewayModuleName = "xendit_mandiri"; break;
  case "PERMATA":
    $gatewayModuleName = "xendit_permata"; break;    
  default:
    throw new Exception('payment method not recognize.');
}

$gatewayParams = getGatewayVariables($gatewayModuleName);
if (!$gatewayParams['type']) {
	exit("Module Not Activated");
}

/*--- end ---*/
$xenditId           = htmlspecialchars($data['id']);
$xenditUserId       = htmlspecialchars($data['user_id']);
$invoiceId          = htmlspecialchars($data['external_id']);
$status             = htmlspecialchars($data['status']);
$email              = htmlspecialchars($data['payer_email']);
$bankCode           = htmlspecialchars($data['bank_code']);
$paymentMethod      = htmlspecialchars($data['payment_method']);
$paymentAmount      = htmlspecialchars($data['adjusted_received_amount']);
$reference          = $bankCode.$xenditId;

//set parameters for Xendit inquiry
$endpoint       	= $gatewayParams['endpoint'];
$apikey      	    = $gatewayParams['apikey'];
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
logTransaction($gatewayParams['name'], $data, $status);


$success = false;
     
if ($status == 'PAID' XOR $status == 'SETTLED') {
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
    echo "Payment success notification accepted";

} else {
	//Adopted from paypal to log all the failed transaction
	$orgipn = "";
	foreach ($data as $key => $value) {
		$orgipn.= ("" . $key . " => " . $value . "\r\n");		
	}
  logTransaction($gatewayModuleName, $orgipn, "Xendit Handshake Invalid");
  
  header("HTTP/1.0 406 Not Acceptable");
	exit();
}