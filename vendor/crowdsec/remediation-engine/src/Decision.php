<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine;

class Decision
{
    public const ID_SEP = '-';
    /**
     * @var int
     */
    private $expiresAt;
    /**
     * @var string
     */
    private $identifier;
    /**
     * @var string
     */
    private $origin;
    /**
     * @var string
     */
    private $scope;
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $value;

    public function __construct(
        string $identifier,
        string $scope,
        string $value,
        string $type,
        string $origin,
        int $expiresAt
    ) {
        $this->identifier = $identifier;
        $this->scope = $scope;
        $this->value = $value;
        $this->type = $type;
        $this->origin = $origin;
        $this->expiresAt = $expiresAt;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): Decision
    {
        $this->scope = $scope;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Decision
    {
        $this->value = $value;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'identifier' => $this->getIdentifier(),
            'origin' => $this->getOrigin(),
            'scope' => $this->getScope(),
            'value' => $this->getValue(),
            'type' => $this->getType(),
            'expiresAt' => $this->getExpiresAt(),
        ];
    }
}
