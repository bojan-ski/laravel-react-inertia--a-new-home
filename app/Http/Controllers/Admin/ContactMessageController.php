<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminContactMessageIndexRequest;
use App\Models\ContactMessage;
use App\Services\ContactMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function __construct(private ContactMessageService $service) {}

    public function index(AdminContactMessageIndexRequest $request): Response
    {
        Gate::authorize('viewAny', ContactMessage::class);

        $status = $request->status();

        return Inertia::render('Admin/ContactMessages/Index', [
            'messages' => $this->service->paginateForAdmin($status === 'handled'),
            'status' => $status,
            'translations' => __('app.admin.contact_messages'),
        ]);
    }

    public function markHandled(ContactMessage $contactMessage, Request $request): RedirectResponse
    {
        Gate::authorize('markHandled', $contactMessage);

        $this->service->markHandled($contactMessage, $request->user());

        return back()->with('success', __('app.admin.contact_messages.flash.handled'));
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        Gate::authorize('delete', $contactMessage);

        $this->service->delete($contactMessage);

        return back()->with('success', __('app.admin.contact_messages.flash.deleted'));
    }
}
