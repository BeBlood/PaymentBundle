<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Unit\Gateway;

use IDCI\Bundle\PaymentBundle\Gateway\AtosSipsBinPaymentGateway;
use IDCI\Bundle\PaymentBundle\Payment\PaymentStatus;
use Symfony\Component\HttpFoundation\Request;

class AtosSipsBinPaymentGatewayTest extends PaymentGatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->fixturesDir = sprintf('%s/../../Fixtures/AtosSipsBinPaymentGatewayTest', __DIR__);

        $this->gateway = new AtosSipsBinPaymentGateway(
            $this->twig,
            sprintf('%s/param/sogenactif/pathfile.sogenactif', $this->fixturesDir),
            sprintf('%s/static/request', $this->fixturesDir),
            sprintf('%s/static/response', $this->fixturesDir)
        );

        $this->paymentGatewayConfiguration
            ->set('merchant_id', '014213245611111')
            ->set('capture_mode', 'AUTHOR_CAPTURE')
            ->set('capture_day', '0')
            ->set('callback_url', 'dummy_callback_url')
            ->set('return_url', 'dummy_return_url')
        ;
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testEmptyDataProcessResponseOnInitialize()
    {
        $gateway = new AtosSipsBinPaymentGateway(
            $this->twig,
            'wrong_pathfile',
            'wrong_request_path',
            'wrong_response_path'
        );

        $gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testErrorDataProcessResponseOnInitialize()
    {
        $this->paymentGatewayConfiguration
            ->set('capture_day', 'longest_dummy_capture_day')
        ;

        $this->gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);
    }

    public function testSuccessfulInitialize()
    {
        $data = $this->gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);

        $this->assertEquals('<FORM', substr($data['form'], 0, 5));
    }

    public function testBuildHTMLView()
    {
        $data = $this->gateway->initialize($this->paymentGatewayConfiguration, $this->transaction);

        $htmlView = $this->twig->render('@IDCIPaymentBundle/Resources/views/Gateway/atos_sips_bin.html.twig', [
            'initializationData' => $data,
        ]);

        $this->assertEquals('<FORM', substr($htmlView, 0, 5));
        $this->assertEquals(
            substr($this->gateway->buildHTMLView($this->paymentGatewayConfiguration, $this->transaction), 0, 5),
            substr($htmlView, 0, 5)
        );
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testInvalidMethodGatewayResponse()
    {
        $request = Request::create('dummy_uri', Request::METHOD_GET);

        $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
    }

    public function testEmptyDataGatewayResponse()
    {
        $request = Request::create('dummy_uri', Request::METHOD_POST);

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals('The request do not contains "DATA"', $gatewayResponse->getMessage());
    }

    public function testInvalidResponseCodeGatewayResponse()
    {
        $request = Request::create('dummy_uri', Request::METHOD_POST, [
            'DATA' => file_get_contents(sprintf('%s/DATA/failed', $this->fixturesDir)),
        ]);

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals('Temporary problem with the payment server', $gatewayResponse->getMessage());
        $this->assertEquals(PaymentStatus::STATUS_FAILED, $gatewayResponse->getStatus());
    }

    public function testCanceledResponseCodeGatewayResponse()
    {
        $request = Request::create('dummy_uri', Request::METHOD_POST, [
            'DATA' => file_get_contents(sprintf('%s/DATA/canceled', $this->fixturesDir)),
        ]);

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals('Cancellation of the buyer', $gatewayResponse->getMessage());
        $this->assertEquals(PaymentStatus::STATUS_CANCELED, $gatewayResponse->getStatus());
    }

    public function testAprrovedTransactionGatewayResponse()
    {
        $request = Request::create('dummy_uri', Request::METHOD_POST, [
            'DATA' => file_get_contents(sprintf('%s/DATA/approved', $this->fixturesDir)),
        ]);

        $gatewayResponse = $this->gateway->getResponse($request, $this->paymentGatewayConfiguration);
        $this->assertEquals(PaymentStatus::STATUS_APPROVED, $gatewayResponse->getStatus());
    }

    public function testGetParameterNames()
    {
        $parameterNames = AtosSipsBinPaymentGateway::getParameterNames();

        $this->assertContains('merchant_id', $parameterNames);
        $this->assertContains('capture_mode', $parameterNames);
        $this->assertContains('capture_day', $parameterNames);
    }
}
