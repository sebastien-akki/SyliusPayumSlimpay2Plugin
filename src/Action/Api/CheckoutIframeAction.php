<?php

namespace Akki\SyliusPayumSlimpayPlugin\Action\Api;

use ArrayAccess;
use HapiClient\Hal\Resource;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\RenderTemplate;
use Akki\SyliusPayumSlimpayPlugin\Constants;
use Akki\SyliusPayumSlimpayPlugin\Reply\SlimpayHttpResponse;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\CheckoutIframe;
use Akki\SyliusPayumSlimpayPlugin\Util\ResourceSerializer;

class CheckoutIframeAction extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @param CheckoutIframe $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model->validateNotEmpty(['order', 'checkout_mode', 'authorize_template']);
        $order = ResourceSerializer::unserializeResource($model['order']);
        if (! $order instanceof Resource) {
            throw new LogicException('Order should be an instance of Resource');
        }
        if (!in_array(
            $model['checkout_mode'],
            [Constants::CHECKOUT_MODE_IFRAME_EMBADDED, Constants::CHECKOUT_MODE_IFRAME_POPIN]
        )) {
            throw new LogicException(sprintf('Iframe is not available for mode %s', $model['checkout_mode']));
        }

        $iframe = $this->api->getCheckoutIframe($order, $model['checkout_mode']);

        $renderTemplate = new RenderTemplate($model['authorize_template'], array(
            'snippet' => $iframe,
        ));
        $this->gateway->execute($renderTemplate);

        $replay = new SlimpayHttpResponse($renderTemplate->getResult());
        $replay->setOrder($order);
        $replay->setModel($model['authorize_template']);
        $replay->setSnippet($iframe);

        throw $replay;
    }
    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof CheckoutIframe &&
            $request->getModel() instanceof ArrayAccess;
    }

}