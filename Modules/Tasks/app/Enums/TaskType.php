<?php

namespace Modules\Tasks\Enums;

enum TaskType: string
{
    case Task = 'task';
    case Bug = 'bug';
    case Story = 'story';
    case Subtask = 'subtask';
}
