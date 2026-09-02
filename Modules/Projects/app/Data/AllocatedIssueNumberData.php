<?php

namespace Modules\Projects\Data;

final readonly class AllocatedIssueNumberData
{
    public function __construct(
        public int $issueNumber,
        public string $displayKey,
    ) {}
}
