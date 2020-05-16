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


if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

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
function payme_MetaData()
{
    return array(
        'DisplayName' => 'PayMe',
        'APIVersion' => '1.1', // Use API Version 1.1
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
 * @return array
 */
function payme_config()
{

    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'PayMe',
        ),
        // a text field 
        'seller_payme_id' => array(
            'FriendlyName' => 'Seller ID',
            'Type' => 'password',
            'Size' => '36',
            'Default' => '',
            'Description' => 'PayMe Seller ID - Format: XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX',
        ),
        'langpayme' => array(
            'FriendlyName' => 'Language',
            'Type' => 'text',
            'Size' => '2',
            'Default' => 'en',
            'Description' => '\'en\' for English and \'he\' for Hebrew',
        ),
        // the yesno field type displays a single checkbox option
        'testMode' => array(
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Tick to enable test mode',
        ),
    );
}
/**
 * No local credit card input.
 *
 * This is a required function declaration. Denotes that the module should
 * not allow local card data input.
 */
function remoteinputgateway_nolocalcc() {}

/**
 * Capture payment.
 *
 * Called when a payment is to be processed and captured.
 *
 * The card cvv number will only be present for the initial card holder present
 * transactions. Automated recurring capture attempts will not provide it.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/merchant-gateway/
 *
 * @return array Transaction response status
 */
function payme_capture($params)
{
    $url = '';
    // Gateway Configuration Parameters
    $sellerID = $params['seller_payme_id'];
    $langPayMe = $params['langpayme'];
    $testMode = $params['testMode'];
    

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Credit Card Parameters
    $cardType = $params['cardtype'];
    $cardNumber = $params['cardnum'];
    $cardExpiry = $params['cardexp'];
    $cardStart = $params['cardstart'];
    $cardIssueNumber = $params['cardissuenum'];
    $cardCvv = $params['cccvv'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];
    
    //switch modes
    if($params['testMode'] == 'on') { //testmode
        $url = 'https://preprod.paymeservice.com/api/generate-sale';
    } 
    else 
    { // live mode
        $url = 'https://ng.paymeservice.com/api/generate-sale';
    }


    $gatewayid = $params['gatewayid'];

    if ($gatewayid != ""){

        $dat->seller_payme_id = $sellerID;
        $dat->sale_price = $amount*100;
        $dat->currency = $currencyCode;
        $dat->product_name = $description;
        $dat->installments = 1;
        $dat->sale_callback_url = $systemUrl . 'modules/gateways/callback/payme.php';
        $dat->language = $langPayMe;
        $dat->buyer_key = $gatewayid;
        $dat->transaction_id = $invoiceId;
        $jsonDat = json_encode($dat);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);        
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDat);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json"
        ));
        
        $response = curl_exec($ch);
        curl_close($ch);
    
        $responseData = json_decode($response);
        
        if ($responseData->status_code == 0 && $responseData->sale_status == "completed") {
            
            return [
                // 'success' if successful, otherwise 'declined', 'error' for failure
                'status' => 'success',
                // The unique transaction id for the payment
                'transid' => $responseData->payme_transaction_id,
                // Data to be recorded in the gateway log - can be a string or array
                'rawdata' => $response . 'Invoice:' . $invoiceId,
            ];
        }
        else{

            return [
                // 'success' if successful, otherwise 'declined', 'error' for failure
                'status' => 'declined',
                // For declines, a decline reason can optionally be returned
                'declinereason' => $response['decline_reason'],
                // Data to be recorded in the gateway log - can be a string or array
                'rawdata' => $responseData,
            ];
        }
    }
    else{
        return [
            'status' => 'declined',
            'decline_message' => 'No Remote Token',
        ];
    }
}
/**
 * Remote input.
 *
 * Called when a pay method is requested to be created or a payment is
 * being attempted.
 *
 * New pay methods can be created or added without a payment being due.
 * In these scenarios, the amount parameter will be empty and the workflow
 * should be to create a token without performing a charge.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/remote-input-gateway/
 *
 * @return array
 */
function payme_remoteinput($params)
{

    // Gateway Configuration Parameters
    $sellerID = $params['seller_payme_id'];
    $langPayMe = $params['langpayme'];
    $testMode = $params['testMode'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params['description'];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $clientId = $params['clientdetails']['id'];
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $postcode = $params['clientdetails']['postcode'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    $url = '';
    //switch modes
    if($params['testMode'] == 'on') { //testmode
        $url = 'https://preprod.paymeservice.com/api/generate-sale';
    } 
    else 
    { // live mode
        $url = 'https://ng.paymeservice.com/api/generate-sale';
    }

    $data->seller_payme_id = $sellerID;
    $data->sale_price = $amount*100;
    $data->currency = $currencyCode;
    $data->product_name = $description;
    $data->installments = 1;
    $data->sale_callback_url = $systemUrl . 'modules/gateways/callback/payme.php';
    $data->capture_buyer = 1;
    $data->language = $langPayMe;
    $data->transaction_id = $invoiceId;
    $jsonData = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);        
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Content-Type: application/json"
    ));
    
    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response);

    if ($responseData->status_code == 0){

        return '<form method="GET" action="'.$responseData->sale_url.'?first_name='.$firstname.'&last_name='.$lastname.'&phone='.$phone.'&email='.$email.'&zip_code='.$postcode.'"> 
        <noscript>
            <input type="submit" value="Click here to continue &raquo;">
        </noscript>
        </form>';    
    }
    else{
        return '<form method="GET" action="https://debug.imarslan.com/helper.php"> 
        <input type="hidden" name="data" value="Error:'. $responseData->status_error_details .', Code: '. $responseData->status_error_code .'" />
        </form>';    
    }
}
/**
 * Remote update.
 *
 * Called when a pay method is requested to be updated.
 *
 * The expected return of this function is direct HTML output. It provides
 * more flexibility than the remote input function by not restricting the
 * return to a form that is posted into an iframe. We still recommend using
 * an iframe where possible and this sample demonstrates use of an iframe,
 * but the update can sometimes be handled by way of a modal, popup or
 * other such facility.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/remote-input-gateway/
 *
 * @return array
 */
function payme_remoteupdate($params)
{
    // Gateway Configuration Parameters
    $sellerID = $params['seller_payme_id'];
    $langPayMe = $params['langpayme'];
    $testMode = $params['testMode'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params['description'];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $clientId = $params['clientdetails']['id'];
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $postcode = $params['clientdetails']['postcode'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    $url = '';
    //switch modes
    if($params['testMode'] == 'on') { //testmode
        $url = 'https://preprod.paymeservice.com/api/generate-sale';
    } 
    else 
    { // live mode
        $url = 'https://ng.paymeservice.com/api/generate-sale';
    }

    $data->seller_payme_id = $sellerID;
    $data->sale_price = $amount*100;
    $data->currency = $currencyCode;
    $data->product_name = $description;
    $data->installments = 1;
    $data->sale_callback_url = $systemUrl . 'modules/gateways/callback/payme.php';
    $data->capture_buyer = 1;
    $data->language = $langPayMe;
    $data->transaction_id = $invoiceId;
    $jsonData = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);        
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Content-Type: application/json"
    ));
    
    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response);

    if ($responseData->status_code == 0){
        return '<form method="GET" action="'.$responseData->sale_url.'?first_name='.$firstname.'&last_name='.$lastname.'&phone='.$phone.'&email='.$email.'&zip_code='.$postcode.'"> 
        <noscript>
            <input type="submit" value="Click here to continue &raquo;">
        </noscript>
        </form>';    
    }
    else{
        return '<form method="GET" action="https://debug.imarslan.com/helper.php"> 
        <input type="hidden" name="data" value="Error:'. $responseData->status_error_details .', Code: '. $responseData->status_error_code .'" />
        </form>';    
    }
}
/**
 * Admin status message.
 *
 * Called when an invoice is viewed in the admin area.
 *
 * @param array $params Payment Gateway Module Parameters.
 *
 * @return array
 */
function remoteinputgateway_adminstatusmsg($params)
{
    // Gateway Configuration Parameters
    $sellerID = $params['seller_payme_id'];
    
    // Invoice Parameters
    $remoteGatewayToken = $params['gatewayid'];
    $invoiceId = $params['id']; // The Invoice ID
    $userId = $params['userid']; // The Owners User ID
    $date = $params['date']; // The Invoice Create Date
    $dueDate = $params['duedate']; // The Invoice Due Date
    $status = $params['status']; // The Invoice Status

    if ($remoteGatewayToken) {
        return [
            'type' => 'info',
            'title' => 'Token Gateway Profile',
            'msg' => 'This customer has a Remote Token storing their card'
                . ' details for automated recurring billing with ID ' . $remoteGatewayToken,
        ];
    }
}