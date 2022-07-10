<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Parser;

use Neomerx\JsonApi\Contracts\Parser\DocumentDataInterface;
use Neomerx\JsonApi\Contracts\Schema\PositionInterface;

final class ParsedDocumentData implements DocumentDataInterface
{
    private PositionInterface $position;
    private bool $isCollection;
    private bool $isNull;

    public function __construct(
        PositionInterface $position,
        bool $isCollection,
        bool $isNull
    ) {
        $this->position = $position;
        $this->isCollection = $isCollection;
        $this->isNull = $isNull;
    }

    public function getPosition(): PositionInterface
    {
        return $this->position;
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    public function isNull(): bool
    {
        return $this->isNull;
    }
}
