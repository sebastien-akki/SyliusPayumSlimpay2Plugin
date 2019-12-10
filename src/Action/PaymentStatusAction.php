<?php
namespace Akki\SyliusPayumSlimpayPlugin\Action;

use Akki\SyliusPayumSlimpayPlugin\Request\Api\GetOrderPaymentReference;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\SyncOrder;
use ArrayAccess;
use Exception;
use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Akki\SyliusPayumSlimpayPlugin\Constants\Constants;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\SyncPayment;
use Akki\SyliusPayumSlimpayPlugin\Util\ResourceSerializer;
use Payum\Core\Request\GetStatusInterface;

class PaymentStatusAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     * @throws Exception
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if($model['payment']) {
            $this->gateway->execute(new SyncPayment($model));
            $payment = ResourceSerializer::unserializeResource($model['payment']);
            switch ($payment->getState()['executionStatus']) {
                case Constants::PAYMENT_STATUS_PROCESSING:
                case Constants::PAYMENT_STATUS_TO_PROCESS:
                    $request->markPending();
                    break;
                case Constants::PAYMENT_STATUS_PROCESSED:
                    $request->markCaptured();
                    break;
                case Constants::PAYMENT_STATUS_REJECTED:
                    $request->markFailed();
                    break;
                default:
                    $request->markUnknown();
            }
            return;
        } else if($model['order']) {
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
                    $request->markCaptured();
                    $this->gateway->execute(new GetOrderPaymentReference($model));
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
            return;
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
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof ArrayAccess
        ;
    }
}
