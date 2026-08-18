@extends('layouts.app')

@section('title', $project->name)

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">
                {{ $project->name }}
            </h1>

            <div class="flex gap-3">
                <a
                    href="{{ route('projects.index') }}"
                    class="rounded border px-4 py-2"
                >
                    Back to projects
                </a>

                <a
                    href="{{ route('projects.edit', $project) }}"
                    class="rounded border px-4 py-2"
                >
                    Edit
                </a>

                @if ($project->status !== 'archived')
                    <form
                        method="POST"
                        action="{{ route('projects.archive', $project) }}"
                        class="inline"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            class="rounded border px-4 py-2"
                        >
                            Archive
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded bg-green-100 p-3">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-4 rounded border p-6">
            <div>
                <strong>Slug:</strong>
                {{ $project->slug }}
            </div>

            <div>
                <strong>Status:</strong>
                {{ $project->status }}
            </div>

            <div>
                <strong>Description:</strong>

                <p class="mt-1">
                    {{ $project->description ?? '—' }}
                </p>
            </div>

            <div>
                <strong>Starts:</strong>
                {{ $project->starts_at?->format('Y-m-d H:i') ?? '—' }}
            </div>

            <div>
                <strong>Due:</strong>
                {{ $project->due_at?->format('Y-m-d H:i') ?? '—' }}
            </div>
        </div>
    </div>
@endsection