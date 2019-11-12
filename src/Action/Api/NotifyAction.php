<?php

namespace Akki\SyliusPayumSlimpayPlugin\Action\Api;


use ArrayAccess;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\GetOrderHumanStatus;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\Notify;

class NotifyAction extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @param Notify $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model->validateNotEmpty(['order']);

        $this->gateway->execute($status = new GetOrderHumanStatus($model));
        $model['status'] = $status->getValue();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof ArrayAccess
            ;
    }
}