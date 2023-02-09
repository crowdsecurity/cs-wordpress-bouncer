<?php

declare(strict_types=1);

namespace CrowdSec\Common\Tests\Unit;

/**
 * Test for file storage.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\Common\Configuration\AbstractConfiguration;
use CrowdSec\Common\Tests\PHPUnitUtil;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CrowdSec\Common\Configuration\AbstractConfiguration::cleanConfigs
 */
final class AbstractConfigurationTest extends TestCase
{
    protected $configuration;

    /**
     * set up test environment.
     */
    public function setUp(): void
    {
        $stub = $this->getMockForAbstractClass(AbstractConfiguration::class);

        $this->configuration = $stub;
    }

    public function testCleanConfigs()
    {
        PHPUnitUtil::setProtectedProperty($this->configuration, 'keys', ['config1', 'config2']);
        $configs = ['config1' => 'value1', 'config2' => 'value2', 'config3' => 'value3'];
        $cleanedConfigs = $this->configuration->cleanConfigs($configs);

        $this->assertEquals(
            ['config1' => 'value1', 'config2' => 'value2'],
            $cleanedConfigs,
            'Unexpected value should have been removed'
        );

        $configs = ['config1' => 'value1'];
        $cleanedConfigs = $this->configuration->cleanConfigs($configs);
        $this->assertEquals(
            ['config1' => 'value1'],
            $cleanedConfigs,
            'Uncompleted config should be left as it'
        );

        $configs = ['config2' => 'value2', 'config1' => 'value1'];
        $cleanedConfigs = $this->configuration->cleanConfigs($configs);
        $this->assertEquals(
            ['config1' => 'value1', 'config2' => 'value2'],
            $cleanedConfigs,
            'Clean config should be left as it'
        );
    }
}
