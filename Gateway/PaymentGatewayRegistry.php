<?php

namespace IDCI\Bundle\PaymentBundle\Gateway;

class PaymentGatewayRegistry implements PaymentGatewayRegistryInterface
{
    /**
     * @var array
     */
    private $paymentGateways;

    public function has(string $alias): bool
    {
        return isset($this->paymentGateways[$alias]);
    }

    public function set(string $alias, PaymentGatewayInterface $paymentGateway): PaymentGatewayRegistryInterface
    {
        $this->paymentGateways[$alias] = $paymentGateway;

        return $this;
    }

    public function get(string $alias): PaymentGatewayInterface
    {
        if (!isset($this->paymentGateways[$alias])) {
            throw new \InvalidArgumentException(sprintf('could not load payment gateway %s', $alias));
        }

        return $this->paymentGateways[$alias];
    }

    public function getAll(): array
    {
        if (null === $this->paymentGateways) {
            $this->paymentGateways = [];
        }

        return $this->paymentGateways;
    }
}
