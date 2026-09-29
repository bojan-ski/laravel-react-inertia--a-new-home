<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidSavedSearchException;
use App\Http\Requests\StoreSavedSearchRequest;
use App\Http\Requests\UpdateSavedSearchRequest;
use App\Models\SavedSearch;
use App\Services\SavedSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SavedSearchController extends Controller
{
    public function __construct(private SavedSearchService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', SavedSearch::class);

        $searches = $request->user()->savedSearches()
            ->latest()
            ->latest('id')
            ->get()
            ->map(fn (SavedSearch $search): array => [
                ...$search->toManageArray(),
                'summary' => $this->service->summarize($search->filterState()),
            ]);

        return Inertia::render('SavedSearches/Index', [
            'searches' => $searches,
            'maxSearches' => SavedSearch::MAX_PER_USER,
            'translations' => __('app.saved_searches'),
        ]);
    }

    public function store(StoreSavedSearchRequest $request): RedirectResponse
    {
        try {
            $this->service->save(
                $request->user(),
                $request->filters(),
                $request->validated('name'),
                $request->boolean('alerts_enabled', true),
            );
        } catch (InvalidSavedSearchException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('app.saved_searches.flash.saved'));
    }

    public function update(UpdateSavedSearchRequest $request, SavedSearch $savedSearch): RedirectResponse
    {
        $this->service->update($savedSearch, $request->validated());

        return back()->with('success', __('app.saved_searches.flash.updated'));
    }

    public function destroy(SavedSearch $savedSearch): RedirectResponse
    {
        Gate::authorize('delete', $savedSearch);

        $this->service->delete($savedSearch);

        return redirect()->route('saved-searches.index')
            ->with('success', __('app.saved_searches.flash.deleted'));
    }
}
