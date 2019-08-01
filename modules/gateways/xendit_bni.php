<?php
/**
 * WHMCS Xendit Payment Gateway Module
 * BNI Virtual Account by XenInvoice
 *
 * This is unofficial module, coming without guarantee
 *
 * For more information, please refer to the online documentation.
 * @see https://github.com/JuniYadi/xendit-whmcs-unofficial
 *
 * Module developed based on official WHMCS Sample Payment Gateway Module
 * @see https://developers.whmcs.com/payment-gateways/getting-started/
 * 
 * @author me@juniyadi.id
 */
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(dirname(__FILE__) . '/xendit-lib/XenditPHPClient.php');

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function xendit_bni_MetaData()
{
    return array(
        'DisplayName' => 'Xendit BNI Virtual Account Payment Gateway Module',
        'APIVersion' => '1.0', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 * 
 * @see https://developers.whmcs.com/payment-gateways/configuration/
 *
 * @return array
 */
function xendit_bni_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type'  => 'System',
            'Value' => 'Xendit BNI Virtual Account',
        ),
        // a text field type allows for single line text input
        'apikey' => array(
            'FriendlyName'  => 'Xendit Private API Keys',
            'Type'          => 'text',
            'Size'          => '100',
            'Default'       => '',
            'Description'   => 'Input Xendit Private API Keys.',
        ),
        // a text field type allows for single line text input
        'paymentfee' => array(
            'FriendlyName'  => 'Payment Fee',
            'Type'          => 'text',
            'Size'          => '100',
            'Default'       => '4950',
            'Description'   => 'Fixed Amount Payment Fee Will Added to Invoice and Pay by Client. (Default: 4.500 + PPN 10% (From 4.500*10% = 450) = 4.950).',
        ),
        // the dropdown field type renders a select menu of options
        'expired' => array(
            'FriendlyName'  => 'Time Invoice Expired',
            'Type'          => 'dropdown',
            'Options'       => array(
                    '300'       => '5 Minutes',
                    '900'       => '15 Minutes',
                    '1800'      => '30 Minutes',
                    '3600'      => '1 Hour',
                    '10800'     => '3 Hours',
                    '21600'     => '6 Hours',
                    '43200'     => '12 Hours',
                    '86400'     => '1 Day',
                    '259200'    => '3 Days',
            ),
            'Description'   => 'Select Duration Time For Invoice Expired.',
        ),
        // the yesno field type displays a single checkbox option
        'sendemail' => array(
            'FriendlyName'  => 'Send Invoice Email',
            'Type'          => 'yesno',
            'Description'   => 'Allow Xendit Send Email Invoice to Client',
        ),
    );
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see http://docs.whmcs.com/Payment_Gateway_Module_Parameters
 *
 * @return string
 */
function xendit_bni_link($params)
{
    // System parameters
    $companyName    = $params['companyname'];
    $systemUrl      = $params['systemurl'];
    $langPayNow     = $params['langpaynow'];

    // Modules parameters
    $apikey         = $params['apikey'];
    $paymentfee     = $params['paymentfee'];
    $expired        = $params['expired'];
    $sendemail      = $params['sendemail'];

    // Invoice parameters
    $modulename     = $params['name'];
    $amount         = (int)$params['amount'];
    $invoiceId      = $params['invoiceid'];	
    $currency       = $params['currency'];
    $url            = $systemUrl . "viewinvoice.php?id=" . $invoiceId;

	// Client Parameters
    $email          = $params['clientdetails']['email'];
   
    if(isset($_POST['payurlgenerator']) || !empty($_POST['payurlgenerator'])) {
        // Check Send Email
        if($sendemail) {
            $send_email_to_client = true;
        } else {
            $send_email_to_client = false;
        }   

        // Xendit Params
        $total          = $amount + $paymentfee;
        $description    = $companyName . ' - Order: #' . $invoiceId;

        // Prepare Parameters	
        $dataRequest = array(
            'should_send_email'     => $send_email_to_client,
            'invoice_duration'      => $expired,
            'payment_methods'       => ['BNI'],
            'currency'              => $currency,
            'success_redirect_url'  => $url . '&paymentsuccess=true',
            'failure_redirect_url'  => $url . '&paymentfailed=true',
        );

        // Api Key For Function
        $options['secret_api_key'] = $apikey;

        // Make a Request to Xendit
        $xendit     = new XenditPHPClient($options);
        $req        = $xendit->createInvoice("$invoiceId", $total, $email, $description, $dataRequest);
        
        // Get and Redirect URL From Response
        $redirUrl   = $req['invoice_url'];
        header('Location:' . $redirUrl);
        exit();
    } else {
        // Form For Generate URL XenInvoice
        $img         = $systemUrl . "/modules/gateways/xendit-images/bni.png"; 
        $htmlOutput .= '<img src="' . $img . '"><br>'.$modulename.'<br>';
        $htmlOutput .= '<form method="post" action="' . $url . '&payurlgenerator=true">';
        $htmlOutput .= '<input type="hidden" name="payurlgenerator" value="true" />';
        $htmlOutput .= '<input type="submit" class="btn btn-primary" value="'.$langPayNow.'" />';
        $htmlOutput .= '</form>';
    }

    return $htmlOutput;   
}