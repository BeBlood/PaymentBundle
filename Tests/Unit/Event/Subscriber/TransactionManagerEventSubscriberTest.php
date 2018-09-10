<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Event\Subscriber;

use Doctrine\Common\Persistence\ObjectManager;
use IDCI\Bundle\PaymentBundle\Entity\Transaction;
use IDCI\Bundle\PaymentBundle\Event\Subscriber\TransactionManagerEventSubscriber;
use IDCI\Bundle\PaymentBundle\Event\TransactionEvent;
use IDCI\Bundle\PaymentBundle\Manager\DoctrineTransactionManager;
use PHPUnit\Framework\TestCase;

class TransactionManagerEventSubscriberTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $om;

    public function setUp()
    {
        $this->transactionManager = $this->getMockBuilder(DoctrineTransactionManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $transaction = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->transactionEvent = $this->getMockBuilder(TransactionEvent::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->transactionEvent
            ->method('getTransaction')
            ->willReturn($transaction)
        ;
    }

    public function testGetSubscribedEvents()
    {
        $events = TransactionManagerEventSubscriber::getSubscribedEvents();

        $this->assertEquals(array_keys($events), [
            TransactionEvent::APPROVED,
            TransactionEvent::CANCELED,
            TransactionEvent::CREATED,
            TransactionEvent::FAILED,
            TransactionEvent::PENDING,
        ]);
    }

    public function testSave()
    {
        $this->transactionManagerEventSubscriber = new TransactionManagerEventSubscriber($this->transactionManager);

        $this->transactionManagerEventSubscriber->save($this->transactionEvent);
    }
}
