<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Client;

use CrowdSec\CapiClient\Client\CapiHandler\CapiHandlerInterface;
use CrowdSec\CapiClient\Client\CapiHandler\Curl;
use CrowdSec\Common\Client\AbstractClient as CommonAbstractClient;
use Psr\Log\LoggerInterface;

/**
 * The low level CrowdSec CAPI Client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractClient extends CommonAbstractClient
{
    /**
     * @var CapiHandlerInterface
     */
    private $capiHandler;

    public function __construct(
        array $configs,
        ?CapiHandlerInterface $listHandler = null,
        ?LoggerInterface $logger = null
    ) {
        $this->configs = $configs;
        $this->capiHandler = ($listHandler) ?: new Curl($this->configs);
        parent::__construct($configs, $this->capiHandler, $logger);
    }

    public function getCapiHandler(): CapiHandlerInterface
    {
        return $this->capiHandler;
    }
}
