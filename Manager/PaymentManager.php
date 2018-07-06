<?php

namespace IDCI\Bundle\PaymentBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use IDCI\Bundle\PaymentBundle\Entity\PaymentGatewayConfiguration;
use IDCI\Bundle\PaymentBundle\Exception\NoPaymentGatewayConfigurationFoundException;
use IDCI\Bundle\PaymentBundle\Gateway\PaymentGatewayRegistryInterface;
use IDCI\Bundle\PaymentBundle\Payment\PaymentContext;
use IDCI\Bundle\PaymentBundle\Payment\PaymentContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class PaymentManager
{
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

    public function __construct(
        ObjectManager $om,
        PaymentGatewayRegistryInterface $paymentGatewayRegistry,
        TransactionManagerInterface $transactionManager,
        EventDispatcher $dispatcher
    ) {
        $this->om = $om;
        $this->dispatcher = $dispatcher;
        $this->paymentGatewayRegistry = $paymentGatewayRegistry;
        $this->transactionManager = $transactionManager;
    }

    public function getAllPaymentGatewayConfiguration(): array
    {
        return $this
            ->om
            ->getRepository(PaymentGatewayConfiguration::class)
            ->findAll()
        ;
    }

    public function createPaymentContextByAlias(string $alias): PaymentContextInterface
    {
        $paymentGatewayConfiguration = $this
            ->om
            ->getRepository(PaymentGatewayConfiguration::class)
            ->findOneBy(['alias' => $alias])
        ;

        if (null === $paymentGatewayConfiguration) {
            throw new NoPaymentGatewayConfigurationFoundException();
        }

        return new PaymentContext(
            $this->dispatcher,
            $paymentGatewayConfiguration,
            $this->paymentGatewayRegistry->get($paymentGatewayConfiguration->getGatewayName()),
            $this->transactionManager
        );
    }
}