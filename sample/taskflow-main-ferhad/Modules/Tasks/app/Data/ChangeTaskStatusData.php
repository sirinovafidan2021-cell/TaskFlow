<?php

namespace Modules\Tasks\Data;

use Modules\Tasks\Enums\TaskStatus;

final readonly class ChangeTaskStatusData { public function __construct(public TaskStatus $status) {} }
