<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Unit\Gateway;

use IDCI\Bundle\PaymentBundle\Gateway\PayPlugPaymentGateway;
use IDCI\Bundle\PaymentBundle\Payment\PaymentStatus;
use Symfony\Component\HttpFoundation\Request;

class PayPlugPaymentGatewayTest extends PaymentGatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = $this->getMockBuilder(PayPlugPaymentGateway::class)
            ->setConstructorArgs([$this->twig])
            ->setMethods(['createPayment'])
            ->getMock()
        ;

        $this->gateway
            ->method('createPayment')
            ->will($this->returnValue([
                'hosted_payment' => [
                    'payment_url' => 'dummy_payment_url',
                ],
            ]))
        ;
    }

    /**
     * @expectedException \Payplug\Exception\ConfigurationException
     */
    public function testUnexpectedPayplugTokenFormat()
    {
        $this->paymentGatewayConfiguration->set('secret_key', 1234567890);

        $this->gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);
    }

    public function testBuildHTMLView()
    {
        $this->paymentGatewayConfiguration->set('secret_key', 'dummy_secret_key');
        $data = $this->gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);

        $htmlView = $this->twig->render('@IDCIPaymentBundle/Resources/views/Gateway/payplug.html.twig', [
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

    public function testGetResponseEmptyPostDataRequest()
    {
        $request = Request::create(
            'dummy_uri',
            Request::METHOD_POST
        );

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals('The request do not contains required post data', $gatewayResponse->getMessage());
    }

    public function testUnauthorizedTransactionResponse()
    {
        $request = Request::create(
            'dummy_uri',
            Request::METHOD_POST,
            [
                'metadata' => [
                    'transaction_id' => 'dummy_transaction_id',
                ],
                'amount' => 20,
                'currency' => 'EUR',
                'is_paid' => false,
            ]
        );

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals('Transaction unauthorized', $gatewayResponse->getMessage());
    }

    public function testGetResponseApproved()
    {
        $request = Request::create(
            'dummy_uri',
            Request::METHOD_POST,
            [
                'metadata' => [
                    'transaction_id' => 'dummy_transaction_id',
                ],
                'amount' => 20,
                'currency' => 'EUR',
                'is_paid' => true,
            ]
        );

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals(PaymentStatus::STATUS_APPROVED, $gatewayResponse->getStatus());
    }

    public function testGetParameterNames()
    {
        $parameterNames = PayPlugPaymentGateway::getParameterNames();

        $this->assertContains('secret_key', $parameterNames);
    }
}
