<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Introduction Section -->
        <div class="bg-gray-800 rounded-lg shadow-xl p-6 mb-8 border border-gray-700">
            <h1 class="text-3xl font-bold text-cyan-500 mb-4 font-mono">&lt;AI Assistant/&gt;</h1>
            <p class="text-gray-300 mb-4">Your advanced AI coding companion, powered by state-of-the-art language models.</p>
            
            <div class="grid md:grid-cols-3 gap-6 mt-8">
                <div class="bg-gray-900 p-4 rounded-lg border border-gray-700">
                    <div class="text-green-400 text-2xl mb-2">⌨️</div>
                    <h3 class="text-lg font-semibold text-white mb-2">Code Generation</h3>
                    <p class="text-gray-400">Generate code snippets, complete functions, and solve coding challenges with AI assistance.</p>
                </div>
                
                <div class="bg-gray-900 p-4 rounded-lg border border-gray-700">
                    <div class="text-blue-400 text-2xl mb-2">🔍</div>
                    <h3 class="text-lg font-semibold text-white mb-2">Code Analysis</h3>
                    <p class="text-gray-400">Get explanations, identify bugs, and receive suggestions for code improvements.</p>
                </div>
                
                <div class="bg-gray-900 p-4 rounded-lg border border-gray-700">
                    <div class="text-purple-400 text-2xl mb-2">📚</div>
                    <h3 class="text-lg font-semibold text-white mb-2">Learning Aid</h3>
                    <p class="text-gray-400">Learn programming concepts, best practices, and get answers to technical questions.</p>
                </div>
            </div>
        </div>

        <!-- Chat Interface -->
        <div class="bg-gray-800 rounded-lg shadow-xl border border-gray-700">
            <!-- Chat Messages -->
            <div id="chat-messages" class="h-[500px] overflow-y-auto p-4 space-y-4 font-mono">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-cyan-500 flex items-center justify-center">
                            <span class="text-white font-semibold">AI</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                            <p class="text-gray-300">Hello! I'm your AI coding assistant. How can I help you today?</p>
                            <p class="text-gray-400 mt-2 text-sm">I can help with:</p>
                            <ul class="list-disc list-inside text-gray-400 text-sm mt-1">
                                <li>Generating and explaining code</li>
                                <li>Debugging and problem-solving</li>
                                <li>Answering programming questions</li>
                                <li>Providing coding best practices</li>
                            </ul>
                        </div>
                        <div class="mt-1 text-xs text-gray-500">Powered by OpenRouter AI</div>
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="border-t border-gray-700 p-4">
                <form id="chat-form" class="flex space-x-4">
                    <div class="flex-1">
                        <textarea
                            id="message-input"
                            class="w-full px-4 py-2 bg-gray-900 text-gray-100 border border-gray-700 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent resize-none font-mono"
                            rows="3"
                            placeholder="Type your message here..."
                        ></textarea>
                    </div>
                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="px-6 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 focus:ring-offset-gray-900 transition-colors duration-200 font-mono"
                        >
                            Send
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const chatMessages = document.getElementById('chat-messages');
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');

        function appendMessage(content, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'flex items-start space-x-4';
            
            const avatar = document.createElement('div');
            avatar.className = 'flex-shrink-0';
            avatar.innerHTML = `
                <div class="w-8 h-8 rounded-full ${isUser ? 'bg-green-500' : 'bg-cyan-500'} flex items-center justify-center">
                    <span class="text-white font-semibold">${isUser ? 'U' : 'AI'}</span>
                </div>
            `;
            
            const messageContent = document.createElement('div');
            messageContent.className = 'flex-1';
            messageContent.innerHTML = `
                <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                    <p class="text-gray-300 whitespace-pre-wrap">${content}</p>
                </div>
                <div class="mt-1 text-xs text-gray-500">${isUser ? 'You' : 'AI Assistant'}</div>
            `;
            
            messageDiv.appendChild(avatar);
            messageDiv.appendChild(messageContent);
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            // Append user message
            appendMessage(message, true);
            messageInput.value = '';

            try {
                // Send message to server
                const response = await fetch('/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ message })
                });

                if (!response.ok) throw new Error('Failed to send message');

                const data = await response.json();
                
                // Create placeholder for AI response
                const responseDiv = document.createElement('div');
                responseDiv.className = 'flex items-start space-x-4';
                responseDiv.innerHTML = `
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-cyan-500 flex items-center justify-center">
                            <span class="text-white font-semibold">AI</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                            <p class="text-gray-300 whitespace-pre-wrap" id="response-${data.message_id}"></p>
                        </div>
                        <div class="mt-1 text-xs text-gray-500">AI Assistant</div>
                    </div>
                `;
                chatMessages.appendChild(responseDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;

                // Start SSE connection for streaming response
                const eventSource = new EventSource(data.stream_url);
                const responseElement = document.getElementById(`response-${data.message_id}`);
                
                eventSource.onmessage = (event) => {
                    responseElement.textContent += event.data;
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                };

                eventSource.onerror = () => {
                    eventSource.close();
                };

            } catch (error) {
                console.error('Error:', error);
                appendMessage('Sorry, there was an error processing your request. Please try again.');
            }
        });

        // Allow Ctrl+Enter to submit
        messageInput.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit'));
            }
        });
    </script>
    @endpush
</x-app-layout>
