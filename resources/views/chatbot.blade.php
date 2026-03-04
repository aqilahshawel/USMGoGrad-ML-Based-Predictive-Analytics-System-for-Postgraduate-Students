@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="text-center">AI ChatBot Assistant</h3>

    <div id="chat-container" class="card p-3 mb-3" style="height: 400px; overflow-y: scroll;">
        <div class="bot-message">Hello! How can I help you today?</div>
    </div>

    <form id="chat-form">
        <div class="input-group">
            <input type="text" id="message-input" class="form-control" placeholder="Type your message..." required>
            <button id="send-button" class="btn btn-primary" type="button">Send</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatContainer = document.getElementById('chat-container');
        const messageInput = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');
        const chatForm = document.getElementById('chat-form'); // Get the form

        sendButton.addEventListener('click', sendMessage);

        // Listen for Enter key press on the input field
        messageInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        });

        function sendMessage() {
            const message = messageInput.value.trim();
            if (message) {
                appendMessage('user', message);
                messageInput.value = '';

                fetch('/chatbot/message', { // Ensure this matches your route
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ message: message })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.reply) {
                        appendMessage('bot', data.reply);
                    } else if (data.error) {
                        appendMessage('error', data.error);
                    }
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                })
                .catch(error => {
                    appendMessage('error', 'Failed to send message.');
                    console.error('Error:', error);
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                });
            }
        }

        function appendMessage(sender, text) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add(`${sender}-message`);
            messageDiv.textContent = text;
            chatContainer.appendChild(messageDiv);
        }
    });
</script>
@endsection

@section('styles')
<style>
    #chat-container {
        height: 450px;
        overflow-y: auto;
        background: var(--n-50);
        border: 1px solid var(--lav-200);
        border-radius: 14px;
        padding: 1.25rem;
    }
    .user-message {
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
    .bot-message {
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
    .error-message {
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
    h3.text-center {
        font-family: var(--font-display);
        color: var(--lav-500);
        margin-bottom: 1.5rem;
    }
</style>
@endsection
