<?php

namespace Akki\SyliusPayumSlimpayPlugin\Controller;


use Akki\SyliusPayumSlimpayPlugin\Util\ResourceSerializer;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Ekyna\Component\Payum\Monetico\Api\Api;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\Notify;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotifyController extends PayumController
{
    /**
     * @param Request $request
     * @return Response
     */
    public function doAction(Request $request) {

        $handleRequest = fopen("request.txt", "w");
        fwrite ($handleRequest, print_r($request,true) );

        $body = file_get_contents('php://input');
        $body = json_decode($body, true);
        $orderReference = $body["reference"];

        $handleRequest = fopen("body.txt", "w");
        fwrite ($handleRequest, print_r($body,true) );

        // $model = ArrayObject::ensureArrayObject($request->getModel());

        // $handleModel= fopen("model.txt", "w");
        // fwrite ($handleModel, print_r($model,true) );

        // $model->validateNotEmpty(['order']);

        // $order = ResourceSerializer::unserializeResource($model['order']);

        // $handleOder= fopen("order.txt", "w");
        // fwrite ($handleOder, print_r($order,true) );

        $payment_reference = ''; // $order['payment_reference'];
        $comment = ''; // $order['comment'];

        // Find your payment entity
        try {
            /** @var PaymentInterface $payment */
            $payment = $this
                ->get('sylius.repository.payment')
                ->createQueryBuilder('p')
                ->join('p.method', 'm')
                ->join('m.gatewayConfig', 'gc')
                ->where('p.details LIKE :reference')
                ->andWhere('p.details LIKE :comment')
                ->andWhere('gc.factoryName = :factory_name')
                ->setParameters([
                    'reference'=>'%"payment_reference":'.$payment_reference.'%',
                    'comment'=>'%"comment":'.$comment.'"%',
                    'factory_name'=>'slimpay'
                ])
                ->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            throw new NotFoundHttpException(
                sprintf('Payments not found for this reference : "%s" !', $payment_reference),
                $e
            );
        } catch (NonUniqueResultException $e) {
            throw new NotFoundHttpException(
                sprintf('Many payments found for this reference : "%s", only one is required !', $payment_reference),
                $e
            );
        }

        /** @var PaymentMethodInterface $payment_method */
        $payment_method = $payment->getMethod();
        $gateway_name = $payment_method->getGatewayConfig()->getGatewayName();

        // Execute notify & status actions.
        $gateway = $this->getPayum()->getGateway($gateway_name);
        $gateway->execute(new Notify($payment));

        // Return expected response
        return new Response(Api::NOTIFY_SUCCESS);
    }
}