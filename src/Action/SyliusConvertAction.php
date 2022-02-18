<?php

namespace Akki\SyliusPayumSlimpayPlugin\Action;

use DateTime;
use Exception;
use Sylius\Component\Core\Model\PaymentInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Convert;
use Akki\SyliusPayumSlimpayPlugin\Constants\Constants;

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
     * @throws Exception
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $model = ArrayObject::ensureArrayObject($payment->getDetails());

        $model['type_paiement'] = 'mandat';

        if (false == $model['payment_reference']) {
            $this->setReference($model, $payment);
        }

        if (false == $model['comment']) {
            $this->setComment($model, $payment);
        }

        if (false == $model['payment_scheme']) {
            $model['payment_scheme'] = Constants::PAYMENT_SCHEME_SEPA_DIRECT_DEBIT_CORE;
        }

        $order = $payment->getOrder();
        if (null !== $customer = $order->getCustomer()) {
            $model['subscriber_reference'] = $customer->getId();
            $model['email'] = $customer->getEmail();
        }

        if (null !== $address = $order->getBillingAddress()) {
            $model['first_name'] = $address->getFirstName();
            $model['last_name'] = $address->getLastName();
            $model['address1'] = $address->getStreet();
            $model['address2'] = $address->getStreetComplement() !== null ? $address->getStreetComplement() : '';
            $model['city'] = $address->getCity();
            $model['zip'] = $address->getPostcode();
            $model['country'] = $address->getCountryCode();
        }

        $model['cancel_url'] = $order->getCancelUrl() !== null && !empty($order->getCancelUrl()) ? $order->getCancelUrl() : '';

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
     * @throws Exception
     */
    protected function setReference(ArrayObject $model, PaymentInterface $payment): void
    {
        $dateNow = new Datetime("now");
        $model['payment_reference'] = $payment->getId();
        $model['mandate_reference'] = "{$payment->getId()}_{$dateNow->format('Y_m_d_H_i_s')}";
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
        $model['label'] = $comment;
    }

}