@extends('layouts.app')

@section('title', 'Project Discussions — '.$project->name)

@section('content')
    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-gray-50 to-gray-100/50"></div>

    <div class="w-full px-4">
        @include('discussion.partials.project._index')
    </div>
@endsection
