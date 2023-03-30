<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Client\CapiHandler;

use CrowdSec\Common\Client\RequestHandler\RequestHandlerInterface;

/**
 * List handler interface to get CAPI linked decisions (blocklists).
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
interface CapiHandlerInterface extends RequestHandlerInterface
{
    /**
     * Retrieve decisions list from a blocklist url.
     */
    public function getListDecisions(string $url, array $headers = []): string;
}
