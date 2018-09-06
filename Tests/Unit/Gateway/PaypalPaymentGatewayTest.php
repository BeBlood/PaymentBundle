<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Unit\Gateway;

use IDCI\Bundle\PaymentBundle\Gateway\PaypalPaymentGateway;
use IDCI\Bundle\PaymentBundle\Payment\PaymentStatus;
use PayPal\Api\Amount as PaypalAmount;
use PayPal\Api\Payment as PaypalPayment;
use PayPal\Api\Transaction as PaypalTransaction;
use Symfony\Component\HttpFoundation\Request;

class PaypalPaymentGatewayTest extends PaymentGatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new PaypalPaymentGateway($this->twig);

        $this->gateway = $this->getMockBuilder(PaypalPaymentGateway::class)
            ->setConstructorArgs([$this->twig])
            ->setMethods(['retrievePayment', 'executePayment'])
            ->getMock()
        ;

        $this->gateway
            ->method('retrievePayment')
            ->will($this->returnValue(
                (new PaypalPayment())
                    ->setTransactions([
                        (new PaypalTransaction())->setAmount(
                            (new PaypalAmount())
                                ->setCurrency('EUR')
                                ->setTotal(1000)
                        ),
                    ])
            ))
        ;

        $this->gateway
            ->method('executePayment')
            ->will($this->returnCallback(function ($paypalPayment, $execution) {
                if ('wrong_payer_id' === $execution->getPayerId()) {
                    return $paypalPayment->setState('failed');
                }

                return $paypalPayment->setState('approved');
            }))
        ;
    }

    public function testInitialize()
    {
        $this->paymentGatewayConfiguration
            ->set('client_id', 'dummy_client_id')
            ->set('client_secret', 'dummy_client_id')
            ->set('environment', 'dummy_environment')
            ->set('return_url', 'dummy_callback_url')
            ->set('callback_url', 'dummy_callback_url')
        ;

        $data = $this->gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);

        $this->assertEquals($this->paymentGatewayConfiguration->get('client_id'), $data['clientId']);
        $this->assertEquals($this->paymentGatewayConfiguration->get('callback_url'), $data['callbackUrl']);
        $this->assertEquals($this->paymentGatewayConfiguration->get('return_url'), $data['returnUrl']);
        $this->assertEquals($this->paymentGatewayConfiguration->get('environment'), $data['environment']);
        $this->assertEquals($this->transaction, $data['transaction']);
    }

    public function testBuildHTMLView()
    {
        $data = $this->gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);

        $htmlView = $this->twig->render('@IDCIPaymentBundle/Resources/views/Gateway/paypal.html.twig', [
            'initializationData' => $data,
        ]);

        $this->assertEquals(
            $this->gateway->buildHTMLView($this->paymentGatewayConfiguration, $this->transaction),
            $htmlView
        );
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testInvalidMethod()
    {
        $request = Request::create('dumy_uri', Request::METHOD_GET);

        $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
    }

    public function testUnauthorizedTransactionResult()
    {
        $request = Request::create('dumy_uri', Request::METHOD_POST, [
            'transactionID' => 'dummy_transaction_id',
            'payerID' => 'wrong_payer_id',
        ]);

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);

        $this->assertEquals('Transaction unauthorized', $gatewayResponse->getMessage());
        $this->assertEquals(PaymentStatus::STATUS_FAILED, $gatewayResponse->getStatus());
    }

    public function testApprovedTransactionResult()
    {
        $request = Request::create('dumy_uri', Request::METHOD_POST, [
            'transactionID' => 'dummy_transaction_id',
            'payerID' => 'dummy_payer_id',
        ]);

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);

        $this->assertEquals(PaymentStatus::STATUS_APPROVED, $gatewayResponse->getStatus());
    }

    public function testGetParameterNames()
    {
        $parameterNames = PaypalPaymentGateway::getParameterNames();

        $this->assertContains('client_id', $parameterNames);
        $this->assertContains('client_secret', $parameterNames);
        $this->assertContains('environment', $parameterNames);
    }
}
