<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Model;

use IDCI\Bundle\PaymentBundle\Model\Transaction;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function setUp()
    {
        $this->transaction = (new Transaction())
            ->setItemId('dummy_item_id')
            ->setGatewayConfigurationAlias('dummy_gateway_configuration_alias')
            ->setCustomerId('dummy_customer_id')
            ->setCustomerEmail('dummy_customer_email')
            ->setStatus('dummy_status')
            ->setAmount('dummy_amount')
            ->setCurrencyCode('dummy_currency_code')
            ->setDescription('dummy_description')
            ->setCreatedAt(new \DateTime('2018'))
            ->setUpdatedAt(new \DateTime('2018'))
            ->setMetadatas([
                'dummy' => 'dummy',
            ])
        ;
    }

    public function testCastTransactionToArray()
    {
        $this->assertEquals([
            'id' => $this->transaction->getId(),
            'gatewayConfigurationAlias' => 'dummy_gateway_configuration_alias',
            'itemId' => 'dummy_item_id',
            'customerId' => 'dummy_customer_id',
            'customerEmail' => 'dummy_customer_email',
            'status' => 'dummy_status',
            'amount' => 'dummy_amount',
            'currencyCode' => 'dummy_currency_code',
            'description' => 'dummy_description',
            'metadatas' => [
                'dummy' => 'dummy',
            ],
            'createdAt' => new \DateTime('2018'),
            'updatedAt' => new \DateTime('2018'),
        ], $this->transaction->toArray());
    }

    public function testCastTransactionToString()
    {
        $this->assertEquals($this->transaction->getId(), (string) $this->transaction);
    }

    public function testTransactionAddMetadata()
    {
        $this->transaction->addMetadata('dummy', 'dummy');

        $this->assertTrue($this->transaction->hasMetadata('dummy'));
        $this->assertEquals('dummy', $this->transaction->getMetadata('dummy'));
    }

    public function testCreatedAndUpdatedTransactionField()
    {
        $this->assertEquals(new \DateTime('2018'), $this->transaction->getCreatedAt());
        $this->assertEquals(new \DateTime('2018'), $this->transaction->getUpdatedAt());
    }
}
