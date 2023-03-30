<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Uid\Factory;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV3;
use Symfony\Component\Uid\UuidV5;

class NameBasedUuidFactory
{
    private $class;
    private $namespace;

    public function __construct(string $class, Uuid $namespace)
    {
        $this->class = $class;
        $this->namespace = $namespace;
    }

    /**
     * @return UuidV5|UuidV3
     */
    public function create(string $name): Uuid
    {
        switch ($class = $this->class) {
            case UuidV5::class: return Uuid::v5($this->namespace, $name);
            case UuidV3::class: return Uuid::v3($this->namespace, $name);
        }

        if (is_subclass_of($class, UuidV5::class)) {
            $uuid = Uuid::v5($this->namespace, $name);
        } else {
            $uuid = Uuid::v3($this->namespace, $name);
        }

        return new $class($uuid);
    }
}
