<?php

namespace Modules\Projects\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';
}
