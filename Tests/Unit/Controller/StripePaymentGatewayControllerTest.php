<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Controller;

use GuzzleHttp\ClientInterface;
use IDCI\Bundle\PaymentBundle\Controller\StripePaymentGatewayController;
use IDCI\Bundle\PaymentBundle\Manager\PaymentManager;
use IDCI\Bundle\PaymentBundle\Model\PaymentGatewayConfiguration;
use IDCI\Bundle\PaymentBundle\Payment\PaymentContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class StripePaymentGatewayControllerTest extends TestCase
{
    public function setUp()
    {
        $this->paymentGatewayConfiguration = $this->getMockBuilder(PaymentGatewayConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->paymentContext = $this->getMockBuilder(PaymentContext::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->paymentContext
            ->method('getPaymentGatewayConfiguration')
            ->will($this->returnValue($this->paymentGatewayConfiguration))
        ;

        $this->paymentManager = $this->getMockBuilder(PaymentManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->paymentManager
            ->method('createPaymentContextByAlias')
            ->will($this->returnValue($this->paymentContext))
        ;

        $this->charge = $this->getMockBuilder(Stripe\StripeObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['__toArray'])
            ->getMock()
        ;

        $this->charge
            ->method('__toArray')
            ->will($this->returnValue([]))
        ;

        $this->httpClient = $this->getMockBuilder(ClientInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['send', 'sendAsync', 'request', 'requestAsync', 'getConfig', 'post'])
            ->getMock()
        ;

        $this->httpClient
            ->method('post')
            ->will($this->returnCallback(array($this, 'clientPostBehavior')))
        ;

        $this->stripePaymentGatewayController = $this->getMockBuilder(StripePaymentGatewayController::class)
            ->setConstructorArgs([$this->paymentManager, $this->httpClient])
            ->setMethods(['createCharge', 'redirect'])
            ->getMock()
        ;

        $this->stripePaymentGatewayController
            ->method('createCharge')
            ->will($this->returnCallback(array($this, 'createChargeBehavior')))
        ;

        $this->stripePaymentGatewayController
            ->method('redirect')
            ->will($this->returnCallback(function ($returnUrl) {
                return $returnUrl;
            }))
        ;
    }

    public function createChargeBehavior($request)
    {
        if ($request->request->has('error')) {
            throw new \Stripe\Error\Api($request->get('error'), 400, null, [
                'error' => $request->get('error'),
            ]);
        }

        return $this->charge;
    }

    public function clientPostBehavior($callbackUrl, $data)
    {
        if ('right_callback_url' !== $callbackUrl) {
            throw new \Exception('dummy_client_error');
        }

        return true;
    }

    public function testStripeErrorProxyRequest()
    {
        $request = Request::create('dummy_uri', Request::METHOD_POST, [
            'transactionId' => 'dummy_transaction_id',
            'amount' => 'dummy_amount',
            'currencyCode' => 'dummy_currency_code',
            'error' => 'dummy_stripe_error',
            'callbackUrl' => 'right_callback_url',
            'cancelUrl' => 'dummy_cancel_url',
            'returnUrl' => 'dummy_return_url',
        ]);

        $returnUrl = $this->stripePaymentGatewayController->proxyAction($request, 'dummy_configuration_alias');
        $this->assertEquals($returnUrl, 'dummy_cancel_url');
    }

    public function testClientErrorProxyRequest()
    {
        $request = Request::create('dummy_uri', Request::METHOD_POST, [
            'transactionId' => 'dummy_transaction_id',
            'amount' => 'dummy_amount',
            'currencyCode' => 'dummy_currency_code',
            'callbackUrl' => 'wrong_callback_url',
            'cancelUrl' => 'dummy_cancel_url',
            'returnUrl' => 'dummy_return_url',
        ]);

        $returnUrl = $this->stripePaymentGatewayController->proxyAction($request, 'dummy_configuration_alias');
        $this->assertEquals($returnUrl, 'dummy_cancel_url');
    }

    public function testSuccessfulProxyRequest()
    {
        $request = Request::create('dummy_uri', Request::METHOD_POST, [
            'transactionId' => 'dummy_transaction_id',
            'amount' => 'dummy_amount',
            'currencyCode' => 'dummy_currency_code',
            'callbackUrl' => 'right_callback_url',
            'cancelUrl' => 'dummy_cancel_url',
            'returnUrl' => 'dummy_return_url',
        ]);

        $returnUrl = $this->stripePaymentGatewayController->proxyAction($request, 'dummy_configuration_alias');
        $this->assertEquals($returnUrl, 'dummy_return_url');
    }
}
