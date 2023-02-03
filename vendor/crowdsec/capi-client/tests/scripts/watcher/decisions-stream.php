<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\Common\Logger\ConsoleLog;

$scenarios = isset($argv[1]) ? json_decode($argv[1], true) : false;
if (is_null($scenarios)) {
    exit('Param <SCENARIOS_JSON> is not a valid json' . \PHP_EOL . 'Usage: php decisions-stream.php <SCENARIOS_JSON>'
         . \PHP_EOL);
}
if (!$scenarios) {
    exit(
        'Usage: php decisions-stream.php <SCENARIOS_JSON>' . \PHP_EOL .
        'Example: php decisions-stream.php \'["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]\' ' .
        \PHP_EOL
    );
}

echo \PHP_EOL . 'Instantiate watcher ...' . \PHP_EOL;
$configs = [
    'machine_id_prefix' => 'capiclienttest',
    'user_agent_suffix' => 'CapiClientTest',
    'scenarios' => $scenarios,
    ];
$client = new Watcher($configs, new FileStorage(), null, new ConsoleLog());
echo 'Watcher instantiated' . \PHP_EOL;

echo 'Calling ' . $client->getConfig('api_url') . ' for decisions stream ...' . \PHP_EOL;
echo 'Scenarios list: ' . \PHP_EOL;
print_r($client->getConfig('scenarios'));
$response = $client->getStreamDecisions();
echo 'Decisions stream response is:' . json_encode($response) . \PHP_EOL;
