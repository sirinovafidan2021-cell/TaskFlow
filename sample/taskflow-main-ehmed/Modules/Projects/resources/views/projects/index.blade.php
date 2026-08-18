@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Projects</h1>

            <a
                href="{{ route('projects.create') }}"
                class="rounded bg-black px-4 py-2 text-white"
            >
                Create Project
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded bg-green-100 p-3">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden rounded border">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b text-left">
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Due date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($projects as $project)
                        <tr class="border-b">
                            <td class="px-4 py-3">
                                {{ $project->name }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $project->status }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $project->due_at?->format('Y-m-d') ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <a
                                    href="{{ route('projects.show', $project) }}"
                                    class="underline"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center">
                                No projects found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $projects->links() }}
        </div>
    </div>
@endsection