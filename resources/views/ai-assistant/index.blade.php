<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">AI Assistant</h1>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-800">Logout</button>
                    </form>
                </div>
                
                <div id="chat-messages" class="space-y-4 mb-4 h-96 overflow-y-auto">
                    @foreach($messages as $message)
                        <div class="p-4 rounded-lg bg-blue-100 ml-12">
                            {{ $message->message }}
                        </div>
                        @if($message->response)
                            <div class="p-4 rounded-lg bg-gray-100 mr-12">
                                {{ $message->response }}
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="flex space-x-2">
                    <input type="text" id="user-input" 
                           class="flex-1 rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Type your message...">
                    <button onclick="sendMessage()" 
                            class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Send
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentResponseDiv = null;

        function appendMessage(message, isUser = false) {
            const messagesDiv = document.getElementById('chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `p-4 rounded-lg ${isUser ? 'bg-blue-100 ml-12' : 'bg-gray-100 mr-12'}`;
            
            if (!isUser) {
                currentResponseDiv = messageDiv;
            }
            
            messageDiv.textContent = message;
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            return messageDiv;
        }

        function sendMessage() {
            const input = document.getElementById('user-input');
            const message = input.value.trim();
            
            if (!message) return;

            appendMessage(message, true);
            input.value = '';

            fetch('/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ message })
            })
            .then(response => response.json())
            .then(data => {
                currentResponseDiv = appendMessage('', false);
                const eventSource = new EventSource(data.stream_url);
                
                eventSource.onmessage = function(event) {
                    if (event.data === '[DONE]') {
                        eventSource.close();
                        return;
                    }
                    
                    const chunk = JSON.parse(event.data);
                    currentResponseDiv.textContent += chunk.content;
                    document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
                };
                
                eventSource.onerror = function() {
                    eventSource.close();
                    if (currentResponseDiv.textContent === '') {
                        currentResponseDiv.textContent = 'Error: Failed to get response from AI';
                    }
                };
            })
            .catch(error => {
                console.error('Error:', error);
                appendMessage('Sorry, there was an error processing your request.', false);
            });
        }

        // Allow sending message with Enter key
        document.getElementById('user-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
</body>
</html>
