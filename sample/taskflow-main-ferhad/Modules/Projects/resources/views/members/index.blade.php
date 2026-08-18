@extends('layouts.app')

@section('title', 'Project members')
@section('page-title', 'Project members')

@section('content')
<div class="mx-auto max-w-5xl">
    <section class="rounded-3xl bg-slate-950 p-7 text-white shadow-xl shadow-slate-900/10">
        <a href="{{ route('projects.show', $project) }}" class="text-sm font-semibold text-indigo-300 hover:text-white">← {{ $project->name }}</a>
        <h2 class="mt-5 text-3xl font-semibold">People who can move this project forward.</h2>
        <p class="mt-2 text-sm text-slate-300">Owner: {{ $project->owner->name ?: $project->owner->email }}</p>
    </section>
    <section class="mt-7 grid gap-7 lg:grid-cols-[0.9fr_1.1fr]">
        <form method="POST" action="{{ route('projects.members.store', $project) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@csrf
            <h3 class="text-lg font-semibold">Add a member</h3><p class="mt-1 text-sm text-slate-500">Choose from existing TaskFlow users.</p>
            <label class="mt-5 block text-sm font-semibold">User<select name="user_id" required class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5">@foreach($availableUsers as $user)<option value="{{ $user->id }}">{{ $user->name ?: $user->email }} — {{ $user->email }}</option>@endforeach</select></label>
            <label class="mt-4 block text-sm font-semibold">Project role<select name="member_role" required class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5">@foreach($roles as $role)<option value="{{ $role->value }}">{{ ucfirst($role->value) }}</option>@endforeach</select></label>
            @error('user_id')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror @error('member_role')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            <button @disabled($availableUsers->isEmpty()) class="mt-6 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Add member</button>
        </form>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-6 py-4"><h3 class="font-semibold">Members ({{ $memberships->count() }})</h3></div><div class="divide-y divide-slate-100">@foreach($memberships as $membership)<div class="flex items-center justify-between gap-4 px-6 py-4"><div><p class="font-semibold text-slate-900">{{ $membership->user->name ?: $membership->user->email }}</p><p class="text-xs text-slate-500">{{ $membership->user->email }} · Joined {{ $membership->joined_at?->format('M j, Y') ?? 'today' }}</p></div><div class="flex items-center gap-3"><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ ucfirst($membership->member_role->value) }}</span>@if($membership->user_id !== $project->owner_id)<form method="POST" action="{{ route('projects.members.destroy', [$project, $membership->user]) }}">@csrf @method('DELETE')<button class="text-sm font-semibold text-rose-600 hover:text-rose-700">Remove</button></form>@endif</div></div>@endforeach</div></section>
    </section>
</div>
@endsection
