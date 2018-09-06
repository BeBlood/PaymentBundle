<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Gateway;

use IDCI\Bundle\PaymentBundle\Gateway\PaymentGatewayInterface;
use IDCI\Bundle\PaymentBundle\Gateway\PaymentGatewayRegistry;
use PHPUnit\Framework\TestCase;

class PaymentGatewayRegistryTest extends TestCase
{
    public function setUp()
    {
        $this->paymentGatewayRegistry = new PaymentGatewayRegistry();

        $this->paymentGateway = $this->getMockBuilder(PaymentGatewayInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    public function testSetPaymentGateway()
    {
        $this->assertFalse($this->paymentGatewayRegistry->has('dummy_payment_gateway_alias'));

        $this->paymentGatewayRegistry->set('dummy_payment_gateway_alias', $this->paymentGateway);

        $this->assertTrue($this->paymentGatewayRegistry->has('dummy_payment_gateway_alias'));
        $this->assertInstanceOf(
            PaymentGatewayInterface::class,
            $this->paymentGatewayRegistry->get('dummy_payment_gateway_alias')
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetPaymentGatewayWithUndefinedPaymentGatewayAlias()
    {
        $this->paymentGatewayRegistry->get('wrong_payment_gateway_alias');
    }

    public function testGetAllPaymentGateways()
    {
        $paymentGateways = $this->paymentGatewayRegistry->getAll();

        $this->paymentGatewayRegistry->set('new_payment_gateway_alias', $this->paymentGateway);

        $this->assertEquals(array_merge(
            $paymentGateways,
            ['new_payment_gateway_alias' => $this->paymentGateway]
        ), $this->paymentGatewayRegistry->getAll());
    }
}
