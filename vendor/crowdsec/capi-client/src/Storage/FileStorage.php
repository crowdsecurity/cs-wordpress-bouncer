<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Storage;

use CrowdSec\CapiClient\Constants;

/**
 * File storage. Should be used only for test or/and as an example of StorageInterface implementation.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class FileStorage implements StorageInterface
{
    public const MACHINE_ID_FILE = 'machine-id.json';

    public const PASSWORD_FILE = 'password.json';
    public const SCENARIOS_FILE = 'scenarios.json';
    public const TOKEN_FILE = 'token.json';
    /**
     * @var string
     */
    private $env;
    /**
     * @var string
     */
    private $rootDir;

    public function __construct(string $rootDir = __DIR__, string $env = Constants::ENV_DEV)
    {
        $this->rootDir = $rootDir;
        $this->env = $env;
    }

    public function retrieveMachineId(): ?string
    {
        $storageContent = $this->readFile($this->getBasePath() . self::MACHINE_ID_FILE);

        return !empty($storageContent['machine_id']) ? $storageContent['machine_id'] : null;
    }

    public function retrievePassword(): ?string
    {
        $storageContent = $this->readFile($this->getBasePath() . self::PASSWORD_FILE);

        return !empty($storageContent['password']) ? $storageContent['password'] : null;
    }

    public function retrieveScenarios(): ?array
    {
        $storageContent = $this->readFile($this->getBasePath() . self::SCENARIOS_FILE);

        return !empty($storageContent['scenarios']) ? $storageContent['scenarios'] : null;
    }

    public function retrieveToken(): ?string
    {
        $storageContent = $this->readFile($this->getBasePath() . self::TOKEN_FILE);

        return !empty($storageContent['token']) ? $storageContent['token'] : null;
    }

    public function storeMachineId(string $machineId): bool
    {
        try {
            $json = json_encode(['machine_id' => $machineId]);
            $this->writeFile($this->getBasePath() . self::MACHINE_ID_FILE, $json);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function storePassword(string $password): bool
    {
        try {
            $json = json_encode(['password' => $password]);
            $this->writeFile($this->getBasePath() . self::PASSWORD_FILE, $json);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function storeScenarios(array $scenarios): bool
    {
        try {
            $json = json_encode(['scenarios' => $scenarios]);
            $this->writeFile($this->getBasePath() . self::SCENARIOS_FILE, $json);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function storeToken(string $token): bool
    {
        try {
            $json = json_encode(['token' => $token]);
            $this->writeFile($this->getBasePath() . self::TOKEN_FILE, $json);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    private function getBasePath(): string
    {
        return $this->rootDir . '/' . $this->env . '-';
    }

    /**
     * Read the content of some file.
     *
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    private function readFile(string $file): array
    {
        $result = [];
        $string = @file_get_contents($file);
        if (false === $string) {
            return $result;
        }
        $json = json_decode($string, true);
        if (null === $json) {
            return $result;
        }

        return $json;
    }

    /**
     * Write some content in a file.
     */
    private function writeFile(string $filepath, string $content): void
    {
        $file = fopen($filepath, 'w');
        fwrite($file, $content);
        fclose($file);
    }
}
