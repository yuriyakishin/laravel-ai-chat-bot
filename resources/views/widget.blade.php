@if(!config('ai-chat.enabled'))
    @php
        return;
    @endphp
@endif
<div class="ai-chat-widget" data-csrf="{{ csrf_token() }}" data-route-prefix="{{ config('ai-chat.route_prefix') }}" data-welcome="{{ config('ai-chat.welcome_message') }}">
    <button type="button" class="ai-chat-bubble" id="ai-chat-bubble" aria-label="Open chat">
        <span class="ai-chat-bubble-dot"></span>
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
        </svg>
    </button>

    <div class="ai-chat-panel" id="ai-chat-panel">
        <div class="ai-chat-header">
            <div class="ai-chat-avatar">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
            </div>
            <div class="ai-chat-header-text">
                <div class="ai-chat-title">Assistant</div>
                <div class="ai-chat-status">Online now</div>
            </div>
            <button type="button" class="ai-chat-clear" id="ai-chat-clear" aria-label="Clear chat">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"></path>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <path d="M10 11v6"></path>
                    <path d="M14 11v6"></path>
                </svg>
            </button>
            <button type="button" class="ai-chat-close" id="ai-chat-close" aria-label="Close chat">&times;</button>
        </div>
        <div class="ai-chat-messages" id="ai-chat-messages"></div>
        <form class="ai-chat-form" id="ai-chat-form">
            <textarea class="ai-chat-input" id="ai-chat-input" rows="1" placeholder="Type a message..." autocomplete="off"></textarea>
            <button type="submit" class="ai-chat-send" aria-label="Send">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 2 11 13"></path>
                    <path d="M22 2 15 22l-4-9-9-4 20-7Z"></path>
                </svg>
            </button>
        </form>
    </div>
</div>
<link rel="stylesheet" href="{{ asset('vendor/ai-chat/widget.css') }}">
@if(config('ai-chat.widget_theme') && config('ai-chat.widget_theme') !=='default')
    <link rel="stylesheet" href="{{ asset('vendor/ai-chat/themes/' . config('ai-chat.widget_theme') . '.css') }}">
@endif
<script src="{{ asset('vendor/ai-chat/widget.js') }}" defer></script>
