<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Client\CapiHandler;

use CrowdSec\Common\Client\RequestHandler\FileGetContents as CommonFileGetContents;
use CrowdSec\Common\Constants;

/**
 * FileGetContents list handler to get CAPI linked decisions (blocklists).
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class FileGetContents extends CommonFileGetContents implements CapiHandlerInterface
{
    public function getListDecisions(string $url, array $headers = []): string
    {
        $config = $this->createListContextConfig($headers);
        $context = stream_context_create($config);

        $fullResponse = $this->exec($url, $context);
        $response = (isset($fullResponse['response'])) ? $fullResponse['response'] : false;
        $responseHeaders = (isset($fullResponse['header'])) ? $fullResponse['header'] : [];
        $parts = !empty($responseHeaders) ? explode(' ', $responseHeaders[0]) : [];
        $status = $this->getResponseHttpCode($parts);

        return 200 === $status ? (string) $response : '';
    }

    private function createListContextConfig(array $headers = []): array
    {
        $header = $this->convertHeadersToString($headers);

        return [
            'http' => [
                'method' => 'GET',
                'header' => $header,
                'ignore_errors' => true,
                'timeout' => Constants::API_TIMEOUT,
            ],
        ];
    }
}
