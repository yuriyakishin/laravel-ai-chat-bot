<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Yu\AiChatBot\Models\Conversation;

class WidgetVisitor
{
    /**
     * @param Request $request
     * @return object
     */
    public function owner(Request $request): object
    {
        if (Auth::check()) {
            return Auth::user();
        }

        $visitorId = $request->cookie('ai_chat_visitor_id');

        if (!$visitorId) {
            $visitorId = Str::uuid()->toString();
            Cookie::queue(Cookie::forever('ai_chat_visitor_id', $visitorId));
        }

        return new AnonymousOwner($visitorId);
    }

    /**
     * @param object $owner
     * @return Conversation|null
     */
    public function activeConversation(object $owner): ?Conversation
    {
        return Conversation::where('owner_type', $owner->getMorphClass())
            ->where('owner_id', (string)$owner->getKey())
            ->where('status', '!=', 'closed')
            ->latest('id')
            ->first();
    }
}
