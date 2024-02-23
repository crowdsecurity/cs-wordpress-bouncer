<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Client\CapiHandler;

use CrowdSec\Common\Client\RequestHandler\Curl as CommonCurl;
use CrowdSec\Common\Constants;

/**
 * Curl list handler to get CAPI linked decisions (blocklists).
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Curl extends CommonCurl implements CapiHandlerInterface
{
    public function getListDecisions(string $url, array $headers = []): string
    {
        $handle = curl_init();

        $curlOptions = $this->createListOptions($url, $headers);
        curl_setopt_array($handle, $curlOptions);

        $response = $this->exec($handle);

        $statusCode = $this->getResponseHttpCode($handle);

        curl_close($handle);

        return 200 === $statusCode ? (string) $response : '';
    }

    /**
     * Retrieve Curl options.
     */
    private function createListOptions(string $url, array $headers = []): array
    {
        $options = [
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_ENCODING => '',
            \CURLOPT_TIMEOUT => Constants::API_TIMEOUT,
            \CURLOPT_POST => false,
            \CURLOPT_CUSTOMREQUEST => 'GET',
            \CURLOPT_HTTPGET => true,
            \CURLOPT_URL => $url,
        ];
        $options[\CURLOPT_HTTPHEADER] = [];
        foreach ($headers as $key => $values) {
            foreach (\is_array($values) ? $values : [$values] as $value) {
                $options[\CURLOPT_HTTPHEADER][] = sprintf('%s:%s', $key, $value);
            }
        }

        return $options;
    }
}
