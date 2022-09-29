<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

use Exception;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * The Library geolocation helper. You'll find here methods used for geolocation purposes.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Geolocation
{
    /**
     * @var array
     */
    protected $maxmindCountry = [];

    /**
     * @var string[]
     */
    private $resultTemplate = ['country' => '', 'not_found' => '', 'error' => ''];

    /**
     * @throws CacheException
     * @throws BouncerException
     * @throws InvalidArgumentException
     */
    public function clearGeolocationCache(string $ip, ApiCache $apiCache): void
    {
        $variables = ['crowdsec_geolocation_country', 'crowdsec_geolocation_not_found'];
        $apiCache->unsetIpVariables(Constants::CACHE_TAG_GEO, $variables, $ip);
    }

    /**
     * Retrieve country from a geo-localised IP.
     *
     * @param array $geolocConfig
     * @param string $ip
     * @param ApiCache $apiCache
     * @return array
     * @throws BouncerException
     * @throws CacheException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getCountryResult(array $geolocConfig, string $ip, ApiCache $apiCache): array
    {
        $result = $this->resultTemplate;
        $saveInCache = !empty($geolocConfig['save_result']);
        if ($saveInCache) {
            $cachedVariables = $apiCache->getIpVariables(
                Constants::CACHE_TAG_GEO,
                ['crowdsec_geolocation_country', 'crowdsec_geolocation_not_found'],
                $ip
            );
            $country = $cachedVariables['crowdsec_geolocation_country'] ?? null;
            $notFoundError = $cachedVariables['crowdsec_geolocation_not_found'] ?? null;
            if ($country) {
                $result['country'] = $country;

                return $result;
            } elseif ($notFoundError) {
                $result['not_found'] = $notFoundError;

                return $result;
            }
        }
        if (Constants::GEOLOCATION_TYPE_MAXMIND !== $geolocConfig['type']) {
            throw new BouncerException('Unknown Geolocation type:' . $geolocConfig['type']);
        }
        $configPath = $geolocConfig[Constants::GEOLOCATION_TYPE_MAXMIND];
        $result = $this->getMaxMindCountry($ip, $configPath['database_type'], $configPath['database_path']);

        if ($saveInCache) {
            if (!empty($result['country'])) {
                $apiCache->setIpVariables(
                    Constants::CACHE_TAG_GEO,
                    ['crowdsec_geolocation_country' => $result['country']],
                    $ip
                );
            } elseif (!empty($result['not_found'])) {
                $apiCache->setIpVariables(
                    Constants::CACHE_TAG_GEO,
                    ['crowdsec_geolocation_not_found' => $result['not_found']],
                    $ip
                );
            }
        }

        return $result;
    }

    /**
     * Retrieve country ISO code alpha-2 if exists for the specified IP
     *
     * @param array $geolocConfig
     * @param string $ip
     * @param ApiCache $apiCache
     * @param LoggerInterface $logger
     * @return string|null
     * @throws BouncerException
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function getCountryToQuery(
        array $geolocConfig,
        string $ip,
        ApiCache $apiCache,
        LoggerInterface $logger
    ): ?string {
        $countryToQuery = null;
        $countryResult = $this->getCountryResult($geolocConfig, $ip, $apiCache);
        if (!empty($countryResult['country'])) {
            $countryToQuery = $countryResult['country'];
            $logger->debug('', ['type' => 'GEOLOCALISED_COUNTRY', 'ip' => $ip, 'country' => $countryToQuery]);
        } elseif (!empty($countryResult['not_found'])) {
            $logger->warning('', [
                'type' => 'IP_NOT_FOUND_WHILE_GETTING_GEOLOC_COUNTRY',
                'ip' => $ip,
                'error' => $countryResult['not_found'],
            ]);
        } elseif (!empty($countryResult['error'])) {
            $logger->warning('', [
                'type' => 'ERROR_WHILE_GETTING_GEOLOC_COUNTRY',
                'ip' => $ip,
                'error' => $countryResult['error'],
            ]);
        }

        return $countryToQuery;
    }

    /**
     * Retrieve a country from a MaxMind database.
     *
     * @throws Exception
     */
    private function getMaxMindCountry(string $ip, string $databaseType, string $databasePath): array
    {
        if (!isset($this->maxmindCountry[$ip][$databaseType][$databasePath])) {
            $result = $this->resultTemplate;
            try {
                $reader = new Reader($databasePath);
                switch ($databaseType) {
                    case Constants::MAXMIND_COUNTRY:
                        $record = $reader->country($ip);
                        break;
                    case Constants::MAXMIND_CITY:
                        $record = $reader->city($ip);
                        break;
                    default:
                        throw new BouncerException("Unknown MaxMind database type:$databaseType");
                }
                $result['country'] = $record->country->isoCode;
            } catch (AddressNotFoundException $e) {
                $result['not_found'] = $e->getMessage();
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
            }

            $this->maxmindCountry[$ip][$databaseType][$databasePath] = $result;
        }

        return $this->maxmindCountry[$ip][$databaseType][$databasePath];
    }
}
