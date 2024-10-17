<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use CrowdSec\Common\Client\RequestHandler\FileGetContents;
use CrowdSec\Common\Logger\ConsoleLog;
use CrowdSec\LapiClient\Bouncer;

$apiKey = $argv[1] ?? false;
$headers = isset($argv[2]) ? json_decode($argv[2], true) : [];
$rawBody = $argv[4] ?? '';
$appSecUrl = $argv[3] ?? false;
if (!$apiKey || !$appSecUrl) {
    exit('Params <BOUNCER_KEY> and <APPSEC_URL> are required' . \PHP_EOL
         . 'Usage: php appsec-decisions.php <BOUNCER_KEY> <HEADERS_JSON> <APPSEC_URL> [<RAW_BODY_STRING>]'
         . \PHP_EOL
         . 'Example: php appsec-decisions.php \'o7bpEAmyNF/YXhcJRSgV+HMDrfrDVRqnhp0bLjRqPVw\' \'{"X-Crowdsec-Appsec-Ip":"1.2.3.4","X-Crowdsec-Appsec-Uri":"/wsstatusevents/eventhandler.asmx","X-Crowdsec-Appsec-Host":"example.com","X-Crowdsec-Appsec-Verb":"POST","X-Crowdsec-Appsec-User-Agent":"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:68.0) Gecko/20100101 Firefox/68.0"}\'  http://crowdsec:7422 \'class.module.classLoader.resources.\''
         . \PHP_EOL
    );
}

if (is_null($headers)) {
    exit('Param <HEADERS_JSON> is not a valid json' . \PHP_EOL
         . 'Usage: php appsec-decision.php <BOUNCER_KEY> <HEADERS_JSON> <APPSEC_METHOD> <APPSEC_URL> [<RAW_BODY_STRING>]'
         . \PHP_EOL);
}

echo \PHP_EOL . 'Instantiate bouncer ...' . \PHP_EOL;
// Config to use appsec_url
$configs = [
    'appsec_url' => $appSecUrl,
    'api_key' => $apiKey,
];
$logger = new ConsoleLog();
$customRequestHandler = new FileGetContents();
$client = new Bouncer($configs, $customRequestHandler, $logger);
echo 'Bouncer instantiated' . \PHP_EOL;

$headers += ['X-Crowdsec-Appsec-Api-Key' => $apiKey];

echo 'Calling ' . $client->getConfig('appsec_url') . ' ...' . \PHP_EOL;
echo 'Headers: ';
print_r(json_encode($headers));
$response = $client->getAppSecDecision($headers, $rawBody);
echo \PHP_EOL . 'Decision response is:' . json_encode($response) . \PHP_EOL;
