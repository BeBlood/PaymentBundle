# PaymentBundle

This Symfony bundle provide help for integrating payments solutions by the normalization of payment process thanks to gateways. Each used gateway must have a configuration to set its parameters.

Example controller :

```php
<?php

$paymentContext = $this->paymentManager->createPaymentContextByAlias('stripe_test'); // raw alias

$payment = $paymentContext->createPayment([
    'item_id' => 5,
    'amount' => 500,
    'currency_code' => 'EUR',
]);

return $this->render('@IDCIPaymentBundle/Resources/views/payment.html.twig', [
    'view' => $paymentContext->buildHTMLView(),
]);
```

A list of [commands](#command) is provided by this bundle to manage gateway configurations & transactions.

Installation
------------

Add dependency in your ```composer.json``` file:

```json
"require": {
    ...,
    "idci/payment-bundle": "dev-master",
}
```

Install this new dependency in your application using composer:

```bash
$ composer update
```

Enable bundle in your application kernel :

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new IDCI\Bundle\PaymentBundle\IDCIPaymentBundle(),
    );
}
```

Add this to your ```config.yml``` file

```yaml
# app/config/config.yml
imports:
    - {resource: '@IDCIPaymentBundle/Resources/config/config.yml'}

# Enable monolog logging using event subscriber plugged on transaction state changes
idci_payment:
    enabled_logger_subscriber: true

# (optional) if you want to customize the payment logger, by defaults, it will output into main handler
monolog:
    handlers:
        payment_log:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            channels: ['payment']

```

And these parameters in ```parameters.yml(.dist)``` file
```yaml
# app/config/parameters.yml(.dist)
idci_payment.mercanet.server_host_name: 'payment-webinit.simu.mercanet.bnpparibas.net' # prod: payment-webinit.mercanet.bnpparibas.net
idci_payment.sogenactif.server_host_name: 'payment-webinit.simu.sips-atos.com' # prod: payment-webinit-ws.sogenactif.com
idci_payment.paybox.server_host_name: 'preprod-tpeweb.paybox.com' # prod: tpeweb.paybox.com
idci_payment.paybox.key_path: /var/www/html/vendor/idci/payment-bundle/Resources/paybox/keys
idci_payment.paybox.public_key_url: 'http://www1.paybox.com/wp-content/uploads/2014/03/pubkey.pem'
```

Install routes in your ```routing.yml``` file:
```yaml
# app/config/routing.yml
idci_payment:
    resource: '@IDCIPaymentBundle/Resources/config/routing.yml'
    prefix:   /

idci_payment_api:
    resource: '@IDCIPaymentBundle/Resources/config/routing_api.yml'
    prefix:   /api
```

These tutorials may help you to personalize yourself this bundle:

- [Create a new payment gateway](./Resources/docs/create-your-payment-gateway.md): incorporate new payment method to this bundle
- [Create your own transaction manager](./Resources/docs/create-your-transaction-manager.md) : help you to retrieve transaction from other stockages methods (default: Doctrine)
- [Use this bundle with step bundle](./Resources/docs/use-step-bundle.md): simple configuration to make this bundle work with step bundle
- [Create your own event subscriber](./Resources/docs/create-your-event-subscriber.md): learn how to work with transaction event

Supported Gateways
------------------

* [Stripe](./Gateway/StripePaymentGateway.php) ([example](./Resources/docs/example/stripe.md))
* [Paypal](./Gateway/PaypalPaymentGateway.php)
([example](./Resources/docs/example/paypal.md))
* [Paybox](./Gateway/PayboxPaymentGateway.php)
([example](./Resources/docs/example/paybox.md))
* [Monetico](./Gateway/MoneticoPaymentGateway.php) (unfinished)
* [Ogone](./Gateway/OgonePaymentGateway.php) (unfinished)
* [PayPlug](./Gateway/PayPlugPaymentGateway.php)
([example](./Resources/docs/example/payplug.md))
* [Atos Sips Bin](./Gateway/AtosSipsBinPaymentGateway.php)
    * Scellius ([example](./Resources/docs/example/scellius-bin.md))
    * Sogenactif ([example](./Resources/docs/example/sogenactif-bin.md))
* [Atos Sips POST](./Gateway/AtosSipsPostPaymentGateway.php)
    * Mercanet ([example](./Resources/docs/example/mercanet-post.md))
    * Sogenactif ([example](./Resources/docs/example/sogenactif-post.md))
* [Atos Sips JSON](./Gateway/AtosSipsJsonPaymentGateway.php)
    * Mercanet ([example](./Resources/docs/example/mercanet-json.md))
    * Sogenactif ([example](./Resources/docs/example/sogenactif-json.md))

For testing purpose:
- [Parameters](./Resources/docs/test-parameters.md)
- [Cards](./Resources/docs/test-cards.md)

Command
-------

##### PaymentGatewayConfiguration

```bash
# To create a PaymentGatewayConfiguration
$ php bin/console app:payment-gateway-configuration:create

# To show the list of PaymentGatewayConfiguration
$ php bin/console app:payment-gateway-configuration:list

# To update a PaymentGatewayConfiguration
$ php bin/console app:payment-gateway-configuration:update

# To delete a PaymentGatewayConfiguration
$ php bin/console app:payment-gateway-configuration:delete
```

##### Transaction

```bash
# Remove all the aborted transaction created 1 day ago
$ php bin/console app:transaction:clean
```

Tests
-----

Add test routing :

```yaml
# app/config/routing_dev.yml

_test_payment:
    resource: '@IDCIPaymentBundle/Resources/config/routing.yml'
    prefix:   /_test/

```

You can now test gateways on ```/_test/payment-gateway/select``` (be sure to have created one or more gateway configuration)

Resources
---------

##### UML Diagram

![UML Diagram](./Resources/docs/uml-schema.png)
