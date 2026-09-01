<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskComment;
use Modules\Tasks\Policies\TaskPolicy;
use Modules\Tasks\Services\TaskAttachmentService;
use Modules\Tasks\Services\TaskCommentService;

beforeEach(function (): void { $this->seed(RolePermissionSeeder::class); });

test('project members can view and create active-project work while outsiders cannot', function () {
    $owner=User::factory()->asProjectManager()->create(); $member=User::factory()->asMember()->create(); $outsider=User::factory()->asMember()->create();
    $project=Project::factory()->active()->create(['owner_id'=>$owner->id]); app(ProjectMemberService::class)->addMember($project,$member,ProjectMemberRole::Member,actor:$owner);
    $task=Task::factory()->for($project)->create(); $policy=app(TaskPolicy::class);
    expect($policy->view($member,$task))->toBeTrue()->and($policy->create($member,$project))->toBeTrue()->and($policy->view($outsider,$task))->toBeFalse()->and($policy->create($outsider,$project))->toBeFalse();
});

test('completed and archived projects reject comment and attachment mutations', function (ProjectStatus $status) {
    $owner=User::factory()->asProjectManager()->create(); $project=Project::factory()->create(['owner_id'=>$owner->id,'status'=>$status]); $task=Task::factory()->for($project)->create(); $comment=TaskComment::factory()->for($task)->for($owner)->create();
    expect(fn()=>app(TaskCommentService::class)->create($task,$owner,'blocked'))->toThrow(LogicException::class);
    expect(fn()=>app(TaskCommentService::class)->delete($comment,$owner))->toThrow(LogicException::class);
    expect(fn()=>app(TaskAttachmentService::class)->upload($task,$owner,UploadedFile::fake()->create('x.txt',1,'text/plain')))->toThrow(LogicException::class);
})->with([[ProjectStatus::Completed],[ProjectStatus::Archived]]);
