<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Schema;

/*
 * Copyright 2015-2020 info@neomerx.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Closure;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaInterface;
use Neomerx\JsonApi\Exceptions\InvalidArgumentException;
use function Neomerx\JsonApi\I18n\format as _;

class SchemaContainer implements SchemaContainerInterface
{
    /**
     * Message code.
     */
    public const MSG_INVALID_MODEL_TYPE = 'Invalid model type.';

    /**
     * Message code.
     */
    public const MSG_INVALID_SCHEME = 'Schema for type `%s` must be non-empty string, callable or SchemaInterface instance.';

    /**
     * Message code.
     */
    public const MSG_TYPE_REUSE_FORBIDDEN = 'Type should not be used more than once to register a schema (`%s`).';

    private array $providerMapping = [];

    /**
     * @var SchemaInterface[]
     */
    private array $createdProviders = [];

    private \Neomerx\JsonApi\Contracts\Factories\FactoryInterface $factory;

    public function __construct(FactoryInterface $factory, iterable $schemas)
    {
        $this->factory = $factory;
        $this->registerCollection($schemas);
    }

    /**
     * Register provider for resource type.
     *
     * @param string|Closure $schema
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function register(string $type, $schema): void
    {
        if (true === empty($type) || false === \class_exists($type)) {
            throw new InvalidArgumentException(_(static::MSG_INVALID_MODEL_TYPE));
        }

        $isOk = (
            (
                true === \is_string($schema) &&
                false === empty($schema) &&
                true === \class_exists($schema) &&
                true === \in_array(SchemaInterface::class, \class_implements($schema), true)
            ) ||
            \is_callable($schema) ||
            $schema instanceof SchemaInterface
        );
        if (false === $isOk) {
            throw new InvalidArgumentException(_(static::MSG_INVALID_SCHEME, $type));
        }

        if (true === $this->hasProviderMapping($type)) {
            throw new InvalidArgumentException(_(static::MSG_TYPE_REUSE_FORBIDDEN, $type));
        }

        if ($schema instanceof SchemaInterface) {
            $this->setProviderMapping($type, \get_class($schema));
            $this->setCreatedProvider($type, $schema);
        } else {
            $this->setProviderMapping($type, $schema);
        }
    }

    /**
     * Register providers for resource types.
     */
    public function registerCollection(iterable $schemas): void
    {
        foreach ($schemas as $type => $schema) {
            $this->register($type, $schema);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema($resource): SchemaInterface
    {
        \assert($this->hasSchema($resource));

        $resourceType = \get_class($resource);

        return $this->getSchemaByType($resourceType);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSchema($resourceObject): bool
    {
        return true === \is_object($resourceObject) &&
            true === $this->hasProviderMapping(\get_class($resourceObject));
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function getSchemaByType(string $type): SchemaInterface
    {
        if (true === isset($this->createdProviders[$type])) {
            return $this->createdProviders[$type];
        }

        $classNameOrCallable = $this->getProviderMapping($type);
        if (true === \is_string($classNameOrCallable)) {
            $schema = $this->createSchemaFromClassName($classNameOrCallable);
        } else {
            \assert(true === \is_callable($classNameOrCallable));
            $schema = $this->createSchemaFromCallable($classNameOrCallable);
        }
        $this->setCreatedProvider($type, $schema);

        /* @var SchemaInterface $schema */

        return $schema;
    }

    protected function hasProviderMapping(string $type): bool
    {
        return isset($this->providerMapping[$type]);
    }

    /**
     * @return mixed
     */
    protected function getProviderMapping(string $type)
    {
        return $this->providerMapping[$type];
    }

    /**
     * @param string|Closure $schema
     */
    protected function setProviderMapping(string $type, $schema): void
    {
        $this->providerMapping[$type] = $schema;
    }

    protected function setCreatedProvider(string $type, SchemaInterface $provider): void
    {
        $this->createdProviders[$type] = $provider;
    }

    protected function createSchemaFromCallable(callable $callable): SchemaInterface
    {
        $schema = \call_user_func($callable, $this->factory);

        return $schema;
    }

    protected function createSchemaFromClassName(string $className): SchemaInterface
    {
        $schema = new $className($this->factory);

        return $schema;
    }
}
