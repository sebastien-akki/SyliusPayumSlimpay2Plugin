<?php

namespace Akki\SyliusPayumSlimpayPlugin\Action\Api;

use ArrayAccess;
use Exception;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\SyncOrder;
use Akki\SyliusPayumSlimpayPlugin\Util\ResourceSerializer;

class SyncOrderAction extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @param SyncOrder $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model->validateNotEmpty(['order']);

        try {
            $order = ResourceSerializer::unserializeResource($model['order']);

            $model['order'] = ResourceSerializer::serializeResource(
                $this->api->getOrder($order->getState()['id'])
            );
        } catch (Exception $e) {
            $this->populateDetailsWithError($model, $e, $request);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof SyncOrder &&
            $request->getModel() instanceof ArrayAccess
            ;
    }
}