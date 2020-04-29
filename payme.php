<?php
/**
 * WHMCS Sample Payment Gateway Module
 *
 * Payment Gateway modules allow you to integrate payment solutions with the
 * WHMCS platform.
 *
 * This sample file demonstrates how a payment gateway module for WHMCS should
 * be structured and all supported functionality it can contain.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "payme" and therefore all functions
 * begin "payme_".
 *
 * If your module or third party API does not support a given function, you
 * should not define that function within your module. Only the _config
 * function is required.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
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
            'Description' => 'PayMe Seller ID',
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
        $url = 'https://preprod.paymeservice.com/api/generate-sale?first_name='.$firstname.'&last_name='.$lastname.'&phone='.$phone.'&email='.$email.'&zip_code='.$postcode;
    } 
    else 
    { // live mode
        $url = 'https://ng.paymeservice.com/api/generate-sale?first_name='.$firstname.'&last_name='.$lastname.'&phone='.$phone.'&email='.$email.'&zip_code='.$postcode;
    }

    $gatewayid = $params['gatewayid'];

    if ($gatewayid){

        $postfield = [
            'seller_payme_id' => $sellerID,
            'sale_price' => $amount,
            'currency' => $currencyCode,
            'installments' => 1,
            'product_name' => $description,
            'sale_email' => $email,
            'sale_mobile' => $phone,
            'sale_name' => $description,
            'buyer_key' => $gatewayid,
            'buyer_perform_validation' => true,
            'language' => $langPayMe,
            'sale_return_url' => $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php',
    
        ];    

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfield));
    
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json"
          ));
        $response = curl_exec($ch);
        curl_close($ch);
    
        $responseData = json_decode($response);


        return [
            'status' => 'success',
            'transid' => $response['transaction_id'],
            'rawdata' => $response,
        ];
    }
    else
    {
        $data->seller_payme_id = $sellerID;
        $data->sale_price = $amount;
        $data->currency = $currencyCode;
        $data->product_name = $description;
        $data->transaction_id = $transid;
        $data->installments = 1;
        $data->sale_return_url = $sale_return_url;
        $data->capture_buyer = 1;
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
    
    
        if ($responseData->status_code == 0) {
            if ($responseData->sale_status == "completed")
            {
                $returnData = [
                    // 'success' if successful, otherwise 'declined', 'error' for failure
                    'status' => 'success',
                    // Data to be recorded in the gateway log - can be a string or array
                    'rawdata' => $responseData,
                    // Unique Transaction ID for the capture transaction
                    'transid' => $responseData->transaction_id,
                    //token returned by payment gateway
                    'gatewayid' => $responseData->buyer_key
                ];    
            }
        } else {
            $returnData = [
                // 'success' if successful, otherwise 'declined', 'error' for failure
                'status' => 'declined',
                // When not successful, a specific decline reason can be logged in the Transaction History
                'declinereason' => $responseData->status_error_details,
                // Data to be recorded in the gateway log - can be a string or array
                'rawdata' => $responseData,
            ];
        }
    
    }
    return $returnData;
}