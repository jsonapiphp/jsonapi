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

use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class Error implements ErrorInterface
{
    /**
     * @var int|string|null
     */
    private $index;

    /**
     * @var iterable|null
     */
    private $links;

    private ?iterable $typeLinks = null;

    private ?string $status = null;

    private ?string $code = null;

    private ?string $title = null;

    private ?string $detail = null;

    private ?array $source;

    private ?bool $hasMeta = null;

    /**
     * @var mixed
     */
    private $meta;

    /**
     * @param int|string|null $idx
     * @param mixed           $meta
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.IfStatementAssignment)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $idx = null,
        LinkInterface $aboutLink = null,
        ?iterable $typeLinks = null,
        string $status = null,
        string $code = null,
        string $title = null,
        string $detail = null,
        array $source = null,
        bool $hasMeta = false,
        $meta = null
    ) {
        $this
            ->setId($idx)
            ->setLink(DocumentInterface::KEYWORD_ERRORS_ABOUT, $aboutLink)
            ->setTypeLinks($typeLinks)
            ->setStatus($status)
            ->setCode($code)
            ->setTitle($title)
            ->setDetail($detail)
            ->setSource($source);

        if (($this->hasMeta = $hasMeta) === true) {
            $this->setMeta($meta);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->index;
    }

    /**
     * @param string|int|null $index
     */
    public function setId($index): self
    {
        \assert(null === $index || true === \is_int($index) || true === \is_string($index));

        $this->index = $index;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLinks(): ?iterable
    {
        return $this->links;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeLinks(): ?iterable
    {
        return $this->typeLinks;
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function setLink(string $name, ?LinkInterface $link): self
    {
        if (null !== $link) {
            $this->links[$name] = $link;
        } else {
            unset($this->links[$name]);
        }

        return $this;
    }

    public function setTypeLinks(?iterable $typeLinks): self
    {
        $this->typeLinks = $typeLinks;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(?string $detail): self
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource(): ?array
    {
        return $this->source;
    }

    public function setSource(?array $source): self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasMeta(): bool
    {
        return $this->hasMeta;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @param mixed|null $meta
     */
    public function setMeta($meta): self
    {
        $this->hasMeta = true;
        $this->meta = $meta;

        return $this;
    }
}
