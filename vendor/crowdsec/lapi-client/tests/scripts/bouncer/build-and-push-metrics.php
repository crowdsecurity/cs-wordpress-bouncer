<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\Common\Logger\ConsoleLog;
use CrowdSec\LapiClient\Bouncer;

$metrics = isset($argv[1]) ? json_decode($argv[1], true) : [];
$bouncerKey = $argv[2] ?? false;
$lapiUrl = $argv[3] ?? false;
if (!$bouncerKey || !$lapiUrl) {
    exit('Params <BOUNCER_KEY> and <LAPI_URL> are required' . \PHP_EOL
         . 'Usage: php build-and-push-metrics.php <METRICS_JSON> <BOUNCER_KEY> <LAPI_URL>' . \PHP_EOL
         . 'Example: php build-and-push-metrics.php \'{"name":"TEST BOUNCER","type":"crowdsec-test-php-bouncer","version":"v0.0.0","items":[{"name":"dropped","value":12,"unit":"request","labels":{"origin":"CAPI"}}],"meta":{"window_size_seconds":900,"utc_now_timestamp":12}}\' my-bouncer-key https://crowdsec:8080 '
         . \PHP_EOL);
}

if (is_null($metrics)) {
    exit('Param <METRICS_JSON> is not a valid json' . \PHP_EOL
         . 'Usage: php build-and-push-metrics.php <METRICS_JSON> <BOUNCER_KEY> <LAPI_URL>'
         . \PHP_EOL);
}

echo \PHP_EOL . 'Instantiate bouncer ...' . \PHP_EOL;
// Config to use an Api Key for connection
$apiKeyConfigs = [
    'auth_type' => 'api_key',
    'api_url' => $lapiUrl,
    'api_key' => $bouncerKey,
];
$logger = new ConsoleLog();
$client = new Bouncer($apiKeyConfigs, null, $logger);
echo 'Bouncer instantiated' . \PHP_EOL;

$properties = $metrics;
unset($properties['meta'], $properties['items']);
$meta = $metrics['meta'] ?? [];
$items = $metrics['items'] ?? [];

echo 'Creating usage metrics ...' . \PHP_EOL;
$response = $client->buildUsageMetrics($properties, $meta, $items);
echo 'Build metrics is:' . json_encode($response, \JSON_UNESCAPED_SLASHES) . \PHP_EOL;
$usageMetrics = $response;

echo 'Pushing usage metrics to ' . $client->getConfig('api_url') . ' ...' . \PHP_EOL;
echo 'Metrics: ';
print_r(json_encode($usageMetrics) . \PHP_EOL);
$response = $client->pushUsageMetrics($usageMetrics);
echo \PHP_EOL . 'Usage metrics response is:' . json_encode($response) . \PHP_EOL;
