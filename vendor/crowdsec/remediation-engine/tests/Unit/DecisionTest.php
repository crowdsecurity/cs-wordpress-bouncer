<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Tests\Unit;

/**
 * Test for decision.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\RemediationEngine\Decision;
use CrowdSec\RemediationEngine\Tests\Constants as TestConstants;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CrowdSec\RemediationEngine\Decision::toArray
 * @covers \CrowdSec\RemediationEngine\Decision::__construct
 * @covers \CrowdSec\RemediationEngine\Decision::getExpiresAt
 * @covers \CrowdSec\RemediationEngine\Decision::getIdentifier
 * @covers \CrowdSec\RemediationEngine\Decision::getOrigin
 * @covers \CrowdSec\RemediationEngine\Decision::getScope
 * @covers \CrowdSec\RemediationEngine\Decision::getType
 * @covers \CrowdSec\RemediationEngine\Decision::getValue
 */
final class DecisionTest extends TestCase
{
    public function testConstruct()
    {
        // Test basic
        $decision = new Decision(
            'Unit-ban-ip-' . TestConstants::IP_V4,
            'ip',
            TestConstants::IP_V4,
            'ban',
            'Unit',
            1668736601
        );

        $this->assertEquals(
            [
                'identifier' => 'Unit-ban-ip-' . TestConstants::IP_V4,
                'origin' => 'Unit',
                'scope' => 'ip',
                'value' => TestConstants::IP_V4,
                'type' => 'ban',
                'expiresAt' => 1668736601,
            ],
            $decision->toArray(),
            'Decision should be as expected'
        );
        // Test with id
        $decision = new Decision('12345',
            'ip',
            TestConstants::IP_V4,
            'ban',
            'Unit',
            1668736601);

        $this->assertEquals(
            [
                'identifier' => '12345',
                'origin' => 'Unit',
                'scope' => 'ip',
                'value' => TestConstants::IP_V4,
                'type' => 'ban',
                'expiresAt' => 1668736601,
            ],
            $decision->toArray(),
            'Decision should be as expected'
        );
    }

    protected function getRemediationMock()
    {
        return $this->getMockBuilder('CrowdSec\RemediationEngine\CapiRemediation')
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfig'])
            ->getMock();
    }
}
