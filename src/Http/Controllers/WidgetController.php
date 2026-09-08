<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Yu\AiChatBot\Facades\AiChat;
use Yu\AiChatBot\Models\Conversation;
use Yu\AiChatBot\Support\AnonymousOwner;

class WidgetController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function messages(Request $request): JsonResponse
    {
        $owner = $this->resolveOwner($request);

        $conversation = Conversation::where('owner_type', $owner->getMorphClass())
            ->where('owner_id', (string)$owner->getKey())
            ->first();

        $messages = $conversation ? $conversation->messages()
            ->whereIn('role', ['user', 'assistant', 'admin'])
            ->orderBy('id')->get(['id', 'role', 'content', 'created_at']) : collect();

        return response()->json(['messages' => $messages]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:4000']);

        $owner = $this->resolveOwner($request);
        $uuid = $this->resolveConversationUuid($owner);

        $result = AiChat::send($owner, $uuid, $request->string('message')->toString());

        return response()->json(['reply' => $result->reply()]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function reset(Request $request): JsonResponse
    {
        Cookie::queue(Cookie::forget('ai_chat_visitor_id'));

        return response()->json(['status' => 'ok']);
    }

    /**
     * @param Request $request
     * @return object
     */
    private function resolveOwner(Request $request): object
    {
        if (Auth::check()) {
            return Auth::user();
        }

        $visitorId = $request->cookie('ai_chat_visitor_id');

        if (!$visitorId) {
            $visitorId = (string)Str::uuid();
            Cookie::queue(Cookie::forever('ai_chat_visitor_id', $visitorId));
        }

        return new AnonymousOwner($visitorId);
    }

    /**
     * @param object $owner
     * @return string
     */
    private function resolveConversationUuid(object $owner): string
    {
        $conversation = Conversation::where('owner_type', $owner->getMorphClass())
            ->where('owner_id', (string)$owner->getKey())
            ->first();

        return $conversation?->uuid ?? (string)Str::uuid();
    }
}
