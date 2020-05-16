<?php
/**
 * Author: Arslan ud Din Shafiq
 * Designation: Software Engineer
 * Author URI: https://imarslan.com
 * Software Company: WebSoft IT Development Solutions (Private) Limited
 * Company URL1: https://itdevsols.com
 * Company URL2: https://websoft.ltd
 * Whatsapp1: +923466257584
 * Whatsapp2: +923041280395
 * WeChat: +923041280395
 * License: GNU Affero General Public License v3.0
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
use WHMCS\Database\Capsule;

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Retrieve data returned in payment gateway callback

// // Retrieve data returned in redirect
$success = isset($_POST['status_code']) ? $_POST['status_code'] : '';
$success = filter_var($success, FILTER_SANITIZE_NUMBER_INT);

$transactionId = isset($_POST['payme_transaction_id']) ? $_POST['payme_transaction_id'] : '';
$transactionId = filter_var($transactionId, FILTER_SANITIZE_STRING);

$invoiceId = isset($_POST['transaction_id']) ? $_POST['transaction_id'] : '';
$invoiceId = filter_var($invoiceId, FILTER_SANITIZE_STRING);

$currencyCode = isset($_POST['currency']) ? $_POST['currency'] : '';
$currencyCode = filter_var($currencyCode, FILTER_SANITIZE_STRING);

$am = isset($_POST['price']) ? $_POST['price'] : '';
$am = filter_var($am, FILTER_SANITIZE_NUMBER_FLOAT);
$amount = $am/100;

$buyer_key = isset($_POST['buyer_key']) ? $_POST['buyer_key'] : '';
$buyer_key = filter_var($buyer_key, FILTER_SANITIZE_STRING);

$buyer_card_mask = isset($_POST['buyer_card_mask']) ? $_POST['buyer_card_mask'] : '';
$buyer_card_mask = filter_var($buyer_card_mask, FILTER_SANITIZE_STRING);

$buyer_card_exp = isset($_POST['buyer_card_exp']) ? $_POST['buyer_card_exp'] : '';
$buyer_card_exp = filter_var($buyer_card_exp, FILTER_SANITIZE_STRING);

$cardType = isset($_POST['payme_transaction_card_brand']) ? $_POST['payme_transaction_card_brand'] : '';
$cardType = filter_var($cardType, FILTER_SANITIZE_STRING);

$sale_status = isset($_POST['sale_status']) ? $_POST['sale_status'] : '';
$sale_status = filter_var($sale_status, FILTER_SANITIZE_STRING);

$psale_status = isset($_POST['payme_sale_status']) ? $_POST['payme_sale_status'] : '';
$psale_status= filter_var($psale_status, FILTER_SANITIZE_STRING);

$orderid = Capsule::table('tblorders')->where('invoiceid',$invoiceId)->value('id');
$orderid = filter_var($orderid, FILTER_SANITIZE_NUMBER_INT);


$command = 'GetInvoice';
$postData = array(
    'invoiceid' => $invoiceId,
);

$results = localAPI($command, $postData);
$userID = $results['userid'];
logModuleCall("User ID", "", "", $results, "", "");   


if ($success == 0) {
    if ($sale_status == "completed" || $psale_status == "completed"){
        $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
        $command = 'AcceptOrder';
        $postData = array(
                'orderid' => $orderid,
                'sendemail' => true,
        );
        $results = localAPI($command, $postData);
        invoiceSaveRemoteCard($invoiceId, substr($buyer_card_mask, -4), $cardType, $buyer_card_exp, $buyer_key);
        if (!empty($buyer_key)){
            addInvoicePayment($invoiceId, $transactionId, $amount, 'payme');
        }
        callback3DSecureRedirect($invoiceId, true);        
        
    }
    else if ($sale_status == "refunded" || $sale_status == "partial-refund" || $sale_status == "chargeback"){
        $command = 'PendingOrder';
        $postData = array(
            'orderid' => $orderid,  
        );
        $results = localAPI($command, $postData);
        logModuleCall("Requested Order Cancellation", "", "", $results, "", "");
        $command = 'CancelOrder';
        $postData = array(
            'orderid' => $orderid,
            'noemail' => true
        );
        $results = localAPI($command, $postData);
        logModuleCall("Order Cancelled", "", "", $results, "", "");
        $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
        $command = 'UpdateInvoice';
        $postData = array(
            'invoiceid' => $invoiceId,
            'status' => 'Refunded',
        );
        $results = localAPI($command, $postData);
        logTransaction($gatewayParams['name'], $_REQUEST, "Refunded");        
    }
    else
    {
        if ($sale_status != "initial"){
            logTransaction($gatewayParams['name'], $_REQUEST, "Failed");
            sendMessage('Credit Card Payment Failed', $invoiceId);
        }
    }
}
else
{
    logTransaction($gatewayParams['name'], $_REQUEST, "Failed");
    sendMessage('Credit Card Payment Failed', $invoiceId);
    callback3DSecureRedirect($invoiceId, false);
}


