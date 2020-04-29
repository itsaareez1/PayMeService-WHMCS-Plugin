<?php
/**
 * WHMCS Sample Payment Callback File
 *
 * This sample file demonstrates how a payment gateway callback should be
 * handled within WHMCS.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/callbacks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

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

$success = isset($_REQUEST['status_code']) ? $_REQUEST['status_code'] : '';
$transactionId = isset($_REQUEST['transaction_id']) ? $_REQUEST['transaction_id'] : '';
$invoiceId = $transactionId;
$currencyCode = isset($_REQUEST['currency']) ? $_REQUEST['currency'] : '';
$am = isset($_REQUEST['price']) ? $_REQUEST['price'] : '';
$amount = $am/100;
$buyer_key = isset($_REQUEST['buyer_key']) ? $_REQUEST['buyer_key'] : '';
$buyer_card_mask = isset($_REQUEST['buyer_card_mask']) ? $_REQUEST['buyer_card_mask'] : '';
$buyer_card_exp = isset($_REQUEST['buyer_card_exp']) ? $_REQUEST['buyer_card_exp'] : '';
$cardType = isset($_REQUEST['payme_transaction_card_brand']) ? $_REQUEST['payme_transaction_card_brand'] : '';
/**
 * Validate callback authenticity.
 *
 * Most payment gateways provide a method of verifying that a callback
 * originated from them. In the case of our example here, this is achieved by
 * way of a shared secret which is used to build and compare a hash.
 */
// $secretKey = $gatewayParams['secretKey'];
// if ($hash != md5($invoiceId . $transactionId . $paymentAmount . $secretKey)) {
//     $transactionStatus = 'Hash Verification Failure';
//     $success = false;
// }

/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 *
 * @param int $invoiceId Invoice ID
 * @param string $gatewayName Gateway Name
 */

/**
 * Check Callback Transaction ID.
 *
 * Performs a check for any existing transactions with the same given
 * transaction number.
 *
 * Performs a die upon encountering a duplicate.
 *
 * @param string $transactionId Unique Transaction ID
 */

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
logTransaction($gatewayParams['name'], $_POST, $transactionStatus);

if ($success == 0) {


    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['payme']);

    logTransaction($gatewayParams['payme'], $_REQUEST, "Success");
    invoiceSaveRemoteCard($invoiceId, substr($buyer_card_mask, -4), $cardType, $buyer_card_exp, $buyer_key);
    addInvoicePayment($invoiceId, $transactionId, $amount, 'payme');
    callback3DSecureRedirect($invoiceId, true);
}
else
{
    logTransaction($gatewayParams['payme'], $_REQUEST, "Failed");
    sendMessage('Credit Card Payment Failed', $invoiceId);
    callback3DSecureRedirect($invoiceId, false);
}


