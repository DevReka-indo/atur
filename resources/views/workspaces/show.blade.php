@extends('layouts.app')

@section('title', $workspace->name)

@section('content')
@php
    $user = Auth::user();
    $isOwner = $workspace->isOwner($user);
    $canManageMembers = $workspace->canManageMembers($user);
    $canCreateProject = $workspace->canCreateProject($user);
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ tab: 'projects', showDeleteModal: false }">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $workspace->name }}</h1>
            <p class="text-gray-600 mt-1">{{ $workspace->description ?: 'No description provided.' }}</p>
        </div>
        @if ($isOwner)
            <div class="flex gap-3">
                <a href="{{ route('workspaces.edit', $workspace) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg"><i class="fa-solid fa-pen-to-square mr-2"></i>Edit</a>
                <button @click="showDeleteModal = true" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg"><i class="fa-solid fa-trash mr-2"></i>Delete</button>
            </div>
        @endif
    </div>

    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button @click="tab = 'projects'" :class="tab === 'projects' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500'" class="py-3 px-1 border-b-2 font-medium text-sm">Projects</button>
            <button @click="tab = 'members'" :class="tab === 'members' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500'" class="py-3 px-1 border-b-2 font-medium text-sm">Members</button>
        </nav>
    </div>

    <div x-show="tab === 'projects'" style="display: none;">
        @if ($canCreateProject)
            <div class="mb-4">
                <a href="{{ route('projects.create') }}?workspace_id={{ $workspace->id }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg"><i class="fa-solid fa-plus mr-2"></i>Create Project</a>
            </div>
        @endif
        @if ($workspace->projects->isEmpty())
            <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500">No projects in this workspace yet.</div>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tasks</th><th class="px-4 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($workspace->projects as $project)
                            @php
                                $totalWeight = $project->tasks->sum('weight');
                                $earnedValue = $project->tasks->sum(fn($task) => $task->weight * ($task->statusWeight->weight_value ?? 0));
                                $progress = $totalWeight > 0 ? ($earnedValue / $totalWeight) * 100 : 0;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $project->name }}</td>
                                <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ str($project->status)->replace('_', ' ')->title() }}</span></td>
                                <td class="px-4 py-3 min-w-[180px]"><div class="w-full bg-gray-200 rounded-full h-2.5"><div class="bg-indigo-600 h-2.5 rounded-full" style="width: {{ min(100, max(0, $progress)) }}%"></div></div><p class="text-xs text-gray-600 mt-1">{{ number_format($progress, 1) }}%</p></td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $project->tasks_count }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('projects.show', $project) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div x-show="tab === 'members'" style="display: none;" class="space-y-4">
        @if ($canManageMembers)
            <form method="POST" action="{{ route('workspaces.members.store', $workspace) }}" class="bg-white rounded-lg border border-gray-200 p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                @csrf
                <select name="user_id" class="px-3 py-2 border border-gray-300 rounded-lg" required>
                    <option value="">Select user</option>
                    @foreach($availableUsers as $userOption)
                        <option value="{{ $userOption->id }}">{{ $userOption->name }} ({{ $userOption->email }})</option>
                    @endforeach
                </select>
                <select name="role" class="px-3 py-2 border border-gray-300 rounded-lg" required>
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                </select>
                <button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2"><i class="fa-solid fa-user-plus mr-2"></i>Add Member</button>
            </form>
        @endif

        <div class="space-y-3">
            @foreach ($workspace->members as $member)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold">{{ strtoupper(substr($member->name, 0, 1)) }}</div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $member->name }}</p>
                                <p class="text-sm text-gray-500">{{ $member->job_title ?: 'No title' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($workspace->isOwner($member))
                                <span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800"> <i class="fa-solid fa-crown mr-1"></i>Owner</span>
                            @elseif($canManageMembers)
                                <form method="POST" action="{{ route('workspaces.members.update', [$workspace, $member]) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" class="px-2 py-1 text-sm border border-gray-300 rounded">
                                        <option value="admin" {{ $member->pivot->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="member" {{ $member->pivot->role === 'member' ? 'selected' : '' }}>Member</option>
                                    </select>
                                    <button class="text-indigo-600 text-sm"><i class="fa-solid fa-floppy-disk mr-1"></i>Save</button>
                                </form>
                                <form method="POST" action="{{ route('workspaces.members.destroy', [$workspace, $member]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 text-sm"><i class="fa-solid fa-user-minus mr-1"></i>Remove</button>
                                </form>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full {{ $member->pivot->role === 'admin' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' }}">{{ ucfirst($member->pivot->role) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Project Membership</p>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($workspace->projects as $project)
                                @php $isInProject = $project->members->contains('id', $member->id); @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs {{ $isInProject ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                    <i class="fa-solid {{ $isInProject ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                                    {{ $project->name }}
                                </span>
                            @empty
                                <span class="text-sm text-gray-500">No project available.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($isOwner)
    <div x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
            <div class="bg-white rounded-lg overflow-hidden shadow-xl sm:max-w-lg sm:w-full z-10">
                <div class="px-4 pt-5 pb-4 sm:p-6"><h3 class="text-lg font-medium text-gray-900">Confirm Delete</h3><p class="mt-2 text-sm text-gray-500">Are you sure you want to delete this workspace?</p></div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form method="POST" action="{{ route('workspaces.destroy', $workspace) }}">@csrf @method('DELETE')<button type="submit" class="inline-flex justify-center rounded-md px-4 py-2 bg-red-600 text-white hover:bg-red-700 sm:ml-3"><i class="fa-solid fa-trash mr-2"></i>Delete</button></form>
                    <button @click="showDeleteModal = false" type="button" class="mt-3 sm:mt-0 inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700 hover:bg-gray-50">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
