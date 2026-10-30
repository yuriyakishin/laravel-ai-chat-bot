<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Broadcast;
use Yu\AiChatBot\Support\WidgetVisitor;

class BroadcastingAuthController extends Controller
{
    /**
     * @param WidgetVisitor $visitor
     */
    public function __construct(private WidgetVisitor $visitor)
    {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'socket_id' => 'required|string',
            'channel_name' => 'required|string',
        ]);

        $conversation = $this->visitor->activeConversation($this->visitor->owner($request));

        abort_unless(
            $conversation && $request->input('channel_name') === 'private-ai-chat.conversation.' . $conversation->uuid,
            403
        );

        return response()->json(Broadcast::driver()->validAuthenticationResponse($request, true));
    }
}
