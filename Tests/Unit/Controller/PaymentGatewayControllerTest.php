<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Controller;

use IDCI\Bundle\PaymentBundle\Controller\PaymentGatewayController;
use IDCI\Bundle\PaymentBundle\Entity\Transaction;
use IDCI\Bundle\PaymentBundle\Manager\PaymentManager;
use IDCI\Bundle\PaymentBundle\Payment\PaymentContextInterface;
use IDCI\Bundle\PaymentBundle\Payment\PaymentStatus;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PaymentGatewayControllerTest extends TestCase
{
    public function setUp()
    {
        $this->transaction = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->transaction
            ->method('getStatus')
            ->willReturn(PaymentStatus::STATUS_APPROVED)
        ;

        $paymentContext = $this->getMockBuilder(PaymentContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $paymentContext
            ->method('handleGatewayCallback')
            ->willReturn($this->transaction)
        ;

        $this->paymentManager = $this->getMockBuilder(PaymentManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->paymentManager
            ->method('createPaymentContextByAlias')
            ->willReturn($paymentContext)
        ;

        $this->dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->paymentGatewayController = new PaymentGatewayController(
            $this->paymentManager
        );

        $container = $this->getMockBuilder(ContainerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $service = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $container
            ->method('get')
            ->with($this->equalTo('monolog.logger.payment'))
            ->willReturn($service)
        ;

        $this->paymentGatewayController
            ->setContainer($container)
        ;
    }

    public function testCallbackAction()
    {
        $request = Request::create('dummy_uri', Request::METHOD_POST, [
            'transaction_uuid' => 'dummy_transaction_uuid',
        ]);

        $transaction = $this->paymentGatewayController->callbackAction($request, $this->dispatcher, 'dummy_configuration_alias');
        $this->assertEquals(new JsonResponse($this->transaction->toArray()), $transaction);
    }
}
