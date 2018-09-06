<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Event;

use IDCI\Bundle\PaymentBundle\Entity\Transaction;
use IDCI\Bundle\PaymentBundle\Event\TransactionEvent;
use PHPUnit\Framework\TestCase;

class TransactionEventSubscriberTest extends TestCase
{
    public function testGetTransaction()
    {
        $transaction = new Transaction();
        $transactionEvent = new TransactionEvent($transaction);

        $this->assertEquals($transaction, $transactionEvent->getTransaction());
    }
}
