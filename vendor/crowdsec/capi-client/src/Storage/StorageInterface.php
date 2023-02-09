<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Storage;

/**
 * Storage interface.
 *
 * Must be used to store machine_id, password, token and scenarios
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
interface StorageInterface
{
    /**
     * Retrieve stored machine_id
     * Return null if not found.
     */
    public function retrieveMachineId(): ?string;

    /**
     * Retrieve stored password
     * Return null if not found.
     */
    public function retrievePassword(): ?string;

    /**
     * Retrieve stored scenarios
     * Return null if not found.
     */
    public function retrieveScenarios(): ?array;

    /**
     * Retrieve stored token
     * Return null if not found.
     */
    public function retrieveToken(): ?string;

    /**
     * Store a machine_id
     * Return true when success and false otherwise.
     */
    public function storeMachineId(string $machineId): bool;

    /**
     * Store a password
     * Return true when success and false otherwise.
     */
    public function storePassword(string $password): bool;

    /**
     * Store a list of scenarios
     * Return true when success and false otherwise.
     */
    public function storeScenarios(array $scenarios): bool;

    /**
     * Store a token
     * Return true when success and false otherwise.
     */
    public function storeToken(string $token): bool;
}
