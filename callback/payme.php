<?php
/**
 * Author: Arslan ud Din Shafiq
 * Software Company: WebSoft IT Development Solutions (Private) Limited
 */

// Require libraries needed for gateway module functions.
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




// Retrieve data returned in redirect
$clientID = $_SESSION['uid'];

$success = isset($_POST['status_code']) ? $_POST['status_code'] : '';
$transactionId = isset($_POST['payme_transaction_id']) ? $_POST['payme_transaction_id'] : '';
$invoiceId = isset($_POST['transaction_id']) ? $_POST['transaction_id'] : '';
$currencyCode = isset($_POST['currency']) ? $_POST['currency'] : '';
$am = isset($_POST['price']) ? $_POST['price'] : '';
$amount = $am/100;
$buyer_key = isset($_POST['buyer_key']) ? $_POST['buyer_key'] : '';
$buyer_card_mask = isset($_POST['buyer_card_mask']) ? $_POST['buyer_card_mask'] : '';
$buyer_card_exp = isset($_POST['buyer_card_exp']) ? $_POST['buyer_card_exp'] : '';
$cardType = isset($_POST['payme_transaction_card_brand']) ? $_POST['payme_transaction_card_brand'] : '';
$sale_status = isset($_POST['sale_status']) ? $_POST['sale_status'] : '';
$psale_status = isset($_POST['payme_sale_status']) ? $_POST['payme_sale_status'] : '';
$orderid = Capsule::table('tblorders')->where('invoiceid',$invoiceId)->value('id');

if ($success == 0) {
    if ($sale_status == "completed" || $psale_status == "completed"){
        $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
        $command = 'AcceptOrder';
        $postData = array(
                'orderid' => $orderid,
                'sendemail' => true,
        );
        $results = localAPI($command, $postData);
        logModuleCall("Success", "", "", $results, "", "");            
        logTransaction($gatewayParams['name'], $_REQUEST, "Success");
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
            //'cancelsub' => true,
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


