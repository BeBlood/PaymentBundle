<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use IDCI\Bundle\PaymentBundle\Entity\Transaction;
use IDCI\Bundle\PaymentBundle\Manager\DoctrineTransactionManager;
use PHPUnit\Framework\TestCase;

class DoctrineTransactionTest extends TestCase
{
    /**
     * @var PaymentManager
     */
    private $paymentManager;

    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var DoctrineTransactionManager
     */
    private $doctrineTransactionManager;

    private $transactionRepository;

    public function setUp()
    {
        $this->transaction = $this->getMockBuilder(Transaction::class)
            ->getMock()
        ;

        $this->transactionRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->transactionRepository
            ->method('findOneBy')
            ->will($this->returnValueMap([
                [['id' => 'wrong_transaction_id'], null, null],
                [['id' => 'dummy_transaction_id'], null, $this->transaction],
            ]))
        ;

        $this->om = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->om
            ->method('getRepository')
            ->with(Transaction::class)
            ->willReturn($this->transactionRepository)
        ;

        $this->om
            ->expects($this->once())
            ->method('persist')
        ;
        $this->om
            ->expects($this->once())
            ->method('flush')
        ;

        $this->doctrineTransactionManager = new DoctrineTransactionManager($this->om);
    }

    public function testSave()
    {
        $this->doctrineTransactionEventSubscriber = new DoctrineTransactionManager($this->om);

        $this->om
            ->expects($this->once())
            ->method('persist')
        ;
        $this->om
            ->expects($this->once())
            ->method('flush')
        ;
        $this->doctrineTransactionEventSubscriber->saveTransaction($this->transaction);
    }

    /**
     * @expectedException \IDCI\Bundle\PaymentBundle\Exception\NoTransactionFoundException
     */
    public function testNotRetrievedTransactionByUuid()
    {
        //Wrong id is passed to the method to throw an exception
        $this->doctrineTransactionManager->retrieveTransactionByUuid('wrong_transaction_id');
    }

    public function testRetrievedTransactionByUuid()
    {
        $transaction = $this->doctrineTransactionManager->retrieveTransactionByUuid('dummy_transaction_id');
        $this->assertInstanceOf(Transaction::class, $transaction);
    }
}
