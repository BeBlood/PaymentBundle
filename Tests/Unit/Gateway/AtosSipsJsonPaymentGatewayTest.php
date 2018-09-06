<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Unit\Gateway;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use IDCI\Bundle\PaymentBundle\Gateway\AtosSipsJsonPaymentGateway;
use IDCI\Bundle\PaymentBundle\Gateway\StatusCode\AtosSipsStatusCode;
use IDCI\Bundle\PaymentBundle\Payment\PaymentStatus;
use Symfony\Component\HttpFoundation\Request;

class AtosSipsJsonPaymentGatewayTest extends PaymentGatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->httpClient = $this->getMockBuilder(ClientInterface::class)
            ->getMock()
        ;

        $response = new Response(
            200,
            ['content-type' => 'text/json'],
            '{"redirectionStatusCode":"00","data":"dummy_data"}',
            1.1
        );

        $this->httpClient
            ->method('request')
            ->willReturn($response)
        ;

        $this->paymentGatewayConfiguration
            ->set('version', 'dummy_version')
            ->set('secret', 'dummy_secret')
            ->set('merchant_id', 'dummy_merchant_id')
            ->set('capture_mode', 'dummy_capture_mode')
            ->set('capture_day', 'dummy_capture_day')
            ->set('order_channel', 'dummy_order_channel')
            ->set('interface_version', 'dummy_interface_version')
            ->set('callback_url', 'dummy_callback_url')
            ->set('return_url', 'dummy_return_url')
        ;

        $this->gateway = new AtosSipsJsonPaymentGateway(
            $this->twig,
            $this->httpClient,
            'dummy_server_host_name'
        );
    }

    public function testSuccessfulInitialize()
    {
        $data = $this->gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);

        $this->assertEquals('dummy_data', $data['data']);
    }

    /**
     * @expectedException \Exception
     */
    public function testInitializeEmptyDataResponse()
    {
        $response = new Response(200, ['content-type' => 'text/json'], '{}', 1.1);

        $httpClient = $this->getMockBuilder(ClientInterface::class)
            ->getMock()
        ;

        $httpClient
            ->method('request')
            ->willReturn($response)
        ;

        $gateway = new AtosSipsJsonPaymentGateway(
            $this->twig,
            $httpClient,
            'dummy_server_host_name'
        );

        $gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);
    }

    /**
     * @expectedException \IDCI\Bundle\PaymentBundle\Exception\UnexpectedAtosSipsResponseCodeException
     */
    public function testInitializeUnexpectedResponseCode()
    {
        $response = new Response(200, ['content-type' => 'text/json'], '{"redirectionStatusCode":"12"}', 1.1);

        $httpClient = $this->getMockBuilder(ClientInterface::class)
            ->getMock()
        ;

        $httpClient
            ->method('request')
            ->willReturn($response)
        ;

        $gateway = new AtosSipsJsonPaymentGateway(
            $this->twig,
            $httpClient,
            'dummy_server_host_name'
        );

        $gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);
    }

    public function testBuildHTMLView()
    {
        $data = $this->gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);

        $htmlView = $this->twig->render('@IDCIPaymentBundle/Resources/views/Gateway/atos_sips_json.html.twig', [
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
        $request = Request::create('dummy_uri', Request::METHOD_GET);

        $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
    }

    public function testRequestNoData()
    {
        $request = Request::create('dummy_uri', Request::METHOD_POST);

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals('The request do not contains "Data"', $gatewayResponse->getMessage());
    }

    public function testRequestSealCheckFail()
    {
        $request = Request::create(
            'dummy_uri',
            Request::METHOD_POST,
            [
                'Data' => 'dummy_data',
            ]
        );

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals('Seal check failed', $gatewayResponse->getMessage());
    }

    public function testRequestResponseCodeError()
    {
        $availableStatusCodes = array_keys(AtosSipsStatusCode::STATUS);
        foreach ($availableStatusCodes as $testedStatusCode) {
            $data = sprintf('dummy_data=data|responseCode=%s|transactionReference=dummy_transaction_reference|amount=20|currencyCode=978', $testedStatusCode);
            $request = Request::create(
                'dummy_uri',
                Request::METHOD_POST,
                [
                    'Data' => $data,
                ]
            );
            $request->request->set('Seal', hash('sha256', $data.$this->paymentGatewayConfiguration->get('secret')));

            $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
            $this->assertEquals(AtosSipsStatusCode::getStatusMessage($testedStatusCode), $gatewayResponse->getMessage());
        }
    }

    public function testCancelRequestResponseCode()
    {
        $data = 'dummy_data=data|responseCode=17|transactionReference=dummy_transaction_reference|amount=20|currencyCode=978';
        $request = Request::create(
            'dummy_uri',
            Request::METHOD_POST,
            [
                'Data' => $data,
            ]
        );
        $request->request->set('Seal', hash('sha256', $data.$this->paymentGatewayConfiguration->get('secret')));

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals(PaymentStatus::STATUS_CANCELED, $gatewayResponse->getStatus());
    }

    public function testUnauthorizedTransactionRequestResponse()
    {
        $data = 'dummy_data=data|responseCode=00|transactionReference=dummy_transaction_reference|amount=20|currencyCode=978|holderAuthentStatus=wrong_holder_authent_status';
        $request = Request::create(
            'dummy_uri',
            Request::METHOD_POST,
            [
                'Data' => $data,
            ]
        );
        $request->request->set('Seal', hash('sha256', $data.$this->paymentGatewayConfiguration->get('secret')));

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals('Transaction unauthorized', $gatewayResponse->getMessage());
    }

    public function testSuccessRequestResponse()
    {
        $data = 'dummy_data=data|responseCode=00|transactionReference=dummy_transaction_reference|amount=20|currencyCode=978|holderAuthentStatus=SUCCESS';
        $request = Request::create(
            'dummy_uri',
            Request::METHOD_POST,
            [
                'Data' => $data,
            ]
        );
        $request->request->set('Seal', hash('sha256', $data.$this->paymentGatewayConfiguration->get('secret')));

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals(PaymentStatus::STATUS_APPROVED, $gatewayResponse->getStatus());
    }

    public function testGetParameterNames()
    {
        $parameterNames = AtosSipsJsonPaymentGateway::getParameterNames();

        $this->assertContains('version', $parameterNames);
        $this->assertContains('secret', $parameterNames);
        $this->assertContains('merchant_id', $parameterNames);
        $this->assertContains('capture_mode', $parameterNames);
        $this->assertContains('capture_day', $parameterNames);
        $this->assertContains('order_channel', $parameterNames);
        $this->assertContains('interface_version', $parameterNames);
    }
}
