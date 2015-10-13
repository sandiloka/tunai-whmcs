<?php

namespace Sandiloka\Tests\Tunai;

use Sandiloka\Tunai\Invoice;
use GuzzleHttp\Exception\ClientException;

class InvoiceTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateInvoice()
    {
        $key = getenv('TUNAI_APP_KEY');
        $secret = getenv('TUNAI_APP_SEC');
        $invoice = new Invoice($key, $secret);

        // Generate current invoice refId
        $currentInvoiceRefId = (string) time();

        // Prepare a dummy data
        $customer = array
            (
                'name' => 'Joni Iskandar',
                'address' => 'Jalan Senandung Bahagia No. 1 Bandung',
                'phone' => '08122097788'
            );
        $item = array
            (
                'id' => '1234567890',
                'price' => 2000,
                'qty' => 1,
                'description' => 'Sepatu Warna Merah Jambu'

            );
        $items = array();
        array_push($items, $item);
        $currentInvoiceData = array
            (
                'refId' => $currentInvoiceRefId,
                'expired' => ((string) time() + 24 * 60 * 60) . '000',
                'customer' => $customer,
                'items' => $items
            );
        $res = $invoice->create($currentInvoiceData);
        $json = $res->getBody();
        $obj = json_decode($json);
        $this->assertEquals($res->getStatusCode(), 200);
        $currentInvoiceId = $obj->token;
        $this->assertNotEmpty($currentInvoiceId);
        $ret = array
            (
                'currentInvoiceId' => $currentInvoiceId,
                'currentInvoiceRefId' => $currentInvoiceRefId,
                'currentInvoiceData' => $currentInvoiceData
            );

        return $ret;
    }

    /**
     * @depends testCreateInvoice
     */
    public function testGetInvoiceById($ret)
    {
        $key = getenv('TUNAI_APP_KEY');
        $secret = getenv('TUNAI_APP_SEC');
        $invoice = new Invoice($key, $secret);
        $currentInvoiceId = $ret['currentInvoiceId'];
        $res = $invoice->getById($currentInvoiceId);
        $this->assertEquals($res->getStatusCode(), 200);
    }

    /**
     * @depends testCreateInvoice
     */
    public function testGetInvoiceByRefId($ret)
    {
        $key = getenv('TUNAI_APP_KEY');
        $secret = getenv('TUNAI_APP_SEC');
        $invoice = new Invoice($key, $secret);
        $currentInvoiceRefId = $ret['currentInvoiceRefId'];
        $res = $invoice->getByRef($currentInvoiceRefId);
        $this->assertEquals($res->getStatusCode(), 200);
    }

    /**
     * @depends testCreateInvoice
     */
    public function testGetByRefOrCreateInvoiceWithAvailableRefId($ret)
    {
        $key = getenv('TUNAI_APP_KEY');
        $secret = getenv('TUNAI_APP_SEC');
        $invoice = new Invoice($key, $secret);
        $currentInvoiceData = $ret['currentInvoiceData'];
        $res = $invoice->getByRefOrCreate($currentInvoiceData);
        $this->assertEquals($res->getStatusCode(), 200);
    }
    
    /**
     * @depends testCreateInvoice
     */
    public function testGetByRefOrCreateInvoiceWithNoAvailableRefId($ret)
    {
        $key = getenv('TUNAI_APP_KEY');
        $secret = getenv('TUNAI_APP_SEC');
        $invoice = new Invoice($key, $secret);
        $currentInvoiceData = $ret['currentInvoiceData'];
        $currentInvoiceData['refId'] = (string) time();
        $res = $invoice->getByRefOrCreate($currentInvoiceData);
        $this->assertEquals($res->getStatusCode(), 200);
    }

    /**
     * Not yet implemented
     */
    /* 
    public function testRemoveInvoiceById()
    {
        $key = getenv(TUNAI_APP_KEY);
        $secret = getenv(TUNAI_APP_SEC);
        $invoice = new Invoice($key, $secret);
    }
    
    public function testGetByRefOrCreateInvoiceWithNoAvailableRefId()
    {
        $key = getenv(TUNAI_APP_KEY);
        $secret = getenv(TUNAI_APP_SEC);
        $invoice = new Invoice($key, $secret);
    }
    */
}
