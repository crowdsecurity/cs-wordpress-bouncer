<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\CapiClient\Constants;
use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;

// Parse arguments
$scenarios = isset($argv[1]) ? json_decode($argv[1], true) : false;
$scenario = isset($argv[2]) ? $argv[2] : false;
$value = isset($argv[3]) ? $argv[3] : false;
$start = isset($argv[4]) ? new \DateTime($argv[4]) : null;
$stop = isset($argv[5]) ? new \DateTime($argv[5]) : null;
$message = $argv[6] ?? '';
$scope = $argv[7] ?? Constants::SCOPE_IP;
$duration = $argv[8] ?? Constants::DURATION;
$type = $argv[9] ?? Constants::REMEDIATION_BAN;
if (is_null($scenarios)) {
    exit('Param <SCENARIOS_JSON> is not a valid json' . \PHP_EOL . 'Usage: php signal-builder.php <SCENARIOS_JSON> <SCENARIO> <VALUE> <START> <STOP> <MESSAGE> <SCOPE> <DURATION> <TYPE>' . \PHP_EOL);
}

if (!$scenarios || !$scenario || !$value) {
    exit(
        'Usage: php signal-builder.php <SCENARIOS_JSON> <SCENARIO> <VALUE> <START> <STOP> <MESSAGE> <SCOPE> <DURATION> <TYPE> ' . \PHP_EOL .
        'Example: php signal-builder.php \'["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]\' "crowdsecurity/http-backdoors-attempts" "1.2.3.4" ' . \PHP_EOL .
        'Example 2: php signal-builder.php \'["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]\' "crowdsecurity/http-backdoors-attempts" "1.2.3.4/24" "2022-12-14 23:25:00" "2022-12-14 23:48:56" "6 events over 30s" "Range" "86400" "captcha"' . \PHP_EOL
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

echo 'Creating signal ...' . \PHP_EOL;
$response = $client->createSignal($scenario, $value, $start, $stop, $message, $scope, $duration, $type);
echo 'Build signal is:' . json_encode($response, \JSON_UNESCAPED_SLASHES) . \PHP_EOL;
