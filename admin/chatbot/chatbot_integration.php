<!-- ChatBot Integration for Dashboard -->
<style>
.chatbot-floating {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1050;
}

.chatbot-floating .btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    font-size: 24px;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
}

.chatbot-floating .btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
}

/* Fullscreen ChatBot Frame */
#chatbot-frame-fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    border: none;
    z-index: 9999;
    display: none;
}

.chatbot-close-btn {
    position: fixed;
    top: 20px;
    right: 30px;
    background: rgba(0,0,0,0.6);
    color: white;
    border: none;
    border-radius: 50%;
    width: 45px;
    height: 45px;
    font-size: 20px;
    z-index: 10000;
    display: none;
    cursor: pointer;
}
.chatbot-close-btn:hover {
    background: rgba(0,0,0,0.8);
}
</style>

<!-- Floating Button -->
<div class="chatbot-floating">
    <button type="button" class="btn" onclick="openChatBot()" title="Open SQL Assistant">
        <i class="fas fa-robot"></i>
    </button>
</div>

<!-- Fullscreen ChatBot Frame -->
<iframe id="chatbot-frame-fullscreen" src="chatbot/chatbot.html"></iframe>
<button id="chatbot-close" class="chatbot-close-btn" onclick="closeChatBot()">✕</button>

<!-- JavaScript -->
<script>
function openChatBot() {
    const frame = document.getElementById('chatbot-frame-fullscreen');
    const closeBtn = document.getElementById('chatbot-close');
    frame.style.display = 'block';
    closeBtn.style.display = 'block';
    frame.src = frame.src; // reload for fresh session
}

function closeChatBot() {
    const frame = document.getElementById('chatbot-frame-fullscreen');
    const closeBtn = document.getElementById('chatbot-close');
    frame.style.display = 'none';
    closeBtn.style.display = 'none';
}

// Optional: Keyboard shortcut (Ctrl + `)
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === '`') {
        e.preventDefault();
        openChatBot();
    }
});
</script>
