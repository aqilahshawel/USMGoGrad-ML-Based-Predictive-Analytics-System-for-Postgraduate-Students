@auth
    @role('student')
        {{-- Floating Action Button --}}
        <button
            type="button"
            id="usmg-chatbot-toggle"
            class="btn btn-primary rounded-circle shadow d-flex align-items-center justify-content-center"
            aria-label="Open chatbot"
            data-bs-toggle="modal"
            data-bs-target="#usmg-chatbot-modal"
        >
            <i class="fas fa-robot" aria-hidden="true"></i>
        </button>

        {{-- Chat Modal --}}
        <div class="modal fade" id="usmg-chatbot-modal" tabindex="-1" aria-labelledby="usmg-chatbot-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered usmg-chatbot-dialog">
                <div class="modal-content usmg-chatbot-content">
                    <div class="modal-header usmg-chatbot-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-robot" aria-hidden="true"></i>
                            <strong id="usmg-chatbot-label">AI ChatBot Assistant</strong>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body usmg-chatbot-body" id="usmg-chatbot-scroll">
                        <div id="usmg-chatbot-messages" class="usmg-chatbot-messages" aria-live="polite"></div>
                    </div>
                    <div class="modal-footer usmg-chatbot-footer">
                        <form id="usmg-chatbot-form" class="w-100">
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="usmg-chatbot-input"
                                    class="form-control"
                                    placeholder="Type your message..."
                                    autocomplete="off"
                                    required
                                >
                                <button id="usmg-chatbot-send" class="btn btn-primary" type="button">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <style>
            /* ── FAB Toggle ── */
            #usmg-chatbot-toggle {
                position: fixed;
                right: 24px;
                bottom: 24px;
                width: 56px;
                height: 56px;
                z-index: 1055;
                border: none;
                background: var(--lav-500);
                box-shadow: 0 4px 14px rgba(124,111,179,.35);
                transition: transform .2s, box-shadow .2s;
            }
            #usmg-chatbot-toggle:hover {
                transform: scale(1.08);
                box-shadow: 0 6px 20px rgba(124,111,179,.45);
            }
            #usmg-chatbot-toggle i { color: #fff; font-size: 1.2rem; }

            /* ── Modal Sizing ── */
            .usmg-chatbot-dialog { max-width: 640px; }
            .usmg-chatbot-content {
                height: 78vh;
                max-height: 720px;
                border-radius: 18px;
                overflow: hidden;
                border: 1px solid var(--lav-200);
                display: flex;
                flex-direction: column;
            }

            /* ── Header ── */
            .usmg-chatbot-header {
                background: var(--lav-500);
                color: #fff;
                border-bottom: none;
                padding: .85rem 1.25rem;
                flex-shrink: 0;
            }
            .usmg-chatbot-header i { font-size: 1.1rem; }
            .usmg-chatbot-header .btn-close-white { filter: brightness(0) invert(1); opacity: .8; }
            .usmg-chatbot-header .btn-close-white:hover { opacity: 1; }

            /* ── Body / Messages ── */
            .usmg-chatbot-body {
                flex: 1 1 auto;
                min-height: 0;
                padding: 1.25rem;
                background: var(--n-50);
                overflow-y: auto;
            }
            .usmg-chatbot-messages {
                display: flex;
                flex-direction: column;
                gap: .5rem;
            }

            /* ── Footer ── */
            .usmg-chatbot-footer {
                border-top: 1px solid var(--lav-200);
                background: var(--surface);
                padding: .75rem 1rem;
                flex-shrink: 0;
            }
            .usmg-chatbot-footer .form-control {
                border-radius: 12px 0 0 12px;
                border: 1.5px solid var(--lav-200);
                padding: .6rem 1rem;
            }
            .usmg-chatbot-footer .form-control:focus {
                border-color: var(--lav-400);
                box-shadow: 0 0 0 .2rem rgba(124,111,179,.15);
            }
            .usmg-chatbot-footer .btn-primary {
                border-radius: 0 12px 12px 0;
                background: var(--lav-500);
                border-color: var(--lav-500);
                padding: .6rem 1rem;
            }

            /* ── Message Bubbles ── */
            .usmg-chatbot-user-message {
                align-self: flex-end;
                padding: .7rem 1rem;
                background: var(--lav-100);
                color: var(--lav-500);
                border-radius: 14px 14px 4px 14px;
                max-width: 78%;
                font-size: .88rem;
                white-space: pre-line;
                word-break: break-word;
            }
            .usmg-chatbot-bot-message {
                align-self: flex-start;
                padding: .7rem 1rem;
                background: var(--surface);
                color: var(--n-900);
                border: 1px solid var(--n-200);
                border-radius: 14px 14px 14px 4px;
                max-width: 90%;
                font-size: .88rem;
                white-space: pre-line;
                word-break: break-word;
            }
            .usmg-chatbot-bot-message.usmg-html-msg {
                max-width: 100%;
                white-space: normal;
            }
            .usmg-chatbot-error-message {
                align-self: flex-start;
                padding: .7rem 1rem;
                background: #fdecea;
                color: #993333;
                border-radius: 14px;
                max-width: 85%;
                font-size: .88rem;
            }

            /* ── Quick Action Chips ── */
            .usmg-quick-actions {
                display: flex;
                flex-wrap: wrap;
                gap: .45rem;
                margin-top: .65rem;
            }
            .usmg-quick-btn {
                display: inline-flex;
                align-items: center;
                gap: .35rem;
                padding: .35rem .75rem;
                border-radius: 999px;
                border: 1px solid var(--lav-200);
                background: var(--surface);
                color: var(--lav-500);
                font-size: .78rem;
                font-weight: 600;
                cursor: pointer;
                transition: background .15s, border-color .15s;
            }
            .usmg-quick-btn:hover {
                background: var(--lav-100);
                border-color: var(--lav-400);
            }
            .usmg-quick-btn i { font-size: .7rem; }

            /* ── IPS News Cards ── */
            .ips-news-heading {
                font-weight: 600;
                margin-bottom: .5rem;
                color: var(--n-900);
            }
            .ips-news-grid {
                display: flex;
                flex-direction: column;
                gap: .6rem;
            }
            .ips-news-card {
                display: flex;
                gap: .75rem;
                background: var(--n-50);
                border: 1px solid var(--lav-200);
                border-radius: 12px;
                overflow: hidden;
                transition: border-color .15s;
            }
            .ips-news-card:hover { border-color: var(--lav-400); }
            .ips-news-img {
                width: 90px;
                min-height: 80px;
                object-fit: cover;
                flex-shrink: 0;
            }
            .ips-news-body {
                padding: .55rem .6rem .55rem 0;
                display: flex;
                flex-direction: column;
                gap: .2rem;
                min-width: 0;
            }
            .ips-news-title {
                font-size: .82rem;
                font-weight: 600;
                color: var(--lav-500);
                text-decoration: none;
                line-height: 1.35;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .ips-news-title:hover { text-decoration: underline; }
            .ips-news-date { color: var(--muted); font-size: .72rem; }

            /* ── Typing Indicator ── */
            .usmg-typing {
                align-self: flex-start;
                padding: .55rem 1rem;
                background: var(--surface);
                border: 1px solid var(--n-200);
                border-radius: 14px 14px 14px 4px;
                display: flex;
                gap: .25rem;
                align-items: center;
            }
            .usmg-typing-dot {
                width: 7px; height: 7px;
                border-radius: 50%;
                background: var(--lav-300);
                animation: usmg-bounce .6s infinite alternate;
            }
            .usmg-typing-dot:nth-child(2) { animation-delay: .2s; }
            .usmg-typing-dot:nth-child(3) { animation-delay: .4s; }
            @keyframes usmg-bounce {
                0%   { transform: translateY(0); }
                100% { transform: translateY(-5px); }
            }

            /* ── Mobile ── */
            @media (max-width: 576px) {
                .usmg-chatbot-dialog { max-width: 100%; margin: 0; }
                .usmg-chatbot-content { height: 100vh; max-height: none; border-radius: 0; }
            }
        </style>

        <script>
            (function () {
                function ready(fn) {
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', fn);
                    } else {
                        fn();
                    }
                }

                ready(function () {
                    var modal = document.getElementById('usmg-chatbot-modal');
                    var scrollPane = document.getElementById('usmg-chatbot-scroll');
                    var messages = document.getElementById('usmg-chatbot-messages');
                    var input = document.getElementById('usmg-chatbot-input');
                    var sendBtn = document.getElementById('usmg-chatbot-send');
                    var initialized = false;

                    if (!modal || !messages || !input || !sendBtn) return;

                    function scrollToBottom() {
                        if (scrollPane) scrollPane.scrollTop = scrollPane.scrollHeight;
                    }

                    function appendMessage(sender, text, isHtml) {
                        var div = document.createElement('div');
                        if (sender === 'user') {
                            div.className = 'usmg-chatbot-user-message';
                            div.textContent = text;
                        } else if (sender === 'bot') {
                            div.className = 'usmg-chatbot-bot-message';
                            if (isHtml) {
                                div.classList.add('usmg-html-msg');
                                div.innerHTML = text;
                            } else {
                                div.textContent = text;
                            }
                        } else {
                            div.className = 'usmg-chatbot-error-message';
                            div.textContent = text;
                        }
                        messages.appendChild(div);
                        scrollToBottom();
                    }

                    function showTyping() {
                        var el = document.createElement('div');
                        el.className = 'usmg-typing';
                        el.id = 'usmg-typing-indicator';
                        el.innerHTML = '<div class="usmg-typing-dot"></div><div class="usmg-typing-dot"></div><div class="usmg-typing-dot"></div>';
                        messages.appendChild(el);
                        scrollToBottom();
                    }

                    function hideTyping() {
                        var el = document.getElementById('usmg-typing-indicator');
                        if (el) el.remove();
                    }

                    function triggerMessage(text) {
                        input.value = text;
                        sendMessage();
                    }

                    function appendGreeting() {
                        appendMessage('bot', "Hello! I'm your USMGoGrad assistant. How can I help you today?");

                        var actions = document.createElement('div');
                        actions.className = 'usmg-quick-actions';
                        var chips = [
                            { label: 'My Reminders', icon: 'fa-bell', msg: 'reminder' },
                            { label: 'Supervisor Schedule', icon: 'fa-calendar', msg: 'is my supervisor free this week' },
                            { label: 'Suggest Meeting Slot', icon: 'fa-clock', msg: 'suggest suitable meeting times with my supervisor' },
                            { label: 'IPS News', icon: 'fa-newspaper', msg: 'show latest IPS news' },
                            { label: 'Next Checkpoint', icon: 'fa-flag-checkered', msg: 'next checkpoint' },
                            { label: 'Graduation Timeline', icon: 'fa-graduation-cap', msg: 'time before graduation' },
                            { label: 'Reading Suggestions', icon: 'fa-book', msg: 'recommend readings' },
                        ];
                        chips.forEach(function (chip) {
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'usmg-quick-btn';
                            btn.innerHTML = '<i class="fas ' + chip.icon + '"></i> ' + chip.label;
                            btn.addEventListener('click', function () { triggerMessage(chip.msg); });
                            actions.appendChild(btn);
                        });
                        messages.appendChild(actions);
                        scrollToBottom();
                    }

                    function sendMessage() {
                        var message = (input.value || '').trim();
                        if (!message) return;

                        appendMessage('user', message);
                        input.value = '';
                        showTyping();

                        fetch('{{ route('chatbot.message') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ message: message })
                        })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            hideTyping();
                            if (data && data.reply) {
                                appendMessage('bot', data.reply, !!data.html);
                            } else if (data && data.error) {
                                appendMessage('error', data.error);
                            } else {
                                appendMessage('error', 'Unexpected response.');
                            }
                        })
                        .catch(function () {
                            hideTyping();
                            appendMessage('error', 'Failed to send message.');
                        });
                    }

                    modal.addEventListener('shown.bs.modal', function () {
                        if (!initialized) {
                            appendGreeting();
                            initialized = true;
                        }
                        setTimeout(function () { input.focus(); }, 100);
                    });

                    sendBtn.addEventListener('click', sendMessage);

                    input.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            sendMessage();
                        }
                    });
                });
            })();
        </script>
    @endrole
@endauth
