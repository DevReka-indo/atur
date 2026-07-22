<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectTemplateCategoryRequest;
use App\Http\Requests\UpdateProjectTemplateCategoryRequest;
use App\Models\ProjectTemplateCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectTemplateCategoryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('project-template-categories.view');

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $categories = ProjectTemplateCategory::query()
            ->with('creator:id,name')
            ->withCount(['templates as templates_count' => fn ($query) => $query->withTrashed()])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")
            ))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $status === 'active'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('project-template-categories.index', compact('categories', 'search', 'status'));
    }

    public function create(): View
    {
        Gate::authorize('project-template-categories.create');

        return view('project-template-categories.create');
    }

    public function store(StoreProjectTemplateCategoryRequest $request): RedirectResponse
    {
        Gate::authorize('project-template-categories.create');
        $validated = $request->validated();

        DB::transaction(fn (): ProjectTemplateCategory => ProjectTemplateCategory::query()->create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]));

        return redirect()->route('project-template-categories.index')
            ->with('success', 'Kategori template berhasil dibuat.');
    }

    public function edit(ProjectTemplateCategory $projectTemplateCategory): View
    {
        Gate::authorize('project-template-categories.update');

        return view('project-template-categories.edit', ['category' => $projectTemplateCategory]);
    }

    public function update(
        UpdateProjectTemplateCategoryRequest $request,
        ProjectTemplateCategory $projectTemplateCategory
    ): RedirectResponse {
        Gate::authorize('project-template-categories.update');
        $validated = $request->validated();

        DB::transaction(function () use ($projectTemplateCategory, $validated): void {
            $projectTemplateCategory->update([
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['name'], $projectTemplateCategory),
                'description' => $validated['description'] ?? null,
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()->route('project-template-categories.index')
            ->with('success', 'Kategori template berhasil diperbarui.');
    }

    public function toggleStatus(Request $request, ProjectTemplateCategory $projectTemplateCategory): RedirectResponse
    {
        Gate::authorize('project-template-categories.update');

        DB::transaction(fn () => $projectTemplateCategory->update([
            'is_active' => ! $projectTemplateCategory->is_active,
            'updated_by' => Auth::id(),
        ]));

        $previousUrl = url()->previous();
        $previousScheme = parse_url($previousUrl, PHP_URL_SCHEME);
        $previousHost = parse_url($previousUrl, PHP_URL_HOST);
        $previousPort = parse_url($previousUrl, PHP_URL_PORT)
            ?? ($previousScheme === 'https' ? 443 : 80);

        $isSafePreviousUrl = is_string($previousScheme)
            && is_string($previousHost)
            && hash_equals($request->getScheme(), $previousScheme)
            && hash_equals($request->getHost(), $previousHost)
            && $request->getPort() === $previousPort;

        $redirect = $isSafePreviousUrl
            ? redirect()->to($previousUrl)
            : redirect()->route('project-template-categories.index');

        return $redirect->with('success', 'Status kategori template berhasil diperbarui.');
    }

    public function destroy(ProjectTemplateCategory $projectTemplateCategory): RedirectResponse
    {
        Gate::authorize('project-template-categories.delete');

        DB::transaction(function () use ($projectTemplateCategory): void {
            $lockedCategory = ProjectTemplateCategory::query()->lockForUpdate()->findOrFail($projectTemplateCategory->id);
            if ($lockedCategory->hasTemplatesIncludingTrashed()) {
                throw ValidationException::withMessages([
                    'category' => 'Kategori yang pernah memiliki template tidak dapat dihapus. Nonaktifkan kategori sebagai gantinya.',
                ]);
            }

            $lockedCategory->delete();
        });

        return redirect()->route('project-template-categories.index')
            ->with('success', 'Kategori template berhasil dihapus.');
    }

    private function uniqueSlug(string $name, ?ProjectTemplateCategory $ignore = null): string
    {
        $baseSlug = Str::slug($name) ?: 'kategori-template';
        $slug = $baseSlug;
        $suffix = 2;

        while (ProjectTemplateCategory::withTrashed()
            ->where('slug', $slug)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
