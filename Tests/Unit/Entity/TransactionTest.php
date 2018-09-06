<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Entity;

use IDCI\Bundle\PaymentBundle\Entity\Transaction;
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

    public function testTransactionOnPrePersistCycleLifeback()
    {
        $this->transaction
            ->setCreatedAt(new \DateTime('2018'))
            ->setUpdatedAt(new \DateTime('2018'))
        ;

        $this->transaction->onPrePersist();

        $this->assertNotEquals(new \DateTime('2018'), $this->transaction->getCreatedAt());
        $this->assertNotEquals(new \DateTime('2018'), $this->transaction->getUpdatedAt());
    }

    public function testTransactionOnPreUpdateCycleLifeback()
    {
        $this->transaction
            ->setCreatedAt(new \DateTime('2018'))
            ->setUpdatedAt(new \DateTime('2018'))
        ;

        $this->transaction->onPreUpdate();

        $this->assertEquals(new \DateTime('2018'), $this->transaction->getCreatedAt());
        $this->assertNotEquals(new \DateTime('2018'), $this->transaction->getUpdatedAt());
    }
}
