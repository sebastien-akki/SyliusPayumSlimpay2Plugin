<?php

namespace Akki\SyliusPayumSlimpayPlugin\Action\Api;

use ArrayAccess;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Akki\SyliusPayumSlimpayPlugin\Constants\Constants;
use Akki\SyliusPayumSlimpayPlugin\Util\ResourceSerializer;
use Payum\Core\Request\Refund;

class RefundAction extends BaseApiAwareAction
{

    /**
     * {@inheritDoc}
     *
     * @param Refund $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model->validateNotEmpty( ['payment_scheme', 'mandate_reference']);

        $amount = $request->getFirstModel()->getAmount();
        $currency = $request->getFirstModel()->getCurrencyCode();
        $number = $request->getFirstModel()->getOrder()->getNumber();

        $fields = [
            'reference' => "Refund KiosqueMag N°$number",
            'amount' => $amount,
            'currency' => $currency,
            'scheme' => Constants::PAYMENT_SCHEME_SEPA_CREDIT_TRANSFER,
            'label' => $model['label'],
            'executionDate' => $model['execution_date']
        ];

        $model['payment'] = ResourceSerializer::serializeResource(
            $this->api->refundPayment(Constants::PAYMENT_SCHEME_SEPA_CREDIT_TRANSFER, $model['mandate_reference'], $fields)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Refund &&
            $request->getModel() instanceof ArrayAccess
            ;
    }
}
