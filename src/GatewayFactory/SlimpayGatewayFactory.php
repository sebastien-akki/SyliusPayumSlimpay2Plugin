<?php
namespace Akki\SyliusPayumSlimpayPlugin\GatewayFactory;

use Akki\SyliusPayumSlimpayPlugin\Action\Api\CheckoutIframeAction;
use Akki\SyliusPayumSlimpayPlugin\Action\Api\CheckoutRedirectAction;
use Akki\SyliusPayumSlimpayPlugin\Action\Api\GetOrderPaymentReferenceAction;
use Akki\SyliusPayumSlimpayPlugin\Action\Api\PaymentAction;
use Akki\SyliusPayumSlimpayPlugin\Action\Api\SetUpCardAliasAction;
use Akki\SyliusPayumSlimpayPlugin\Action\Api\SignMandateAction;
use Akki\SyliusPayumSlimpayPlugin\Action\Api\SyncOrderAction;
use Akki\SyliusPayumSlimpayPlugin\Action\Api\SyncPaymentAction;
use Akki\SyliusPayumSlimpayPlugin\Action\Api\UpdatePaymentMethodWithCheckoutAction;
use Akki\SyliusPayumSlimpayPlugin\Action\Api\UpdatePaymentMethodWithIbanAction;
use Akki\SyliusPayumSlimpayPlugin\Action\AuthorizeAction;
use Akki\SyliusPayumSlimpayPlugin\Action\CancelAction;
use Akki\SyliusPayumSlimpayPlugin\Action\ConvertPaymentAction;
use Akki\SyliusPayumSlimpayPlugin\Action\CaptureAction;
use Akki\SyliusPayumSlimpayPlugin\Action\NotifyAction;
use Akki\SyliusPayumSlimpayPlugin\Action\OrderStatusAction;
use \Akki\SyliusPayumSlimpayPlugin\Action\Api\RefundAction as ApiRefundAction;
use Akki\SyliusPayumSlimpayPlugin\Action\PaymentStatusAction;
use Akki\SyliusPayumSlimpayPlugin\Api\Api;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Akki\SyliusPayumSlimpayPlugin\Action\SyncAction;
use Akki\SyliusPayumSlimpayPlugin\Action\Api\NotifyAction as ApiNotifyAction;
use Payum\Core\GatewayFactoryInterface;

/**
 * Class SlimpayGatewayFactory
 * @package Akki\SyliusPayumSlimpayPlugin\GatewayFactory
 */
class SlimpayGatewayFactory extends GatewayFactory
{
    /**
     * Builds a new factory.
     *
     * @param array                   $defaultConfig
     * @param GatewayFactoryInterface $coreGatewayFactory
     *
     * @return SlimpayGatewayFactory
     */
    public static function build(array $defaultConfig, GatewayFactoryInterface $coreGatewayFactory = null)
    {
        return new static($defaultConfig, $coreGatewayFactory);
    }

    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'template_authorize' => '@PayumSlimpay/Action/capture.html.twig'
        ]);
        $config->defaults([
            'payum.factory_name' => 'slimpay',
            'payum.factory_title' => 'slimpay',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.authorize' => new AuthorizeAction($config['template_authorize']),
            'payum.action.cancel' => new CancelAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new PaymentStatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.order_status' => new OrderStatusAction(),
            'payum.action.payment_status' => new PaymentStatusAction(),
            'payum.action.sync' => new SyncAction(),

            'payum.action.api.checkout_iframe' => new CheckoutIframeAction(),
            'payum.action.api.checkout_redirect' => new CheckoutRedirectAction(),
            'payum.action.api.notify' => new ApiNotifyAction(),
            'payum.action.api.payment' => new PaymentAction(),
            'payum.action.api.refund' => new ApiRefundAction(),
            'payum.action.api.set_up_card_alias' => new SetUpCardAliasAction(),
            'payum.action.api.get_order_mandate' => new GetOrderPaymentReferenceAction(),
            'payum.action.api.sign_mandate' => new SignMandateAction(),
            'payum.action.api.sync_order' => new SyncOrderAction(),
            'payum.action.api.sync_payment' => new SyncPaymentAction(),
            'payum.action.api.update_payment_method_with_checkout' => new UpdatePaymentMethodWithCheckoutAction(),
            'payum.action.api.update_payment_method_with_iban' => new UpdatePaymentMethodWithIbanAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'app_id' => '',
                'app_secret' => '',
                'creditor_reference' => '',
                'sandbox' => true,
                'default_checkout_mode' => null
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                $slimpayConfig = [
                    'app_id' => $config['app_id'],
                    'app_secret' => $config['app_secret'],
                    'creditor_reference' => $config['creditor_reference'],
                    'sandbox' => $config['sandbox'],
                    'default_checkout_mode' => $config['default_checkout_mode']
                    ];

                return new Api($slimpayConfig, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
    }
}
