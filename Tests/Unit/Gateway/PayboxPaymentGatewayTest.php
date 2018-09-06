<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Unit\Gateway;

<<<<<<< HEAD
=======
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
>>>>>>> Update: Tests
use IDCI\Bundle\PaymentBundle\Gateway\PayboxPaymentGateway;
use IDCI\Bundle\PaymentBundle\Payment\PaymentStatus;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

class PayboxPaymentGatewayTest extends PaymentGatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->httpClient = $this->getMockBuilder(ClientInterface::class)
            ->getMock()
        ;

        $this->fixturesDir = sprintf('%s/../../Fixtures/PayboxPaymentGatewayTest', __DIR__);

        $response = new Response(
            200,
            ['content-type' => 'text/plain'],
            file_get_contents(sprintf('%s/key.pub', $this->fixturesDir)),
            1.1
        );

        $this->httpClient
            ->method('request')
            ->with('GET', 'dummy_public_key_url')
            ->willReturn($response)
        ;

        $this->gateway = new PayboxPaymentGateway(
            $this->twig,
            $this->httpClient,
            'dummy_server_host_name',
            sys_get_temp_dir(),
            'dummy_public_key_url'
        );

        $fileSystem = new Filesystem();
        $fileSystem->touch(sys_get_temp_dir().'/dummy_client_site.bin');
    }

    public function tearDown()
    {
        $filePath = sprintf('%s/dummy_client_site.bin', sys_get_temp_dir());

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function testInitialize()
    {
        $this->paymentGatewayConfiguration
            ->set('client_id', 'dummy_client_id')
            ->set('client_site', 'dummy_client_site')
            ->set('client_rang', 'dummy_client_rang')
            ->set('callback_url', 'dummy_callback_url')
            ->set('return_url', 'dummy_callback_url')
            ->set('environment', 'dummy_environment')
        ;
        $data = $this->gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);

        $this->assertEquals('https://dummy_server_host_name/cgi/MYchoix_pagepaiement.cgi', $data['url']);
    }

    public function testBuildHTMLView()
    {
        $this->paymentGatewayConfiguration
            ->set('client_id', 'dummy_client_id')
            ->set('client_site', 'dummy_client_site')
            ->set('client_rang', 'dummy_client_rang')
            ->set('callback_url', 'dummy_callback_url')
            ->set('return_url', 'dummy_callback_url')
        ;
        $data = $this->gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);

        $htmlView = $this->twig->render('@IDCIPaymentBundle/Resources/views/Gateway/paybox.html.twig', [
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

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testMissingFieldRequest()
    {
        $request = Request::create(
            'dummy_uri',
            Request::METHOD_POST
        );

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
    }

    /**
     * @expectedException \TypeError
     */
    public function testUnauthorizedTransactionRequestResponse()
    {
        $request = Request::create(
            'dummy_uri',
            Request::METHOD_POST,
            [
                'amount' => 'dummy_amount',
                'reference' => 'dummy_reference',
            ]
        );

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
    }

    public function testErrorTransactionRequestResponse()
    {
        $request = Request::create(
            'dummy_uri',
            Request::METHOD_POST,
            [
                'amount' => '1000',
                'reference' => 'dummy_reference',
                'error' => '00001',
            ]
        );

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals(PaymentStatus::STATUS_FAILED, $gatewayResponse->getStatus());
        $this->assertEquals('Transaction unauthorized', $gatewayResponse->getMessage());
    }

    public function testIntegrityErrorRequestResponse()
    {
        $request = Request::create(
            'dummy_uri',
            Request::METHOD_POST,
            [
                'amount' => '1000',
                'reference' => 'dummy_reference',
                'error' => '00000',
                'hash' => 'wrong_hash',
            ]
        );

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals(PaymentStatus::STATUS_FAILED, $gatewayResponse->getStatus());
        $this->assertEquals('Could not verify the integrity of paybox return response', $gatewayResponse->getMessage());
    }

    public function testApprovedTransactionRequestResponse()
    {
        $request = Request::create(
            'dummy_uri',
            Request::METHOD_POST,
            [
                'amount' => '1000',
                'reference' => 'dummy_reference',
                'error' => '00000',
                'hash' => 'VE4LyiEbuGLYsEElKTkXsB6PvSAtmWEekcfyn0C7J0XymhCKTe/53LzIfk8GFcTf5PpSOLrrSDKFZjejYA/JyFrEalHje7RCKZ+T2MM1r5Of3ajXeMnKyb8TINFPLH78s6M4EjCV6U+E9yQloUCeZcDkFqWklXm3qEhlT7AC2hg=',
            ]
        );

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals(PaymentStatus::STATUS_APPROVED, $gatewayResponse->getStatus());
    }

    public function testGetParameterNames()
    {
        $parameterNames = PayboxPaymentGateway::getParameterNames();

        $this->assertContains('client_id', $parameterNames);
        $this->assertContains('client_rang', $parameterNames);
        $this->assertContains('client_site', $parameterNames);
    }
}
