<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Controller\Api;

use IDCI\Bundle\PaymentBundle\Controller\Api\ApiTransactionController;
use IDCI\Bundle\PaymentBundle\Exception\NoTransactionFoundException;
use IDCI\Bundle\PaymentBundle\Manager\TransactionManagerInterface;
use IDCI\Bundle\PaymentBundle\Payment\TransactionFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class PaymentGatewayControllerTest extends TestCase
{
    public function setUp()
    {
        $this->transaction = TransactionFactory::getInstance()->create([
            'gateway_configuration_alias' => 'dummy_gateway_configuration_alias',
            'item_id' => 'dummy_item_id',
            'customer_id' => 'dummy_customer_id',
            'customer_email' => 'dummy_customer_email',
            'amount' => 100,
            'currency_code' => 'EUR',
            'description' => 'dummy_description',
            'metadatas' => [],
        ]);

        $this->transactionManager = $this->getMockBuilder(TransactionManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->transactionManager
            ->method('retrieveTransactionByUuid')
            ->with($this->logicalOr(
                 $this->equalTo($this->transaction->getId()),
                 $this->equalTo('wrong_transaction_id')
             ))
            ->will($this->returnCallback(array($this, 'buildBehavior')))
        ;

        $this->apiTransactionController = new ApiTransactionController($this->transactionManager);
    }

    public function buildBehavior($id)
    {
        if ('wrong_transaction_id' === $id) {
            throw new NoTransactionFoundException($id);
        }

        return $this->transaction;
    }

    /**
     * @expectedException \IDCI\Bundle\PaymentBundle\Exception\NoTransactionFoundException
     */
    public function testShowUndefinedTransaction()
    {
        $transaction = $this->apiTransactionController->show('wrong_transaction_id');
    }

    public function testShowTransaction()
    {
        $transaction = $this->apiTransactionController->show($this->transaction->getId());
        $this->assertEquals(new JsonResponse([
            'id' => $this->transaction->getId(),
            'amount' => $this->transaction->getAmount(),
            'currencyCode' => $this->transaction->getCurrencyCode(),
            'status' => $this->transaction->getStatus(),
            'createdAt' => $this->transaction->getCreatedAt(),
            'updatedAt' => $this->transaction->getUpdatedAt(),
        ]), $transaction);
    }
}
