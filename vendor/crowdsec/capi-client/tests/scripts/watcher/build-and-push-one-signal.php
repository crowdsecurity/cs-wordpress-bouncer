<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\Common\Logger\ConsoleLog;

// Parse arguments
$scenarios = isset($argv[1]) ? json_decode($argv[1], true) : false;
$signal = isset($argv[2]) ? json_decode($argv[2], true) : false;
if (is_null($signal)) {
    exit('Param <SIGNAL_JSON> is not a valid json' . \PHP_EOL . 'Usage: php build-and-push-one-signal.php <SCENARIOS_JSON> <SIGNAL_JSON>' . \PHP_EOL);
}
if (is_null($scenarios)) {
    exit('Param <SCENARIOS_JSON> is not a valid json' . \PHP_EOL . 'Usage: php build-and-push-one-signal.php <SCENARIOS_JSON> <SIGNAL_JSON>' . \PHP_EOL);
}

if (!$signal || !$scenarios) {
    exit(
        'Usage: php build-and-push-one-signal.php <SCENARIOS_JSON> <SIGNAL_JSON>' . \PHP_EOL .
        'Example: php build-and-push-one-signal.php \'["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]\' \'{"message":"Ip 2.2.2.2 performed crowdsecurity/http-probing (6 events over 29.992437958s) at 2020-11-06 20:14:11.189255784 +0000 UTC m=+52.785061338","scenario":"crowdsecurity/http-probing","scenario_hash":"","scenario_version":"","source":{"id":2,"as_name":"TEST","cn":"FR","ip":"2.2.2.2","latitude":48.9917,"longitude":1.9097,"range":"2.2.2.2\/32","scope":"Ip","value":"2.2.2.2"},"context":[{"key":"exampleKey1","value":"exampleValue1"}]}\'' . \PHP_EOL
    );
}
echo \PHP_EOL . 'Instantiate watcher ...' . \PHP_EOL;
$configs = [
    'machine_id_prefix' => 'capiclienttest',
    'user_agent_suffix' => 'CapiClientTest',
    'scenarios' => $scenarios,
    'env' => 'dev',
];
$client = new Watcher(
    $configs,
    new FileStorage(__DIR__ . '/../../../src/Storage', $configs['env']),
    null,
    new ConsoleLog(['level' => 'critical'])
);
echo 'Watcher instantiated' . \PHP_EOL;

$properties = $signal;
unset($properties['source'], $properties['decisions']);
$source = $signal['source'] ?? [];
$decisions = $signal['decisions'] ?? [];

echo 'Creating signal ...' . \PHP_EOL;
$response = $client->buildSignal($properties, $source, $decisions);
echo 'Build signal is:' . json_encode($response, \JSON_UNESCAPED_SLASHES) . \PHP_EOL;
$signals = [$response];

echo 'Pushing signals for ' . $client->getConfig('api_url') . \PHP_EOL;
echo 'Scenarios list: ' . \PHP_EOL;
print_r($client->getConfig('scenarios'));
echo 'Signals list: ' . \PHP_EOL;
print_r($signals);
$response = $client->pushSignals($signals);
echo 'Push signals response is:' . json_encode($response) . \PHP_EOL;
