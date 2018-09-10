<?php

namespace IDCI\Bundle\PaymentBundle\Tests\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use IDCI\Bundle\PaymentBundle\Entity\PaymentGatewayConfiguration;
use IDCI\Bundle\PaymentBundle\Gateway\PaymentGatewayRegistryInterface;
use IDCI\Bundle\PaymentBundle\Manager\PaymentManager;
use IDCI\Bundle\PaymentBundle\Manager\TransactionManagerInterface;
use IDCI\Bundle\PaymentBundle\Payment\PaymentContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class PaymentManagerTest extends TestCase
{
    /**
     * @var PaymentManager
     */
    private $paymentManager;

    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var PaymentGatewayRegistryInterface
     */
    private $paymentGatewayRegistry;

    /**
     * @var TransactionManagerInterface
     */
    private $transactionManager;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    private $paymentGatewayConfigurationRepository;

    public function setUp()
    {
        $this->transactionManager = $this->getMockBuilder(TransactionManagerInterface::class)
            ->getMock()
        ;

        $this->dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->paymentGatewayConfiguration = $this->getMockBuilder(PaymentGatewayConfiguration::class)
            ->getMock()
        ;

        $this->paymentGatewayConfiguration
            ->method('getGatewayName')
            ->willReturn('dummy_gateway_name')
        ;

        $this->paymentGatewayConfiguration
            ->method('getAlias')
            ->willReturn('dummy_gateway_alias')
        ;

        $this->paymentGatewayConfigurationRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->paymentGatewayConfigurationRepository
            ->method('findOneBy')
            ->will($this->returnValueMap([
                [['alias' => 'wrong_payment_gateway_configuration_alias'], null, null],
                [['alias' => 'dummy_gateway_alias'], null, $this->paymentGatewayConfiguration],
            ]))
        ;

        $this->paymentGatewayConfigurationRepository
            ->method('findAll')
            ->will($this->returnValue([
                $this->paymentGatewayConfiguration,
            ]))
        ;

        $this->paymentGatewayRegistry = $this->getMockBuilder(PaymentGatewayRegistryInterface::class)
            ->getMock()
        ;

        $this->om = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->om
            ->method('getRepository')
            ->with(PaymentGatewayConfiguration::class)
            ->willReturn($this->paymentGatewayConfigurationRepository)
        ;

        $this->paymentCategayConfigurations = [
            'dummy_gateway_alias' => [
                'gateway_name' => 'paypal',
                'enabled' => true,
                'parameters' => [
                    'client_id' => 'dummy_client_id',
                ],
            ],
        ];

        $this->paymentManager = new PaymentManager(
            $this->om,
            $this->paymentGatewayRegistry,
            $this->transactionManager,
            $this->dispatcher,
            $this->paymentCategayConfigurations = []
        );
    }

    public function testGetAllPaymentGatewayConfigurationFromDoctrine()
    {
        $paymentGatewayConfigurations = $this->paymentManager->getAllPaymentGatewayConfigurationFromDoctrine();

        $this->assertEquals(
            $this->paymentGatewayConfiguration,
            $paymentGatewayConfigurations[$this->paymentGatewayConfiguration->getAlias()]
        );
    }

    /**
     * @expectedException \IDCI\Bundle\PaymentBundle\Exception\NoPaymentGatewayConfigurationFoundException
     */
    public function testNotCreatedPaymentContextByAlias()
    {
        //Wrong alias is passed to the method to throw an exception
        $this->paymentManager->createPaymentContextByAlias('wrong_payment_gateway_configuration_alias');
    }

    public function testCreatedPaymentContextByAlias()
    {
        $paymentContext = $this->paymentManager->createPaymentContextByAlias('dummy_gateway_alias');

        $this->assertInstanceOf(PaymentContext::class, $paymentContext);
        $this->assertEquals($this->dispatcher, $paymentContext->dispatcher);
        $this->assertEquals($this->paymentGatewayConfiguration, $paymentContext->getPaymentGatewayConfiguration());
    }
}
