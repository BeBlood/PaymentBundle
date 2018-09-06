<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Model;

use IDCI\Bundle\PaymentBundle\Model\PaymentGatewayConfiguration;
use PHPUnit\Framework\TestCase;

class PaymentGatewayConfigurationTest extends TestCase
{
    public function setUp()
    {
        $this->paymentGatewayConfiguration = (new PaymentGatewayConfiguration())
            ->setAlias('dummy_alias')
            ->setGatewayName('dummy_gateway_name')
            ->setEnabled(true)
            ->setParameters([
                'parameter_one' => 'dummy_first_value',
            ])
        ;
    }

    public function testCastToString()
    {
        $this->assertEquals('dummy_alias', (string) $this->paymentGatewayConfiguration);
    }

    public function testIfAllGettersAreWorking()
    {
        $this->assertEquals(null, $this->paymentGatewayConfiguration->getId());
        $this->assertEquals('dummy_alias', $this->paymentGatewayConfiguration->getAlias());
        $this->assertEquals('dummy_gateway_name', $this->paymentGatewayConfiguration->getGatewayName());
        $this->assertTrue($this->paymentGatewayConfiguration->isEnabled());
        $this->assertEquals([
            'parameter_one' => 'dummy_first_value',
        ], $this->paymentGatewayConfiguration->getParameters());
        $this->assertEquals(1, count($this->paymentGatewayConfiguration->getParameters()));
    }
}
