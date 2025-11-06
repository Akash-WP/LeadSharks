<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL ChatBot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chatbot-container {
            position: fixed;
            bottom: 0;
            right: 20px;
            z-index: 1000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            width: 400px;
            height: 600px;
            background: white;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 -5px 25px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e1e8ed;
            transform: translateY(calc(100% - 60px));
            transition: transform 0.3s ease;
        }
        
        .chatbot-container.open {
            transform: translateY(0);
        }
        
        .chat-toggle-btn {
            width: 100%;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            transition: all 0.3s ease;
        }
        
        .chat-toggle-btn:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        
        .toggle-text {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chat-toggle-btn i {
            transition: transform 0.3s ease;
        }
        
        .chatbot-container.open .chat-toggle-btn i {
            transform: rotate(180deg);
        }
        
        .chat-window {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .chat-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: background 0.3s ease;
        }
        
        .chat-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .chat-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8fafc;
        }
        
        .message {
            margin-bottom: 16px;
            display: flex;
            align-items: flex-start;
        }
        
        .message.user {
            justify-content: flex-end;
        }
        
        .message-content {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .message.user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.bot .message-content {
            background: white;
            color: #333;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .sql-code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 12px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin: 8px 0;
            overflow-x: auto;
        }
        
        .result-summary {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 12px;
            margin: 8px 0;
        }
        
        .result-summary h4 {
            margin: 0 0 8px 0;
            color: #0369a1;
            font-size: 14px;
        }
        
        .insights {
            list-style: none;
            padding: 0;
            margin: 8px 0 0 0;
        }
        
        .insights li {
            padding: 4px 0;
            font-size: 13px;
            color: #0369a1;
        }
        
        .insights li:before {
            content: "💡 ";
            margin-right: 4px;
        }
        
        .result-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            margin: 8px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .result-table table {
            width: 100%;
            font-size: 12px;
        }
        
        .result-table th {
            background: #f1f5f9;
            padding: 8px;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .result-table td {
            padding: 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .feedback-buttons {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        
        .feedback-btn {
            background: none;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .feedback-btn:hover {
            background: #f1f5f9;
        }
        
        .feedback-btn.positive {
            color: #059669;
            border-color: #059669;
        }
        
        .feedback-btn.negative {
            color: #dc2626;
            border-color: #dc2626;
        }
        
        .chat-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid #e1e8ed;
        }
        
        .chat-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e1e8ed;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .chat-input:focus {
            border-color: #667eea;
        }
        
        .send-button {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .send-button:hover {
            transform: translateY(-50%) scale(1.1);
        }
        
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-style: italic;
            padding: 12px 16px;
        }
        
        .typing-dots {
            display: flex;
            gap: 4px;
        }
        
        .typing-dot {
            width: 6px;
            height: 6px;
            background: #64748b;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        .typing-dot:nth-child(3) { animation-delay: 0s; }
        
        @keyframes typing {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin: 8px 0;
            font-size: 13px;
        }
        
        /* Suggestions */
        .suggestions {
            padding: 15px 20px;
            background: white;
            border-top: 1px solid #e1e8ed;
        }
        
        .suggestion-chip {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 6px 12px;
            margin: 4px;
            border-radius: 16px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .suggestion-chip:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }
        
        /* Mobile Responsive */
        @media (max-width: 480px) {
            .chatbot-container {
                width: 100vw;
                height: 100vh;
                right: 0;
                border-radius: 0;
            }
            
            .chatbot-container:not(.open) {
                transform: translateY(calc(100% - 60px));
            }
            
            .chatbot-container.open {
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .chatbot-container {
                width: 90vw;
                right: 5vw;
            }
        }
    </style>
</head>
<body>
    <div class="chatbot-container open" id="chatbotContainer">
        <!-- Chat Toggle Button -->
        <button class="chat-toggle-btn" onclick="toggleChat()">
            <div class="toggle-text">
                <i class="fas fa-robot"></i>
                <span>SQL Assistant</span>
            </div>
            <i class="fas fa-chevron-down"></i>
        </button>
        
        <!-- Chat Window -->
        <div class="chat-window">
            <!-- Header -->
            <div class="chat-header">
                <div>
                    <h3><i class="fas fa-database"></i> SQL Assistant</h3>
                    <small>Ask questions about your data</small>
                </div>
                <button class="chat-close" onclick="toggleChat()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Chat Body -->
            <div class="chat-body" id="chatBody">
                <div class="message bot">
                    <div class="message-content">
                        👋 Hello! I'm your SQL assistant. Ask me anything about your leads, clients, or database data and I'll generate the right query for you.
                    </div>
                </div>
            </div>
            
            <!-- Suggestions -->
            <div class="suggestions">
                <div class="suggestion-chip" onclick="sendSuggestion('How many total leads do we have?')">
                    Total leads count
                </div>
                <div class="suggestion-chip" onclick="sendSuggestion('Show me leads assigned to Ritik')">
                    Ritik's leads
                </div>
                <div class="suggestion-chip" onclick="sendSuggestion('List all companies from Mumbai')">
                    Mumbai companies
                </div>
                <div class="suggestion-chip" onclick="sendSuggestion('Show won leads with company names')">
                    Won leads
                </div>
            </div>
            
            <!-- Input -->
            <div class="chat-input-container">
                <div style="position: relative;">
                    <input 
                        type="text" 
                        class="chat-input" 
                        id="chatInput" 
                        placeholder="Ask about your data..."
                        onkeypress="handleKeyPress(event)"
                    >
                    <button class="send-button" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let isTyping = false;
        
        function toggleChat() {
            const chatbotContainer = document.getElementById('chatbotContainer');
            chatbotContainer.classList.toggle('open');
        }
        
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }
        
        function sendSuggestion(text) {
            document.getElementById('chatInput').value = text;
            sendMessage();
        }
        
        async function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message || isTyping) return;
            
            // Clear input
            input.value = '';
            
            // Add user message
            addMessage(message, 'user');
            
            // Show typing indicator
            showTypingIndicator();
            isTyping = true;
            
            try {
                // Step 1: Generate SQL
                const sqlResponse = await fetch('chatbot/api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'generate_sql',
                        prompt: message
                    })
                });
                
                // Check if response is ok
                if (!sqlResponse.ok) {
                    throw new Error(`HTTP ${sqlResponse.status}: ${sqlResponse.statusText}`);
                }
                
                // Get response text first to debug JSON parsing issues
                const responseText = await sqlResponse.text();
                
                let sqlResult;
                try {
                    sqlResult = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Response Text:', responseText);
                    throw new Error(`Invalid JSON response: ${parseError.message}. Response: ${responseText.substring(0, 200)}...`);
                }
                
                if (!sqlResult.success) {
                    throw new Error(sqlResult.error || 'Failed to generate SQL');
                }
                
                // Show generated SQL
                addSQLMessage(sqlResult.sql);
                
                // Step 2: Execute query
                const executeResponse = await fetch('chatbot/api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'execute_query',
                        query: sqlResult.sql,
                        question: message
                    })
                });
                
                if (!executeResponse.ok) {
                    throw new Error(`HTTP ${executeResponse.status}: ${executeResponse.statusText}`);
                }
                
                const executeText = await executeResponse.text();
                let executeResult;
                try {
                    executeResult = JSON.parse(executeText);
                } catch (parseError) {
                    console.error('Execute JSON Parse Error:', parseError);
                    console.error('Execute Response Text:', executeText);
                    throw new Error(`Invalid JSON in execute response: ${parseError.message}`);
                }
                
                if (executeResult.success) {
                    // Show results
                    addResultMessage(executeResult, message, sqlResult.sql);
                } else {
                    addErrorMessage(executeResult.error || 'Query execution failed');
                }
                
            } catch (error) {
                addErrorMessage('Connection error: ' + error.message);
            } finally {
                hideTypingIndicator();
                isTyping = false;
            }
        }
        
        function addMessage(content, type) {
            const chatBody = document.getElementById('chatBody');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            contentDiv.textContent = content;
            
            messageDiv.appendChild(contentDiv);
            chatBody.appendChild(messageDiv);
            
            scrollToBottom();
        }
        
        function addSQLMessage(sql) {
            const chatBody = document.getElementById('chatBody');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message bot';
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            contentDiv.innerHTML = `
                <strong>🔍 Generated SQL:</strong>
                <div class="sql-code">${escapeHtml(sql)}</div>
            `;
            
            messageDiv.appendChild(contentDiv);
            chatBody.appendChild(messageDiv);
            
            scrollToBottom();
        }
        
        function addResultMessage(result, originalQuestion, sql) {
            const chatBody = document.getElementById('chatBody');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message bot';
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            
            let html = `<strong>✅ Query Results:</strong>`;
            
            // Add summary if available
            if (result.summary) {
                html += `
                    <div class="result-summary">
                        <h4>${result.summary.message}</h4>
                        ${result.summary.insights ? `
                            <ul class="insights">
                                ${result.summary.insights.map(insight => `<li>${insight}</li>`).join('')}
                            </ul>
                        ` : ''}
                    </div>
                `;
            }
            
            // Add data table if available
            if (result.data && result.data.length > 0) {
                const columns = Object.keys(result.data[0]);
                html += `
                    <div class="result-table">
                        <table>
                            <thead>
                                <tr>
                                    ${columns.map(col => `<th>${escapeHtml(col)}</th>`).join('')}
                                </tr>
                            </thead>
                            <tbody>
                                ${result.data.slice(0, 10).map(row => `
                                    <tr>
                                        ${columns.map(col => `<td>${escapeHtml(row[col] || '')}</td>`).join('')}
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        ${result.data.length > 10 ? `<small>Showing first 10 of ${result.data.length} results</small>` : ''}
                    </div>
                `;
            } else if (result.metadata && result.metadata.row_count === 0) {
                html += `<div class="result-summary">No results found</div>`;
            }
            
            // Add feedback buttons
            html += `
                <div class="feedback-buttons">
                    <button class="feedback-btn positive" onclick="sendFeedback('${originalQuestion}', '${sql}', true)">
                        👍 Helpful
                    </button>
                    <button class="feedback-btn negative" onclick="sendFeedback('${originalQuestion}', '${sql}', false)">
                        👎 Not helpful
                    </button>
                </div>
            `;
            
            contentDiv.innerHTML = html;
            messageDiv.appendChild(contentDiv);
            chatBody.appendChild(messageDiv);
            
            scrollToBottom();
        }
        
        function addErrorMessage(error) {
            const chatBody = document.getElementById('chatBody');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message bot';
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            contentDiv.innerHTML = `
                <div class="error-message">
                    <strong>❌ Error:</strong> ${escapeHtml(error)}
                </div>
            `;
            
            messageDiv.appendChild(contentDiv);
            chatBody.appendChild(messageDiv);
            
            scrollToBottom();
        }
        
        function showTypingIndicator() {
            const chatBody = document.getElementById('chatBody');
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typingIndicator';
            typingDiv.className = 'message bot';
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            contentDiv.innerHTML = `
                <div class="typing-indicator">
                    <span>AI is thinking</span>
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            `;
            
            typingDiv.appendChild(contentDiv);
            chatBody.appendChild(typingDiv);
            
            scrollToBottom();
        }
        
        function hideTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }
        
        async function sendFeedback(question, sql, isPositive) {
            try {
                await fetch('chatbot/api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'feedback',
                        question: question,
                        sql: sql,
                        is_correct: isPositive,
                        feedback: isPositive ? 'Helpful response' : 'Needs improvement'
                    })
                });
                
                // Show feedback confirmation
                const chatBody = document.getElementById('chatBody');
                const feedbackDiv = document.createElement('div');
                feedbackDiv.className = 'message bot';
                feedbackDiv.innerHTML = `
                    <div class="message-content" style="background: #f0f9ff; color: #0369a1; font-size: 12px;">
                        ${isPositive ? '👍' : '👎'} Thank you for your feedback!
                    </div>
                `;
                chatBody.appendChild(feedbackDiv);
                scrollToBottom();
                
            } catch (error) {
                console.error('Feedback error:', error);
            }
        }
        
        function scrollToBottom() {
            const chatBody = document.getElementById('chatBody');
            chatBody.scrollTop = chatBody.scrollHeight;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Initialize chat
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on input when chat opens
            const chatInput = document.getElementById('chatInput');
            chatInput.addEventListener('focus', function() {
                setTimeout(() => scrollToBottom(), 100);
            });
        });
    </script>
</body>
</html>