<?php

namespace Akki\SyliusPayumSlimpayPlugin\Action\Api;


use ArrayAccess;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Akki\SyliusPayumSlimpayPlugin\Constants\Constants;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\GetOrderPaymentReference;
use Akki\SyliusPayumSlimpayPlugin\Util\ResourceSerializer;

class GetOrderPaymentReferenceAction  extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @param GetOrderPaymentReference $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model->validateNotEmpty(['order']);

        $order = ResourceSerializer::unserializeResource($model['order']);

        if(Constants::ORDER_STATE_COMPLETE != $order->getState()['state']) {
            throw new LogicException('Cannot get payment reference for not completed orders.');
        }

        if (Constants::PAYMENT_SCHEME_CARD == $order->getState()['paymentScheme']) {
            $follow = Constants::FOLLOW_GET_CARD_ALIAS;
        } else {
            $follow = Constants::FOLLOW_GET_MANDATE;
        }

        $model['reference'] = ResourceSerializer::serializeResource(
            $this->api->getOrderPaymentReference($order, $follow)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetOrderPaymentReference &&
            $request->getModel() instanceof ArrayAccess
            ;
    }
}