@extends('layouts.app')

@section('title', 'Projects')
@section('page-title', 'Projects')

@section('content')
    <section class="flex flex-col justify-between gap-5 rounded-3xl bg-slate-950 p-6 text-white shadow-xl shadow-slate-900/10 sm:flex-row sm:items-end sm:p-8">
        <div class="max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-indigo-300">Project workspace</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight">Bring purposeful work into focus.</h2>
            <p class="mt-3 text-sm leading-6 text-slate-300">Create a project, define its dates, and build a shared foundation for the work ahead.</p>
        </div>
        @can('create', Modules\Projects\Models\Project::class)
            <a href="{{ route('projects.create') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-semibold text-slate-950 shadow-lg transition hover:bg-slate-100">New project</a>
        @endcan
    </section>

    <section class="mt-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('projects.index') }}" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem_auto]">
            <label class="sr-only" for="q">Search projects</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Search projects" class="rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15">
            <label class="sr-only" for="status">Status</label>
            <select id="status" name="status" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700">Filter</button>
        </form>
    </section>

    @if ($projects->isEmpty())
        <section class="mt-7 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
            <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-indigo-100 text-lg font-bold text-indigo-700">P</span>
            <h3 class="mt-5 text-lg font-semibold text-slate-950">No projects yet</h3>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Create your first project to start organizing work, ownership, and delivery dates.</p>
            @can('create', Modules\Projects\Models\Project::class)
                <a href="{{ route('projects.create') }}" class="mt-6 inline-flex rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">Create your first project</a>
            @endcan
        </section>
    @else
        <section class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-4">Project</th><th class="px-6 py-4">Owner</th><th class="px-6 py-4">Status</th><th class="px-6 py-4">Timeline</th><th class="px-6 py-4"><span class="sr-only">Open</span></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($projects as $project)
                            @php($badge = match ($project->status?->value) { 'draft' => 'bg-slate-100 text-slate-600', 'active' => 'bg-sky-100 text-sky-700', 'completed' => 'bg-emerald-100 text-emerald-700', 'archived' => 'bg-amber-100 text-amber-700', default => 'bg-slate-100 text-slate-600' })
                            <tr class="transition hover:bg-slate-50"><td class="px-6 py-5"><a href="{{ route('projects.show', $project) }}" class="font-semibold text-slate-950 hover:text-indigo-600">{{ $project->name }}</a><p class="mt-1 max-w-sm truncate text-xs text-slate-500">{{ $project->description ?: 'No description yet.' }}</p></td><td class="px-6 py-5 text-slate-600">{{ $project->owner->name ?: $project->owner->email }}</td><td class="px-6 py-5"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">{{ ucfirst($project->status->value) }}</span></td><td class="px-6 py-5 text-xs leading-5 text-slate-500">{{ $project->starts_at?->format('M j, Y') ?? 'No start date' }}<br>{{ $project->due_at?->format('M j, Y') ?? 'No due date' }}</td><td class="px-6 py-5 text-right"><a href="{{ route('projects.show', $project) }}" class="font-semibold text-indigo-600 hover:text-indigo-500">Open</a></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $projects->links() }}</div>
        </section>
    @endif
@endsection
