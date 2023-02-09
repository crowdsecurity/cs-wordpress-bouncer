<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\LapiClient\Bouncer;
use CrowdSec\Common\Logger\ConsoleLog;

$filter = isset($argv[1]) ? json_decode($argv[1], true) : [];
$bouncerKey = $argv[2] ?? false;
$lapiUrl = $argv[3] ?? false;
if (!$bouncerKey || !$lapiUrl) {
    exit('Params <BOUNCER_KEY> and <LAPI_URL> are required' . \PHP_EOL
         . 'Usage: php decisions-filter.php <FILTER_JSON> <BOUNCER_KEY> <LAPI_URL>'
         . \PHP_EOL);
}

if (is_null($filter)) {
    exit('Param <FILTER_JSON> is not a valid json' . \PHP_EOL
         . 'Usage: php decisions-filter.php <FILTER_JSON> <BOUNCER_KEY> <LAPI_URL>'
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

echo 'Calling ' . $client->getConfig('api_url') . ' for decisions ...' . \PHP_EOL;
echo 'Filter: ';
print_r(json_encode($filter));
$response = $client->getFilteredDecisions($filter);
echo \PHP_EOL . 'Decisions response is:' . json_encode($response) . \PHP_EOL;
