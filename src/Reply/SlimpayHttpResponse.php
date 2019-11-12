<?php

namespace Akki\SyliusPayumSlimpayPlugin\Reply;

use HapiClient\Hal\Resource;
use Payum\Core\Reply\HttpResponse;

class SlimpayHttpResponse extends HttpResponse
{
    /**
     * @var Resource
     */
    private $order;

    /**
     * @var string
     */
    private $model;

    /**
     * @var string
     */
    private $snippet;

    /**
     * @return Resource
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Resource $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param string $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function getSnippet()
    {
        return $this->snippet;
    }

    /**
     * @param string $snippet
     */
    public function setSnippet($snippet)
    {
        $this->snippet = $snippet;
    }
}