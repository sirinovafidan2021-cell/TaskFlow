@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <section class="flex flex-col justify-between gap-5 rounded-3xl bg-slate-950 p-7 text-white shadow-xl shadow-slate-900/10 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-indigo-300">Live overview</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight">Work that needs your attention.</h2>
            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300">A concise view of the projects, progress, and work currently visible to you.</p>
        </div>
        <a href="{{ route('tasks.index') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">View tasks</a>
    </section>

    @php
        $metrics = [
            ['label' => 'Active projects', 'value' => $activeProjects, 'tone' => 'text-slate-950'],
            ['label' => 'Archived projects', 'value' => $archivedProjects, 'tone' => 'text-slate-950'],
            ['label' => 'Total tasks', 'value' => $totalTasks, 'tone' => 'text-slate-950'],
            ['label' => 'To do', 'value' => $todo, 'tone' => 'text-slate-950'],
            ['label' => 'In progress', 'value' => $inProgress, 'tone' => 'text-sky-700'],
            ['label' => 'In review', 'value' => $review, 'tone' => 'text-violet-700'],
            ['label' => 'Overdue', 'value' => $overdue, 'tone' => $overdue ? 'text-rose-700' : 'text-slate-950'],
            ['label' => 'Completed today', 'value' => $completedToday, 'tone' => 'text-emerald-700'],
        ];
    @endphp

    <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($metrics as $metric)
            <article @class(['rounded-2xl border bg-white p-5 shadow-sm', 'border-rose-200 bg-rose-50/50' => $metric['label'] === 'Overdue' && $overdue])>
                <p class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-500">{{ $metric['label'] }}</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight {{ $metric['tone'] }}">{{ $metric['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-7 grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-950">My Tasks</h3>
                    <p class="mt-1 text-sm text-slate-500">The next assigned work in your queue.</p>
                </div>
                <a href="{{ route('tasks.index') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-600">All tasks</a>
            </div>
            <div class="mt-5 space-y-3">
                @forelse ($myTasks as $task)
                    <a href="{{ route('tasks.show', $task) }}" class="block rounded-xl border border-slate-200 p-4 transition hover:border-indigo-200 hover:bg-indigo-50/40">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold tracking-wide text-indigo-700">{{ $task->number }}</p>
                                <p class="mt-1 truncate font-semibold text-slate-950">{{ $task->title }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $task->project->name }} · Due {{ $task->due_at?->format('M j, Y') ?? 'not set' }}</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <x-status-badge :status="$task->status" />
                                <x-priority-badge :priority="$task->priority" />
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 px-5 py-10 text-center">
                        <p class="font-semibold text-slate-900">No assigned tasks right now.</p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">When work is assigned to you, it will appear here.</p>
                    </div>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-950">Recent Activity</h3>
                    <p class="mt-1 text-sm text-slate-500">Latest visible changes in your workspace.</p>
                </div>
                @can('activity.view')<a href="{{ route('activity.index') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-600">All activity</a>@endcan
            </div>
            <div class="mt-5 space-y-4">
                @forelse ($recentActivity as $activity)
                    <div class="border-l-2 border-indigo-200 pl-4">
                        <p class="font-semibold text-slate-900">{{ \Modules\Activity\Support\ActivityDisplay::label($activity) }}</p>
                        @if ($summary = \Modules\Activity\Support\ActivityDisplay::summary($activity))
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $summary }}</p>
                        @endif
                        <p class="mt-1 text-xs text-slate-500">{{ $activity->causer?->name ?: 'System' }} · {{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 px-5 py-10 text-center">
                        <p class="font-semibold text-slate-900">No recent activity in your permitted workspace.</p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Visible project and task updates will appear here.</p>
                    </div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mt-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
            <div>
                <h3 class="text-lg font-semibold text-slate-950">Task status overview</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $totalTasks }} visible tasks across your workspace.</p>
            </div>
        </div>
        <div class="mt-6 grid gap-5 md:grid-cols-3">
            @foreach (['To do' => $todo, 'In progress' => $inProgress, 'In review' => $review] as $label => $value)
                <div>
                    <div class="flex items-center justify-between gap-4 text-sm"><span class="font-medium text-slate-700">{{ $label }}</span><span class="font-semibold text-slate-900">{{ $value }}</span></div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-indigo-500" style="width: {{ $totalTasks ? min(100, ($value / $totalTasks) * 100) : 0 }}%"></div></div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
