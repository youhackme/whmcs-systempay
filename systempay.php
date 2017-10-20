<?php
/**
 * WHMCS SystemPay Payment Gateway Module
 *
 * This Payment Gateway modules allow you to integrate SystemPay payment solutions with the
 * WHMCS platform.
 *
 */
if ( ! defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (file_exists(__DIR__ . '/systempay/vendor/autoload.php')) {
    require_once(__DIR__ . '/systempay/vendor/autoload.php');

} else {
    die('Unable to  autoload class');
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
function systempay_MetaData()
{
    return [
        'DisplayName'                => 'Systempay',
        'APIVersion'                 => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage'           => false,
    ];
}


/**
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 * @return array
 */
function systempay_config()
{
    return [
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName'    => [
            'Type'    => 'System',
            'Value'   => 'Systempay',
            'Default' => 'Systempay',
        ],
        // SystemPay merchant ID
        'siteId'          => [
            'FriendlyName' => 'Identifiant de votre site',
            'Type'         => 'text',
            'Size'         => '10',
            'Default'      => '',
            'Description'  => 'L\'identifiant de votre site, disponible dans l\'outil de gestion de caisse',
        ],
        // Your test certificate
        'certificateTest' => [
            'FriendlyName' => 'Certificat en mode test',
            'Type'         => 'text',
            'Size'         => '20',
            'Default'      => '',
            'Description'  => 'Certificat fourni par la plateforme de paiement',
        ],
        //Your production certificate
        'certificateProd' => [
            'FriendlyName' => 'Certificat en mode prod',
            'Type'         => 'text',
            'Size'         => '20',
            'Default'      => '',
            'Description'  => 'Certificat fourni par la plateforme de paiement',
        ],
        // Switch between production and sandbox mode
        'mode'            => [
            'FriendlyName' => 'Mode',
            'Type'         => 'dropdown',
            'Options'      => [
                'PRODUCTION' => 'PRODUCTION',
                'TEST'       => 'TEST',
            ],
            'Description'  => 'Mode test ou production',
        ],

    ];
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
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */
function systempay_link($params)
{

    // Gateway Configuration Parameters
    // SystemPay site id
    $siteId = $params['siteId'];

    $certificateKey = $params['certificateProd'];
    $mode           = $params['mode'];
    if ($mode == 'TEST') {
        $certificateKey = $params['certificateTest'];
    }


    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $amount    = $params['amount'];
    $orderId   = $params['invoicenum'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname  = $params['clientdetails']['lastname'];
    $address1  = $params['clientdetails']['address1'];
    $city      = $params['clientdetails']['city'];
    $postcode  = $params['clientdetails']['postcode'];
    $country   = $params['clientdetails']['country'];
    $phone     = $params['clientdetails']['phonenumber'];
    $userid    = $params['clientdetails']['userid'];


    // System Parameters
    $systemUrl = $params['systemurl'];
    // This points to the invoice
    $returnUrl = $params['returnurl'];
    // Button Text
    $langPayNow = $params['langpaynow'];
    $moduleName = $params['paymentmethod'];


    //Callback url for server-to-server communication
    $callbackUrl = $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php';

    // Default configuration
    $systempay = (new \Systempay\Systempay())->set_params(
        [
            'vads_page_action'    => 'PAYMENT',
            'vads_action_mode'    => 'INTERACTIVE',
            'vads_payment_config' => 'SINGLE',
            'vads_page_action'    => 'PAYMENT',
            'vads_version'        => 'V2',
            'vads_trans_date'     => gmdate('YmdHis'),
            'vads_currency'       => '978',
        ]);


    $systempay->set_site_id($siteId)
              ->set_amount($amount)
              ->set_trans_id(sprintf("%06d", $invoiceId))
              ->set_cust_id($userid)
              ->set_cust_name($firstname . ' ' . $lastname)
              ->set_cust_address($address1)
              ->set_cust_phone($phone)
              ->set_cust_city($city)
              ->set_cust_country($country)
              ->set_cust_zip($postcode)
              ->set_url_return($returnUrl)
              ->set_url_check($callbackUrl)
              ->set_ctx_mode($mode)
              ->set_shop_url($systemUrl)
              ->set_order_id($orderId);

    //create the signature based on the certificate key
    $systempay->set_signature($certificateKey);

    //create html systempay call form
    return $systempay->get_form(
        '<button class="btn btn-lg btn-primary btn-payment" type="submit">' . $langPayNow . '</button>'
    );
}