<?php
/**
 * This is the web checkout module for Paynow Zimbabwe
 * 
 */

use Paynow\Payments\Paynow;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


require_once __DIR__ . '/paynowzimbabwe/autoloader.php';

/**
 * Define module related meta data.
 *
 * @return array
 */
function paynowwebcheckoutmodule_MetaData()
{
    return array(
        'DisplayName' => 'Paynow - Web Checkout',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Define gateway configuration options.
 *
 * @return array
 */
function paynowwebcheckoutmodule_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Paynow - Web Checkout',
        ),
        // a text field type allows for single line text input
        'integrationID' => array(
            'FriendlyName' => 'Integration ID',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '',
            'Description' => 'Enter your integration ID here',
        ),
        // a text field type allows for single line text input
        'integrationKey' => array(
            'FriendlyName' => 'Integration Key',
            'Type' => 'text',
            'Size' => '32',
            'Default' => '',
            'Description' => 'Enter your integration key here',
        )
    );
}

/**
 * This function generates the link where the user is redirected to on checkout
 *
 * @return string
 */
function paynowwebcheckoutmodule_link($params)
{
    // Gateway Configuration Parameters
    $integrationID = $params['integrationID'];
    $integrationKey = $params['integrationKey'];


    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];

    // Client Parameters
    $email = $params['clientdetails']['email'];

    // System Parameters
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $moduleName = $params['paymentmethod'];

    $callbackUrl = $systemUrl . 'modules/gateways/callback/' . $moduleName . '.php';

    // Instantiate the Paynow class
    $paynow = new Paynow($integrationID, $integrationKey, $returnUrl, $callbackUrl);

    // Create the invoice
    $invoice = $paynow
        ->createPayment($invoiceId, $email)
        ->add($description, $amount);

    // HTML output
    $htmlOutput = "";

    try {
        // Send the payment off to paynow
        $result = $paynow->send($invoice);

        // If the initiation was not successful, throw an exception
        if (!$result->success) throw new Exception(serialize($result->data()));

        $svg = base64_encode(file_get_contents(__DIR__ . "/paynowzimbabwe/button.svg"));

        // Append the form HTML to the output
        $htmlOutput .= "<form style='padding-top: 15px' method='get' action='{$result->redirectUrl()}'>
            <button title='Checkout using Paynow' style='height:55px;background:none;border:none;' type='submit'>
                <img src=\"data:image/svg+xml;base64,{$svg}\" style='max-height:55px;'>
            </button>
        </form>";

    } catch (Exception $ex) {
        // TODO: Log the exception

        // Show some generic message to the user. Let them know some shit broke
        $htmlOutput .= "<h6>An error occurred while initiating transaction</h6>";
    }

    return $htmlOutput;
}
