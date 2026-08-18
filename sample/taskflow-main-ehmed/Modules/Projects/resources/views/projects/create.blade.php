@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-6">
        <h1 class="mb-6 text-2xl font-bold">Create Project</h1>

        <form
            method="POST"
            action="{{ route('projects.store') }}"
            class="space-y-4"
        >
            @csrf

            <div>
                <label for="name" class="mb-1 block">
                    Name
                </label>

                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    class="w-full rounded border px-3 py-2"
                >

                @error('name')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="slug" class="mb-1 block">
                    Slug
                </label>

                <input
                    id="slug"
                    name="slug"
                    type="text"
                    value="{{ old('slug') }}"
                    class="w-full rounded border px-3 py-2"
                >

                @error('slug')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="description" class="mb-1 block">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    class="w-full rounded border px-3 py-2"
                >{{ old('description') }}</textarea>

                @error('description')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="status" class="mb-1 block">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                    class="w-full rounded border px-3 py-2"
                >
                    @foreach (['draft', 'active', 'completed', 'archived'] as $status)
                        <option
                            value="{{ $status }}"
                            @selected(old('status', 'draft') === $status)
                        >
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>

                @error('status')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="starts_at" class="mb-1 block">
                    Starts at
                </label>

                <input
                    id="starts_at"
                    name="starts_at"
                    type="datetime-local"
                    value="{{ old('starts_at') }}"
                    class="w-full rounded border px-3 py-2"
                >

                @error('starts_at')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="due_at" class="mb-1 block">
                    Due at
                </label>

                <input
                    id="due_at"
                    name="due_at"
                    type="datetime-local"
                    value="{{ old('due_at') }}"
                    class="w-full rounded border px-3 py-2"
                >

                @error('due_at')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button
                    type="submit"
                    class="rounded bg-black px-4 py-2 text-white"
                >
                    Create
                </button>

                <a
                    href="{{ route('projects.index') }}"
                    class="rounded border px-4 py-2"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection