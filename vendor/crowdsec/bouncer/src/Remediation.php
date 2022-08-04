<?php

namespace CrowdSecBouncer;

/**
 * Remediation Helpers.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Remediation
{
    /**
     * Compare two priorities.
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private static function comparePriorities(array $a, array $b): int
    {
        $a = $a[3];
        $b = $b[3];
        if ($a == $b) {
            return 0;
        }

        return ($a < $b) ? -1 : 1;
    }

    /**
     * Add numerical priority allowing easy sorting.
     */
    private static function addPriority(array $remediation): array
    {
        $prio = array_search($remediation[0], Constants::ORDERED_REMEDIATIONS);

        // Consider every unknown type as a top priority
        $remediation[3] = false !== $prio ? $prio : 0;

        return $remediation;
    }

    /**
     * Sort the remediations array of a cache item, by remediation priorities.
     */
    public static function sortRemediationByPriority(array $remediations): array
    {
        // Add priorities.
        $remediationsWithPriorities = [];
        foreach ($remediations as $key => $remediation) {
            $remediationsWithPriorities[$key] = self::addPriority($remediation);
        }

        // Sort by priorities.
        /** @var callable */
        $compareFunction = 'self::comparePriorities';
        usort($remediationsWithPriorities, $compareFunction);

        return $remediationsWithPriorities;
    }
}
