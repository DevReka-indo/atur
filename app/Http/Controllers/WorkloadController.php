<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkloadIndexRequest;
use App\Models\User;
use App\Services\WorkloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WorkloadController extends Controller
{
    public function __construct(private WorkloadService $workloadService) {}

    public function index(WorkloadIndexRequest $request): View
    {
        Gate::authorize('view-workload-monitoring');

        return view('workload.index', [
            ...$this->workloadService->index($request->user(), $request->validated()),
            'disclaimer' => WorkloadService::DISCLAIMER,
        ]);
    }

    public function detail(WorkloadIndexRequest $request, User $user): JsonResponse
    {
        Gate::authorize('view-workload-monitoring');

        return response()->json(
            $this->workloadService->detail($request->user(), $user, $request->validated()),
        );
    }
}
