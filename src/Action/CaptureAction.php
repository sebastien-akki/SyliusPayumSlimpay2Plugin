<?php
namespace Akki\SyliusPayumSlimpayPlugin\Action;

use Akki\SyliusPayumSlimpayPlugin\Constants\Constants;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\SetUpCardAlias;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\SignMandate;
use ArrayAccess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use Akki\SyliusPayumSlimpayPlugin\Request\Api\Payment;

class CaptureAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if ($request->getToken()) {
            $model['return_url'] = $request->getToken()->getAfterUrl();
        }

        if ($model['type_paiement'] !== 'mandat'){
            $model->validateNotEmpty(['amount', 'currency', 'payment_scheme', 'payment_reference']);

            $this->gateway->execute(new Payment($model));
        }else {
            $model->validateNotEmpty(['payment_scheme']);

            if(Constants::PAYMENT_SCHEME_CARD == $model['payment_scheme']) {
                $this->gateway->execute(new SetUpCardAlias($model));
            } else {
                $this->gateway->execute(new SignMandate($model));
            }
        }

    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof ArrayAccess
        ;
    }
}
