<?php

namespace App\Events;

class FileDeleted
{
    public function __construct(
        public readonly string $originalName,
        public readonly string $reason,
        public readonly int $size,
        public readonly string $deletedAt,
    ) {}
}
