<?php

namespace CrowdSec\RemediationEngine\Tests\Integration;

/**
 * Integration Test for Capi Remediation.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\CapiClient\Client\CapiHandler\Curl;
use CrowdSec\CapiClient\Client\CapiHandler\FileGetContents;
use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\Common\Client\AbstractClient;
use CrowdSec\Common\Logger\FileLog;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CapiRemediation;
use CrowdSec\RemediationEngine\Tests\Constants as TestConstants;
use CrowdSec\RemediationEngine\Tests\PHPUnitUtil;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Exception;

final class CapiRemediationTest extends TestCase
{
    protected $configs = [
        'machine_id_prefix' => TestConstants::MACHINE_ID_PREFIX,
        'user_agent_suffix' => TestConstants::USER_AGENT_SUFFIX,
        'scenarios' => ['crowdsecurity/http-backdoors-attempts', 'crowdsecurity/http-bad-user-agent'],
    ];

    /**
     * @var string
     */
    private $debugFile;
    /**
     * @var FileLog
     */
    private $logger;
    /**
     * @var string
     */
    private $prodFile;
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * @var PhpFiles
     */
    private $cacheStorage;

    /**
     * set up test environment.
     */
    public function setUp(): void
    {
        $this->root = vfsStream::setup('/tmp');
        $currentDate = date('Y-m-d');
        $this->debugFile = 'debug-' . $currentDate . '.log';
        $this->prodFile = 'prod-' . $currentDate . '.log';
        $this->logger = new FileLog(['log_directory_path' => $this->root->url(), 'debug_mode' => true]);
        // Init PhpFiles cache storage
        $cacheFileConfigs = [
            'fs_cache_path' => __DIR__ . '/.cache/capi',
        ];
        $this->cacheStorage = new PhpFiles($cacheFileConfigs, $this->logger);
    }

    protected function tearDown(): void
    {
        $this->cacheStorage->clear();
    }

    public function requestHandlerProvider(): array
    {
        return [
            'Default (Curl)' => [null],
            'FileGetContents' => [new FileGetContents()],
        ];
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testRefreshDecisions($requestHandler)
    {
        $password = file_get_contents(__DIR__ . '/dev-password.json');
        if (!$password) {
            throw new Exception('Error while trying to get content of dev-password.json file');
        }
        $machineId = file_get_contents(__DIR__ . '/dev-machine-id.json');
        if (!$machineId) {
            throw new Exception('Error while trying to get content of dev-machine-id.json file');
        }

        $capiClient = new Watcher($this->configs, new FileStorage(__DIR__), $requestHandler, $this->logger);
        $this->checkRequestHandler($capiClient, $requestHandler);

        $remediationEngine = new CapiRemediation($this->configs, $capiClient, $this->cacheStorage, $this->logger);
        // Test 1 : refresh and check result format and log (should have pull list)
        $result = $remediationEngine->refreshDecisions();

        $this->assertArrayHasKey('deleted', $result);
        $this->assertArrayHasKey('new', $result);
        $new = (int) $result['new'];
        $deleted = (int) $result['deleted'];

      /*  PHPUnitUtil::assertRegExp(
            $this,
            '/.*100.*"type":"CAPI_REM_HANDLE_LIST_DECISIONS.*list_count"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Log content should be correct'
        );*/
        // Test 2 : Refresh again and check that list has not been downloaded again
        // Empty log file
        file_put_contents($this->root->url() . '/' . $this->debugFile, '');
        $result = $remediationEngine->refreshDecisions();
        $this->assertTrue(0 === (int) $result['new']);

        PHPUnitUtil::assertRegExp(
            $this,
            '/.*100.*"type":"WATCHER_REQUEST.*\/decisions\/stream"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Log content should be correct'
        );

        PHPUnitUtil::assertDoesNotMatchRegExp(
            $this,
            '/.*100.*"type":"CAPI_REM_HANDLE_LIST_DECISIONS.*list_count"/',
            file_get_contents($this->root->url() . '/' . $this->debugFile),
            'Log content should be correct'
        );
        // Test 3 : clear cache and refresh again : new and deleted should be as in Test  1
        $this->cacheStorage->clear();
        $result = $remediationEngine->refreshDecisions();
        $this->assertEquals($new, (int) $result['new']);
        $this->assertEquals($deleted, (int) $result['deleted']);
    }

    /**
     * @return void
     */
    private function checkRequestHandler(AbstractClient $client, $requestHandler)
    {
        if (null === $requestHandler) {
            $this->assertEquals(
                Curl::class,
                get_class($client->getRequestHandler()),
                'Request handler should be curl by default'
            );
        } else {
            $this->assertEquals(
                FileGetContents::class,
                get_class($client->getRequestHandler()),
                'Request handler should be file_get_contents'
            );
        }
    }
}
