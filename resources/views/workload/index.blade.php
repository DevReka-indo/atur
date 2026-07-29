@extends('layouts.app')

@section('title', 'Workload Monitoring')

@section('content')
    <main class="w-full px-4 py-4 md:px-8" data-workload-page>
        @include('workload.partials._header')
        @include('workload.partials._filters')
        @include('workload.partials._summary')
        @include('workload.partials._member-list')
    </main>

    @include('workload.partials._calculation-modal')
    @include('workload.partials._member-detail-modal')
@endsection
