<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\Common\Logger\ConsoleLog;

// Parse arguments
$scenarios = isset($argv[1]) ? json_decode($argv[1], true) : false;
$name = $argv[2] ?? null;
$overwrite = isset($argv[3]) ? (bool) $argv[3] : null;
$enrollKey = $argv[4] ?? null;
$tags = isset($argv[5]) ? json_decode($argv[5], true) : [];
if (is_null($scenarios)) {
    exit('Param <SCENARIOS_JSON> is not a valid json' . \PHP_EOL . 'Usage: php enroll.php <SCENARIOS_JSON> <NAME> <OVERWRITE> <ENROLL_KEY> <TAGS_JSON>' . \PHP_EOL);
}
if (is_null($tags)) {
    exit('Param <TAGS_JSON> is not a valid json' . \PHP_EOL . 'Usage: php enroll.php <SCENARIOS_JSON> <NAME> <OVERWRITE> <ENROLL_KEY> <TAGS_JSON>' . \PHP_EOL);
}

if (!$scenarios || !$name || is_null($overwrite) || !$enrollKey || !$tags) {
    exit(
        'Usage: php enroll.php <SCENARIOS_JSON> <NAME> <OVERWRITE> <ENROLL_KEY> <TAGS_JSON>' . \PHP_EOL .
        'Example: php enroll.php  \'["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]\' TESTWATCHER 0 ZZZZZAAAAA \'["tag1", "tag2"]\'' . \PHP_EOL
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

echo 'Calling enroll for ' . $client->getConfig('api_url') . \PHP_EOL;
echo 'Scenarios list: ' . \PHP_EOL;
print_r($client->getConfig('scenarios'));
$response = $client->enroll($name, $overwrite, $enrollKey, $tags);
echo 'Enroll response is:' . json_encode($response) . \PHP_EOL;
