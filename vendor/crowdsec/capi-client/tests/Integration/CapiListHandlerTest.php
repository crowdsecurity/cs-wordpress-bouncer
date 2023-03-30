<?php

namespace CrowdSec\CapiClient\Tests\Integration;

/**
 * Integration Test for CapiHandler (List handler).
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
use PHPUnit\Framework\TestCase;

/**
 * @covers \CrowdSec\CapiClient\Client\CapiHandler\Curl::getListDecisions
 * @covers \CrowdSec\CapiClient\Client\CapiHandler\Curl::createListOptions
 * @covers \CrowdSec\CapiClient\Client\CapiHandler\FileGetContents::getListDecisions
 * @covers \CrowdSec\CapiClient\Client\CapiHandler\FileGetContents::createListContextConfig
 */
final class CapiListHandlerTest extends TestCase
{
    private $urls = [
        '404' => 'https://github.com/crowdsecurity/php-capi-client/test.txt',
        '200' => 'https://gist.githubusercontent.com/julienloizelet/8aaa7557c07fe83435303a9f3b412d00/raw/751f6867f775f0aec88b34a86032ea19e6dceb3c/cs-list-test.txt',
    ];

    public function capiHandlerProvider(): array
    {
        return [
            'Curl' => [new Curl()],
            'FileGetContents' => [new FileGetContents()],
        ];
    }

    /**
     * @dataProvider capiHandlerProvider
     */
    public function testGetListDecisions($capiHandler)
    {
        // test 1 : file does not exist
        $list = $capiHandler->getListDecisions($this->urls['404']);

        $this->assertEquals('', $list);

        // test 2 : file exist and contains 2 IPS
        $list = $capiHandler->getListDecisions($this->urls['200']);

        $this->assertEquals("1.2.3.4\n5.6.7.8", $list);

        // test 2 : file exist but has not been modified since 2115
        $list = $capiHandler->getListDecisions($this->urls['200'], ['If-Modified-Since' => 'Wed, 23 Oct 2115 07:28:00 GMT']);

        $this->assertEquals('', $list);
    }
}
