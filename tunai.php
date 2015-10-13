<?php

require realpath(__DIR__ . DIRECTORY_SEPARATOR .'tunai/Tunai.php');

use Sandiloka\tunai\Invoice;

function tunai_config()
{
    $configarray = array
    (
        "FriendlyName" => array("Type" => "System", "Value"=>"tunai.ID"),
        "api_username" => array("FriendlyName" => "API Username", "Type" => "text", "Size" => "25", "Description" => "WHMCS API Username (Administrator)"),
        "accesskey" => array("FriendlyName" => "Access Key", "Type" => "text", "Size" => "16", ),
        "secretkey" => array("FriendlyName" => "Secret Key", "Type" => "text", "Size" => "16", ),
        "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Test Mode", ),
        "log_enable" => array("FriendlyName" => "Log Enable", "Type" => "yesno", "Description" => "Enable module logging"),
    );
    return $configarray;
}

function tunai_link($params)
{
    # Gateway Specific Variables
    $merchantid = $params['merchantid'];
    $accesskey  = $params['accesskey'];
    $secretkey  = $params['secretkey'];

    # Invoice Variables
    $invoiceid = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount']; # Format: ##.##
    $currency = $params['currency']; # Currency Code

    # Client Variables
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

    # System Variables
    $companyname = $params['companyname'];
    $systemurl = $params['systemurl'];
    $currency = $params['currency'];
    $log_enable = $params['log_enable'];

    # Invoice Items
    $command = "getinvoice";
    $adminuser = $params['api_username'];
    $values["invoiceid"] = $invoiceid;
    $results = localAPI($command,$values,$adminuser);

    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, $results, $values, $tags);
    xml_parser_free($parser);

    if($log_enable == true)
    {
        logModuleCall('tunai','getinvoice',$values,$results,$ressultarray,'');
    }

    $item_details = array();
    $itemid = 0;

    if($results["result"] == "success")
    {
        $invoiceitems = $results['items']['item'];
        for ($i = 0; $i < count($invoiceitems); $i++)
        {
            $invoiceitem = $invoiceitems[$i];
            $itemdescription = $invoiceitem['description'];
            $itemdescription = preg_replace( "/\r|\n/", ",", $itemdescription );
            if(strlen($itemdescription)>50)
            {
                $itemdescription = substr($itemdescription, 0, 50);
            }

            $itemid++;
            $data = array(
                'itemId' => strval($itemid),
                'price' => $invoiceitem['amount'],
                'quantity' => 1,
                'description' => $itemdescription
            );
            $item_details[] = $data;
        }

        # PPn
        $tax = $results['tax'];
        if ($tax > 0)
        {
            $itemid++;
            $data = array(
                'itemId' => strval($itemid),
                'price' => $tax,
                'quantity' => 1,
                'description' => 'Pajak'
            );
            $item_details[] = $data;
        }

        # credit
        $credit = $results['credit'];
        if($credit > 0)
        {
            $itemid++;
            $data = array(
                'itemId' => strval($itemid),
                'price' => $credit * (-1),
                'quantity' => 1,
                'description' => 'Saldo'
            );
            $item_details[] = $data;
        }
    }

    if($log_enable == true)
    {
        logModuleCall('tunai','itemdetails',$values,$results,$item_details,'');
    }

    $customer = array
        (
            "name" => $firstname." ".$lastname,
            "address" => $address1."\r\n".$address2,
            "phone" => $phone,
            "city" => $city,
            "province" => $state,
            "zipcode" => $postcode,
            "country" => $country,
            "mobilephone" => "",
            "identity" => ""
        );

    $data = array
        (
            "refId" => strval($invoiceid),
            "expired" => ((string) time() + 24 * 60 * 60) . '000',
            "amount" => $amount,
            "customer" => $customer,
            "items" => $item_details
        );

    $invoice = new Invoice($accesskey, $secretkey);
    $response = $invoice->getByRefOrCreate($data);

    $json = $response->getBody();
    if($log_enable == true)
    {
        logModuleCall('tunai','getByRefOrCreate',$data, $json, $ressultarray,'');
    }

    $statusCode = $response->getStatusCode();
    $obj = json_decode($json);

    if ($statusCode == 200) {
        $code = '<img src="//files.tunai.id/images/tunai-button-white.png" alt="tunai" /><p>Kode Pembayaran: <strong>' . $obj->token . '</strong><br />Total: Rp ' . $obj->amount . ' </p><form method="post" action="https://pay.tunai.id/'.$obj->token.'"><input type="submit" value="Bayar ke Agen" /></form>';
        return $code;
    }
    return '<b>' . $obj->message . '</b>';
}

?>
