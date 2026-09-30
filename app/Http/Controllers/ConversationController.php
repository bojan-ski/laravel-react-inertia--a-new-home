<?php

namespace App\Http\Controllers;

use App\Http\Requests\Conversation\StartConversationRequest;
use App\Http\Requests\Conversation\StoreChatMessageRequest;
use App\Models\Conversation;
use App\Models\Listing;
use App\Services\ConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function __construct(
        private ConversationService $conversationService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Conversations/Index', [
            'conversations' => $this->conversationService->getInboxFor($request->user()),
            'translations' => __('app.conversations'),
        ]);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        Gate::authorize('view', $conversation);

        $this->conversationService->markRead($conversation, $request->user());

        $conversation->load([
            ...ConversationService::LISTING_SUMMARY_RELATIONS,
            'buyer:id,name',
            'owner:id,name',
            'owner.agency:id,user_id,name',
            'messages.sender:id,name',
        ]);

        return Inertia::render('Conversations/Show', [
            'conversation' => $conversation->toShowArray($request->user()),
            'conversations' => fn () => $this->conversationService->getInboxFor($request->user()),
            'canReply' => Gate::allows('sendMessage', $conversation),
            'translations' => __('app.conversations'),
        ]);
    }

    public function store(StartConversationRequest $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('start', [Conversation::class, $listing]);

        $conversation = $this->conversationService->startOrGet(
            $listing,
            $request->user(),
            $request->input('message'),
        );

        return redirect()->route('conversations.show', $conversation);
    }

    public function sendMessage(StoreChatMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        Gate::authorize('sendMessage', $conversation);

        $this->conversationService->sendMessage(
            $conversation,
            $request->user(),
            $request->string('body')->toString(),
        );

        return redirect()->route('conversations.show', $conversation);
    }
}
