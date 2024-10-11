<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient;

/**
 * Exception for timeout exceptions thrown by CrowdSec LAPI Client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class TimeoutException extends ClientException
{
}
