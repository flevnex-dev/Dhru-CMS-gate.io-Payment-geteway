<?php

defined("DEFINE_MY_ACCESS") or die('<h1 style="color: #C00; text-align: center;"><strong>Restricted Access</strong></h1>');

function gateio_config()
{
    $configarray = array(
        'name' => array('Type' => 'System', 'Value' => 'Gate.io'),
        'api_key' => array(
            'Name' => 'API Key',
            'Type' => 'text',
            'Size' => '40',
            'Description' => 'Your Gate.io API key.'
        ),
        'secret_key' => array(
            'Name' => 'Secret Key',
            'Type' => 'text',
            'Size' => '40',
            'Description' => 'Your Gate.io Secret key.'
        ),
        'client_id' => array(
            'Name' => 'Client ID',
            'Type' => 'text',
            'Size' => '40',
            'Description' => 'Your Gate.io Client ID.'
        ),
    );
    return $configarray;
}

function gateio_link($params)
{
    $client_id = $params['client_id'];
    $api_key = $params['api_key'];
    $secret_key = $params['secret_key'];
    $invoiceid = $params['invoiceid'];
    $invoicetotal = number_format($params['amount'], 2, '.', '');
    $currency = 'USDT';  // Correct currency
    $return_url = $params['returnurl'];
    $callback_url = $params['systemurl'] . 'gatecallback.php'; 

    // Create the HTML form for payment
    $htmlOutput = '<form action="' . $params['systemurl'] . 'gateio_process.php" method="POST">';
    $htmlOutput .= '<input type="hidden" name="invoiceid" value="' . $invoiceid . '">';
    $htmlOutput .= '<input type="hidden" name="amount" value="' . $invoicetotal . '">';
    $htmlOutput .= '<input type="hidden" name="currency" value="' . $params['currency'] . '">';
    $htmlOutput .= '<input type="hidden" name="api_key" value="' . $api_key . '">';
    $htmlOutput .= '<input type="hidden" name="secret_key" value="' . $secret_key . '">';
    $htmlOutput .= '<input type="hidden" name="client_id" value="' . $params['client_id'] . '">';
    $htmlOutput .= '<input type="hidden" name="callbackURL" value="' . $callback_url . '">';
    $htmlOutput .= '<input type="hidden" name="returnurl" value="' . $return_url . '">';
    $htmlOutput .= '<input type="submit" value="Pay with Gate.io" class="btn btn-success">';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}




