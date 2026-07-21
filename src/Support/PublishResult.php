<?php

namespace Corestack\ArchSupport\Support;

class PublishResult
{
    public function __construct(
        public readonly string $status,
        public readonly string $target,
    ) {
    }
}
