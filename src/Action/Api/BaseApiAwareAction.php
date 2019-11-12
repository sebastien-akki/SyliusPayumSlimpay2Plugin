<?php
namespace Akki\SyliusPayumSlimpayPlugin\Action\Api;

use ArrayAccess;
use Exception;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Akki\SyliusPayumSlimpayPlugin\Api\Api;

abstract class BaseApiAwareAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait;

    /**
     * @var Api
     */
    protected $api;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * @param ArrayAccess     $details
     * @param Exception $e
     * @param object           $request
     */
    protected function populateDetailsWithError(ArrayAccess $details, Exception $e, $request)
    {
        $details['error_request'] = get_class($request);
        $details['error_file'] = $e->getFile();
        $details['error_line'] = $e->getLine();
        $details['error_code'] = (int) $e->getCode();
        $details['error_message'] = $e->getMessage();
    }
}
