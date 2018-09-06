<?php

namespace IDCI\Bundle\PaymentBundle\Controller;

use GuzzleHttp\ClientInterface;
use IDCI\Bundle\PaymentBundle\Manager\PaymentManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/stripe-payment-gateway")
 */
class StripePaymentGatewayController extends Controller
{
    /**
     * @var PaymentManager
     */
    private $paymentManager;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    public function __construct(PaymentManager $paymentManager, ClientInterface $httpClient)
    {
        $this->paymentManager = $paymentManager;
        $this->httpClient = $httpClient;
    }

    protected function createCharge(Request $request)
    {
        return Stripe\Charge::create([
            'amount' => $request->get('amount'),
            'currency' => $request->get('currencyCode'),
            'source' => $request->get('stripeToken'),
        ]);
    }

    /**
     * @Route("/proxy/{configuration_alias}")
     * @Method({"POST"})
     */
    public function proxyAction(Request $request, $configuration_alias)
    {
        $paymentContext = $this
            ->paymentManager
            ->createPaymentContextByAlias($configuration_alias)
        ;

        $paymentGatewayConfiguration = $paymentContext->getPaymentGatewayConfiguration();

        $data = [
            'transactionId' => $request->get('transactionId'),
            'amount' => $request->get('amount'),
            'currencyCode' => $request->get('currencyCode'),
        ];

        try {
            Stripe\Stripe::setApiKey($paymentGatewayConfiguration->get('secret_key'));

            $charge = $this->createCharge($request);

            $data['raw'] = $charge->__toArray();
        } catch (Stripe\Error\Base $e) {
            $data['error'] = $e->getJsonBody()['error'];
        }

        try {
            $response = $this->httpClient->post($request->get('callbackUrl'), [
                'form_params' => $data,
            ]);
        } catch (\Exception $e) {
            return $this->redirect($request->get('cancelUrl'));
        }

        if (isset($data['error'])) {
            return $this->redirect($request->get('cancelUrl'));
        }

        return $this->redirect($request->get('returnUrl'));
    }
}
