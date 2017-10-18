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
 * @see       https://developers.whmcs.com/payment-gateways/callbacks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license   http://www.whmcs.com/license/ WHMCS Eula
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
if ( ! $gatewayParams['type']) {
    die("Module Not Activated");
}

error_log(json_encode($_POST));

//{"vads_amount":"400","vads_auth_mode":"FULL","vads_auth_number":"3fdabf","vads_auth_result":"00","vads_capture_delay":"0","vads_card_brand":"CB","vads_card_number":"497010XXXXXX0014","vads_payment_certificate":"88cfef1f12571184e1fc9fcf9ecfa165d9f14447","vads_ctx_mode":"TEST","vads_currency":"978","vads_effective_amount":"400","vads_effective_currency":"978","vads_site_id":"41609008","vads_trans_date":"20171018135302","vads_trans_id":"000049","vads_trans_uuid":"92baf8e56e76446d8dbbd87c2d72fefd","vads_validation_mode":"0","vads_version":"V2","vads_warranty_result":"YES","vads_payment_src":"EC","vads_order_id":"49","vads_cust_id":"4","vads_cust_name":"Hyder Abbass Bangash","vads_cust_last_name":"Hyder Abbass Bangash","vads_cust_address":"Morcellement Seetaram, Henrietta, Vacoas","vads_cust_zip":"12345","vads_cust_city":"vacoas","vads_cust_country":"MU","vads_cust_phone":"57990900","vads_sequence_number":"1","vads_contract_used":"8800268","vads_trans_status":"AUTHORISED","vads_expiry_month":"6","vads_expiry_year":"2018","vads_bank_code":"17807","vads_bank_product":"F","vads_pays_ip":"FR","vads_presentation_date":"20171018135309","vads_effective_creation_date":"20171018135309","vads_operation_type":"DEBIT","vads_threeds_enrolled":"Y","vads_threeds_cavv":"Q2F2dkNhdnZDYXZ2Q2F2dkNhdnY=","vads_threeds_eci":"05","vads_threeds_xid":"SlRUSG9xNlJNNzVvRldKOGpSY3U=","vads_threeds_cavvAlgorithm":"2","vads_threeds_status":"Y","vads_threeds_sign_valid":"1","vads_threeds_error_code":"","vads_threeds_exit_status":"10","vads_result":"00","vads_extra_result":"","vads_card_country":"FR","vads_language":"en","vads_hash":"d69b8739252c5461f49d4322005c0a7931c0da5d2dd03f41452056c1cd212333","vads_url_check_src":"PAY","vads_action_mode":"INTERACTIVE","vads_payment_config":"SINGLE","vads_page_action":"PAYMENT","vads_shop_url":"https:\\/\\/cloud.stellatelecom.com\\/","signature":"d813a2706d50976667afb660bce3e72af3cb3d47"}


// Retrieve data returned in payment gateway callback
// Varies per payment gateway
$success       = $_POST["vads_trans_status"];
$invoiceId     = $_POST["x_invoice_id"];
$transactionId = $_POST["vads_trans_uuid"];
$paymentAmount = $_POST["vads_amount"];
$paymentFee    = '0';
$hash          = $_POST["signature"];

$transactionStatus = ($success == 'AUTHORISED') ? 'Success' : 'Failure';

/**
 * Validate callback authenticity.
 *
 * Most payment gateways provide a method of verifying that a callback
 * originated from them. In the case of our example here, this is achieved by
 * way of a shared secret which is used to build and compare a hash.
 */
$secretKey = $gatewayParams['secretKey'];
if ($hash != md5($invoiceId . $transactionId . $paymentAmount . $secretKey)) {
    $transactionStatus = 'Hash Verification Failure';
    $success           = false;
}

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
 * @param int    $invoiceId   Invoice ID
 * @param string $gatewayName Gateway Name
 */
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

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
checkCbTransID($transactionId);

/**
 * Log Transaction.
 *
 * Add an entry to the Gateway Log for debugging purposes.
 *
 * The debug data can be a string or an array. In the case of an
 * array it will be
 *
 * @param string       $gatewayName       Display label
 * @param string|array $debugData         Data to log
 * @param string       $transactionStatus Status
 */
logTransaction($gatewayParams['name'], $_POST, $transactionStatus);

if ($success) {

    /**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int    $invoiceId     Invoice ID
     * @param string $transactionId Transaction ID
     * @param float  $paymentAmount Amount paid (defaults to full balance)
     * @param float  $paymentFee    Payment fee (optional)
     * @param string $gatewayModule Gateway module name
     */
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $paymentAmount,
        $paymentFee,
        $gatewayModuleName
    );

}
