<?php

namespace Akki\SyliusPayumSlimpayPlugin\Config;


class Config
{
    /**
     * @var string
     */
    public $appId;

    /**
     * @var string
     */
    public $appSecret;

    /**
     * @var string
     */
    public $creditorReference;

    /**
     * @var string
     */
    public $notifyUrl;

    /**
     * @var string
     */
    public $returnUrl;

    /**
     * @var string
     */
    public $baseUri = Constants::BASE_URI_SANDBOX;
}