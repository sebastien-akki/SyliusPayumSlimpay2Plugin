<?php

namespace Akki\SyliusPayumSlimpayPlugin\Action;

use Sylius\Component\Core\Model\PaymentInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Convert;
use Payum\Core\Request\GetCurrency;
use Payum\Slimpay\Constants;

/**
 * Class SyliusConvertAction
 * @package Akki\SyliusPayumSlimpayPlugin\Action
 */
class SyliusConvertAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $model = ArrayObject::ensureArrayObject($payment->getDetails());

        if (false == $model['payment_reference']) {
            $this->setReference($model, $payment);
        }

        if (false == $model['comment']) {
            $this->setComment($model, $payment);
        }

        if (false == $model['payment_scheme']) {
            $model['payment_scheme'] = Constants::PAYMENT_SCHEME_SEPA_DIRECT_DEBIT_CORE;
        }



        $request->setResult((array)$model);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof Convert
            && $request->getSource() instanceof PaymentInterface
            && $request->getTo() == 'array';
    }

    /**
     * @param ArrayObject $model
     * @param PaymentInterface $payment
     */
    protected function setReference(ArrayObject $model, PaymentInterface $payment): void
    {
        $model['payment_reference'] = $payment->getId();
    }

    /**
     * @param ArrayObject $model
     * @param PaymentInterface $payment
     */
    protected function setComment(ArrayObject $model, PaymentInterface $payment): void
    {
        $order = $payment->getOrder();
        $comment = "Order: {$order->getNumber()}";
        if (null !== $customer = $order->getCustomer()) {
            $comment .= ", Customer: {$customer->getId()}";
        }
        $model['comment'] = $comment;
    }

}