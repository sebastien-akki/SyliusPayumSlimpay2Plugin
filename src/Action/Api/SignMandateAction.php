<?php

namespace Akki\SyliusPayumSlimpayPlugin\Action\Api;

use ArrayAccess;
use Exception;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Akki\SyliusPayumSlimpayPlugin\Constants\Constants;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\CheckoutIframe;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\CheckoutRedirect;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\SignMandate;
use Akki\SyliusPayumSlimpayPlugin\Util\ResourceSerializer;

class SignMandateAction extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @param SignMandate $request
     * @throws Exception
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (!in_array($model['payment_scheme'], Constants::getSupportedPaymentShemas())) {
            throw new LogicException('Payment Schema not set or not supported');
        }

        if (Constants::PAYMENT_SCHEME_CARD == $model['payment_scheme']) {
            throw new LogicException('Mandate signing is not available for Card scheme');
        }

        $model->validateNotEmpty([
            'subscriber_reference',
            'first_name',
            'last_name',
            'address1',
            'city',
            'zip',
            'country'
        ]);

        if(null === $model['checkout_mode']) {
            $model['checkout_mode'] = $this->api->getDefaultCheckoutMode();
        }

        $model->validateNotEmpty(['checkout_mode']);

        $model['order'] = ResourceSerializer::serializeResource(
            $this->api->signMandate($model['subscriber_reference'],
            $model['payment_scheme'],
            [
                'givenName' => $model['first_name'],
                'familyName' => $model['last_name'],
                'email' => $model['email'],
                'telephone' => $model['phone'],
                'companyName' => $model['company'],
                'organizationName' => $model['organization'],
                'billingAddress' => [
                    'street1' => $model['address1'],
                    'street2' => $model['address2'],
                    'city' => $model['city'],
                    'postalCode' => $model['zip'],
                    'country' => $model['country']
                ]
            ],
                $model['return_url'],
                $model['cancel_url'],
                $model['mandate_reference'])
        );

        if(Constants::CHECKOUT_MODE_REDIRECT == $model['checkout_mode']) {
            $this->gateway->execute(new CheckoutRedirect($model));
        } elseif (in_array(
            $model['checkout_mode'],
            [Constants::CHECKOUT_MODE_IFRAME_EMBADDED, Constants::CHECKOUT_MODE_IFRAME_POPIN]
        )) {
            $this->gateway->execute(new CheckoutIframe($model));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof SignMandate &&
            $request->getModel() instanceof ArrayAccess;
    }
}