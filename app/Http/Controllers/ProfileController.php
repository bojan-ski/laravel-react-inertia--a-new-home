<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $listings = $request->user()->listings()
            ->with('images')
            ->latest()
            ->get()
            ->map(fn (Listing $listing): array => [
                ...$listing->toCardArray(),
                'status' => $listing->status->value,
                'createdAt' => $listing->created_at->toIso8601String(),
                'rejectionReason' => $listing->rejection_reason,
            ]);

        return Inertia::render('Profile/Index', [
            'listings' => $listings,
            'translations' => __('app.profile'),
            'propertyTypeLabels' => __('app.listings.filters.property_types'),
        ]);
    }
}
