<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient;

use CrowdSec\Common\Client\ClientException as CommonClientException;

/**
 * Exception interface for all exceptions thrown by CrowdSec LAPI Client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class ClientException extends CommonClientException
{
}
