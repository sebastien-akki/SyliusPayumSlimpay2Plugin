<?php

namespace Akki\SyliusPayumSlimpayPlugin\Action\Api;


use ArrayAccess;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Akki\SyliusPayumSlimpayPlugin\Constants\Constants;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\CheckoutIframe;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\CheckoutRedirect;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\UpdatePaymentMethodWithCheckout;
use Akki\SyliusPayumSlimpayPlugin\Util\ResourceSerializer;

class UpdatePaymentMethodWithCheckoutAction extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @param UpdatePaymentMethodWithCheckout $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model->validateNotEmpty(['subscriber_reference', 'mandate_reference']);

        $model['order'] = ResourceSerializer::serializeResource($this->api->updatePaymentMethodWithCheckout(
            $model['subscriber_reference'],
            $model['mandate_reference']
        ));

        if(null === $model['checkout_mode']) {
            $model['checkout_mode'] = $this->api->getDefaultCheckoutMode();
        }

        $model->validateNotEmpty(['checkout_mode']);

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
        return $request instanceof UpdatePaymentMethodWithCheckout &&
            $request->getModel() instanceof ArrayAccess;
    }
}