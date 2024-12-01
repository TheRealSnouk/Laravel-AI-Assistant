<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laravel AI Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-blue: #00f3ff;
            --neon-purple: #9d00ff;
            --dark-bg: #0a0a0f;
        }
        body {
            font-family: 'Share Tech Mono', monospace;
            background-color: var(--dark-bg);
            color: #fff;
        }
        .chat-container {
            background: linear-gradient(45deg, rgba(0,243,255,0.1), rgba(157,0,255,0.1));
            border: 1px solid var(--neon-blue);
            box-shadow: 0 0 10px var(--neon-blue);
        }
        .message {
            border-left: 2px solid var(--neon-purple);
            animation: fadeIn 0.5s ease-out;
        }
        .user-message {
            border-left-color: var(--neon-blue);
        }
        .assistant-message {
            border-left-color: var(--neon-purple);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .input-container {
            border: 1px solid var(--neon-blue);
            background: rgba(10,10,15,0.9);
        }
        .send-button {
            background: linear-gradient(45deg, var(--neon-blue), var(--neon-purple));
            transition: all 0.3s ease;
        }
        .send-button:hover {
            box-shadow: 0 0 15px var(--neon-blue);
            transform: scale(1.05);
        }
        .typing-indicator {
            display: none;
        }
        .typing-indicator.active {
            display: flex;
        }
        .dot {
            width: 8px;
            height: 8px;
            margin: 0 2px;
            background-color: var(--neon-purple);
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out;
        }
        .dot:nth-child(1) { animation-delay: -0.32s; }
        .dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl mb-8 text-center" style="color: var(--neon-blue)">
            Laravel AI Assistant
            <span class="block text-sm mt-2" style="color: var(--neon-purple)">Powered by Claude-2</span>
        </h1>

        <div class="chat-container rounded-lg p-4 mb-4 min-h-[400px] max-h-[600px] overflow-y-auto">
            <div id="messages" class="space-y-4">
                <div class="message assistant-message p-3 rounded">
                    Hello! I'm your Laravel AI assistant. How can I help you with your development tasks today?
                </div>
            </div>
            <div class="typing-indicator p-3 space-x-1 justify-start items-center">
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
        </div>

        <div class="input-container rounded-lg p-2 flex items-center space-x-2">
            <textarea 
                id="userInput"
                class="flex-1 bg-transparent border-none outline-none resize-none h-12 px-2"
                placeholder="Ask me anything about Laravel development..."
            ></textarea>
            <button 
                id="sendButton"
                class="send-button px-6 py-2 rounded-lg text-white font-bold"
            >
                Send
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.getElementById('messages');
            const userInput = document.getElementById('userInput');
            const sendButton = document.getElementById('sendButton');
            const typingIndicator = document.querySelector('.typing-indicator');

            function addMessage(content, isUser = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${isUser ? 'user-message' : 'assistant-message'} p-3 rounded`;
                messageDiv.textContent = content;
                messagesContainer.appendChild(messageDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            function setLoading(loading) {
                typingIndicator.classList.toggle('active', loading);
                sendButton.disabled = loading;
                userInput.disabled = loading;
            }

            async function sendMessage() {
                const message = userInput.value.trim();
                if (!message) return;

                addMessage(message, true);
                userInput.value = '';
                setLoading(true);

                try {
                    const response = await fetch('/chat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ message })
                    });

                    const data = await response.json();
                    if (data.status === 'success') {
                        addMessage(data.message);
                    } else {
                        addMessage('Sorry, I encountered an error. Please try again.');
                    }
                } catch (error) {
                    addMessage('Sorry, I encountered an error. Please try again.');
                } finally {
                    setLoading(false);
                }
            }

            sendButton.addEventListener('click', sendMessage);
            userInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html>
