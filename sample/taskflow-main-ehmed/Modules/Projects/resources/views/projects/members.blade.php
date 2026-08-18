@extends('layouts.app')

@section('title', $project->name . ' — Members')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-6">

        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">
                {{ $project->name }} — Members
            </h1>

            <a
                href="{{ route('projects.show', $project) }}"
                class="rounded border px-4 py-2"
            >
                Back to project
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded bg-green-100 p-3">
                {{ session('success') }}
            </div>
        @endif

        @can('manageMembers', $project)
            <form
                method="POST"
                action="{{ route('projects.members.store', $project) }}"
                class="mb-6 space-y-4 rounded border p-4"
            >
                @csrf

                <h2 class="text-lg font-semibold">
                    Add Member
                </h2>

                <div>
                    <label for="user_id" class="mb-1 block">
                        User ID
                    </label>

                    <input
                        id="user_id"
                        name="user_id"
                        type="number"
                        value="{{ old('user_id') }}"
                        class="w-full rounded border px-3 py-2"
                    >

                    @error('user_id')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="member_role" class="mb-1 block">
                        Member role
                    </label>

                    <input
                        id="member_role"
                        name="member_role"
                        type="text"
                        value="{{ old('member_role') }}"
                        class="w-full rounded border px-3 py-2"
                    >

                    @error('member_role')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="rounded bg-black px-4 py-2 text-white"
                >
                    Add Member
                </button>
            </form>
        @endcan

        <div class="overflow-hidden rounded border">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b text-left">
                        <th class="px-4 py-3">
                            User
                        </th>

                        <th class="px-4 py-3">
                            Role
                        </th>

                        <th class="px-4 py-3">
                            Joined
                        </th>

                        @can('manageMembers', $project)
                            <th class="px-4 py-3"></th>
                        @endcan
                    </tr>
                </thead>

                <tbody>
                    @forelse ($members as $member)
                        <tr class="border-b">
                            <td class="px-4 py-3">
                                {{ $member->user->name }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $member->member_role }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $member->joined_at?->format('Y-m-d') ?? '—' }}
                            </td>

                            @can('manageMembers', $project)
                                <td class="px-4 py-3 text-right">
                                    <form
                                        method="POST"
                                        action="{{ route('projects.members.destroy', [$project, $member]) }}"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="rounded border px-3 py-1"
                                        >
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="{{ auth()->user()->can('manageMembers', $project) ? 4 : 3 }}"
                                class="px-4 py-6 text-center"
                            >
                                No members found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection