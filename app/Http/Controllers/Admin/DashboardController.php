<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminStatsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private AdminStatsService $stats,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => $this->stats->dashboardStats(),
            'translations' => __('app.admin.dashboard'),
        ]);
    }
}
