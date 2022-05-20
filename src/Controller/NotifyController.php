<?php

namespace Akki\SyliusPayumSlimpayPlugin\Controller;


use Akki\SyliusPayumSlimpayPlugin\Constants\Constants;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use HapiClient\Exception\RelNotFoundException;
use HapiClient\Hal\CustomRel;
use HapiClient\Hal\Resource;
use HapiClient\Http\Auth\Oauth2BasicAuthentication;
use HapiClient\Http\Follow;
use HapiClient\Http\HapiClient;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Request\Notify;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethod;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotifyController extends PayumController
{

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function doAction(Request $request) {

        $body = file_get_contents('php://input');
        $order = Resource::fromJson($body);

        if (strpos($order->getState()['state'], 'closed.aborted') === 0) {
            // Return expected response
            return new Response();
        } elseif (strpos($order->getState()['state'], 'closed.completed') === 0) {
            // L'utilisateur a été au bout du paiement
            /** @var PaymentMethod $slimpay */
            $slimpay = $this->get('sylius.repository.payment_method')->findOneByCode('slimpay');

            $hapiClient = $this->getHapiClient($slimpay->getGatewayConfig()->getConfig());

            $mandate = $this->doRequestInfosNotify($hapiClient, 'get-mandate', $order);
            $mandateSate = $mandate->getState();
            $mandateReference = $mandateSate['reference'];

            // Find your payment entity
            try {
                /** @var EntityRepository $paymentRepository */
                $paymentRepository = $this->get('sylius.repository.payment');

                /** @var PaymentInterface $payment */
                $payment = $paymentRepository
                    ->createQueryBuilder('p')
                    ->join('p.method', 'm')
                    ->join('m.gatewayConfig', 'gc')
                    ->where('p.mandateReference = :reference')
                    ->andWhere('gc.factoryName = :factory_name')
                    ->setParameters([
                        'reference' => $mandateReference,
                        'factory_name' => 'slimpay'
                    ])
                    ->getQuery()->getSingleResult();
            } catch (NoResultException $e) {
                throw new NotFoundHttpException(
                    sprintf('Payments not found for this reference : "%s" !', $mandateReference),
                    $e
                );
            } catch (NonUniqueResultException $e) {
                throw new NotFoundHttpException(
                    sprintf('Many payments found for this reference : "%s", only one is required !', $mandateReference),
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
            return new Response();
        }else {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }
    }

    private function getHapiClient(array $config) {

        $apiEndPoint = $config['sandbox'] ? Constants::BASE_URI_SANDBOX : Constants::BASE_URI_PROD;
        return new HapiClient(
            $apiEndPoint,
            '/',
            Constants::PROFILE_URI . '/alps/v1',
            new Oauth2BasicAuthentication(
                '/oauth/token',
                $config['app_id'],
                $config['app_secret']
            )
        );
    }

    /**
     * @param HapiClient $hapiClient
     * @param string $follow
     * @param Resource|null $resource
     *
     * @return Resource
     */
    protected function doRequestInfosNotify(HapiClient $hapiClient, $follow, Resource $resource = null) {
        $rel = new CustomRel($this->getRelationsNamespace() . $follow);
        $follow = new Follow($rel);
        return $hapiClient->sendFollow($follow, $resource);
    }

    /**
     * @return string
     */
    protected function getRelationsNamespace()
    {
        return Constants::RELATION_URI . '/alps#';
    }
}