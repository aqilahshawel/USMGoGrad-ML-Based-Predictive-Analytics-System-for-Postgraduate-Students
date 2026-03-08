@auth
    @role('student')
        <button
            type="button"
            id="usmg-chatbot-toggle"
            class="btn btn-primary rounded-circle shadow d-flex align-items-center justify-content-center"
            aria-label="Open chatbot"
            aria-controls="usmg-chatbot-popup"
            aria-expanded="false"
        >
            <i class="fas fa-robot" aria-hidden="true"></i>
        </button>

        <div id="usmg-chatbot-popup" class="card shadow" hidden>
            <div class="card-header d-flex align-items-center justify-content-between usmg-chatbot-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-robot" aria-hidden="true"></i>
                    <strong>AI ChatBot Assistant</strong>
                </div>
                <button type="button" id="usmg-chatbot-close" class="btn btn-sm btn-light" aria-label="Close chatbot">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <div class="card-body usmg-chatbot-body">
                <div id="usmg-chatbot-messages" class="usmg-chatbot-messages" aria-live="polite"></div>
            </div>

            <div class="card-footer usmg-chatbot-footer">
                <form id="usmg-chatbot-form">
                    <div class="input-group">
                        <input
                            type="text"
                            id="usmg-chatbot-input"
                            class="form-control"
                            placeholder="Type your message..."
                            autocomplete="off"
                            required
                        >
                        <button id="usmg-chatbot-send" class="btn btn-primary" type="button">Send</button>
                    </div>
                </form>
            </div>
        </div>

        <style>
            #usmg-chatbot-toggle {
                position: fixed;
                right: 18px;
                bottom: 18px;
                width: 56px;
                height: 56px;
                z-index: 1065;
                border: 1px solid var(--lav-200);
                background: var(--lav-500);
            }

            #usmg-chatbot-toggle i {
                color: var(--surface);
                font-size: 1.1rem;
            }

            #usmg-chatbot-popup {
                position: fixed;
                right: 18px;
                bottom: 86px;
                width: 360px;
                max-width: calc(100vw - 36px);
                height: 520px;
                max-height: calc(100vh - 140px);
                z-index: 1065;
                border-radius: 16px;
                overflow: hidden;
                border: 1px solid var(--lav-200);
                background: var(--surface);
                display: flex;
                flex-direction: column;
            }

            .usmg-chatbot-header {
                background: var(--n-50);
                border-bottom: 1px solid var(--lav-200);
                color: var(--lav-500);
            }

            .usmg-chatbot-body {
                background: var(--n-50);
                padding: 1rem;
                flex: 1 1 auto;
                min-height: 0;
            }

            .usmg-chatbot-messages {
                height: 100%;
                overflow-y: auto;
            }

            .usmg-chatbot-footer {
                border-top: 1px solid var(--lav-200);
                background: var(--surface);
            }

            .usmg-chatbot-user-message {
                text-align: right;
                margin-bottom: 0.75rem;
                padding: 0.65rem 1rem;
                background: var(--lav-100);
                color: var(--lav-500);
                border-radius: 12px 12px 4px 12px;
                display: inline-block;
                max-width: 80%;
                float: right;
                clear: both;
                font-size: 0.9rem;
            }

            .usmg-chatbot-bot-message {
                text-align: left;
                margin-bottom: 0.75rem;
                padding: 0.65rem 1rem;
                background: var(--surface);
                color: var(--n-900);
                border: 1px solid var(--n-200);
                border-radius: 12px 12px 12px 4px;
                display: inline-block;
                max-width: 80%;
                float: left;
                clear: both;
                font-size: 0.9rem;
            }

            .usmg-chatbot-error-message {
                text-align: left;
                margin-bottom: 0.75rem;
                padding: 0.65rem 1rem;
                background: #fdecea;
                color: #993333;
                border-radius: 12px;
                display: inline-block;
                max-width: 80%;
                float: left;
                clear: both;
                font-size: 0.9rem;
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
                    var toggleBtn = document.getElementById('usmg-chatbot-toggle');
                    var popup = document.getElementById('usmg-chatbot-popup');
                    var closeBtn = document.getElementById('usmg-chatbot-close');
                    var messages = document.getElementById('usmg-chatbot-messages');
                    var input = document.getElementById('usmg-chatbot-input');
                    var sendBtn = document.getElementById('usmg-chatbot-send');

                    if (!toggleBtn || !popup || !closeBtn || !messages || !input || !sendBtn) return;

                    var isOpen = false;

                    function setOpen(next) {
                        isOpen = !!next;
                        popup.hidden = !isOpen;
                        toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                        if (isOpen) {
                            if (messages.childElementCount === 0) {
                                appendMessage('bot', 'Hello! How can I help you today?');
                            }
                            setTimeout(function () { input.focus(); }, 0);
                            scrollToBottom();
                        }
                    }

                    function scrollToBottom() {
                        messages.scrollTop = messages.scrollHeight;
                    }

                    function appendMessage(sender, text) {
                        var div = document.createElement('div');
                        if (sender === 'user') div.className = 'usmg-chatbot-user-message';
                        else if (sender === 'bot') div.className = 'usmg-chatbot-bot-message';
                        else div.className = 'usmg-chatbot-error-message';
                        div.textContent = text;
                        messages.appendChild(div);
                    }

                    function sendMessage() {
                        var message = (input.value || '').trim();
                        if (!message) return;

                        appendMessage('user', message);
                        input.value = '';
                        scrollToBottom();

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
                            if (data && data.reply) {
                                appendMessage('bot', data.reply);
                            } else if (data && data.error) {
                                appendMessage('error', data.error);
                            } else {
                                appendMessage('error', 'Unexpected response.');
                            }
                            scrollToBottom();
                        })
                        .catch(function () {
                            appendMessage('error', 'Failed to send message.');
                            scrollToBottom();
                        });
                    }

                    toggleBtn.addEventListener('click', function () {
                        setOpen(!isOpen);
                    });

                    closeBtn.addEventListener('click', function () {
                        setOpen(false);
                    });

                    sendBtn.addEventListener('click', function () {
                        sendMessage();
                    });

                    input.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            sendMessage();
                        }
                        if (event.key === 'Escape') {
                            setOpen(false);
                        }
                    });

                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape' && isOpen) {
                            setOpen(false);
                        }
                    });
                });
            })();
        </script>
    @endrole
@endauth
