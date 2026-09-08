<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsChatEnabled
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('ai-chat.enabled')) {
            abort(404);
        }

        return $next($request);
    }
}
