<?php
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Data\ReorderTaskData;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Exceptions\TaskStatusConflict;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Services\TaskRankService;
beforeEach(function(){ $this->seed(RolePermissionSeeder::class); });
function rankContext(): array { $manager=User::factory()->asProjectManager()->create(); $member=User::factory()->asMember()->create(); $p=Project::factory()->active()->create(['owner_id'=>$manager->id]); $other=Project::factory()->active()->create(['owner_id'=>$manager->id]); app(ProjectMemberService::class)->addMember($p,$member,ProjectMemberRole::Member,actor:$manager); $a=Task::factory()->for($p)->for($manager,'creator')->create(['status'=>TaskStatus::Backlog,'rank'=>1000]); $b=Task::factory()->for($p)->for($manager,'creator')->create(['status'=>TaskStatus::Backlog,'rank'=>2000]); $c=Task::factory()->for($p)->for($manager,'creator')->create(['status'=>TaskStatus::Backlog,'rank'=>3000,'assignee_id'=>$member->id]); return [$manager,$member,$p,$other,$a,$b,$c]; }
test('manager neighbor reorder is versioned and deterministic',function(){ [$manager,,$p,,$a,$b,$c]=rankContext(); $service=app(TaskRankService::class); $moved=$service->reorder($c->load('project'),new ReorderTaskData($a->id,$b->id,$c->version),$manager); expect(Task::query()->where('project_id',$p->id)->orderBy('rank')->pluck('id')->all())->toBe([$c->id,$a->id,$b->id])->and($moved->version)->toBe($c->version+1); expect(fn()=> $service->reorder($moved->load('project'),new ReorderTaskData(null,null,$c->version),$manager))->toThrow(TaskStatusConflict::class); });
test('cross project and non-manager reorder are rejected while status move appends to destination',function(){ [$manager,$member,$p,$other,$a,$b,$c]=rankContext(); $foreign=Task::factory()->for($other)->for($manager,'creator')->create(['status'=>TaskStatus::Backlog,'rank'=>1000]); $service=app(TaskRankService::class); expect(fn()=> $service->reorder($a->load('project'),new ReorderTaskData($foreign->id,null,$a->version),$manager))->toThrow(LogicException::class)->and(fn()=> $service->reorder($a->load('project'),new ReorderTaskData(null,null,$a->version),$member))->toThrow(LogicException::class); $this->actingAs($member)->patch(route('tasks.status',$c),['status'=>'todo','expected_version'=>$c->version])->assertRedirect(); expect($c->fresh()->rank)->toBe(1000); });
test('API reorder and backlog pagination preserve rank independently of priority',function(){ [$manager,,$p,,$a,$b,$c]=rankContext(); Sanctum::actingAs($manager,['tasks:read','tasks:write']); $this->patchJson('/api/v1/tasks/'.$c->id.'/rank',['before_task_id'=>$a->id,'after_task_id'=>$b->id,'expected_version'=>$c->version])->assertOk()->assertJsonPath('data.rank',1000); $this->getJson('/api/v1/projects/'.$p->id.'/backlog?per_page=2')->assertOk()->assertJsonCount(2,'data'); expect($a->fresh()->priority)->not->toBeNull()->and($a->fresh()->rank)->not->toBe($a->fresh()->priority->value); });
