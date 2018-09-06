<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Model;

use IDCI\Bundle\PaymentBundle\Model\GatewayResponse;
use PHPUnit\Framework\TestCase;

class GatewayResponseTest extends TestCase
{
    public function setUp()
    {
        $this->gatewayResponse = (new GatewayResponse())
            ->setTransactionUuid('dummy_transaction_uuid')
            ->setAmount(1000)
            ->setCurrencyCode('dummy_currency_code')
            ->setStatus('dummy_status')
            ->setMessage('dummy_message')
            ->setDate(new \DateTime('2018'))
            ->setRaw('dummy_raw')
        ;
    }

    public function testCastGatewayResponseToArray()
    {
        $this->assertEquals([
            'amount' => 1000,
            'status' => 'dummy_status',
            'message' => 'dummy_message',
            'raw' => 'dummy_raw',
            'transaction_uuid' => 'dummy_transaction_uuid',
        ], $this->gatewayResponse->toArray());
    }

    public function testIfAllGettersAreWorking()
    {
        $this->assertEquals('dummy_transaction_uuid', $this->gatewayResponse->getTransactionUuid());
        $this->assertEquals(1000, $this->gatewayResponse->getAmount());
        $this->assertEquals('dummy_currency_code', $this->gatewayResponse->getCurrencyCode());
        $this->assertEquals('dummy_status', $this->gatewayResponse->getStatus());
        $this->assertEquals('dummy_message', $this->gatewayResponse->getMessage());
        $this->assertEquals(new \DateTime('2018'), $this->gatewayResponse->getDate());
        $this->assertEquals('dummy_raw', $this->gatewayResponse->getRaw());
    }
}
