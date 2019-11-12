<?php

namespace Akki\SyliusPayumSlimpayPlugin\Action;

use ArrayAccess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Akki\SyliusPayumSlimpayPlugin\Constants\Constants;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\GetOrderHumanStatus;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\SyncOrder;
use Akki\SyliusPayumSlimpayPlugin\Util\ResourceSerializer;

class OrderStatusAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param GetOrderHumanStatus $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if($model['order']) {
            $this->gateway->execute(new SyncOrder($model));
            $order = ResourceSerializer::unserializeResource($model['order']);
            switch ($order->getState()['state']) {
                case Constants::ORDER_STATE_ABORT:
                case Constants::ORDER_STATE_ABORT_BY_CLIENT:
                    $request->markCanceled();
                    break;
                case Constants::ORDER_STATE_ABORT_BY_SERVER:
                    $request->markExpired();
                    break;
                case Constants::ORDER_STATE_COMPLETE:
                    $request->markAuthorized();
                    break;
                case Constants::ORDER_STATE_RUNNING:
                    $request->markPending();
                    break;
                case Constants::ORDER_STATE_NOT_RUNNING:
                case Constants::ORDER_STATE_NOT_RUNNING_NOT_STARTED:
                    $request->markNew();
                    break;
                case Constants::ORDER_STATE_NOT_RUNNING_SUSPENDED:
                case Constants::ORDER_STATE_NOT_RUNNING_SUSPENDED_AVAITING_INPUT:
                case Constants::ORDER_STATE_NOT_RUNNING_SUSPENDED_AVAITING_VALIDATION:
                    $request->markSuspended();
                    break;
                default:
                    $request->markUnknown();
            }
        } else {
            $request->markNew();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetOrderHumanStatus &&
            $request->getModel() instanceof ArrayAccess
            ;
    }

}