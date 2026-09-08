(function () {
    const widget = document.querySelector('.ai-chat-widget');
    const csrfToken = widget.dataset.csrf;
    const routePrefix = widget.dataset.routePrefix;
    const errorMessage = widget.dataset.errorMessage || 'Sorry, something went wrong.';

    const bubble = document.getElementById('ai-chat-bubble');
    const panel = document.getElementById('ai-chat-panel');
    const closeButton = document.getElementById('ai-chat-close');
    const clearButton = document.getElementById('ai-chat-clear');
    const messagesEl = document.getElementById('ai-chat-messages');
    const form = document.getElementById('ai-chat-form');
    const input = document.getElementById('ai-chat-input');

    let historyLoaded = false;
    let lastMessageId = 0;
    let pollTimer = null;

    const avatarSvg = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>';

    function formatTime(isoString) {
        const date = isoString ? new Date(isoString) : new Date();
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function renderMessage(role, content, isoString) {
        const row = document.createElement('div');
        row.className = 'ai-chat-message-row ai-chat-message-row-' + role;

        if (role === 'assistant') {
            const avatar = document.createElement('div');
            avatar.className = 'ai-chat-message-avatar';
            avatar.innerHTML = avatarSvg;
            row.appendChild(avatar);
        }

        const group = document.createElement('div');
        group.className = 'ai-chat-message-group';

        const bubble = document.createElement('div');
        bubble.className = 'ai-chat-message ai-chat-message-' + role;
        bubble.textContent = content;
        group.appendChild(bubble);

        const time = document.createElement('div');
        time.className = 'ai-chat-message-time';
        time.textContent = formatTime(isoString);
        group.appendChild(time);

        row.appendChild(group);
        messagesEl.appendChild(row);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function showTyping() {
        const row = document.createElement('div');
        row.className = 'ai-chat-message-row ai-chat-message-row-assistant';
        row.id = 'ai-chat-typing-indicator';

        const avatar = document.createElement('div');
        avatar.className = 'ai-chat-message-avatar';
        avatar.innerHTML = avatarSvg;
        row.appendChild(avatar);

        const dots = document.createElement('div');
        dots.className = 'ai-chat-typing';
        dots.innerHTML = '<span></span><span></span><span></span>';
        row.appendChild(dots);

        messagesEl.appendChild(row);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function hideTyping() {
        const el = document.getElementById('ai-chat-typing-indicator');
        if (el) {
            el.remove();
        }
    }

    function loadHistory() {
        if (historyLoaded) {
            return;
        }
        historyLoaded = true;

        fetch('/' + routePrefix + '/messages', {
            headers: { 'Accept': 'application/json' },
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.messages.length === 0 && widget.dataset.welcome) {
                    renderMessage('assistant', widget.dataset.welcome);
                }

                data.messages.forEach(function (message) {
                    renderMessage(message.role, message.content, message.created_at);
                });

                if (data.messages.length > 0) {
                    lastMessageId = data.messages[data.messages.length - 1].id;
                }
            });
    }

    function pollMessages() {
        fetch('/' + routePrefix + '/messages', {
            headers: { 'Accept': 'application/json' },
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                data.messages.forEach(function (message) {
                    if (message.id > lastMessageId) {
                        renderMessage(message.role, message.content, message.created_at);
                        lastMessageId = message.id;
                    }
                });
            });
    }

    function isPanelOpen() {
        return panel.classList.contains('ai-chat-panel-open');
    }

    function openPanel() {
        panel.classList.add('ai-chat-panel-open');
        loadHistory();
        input.focus();
        pollTimer = setInterval(pollMessages, 5000);
    }

    function closePanel() {
        panel.classList.remove('ai-chat-panel-open');
        clearInterval(pollTimer);
    }

    function resizeInput() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        input.style.overflowY = input.scrollHeight > 120 ? 'auto' : 'hidden';
    }

    resizeInput();

    bubble.addEventListener('click', function () {
        if (isPanelOpen()) {
            closePanel();
        } else {
            openPanel();
        }
    });

    closeButton.addEventListener('click', closePanel);

    clearButton.addEventListener('click', function () {
        messagesEl.innerHTML = '';

        if (widget.dataset.welcome) {
            renderMessage('assistant', widget.dataset.welcome);
        }

        fetch('/' + routePrefix + '/reset', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });
    });

    input.addEventListener('input', resizeInput);

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const message = input.value.trim();
        if (!message) {
            return;
        }

        renderMessage('user', message);
        input.value = '';
        resizeInput();
        input.disabled = true;
        showTyping();

        fetch('/' + routePrefix + '/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ message: message }),
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                hideTyping();
                if (data.reply) {
                    renderMessage('assistant', data.reply);
                }
            })
            .catch(function () {
                hideTyping();
                renderMessage('assistant', errorMessage);
            })
            .finally(function () {
                input.disabled = false;
                input.focus();
            });
    });
})();
