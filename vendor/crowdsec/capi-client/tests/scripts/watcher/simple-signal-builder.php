<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\CapiClient\Constants;
use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;

// Parse arguments
$scenarios = isset($argv[1]) ? json_decode($argv[1], true) : false;
$scenario = isset($argv[2]) ? $argv[2] : false;
$ip = isset($argv[3]) ? $argv[3] : false;
$createdAt = isset($argv[4]) ? new \DateTime($argv[4]) : null;
$message = $argv[5] ?? '';
$duration = $argv[6] ?? Constants::DURATION;
if (is_null($scenarios)) {
    exit('Param <SCENARIOS_JSON> is not a valid json' . \PHP_EOL . 'Usage: php simple-signal-builder.php <SCENARIOS_JSON> <SCENARIO> <IP> <CREATED_AT> <MESSAGE> <DURATION>' . \PHP_EOL);
}

if (!$scenarios || !$scenario || !$ip) {
    exit(
        'Usage: php simple-signal-builder.php <SCENARIOS_JSON> <SCENARIO> <IP> <CREATED_AT> <MESSAGE> <DURATION> ' . \PHP_EOL .
        'Example: php simple-signal-builder.php \'["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]\' "crowdsecurity/http-backdoors-attempts" "1.2.3.4" ' . \PHP_EOL .
        'Example 2: php simple-signal-builder.php \'["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]\' "crowdsecurity/http-backdoors-attempts" "1.2.3.4" "2022-12-14 23:25:00" "6 events over 30s" "86400"' . \PHP_EOL
    );
}
echo \PHP_EOL . 'Instantiate watcher ...' . \PHP_EOL;
$configs = [
    'machine_id_prefix' => 'capiclienttest',
    'user_agent_suffix' => 'CapiClientTest',
    'scenarios' => $scenarios,
    ];
$client = new Watcher($configs, new FileStorage());
echo 'Watcher instantiated' . \PHP_EOL;

echo 'Creating simple signal ...' . \PHP_EOL;
$response = $client->buildSimpleSignalForIp($ip, $scenario, $createdAt, $message, $duration);
echo 'Build signal is:' . json_encode($response, \JSON_UNESCAPED_SLASHES) . \PHP_EOL;
