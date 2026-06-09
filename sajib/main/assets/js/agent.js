/**
 * ============================================================
 * SHIFT INTELLIGENCE AGENT — Chat Widget
 * ============================================================
 * Floating animated bubble agent for Shift Handover Management.
 * Features: Markdown rendering, typing animation, quick prompts,
 * message history, glassmorphism UI.
 */

(function () {
    'use strict';

    // --------------------------------------------------------
    // CONFIG
    // --------------------------------------------------------
    // Auto-detect API URL based on this script's loaded location to ensure 100% path accuracy
    let autoApiUrl = '';
    try {
        const scriptEl = document.querySelector('script[src*="agent.js"]');
        if (scriptEl && scriptEl.src) {
            autoApiUrl = scriptEl.src.replace(/\/assets\/js\/agent\.js(\?.*)?$/, '/modules/agent/api');
        }
    } catch (e) {
        console.warn("Could not auto-detect Agent API path, using fallback", e);
    }
    const AGENT_API_URL = autoApiUrl || (window.__AGENT_BASE_URL ? window.location.origin + window.__AGENT_BASE_URL : window.location.origin) + '/modules/agent/api';
    const STORAGE_KEY   = 'shift_agent_history';
    const MAX_HISTORY   = 30;

    // --------------------------------------------------------
    // QUICK PROMPTS
    // --------------------------------------------------------
    const QUICK_PROMPTS = [
        { label: '📊 Shift Summary', msg: 'Give me a full shift handover summary' },
        { label: '🔴 Outages',       msg: 'Show active service outages' },
        { label: '🔒 SSL Status',    msg: 'Check SSL certificate status' },
        { label: '🛡️ Security',      msg: 'Show security alerts' },
        { label: '🧠 Analyze',       msg: 'Analyze the system and give recommendations' },
        { label: '📋 CR List',       msg: 'Show change request list' },
    ];

    // --------------------------------------------------------
    // INJECT STYLES
    // --------------------------------------------------------
    function injectStyles() {
        const css = `
/* ========== AGENT BUBBLE & WIDGET ========== */
#agent-fab {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    box-shadow: 0 4px 24px rgba(67,97,238,0.45), 0 0 0 0 rgba(67,97,238,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 9999;
    border: none;
    outline: none;
    animation: agentPulse 2.4s infinite;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
#agent-fab:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 30px rgba(67,97,238,0.6);
    animation: none;
}
#agent-fab .fab-icon-ai   { transition: opacity 0.2s, transform 0.2s; }
#agent-fab .fab-icon-close{ position:absolute; opacity:0; transform:rotate(-90deg); transition: opacity 0.2s, transform 0.2s; }
#agent-fab.open .fab-icon-ai   { opacity:0; transform:rotate(90deg); }
#agent-fab.open .fab-icon-close{ opacity:1; transform:rotate(0deg); }

@keyframes agentPulse {
    0%   { box-shadow: 0 4px 24px rgba(67,97,238,0.45), 0 0 0 0 rgba(67,97,238,0.4); }
    70%  { box-shadow: 0 4px 24px rgba(67,97,238,0.45), 0 0 0 14px rgba(67,97,238,0); }
    100% { box-shadow: 0 4px 24px rgba(67,97,238,0.45), 0 0 0 0 rgba(67,97,238,0); }
}

/* Notification badge */
#agent-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 18px;
    height: 18px;
    background: #ef233c;
    border-radius: 50%;
    font-size: 10px;
    font-weight: 700;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
    display: none;
}

/* ========== CHAT WINDOW ========== */
#agent-window {
    position: fixed;
    bottom: 100px;
    right: 28px;
    width: 400px;
    max-width: calc(100vw - 56px);
    max-height: calc(100vh - 130px);
    display: flex;
    flex-direction: column;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.18), 0 0 0 1px rgba(255,255,255,0.6);
    z-index: 9998;
    overflow: hidden;
    transform: scale(0.85) translateY(20px);
    opacity: 0;
    pointer-events: none;
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s ease;
    transform-origin: bottom right;
}
#agent-window.open {
    transform: scale(1) translateY(0);
    opacity: 1;
    pointer-events: all;
}

/* Header */
#agent-header {
    background: linear-gradient(135deg, #4361ee, #7209b7);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
.agent-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
    animation: agentBreathe 3s infinite ease-in-out;
}
@keyframes agentBreathe {
    0%,100% { transform: scale(1); }
    50%      { transform: scale(1.08); }
}
.agent-header-info { flex: 1; min-width: 0; }
.agent-header-info .agent-name  { color:#fff; font-weight:700; font-size:15px; line-height:1.2; }
.agent-header-info .agent-status{ color:rgba(255,255,255,0.75); font-size:11px; display:flex; align-items:center; gap:5px; margin-top:2px; }
.agent-status-dot { width:7px; height:7px; border-radius:50%; background:#4ade80; animation:statusBlink 2s infinite; }
@keyframes statusBlink { 0%,100%{opacity:1} 50%{opacity:0.4} }
.agent-header-actions { display:flex; gap:8px; }
.agent-header-btn {
    width:30px; height:30px; border-radius:50%;
    background:rgba(255,255,255,0.15); border:none;
    color:#fff; cursor:pointer; display:flex;
    align-items:center; justify-content:center;
    font-size:12px; transition:background 0.2s;
}
.agent-header-btn:hover { background:rgba(255,255,255,0.3); }

/* Messages area */
#agent-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 200px;
    /* height dynamically scales via flex up to window's max-height */
    scroll-behavior: smooth;
}
#agent-messages::-webkit-scrollbar { width: 4px; }
#agent-messages::-webkit-scrollbar-track { background: transparent; }
#agent-messages::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 4px; }

/* Message bubbles */
.agent-msg {
    display: flex;
    flex-direction: column;
    gap: 4px;
    animation: msgSlideIn 0.25s ease;
}
@keyframes msgSlideIn {
    from { opacity:0; transform:translateY(8px); }
    to   { opacity:1; transform:translateY(0); }
}
.agent-msg.user { align-items: flex-end; }
.agent-msg.bot  { align-items: flex-start; }

.msg-bubble {
    max-width: 88%;
    padding: 10px 14px;
    border-radius: 18px;
    font-size: 13.5px;
    line-height: 1.55;
    word-break: break-word;
}
.agent-msg.user .msg-bubble {
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.agent-msg.bot .msg-bubble {
    background: rgba(67,97,238,0.06);
    color: #1a1a2e;
    border-bottom-left-radius: 4px;
    border: 1px solid rgba(67,97,238,0.1);
}

/* Markdown inside bot messages */
.msg-bubble h2 { font-size:14px; font-weight:700; margin:8px 0 4px; color:#4361ee; }
.msg-bubble h3 { font-size:13px; font-weight:700; margin:6px 0 3px; color:#333; }
.msg-bubble p  { margin:0 0 4px; }
.msg-bubble ul { padding-left:18px; margin:4px 0; }
.msg-bubble li { margin-bottom:2px; }
.msg-bubble strong { font-weight:700; }
.msg-bubble em    { font-style:italic; opacity:0.8; }
.msg-bubble table { width:100%; border-collapse:collapse; font-size:12px; margin:6px 0; }
.msg-bubble th    { background:rgba(67,97,238,0.1); padding:4px 8px; text-align:left; font-weight:600; border:1px solid rgba(67,97,238,0.15); }
.msg-bubble td    { padding:4px 8px; border:1px solid rgba(0,0,0,0.08); }
.msg-bubble code  { background:rgba(0,0,0,0.07); padding:1px 5px; border-radius:4px; font-size:11.5px; }
.msg-bubble hr    { border:none; border-top:1px solid rgba(0,0,0,0.1); margin:6px 0; }

.msg-time { font-size:10px; color:rgba(0,0,0,0.35); padding: 0 4px; }

/* Typing indicator */
.typing-bubble { display:flex; gap:5px; align-items:center; padding:10px 14px !important; }
.typing-bubble span {
    width:7px; height:7px; border-radius:50%;
    background:rgba(67,97,238,0.5);
    animation:typingBounce 1.2s infinite;
}
.typing-bubble span:nth-child(2) { animation-delay:0.15s; }
.typing-bubble span:nth-child(3) { animation-delay:0.30s; }
@keyframes typingBounce {
    0%,60%,100% { transform:translateY(0); opacity:0.5; }
    30%          { transform:translateY(-5px); opacity:1; }
}

/* Quick prompts */
#agent-quick-prompts {
    padding: 8px 12px;
    display: flex;
    gap: 6px;
    flex-wrap: nowrap;
    overflow-x: auto;
    flex-shrink: 0;
    border-top: 1px solid rgba(0,0,0,0.06);
    scrollbar-width: none;
}
#agent-quick-prompts::-webkit-scrollbar { display:none; }
.quick-prompt-btn {
    white-space: nowrap;
    padding: 5px 12px;
    border-radius: 20px;
    border: 1.5px solid rgba(67,97,238,0.25);
    background: rgba(67,97,238,0.05);
    color: #4361ee;
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
}
.quick-prompt-btn:hover {
    background: rgba(67,97,238,0.12);
    border-color: rgba(67,97,238,0.5);
    transform: translateY(-1px);
}

/* Input area */
#agent-input-area {
    padding: 12px 16px;
    border-top: 1px solid rgba(0,0,0,0.07);
    display: flex;
    gap: 8px;
    align-items: flex-end;
    flex-shrink: 0;
    background: rgba(255,255,255,0.6);
}
#agent-input {
    flex: 1;
    border: 1.5px solid rgba(67,97,238,0.2);
    border-radius: 16px;
    padding: 9px 14px;
    font-size: 13.5px;
    background: rgba(255,255,255,0.9);
    color: #1a1a2e;
    outline: none;
    resize: none;
    max-height: 90px;
    min-height: 40px;
    line-height: 1.4;
    transition: border-color 0.2s;
    font-family: inherit;
}
#agent-input:focus { border-color: #4361ee; }
#agent-input::placeholder { color: rgba(0,0,0,0.35); }
#agent-send {
    width: 40px; height: 40px; flex-shrink: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    border: none; color: white; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 2px 8px rgba(67,97,238,0.35);
}
#agent-send:hover  { transform:scale(1.08); box-shadow:0 4px 12px rgba(67,97,238,0.5); }
#agent-send:active { transform:scale(0.95); }
#agent-send:disabled { opacity:0.5; cursor:not-allowed; transform:none; }

/* Welcome screen */
#agent-welcome {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px 20px;
    text-align: center;
    gap: 8px;
    color: #555;
}
#agent-welcome .welcome-icon { font-size: 40px; margin-bottom: 4px; }
#agent-welcome h3 { font-size: 15px; font-weight: 700; color: #1a1a2e; margin: 0; }
#agent-welcome p  { font-size: 12.5px; margin: 0; color: #777; }

/* Dynamic action trigger button */
.action-trigger-btn {
    display: inline-block;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 600;
    color: #ef233c;
    background: rgba(239, 35, 96, 0.08);
    border: 1px solid rgba(239, 35, 96, 0.25);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin: 4px 4px 0 0;
}
.action-trigger-btn:hover {
    color: #fff;
    background: #ef233c;
    border-color: #ef233c;
}
.action-trigger-btn.btn-answered {
    color: #2a9d8f;
    background: rgba(42, 157, 147, 0.08);
    border-color: rgba(42, 157, 147, 0.25);
}
.action-trigger-btn.btn-answered:hover {
    color: #fff;
    background: #2a9d8f;
    border-color: #2a9d8f;
}

/* Responsive */
@media (max-width: 768px) {
    #agent-window { 
        right: 20px; 
        bottom: 90px;
        max-width: calc(100vw - 40px);
        max-height: calc(100vh - 120px);
    }
    #agent-fab { right: 20px; bottom: 20px; }
}
@media (max-width: 480px) {
    #agent-window { 
        width: calc(100vw - 32px); 
        right: 16px; 
        bottom: 85px; 
        max-height: calc(100vh - 110px); 
        border-radius: 20px;
    }
    #agent-fab { 
        bottom: 16px; 
        right: 16px; 
        width: 56px; 
        height: 56px; 
    }
}
        `;
        const style = document.createElement('style');
        style.textContent = css;
        document.head.appendChild(style);
    }

    // --------------------------------------------------------
    // BUILD HTML
    // --------------------------------------------------------
    function buildHTML() {
        // FAB Button
        const fab = document.createElement('button');
        fab.id = 'agent-fab';
        fab.setAttribute('aria-label', 'Open AI Agent');
        fab.title = 'Shift Intelligence Agent';
        fab.innerHTML = `
            <span class="fab-icon-ai">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/>
                    <circle cx="9" cy="14" r="1" fill="white" stroke="none"/>
                    <circle cx="15" cy="14" r="1" fill="white" stroke="none"/>
                </svg>
            </span>
            <span class="fab-icon-close">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </span>
            <span id="agent-badge">1</span>
        `;

        // Chat Window
        const win = document.createElement('div');
        win.id = 'agent-window';
        win.setAttribute('role', 'dialog');
        win.setAttribute('aria-label', 'AI Agent Chat');

        // Quick prompts HTML
        const quickHTML = QUICK_PROMPTS.map(p =>
            `<button class="quick-prompt-btn" data-msg="${escapeAttr(p.msg)}">${p.label}</button>`
        ).join('');

        win.innerHTML = `
            <div id="agent-header">
                <div class="agent-avatar">🤖</div>
                <div class="agent-header-info">
                    <div class="agent-name">Shift Intelligence Agent</div>
                    <div class="agent-status">
                        <span class="agent-status-dot"></span>
                        Live — Connected to all modules
                    </div>
                </div>
                <div class="agent-header-actions">
                    <button class="agent-header-btn" id="agent-clear-btn" title="Clear chat">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                    </button>
                </div>
            </div>
            <div id="agent-messages">
                <div id="agent-welcome">
                    <div class="welcome-icon">🤖</div>
                    <h3>Hello! I'm your Shift Agent</h3>
                    <p>Ask me about outages, SSL certs, campaigns,<br>or type <strong>summary</strong> for a full shift report.</p>
                </div>
            </div>
            <div id="agent-quick-prompts">${quickHTML}</div>
            <div id="agent-input-area">
                <textarea id="agent-input" placeholder="Ask me anything about the shift..." rows="1" maxlength="500"></textarea>
                <button id="agent-send" title="Send message">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(fab);
        document.body.appendChild(win);
        return { fab, win };
    }

    // --------------------------------------------------------
    // MARKDOWN RENDERER (lightweight)
    // --------------------------------------------------------
    function renderMarkdown(text) {
        if (!text) return '';
        let html = text
            // Headers
            .replace(/^### (.+)$/gm, '<h3>$1</h3>')
            .replace(/^## (.+)$/gm, '<h2>$1</h2>')
            .replace(/^# (.+)$/gm, '<h2>$1</h2>')
            // Bold
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            // Italic
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/_(.+?)_/g, '<em>$1</em>')
            // Inline code
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            // HR
            .replace(/^---$/gm, '<hr>')
            // Tables — header row
            .replace(/^\|(.+)\|$/gm, (match, inner) => {
                const cells = inner.split('|').map(c => c.trim());
                return '<tr>' + cells.map(c => `<th>${c}</th>`).join('') + '</tr>';
            })
            // Table separator rows (---|---)
            .replace(/<tr>(<th>[-:\s]+<\/th>)+<\/tr>/g, '')
            // Wrap table rows in table
            ;

        // Wrap consecutive <tr> in <table>
        html = html.replace(/(<tr>.*?<\/tr>\s*)+/gs, m => `<table>${m}</table>`);

        // Unordered lists
        const listLines = html.split('\n');
        const processed = [];
        let inList = false;
        for (const line of listLines) {
            if (/^- (.+)/.test(line)) {
                if (!inList) { processed.push('<ul>'); inList = true; }
                processed.push(line.replace(/^- (.+)/, '<li>$1</li>'));
            } else {
                if (inList) { processed.push('</ul>'); inList = false; }
                processed.push(line);
            }
        }
        if (inList) processed.push('</ul>');
        html = processed.join('\n');

        // Newlines to <br> (but not inside HTML tags)
        html = html.replace(/\n{2,}/g, '\n').replace(/\n/g, '<br>');

        // Prevent DOM XSS by sanitizing the final HTML
        return sanitizeHTML(html);
    }

    function sanitizeHTML(html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        // Whitelist of safe HTML tags
        const allowedTags = ['DIV', 'SPAN', 'BUTTON', 'A', 'I', 'B', 'STRONG', 'EM', 'TABLE', 'TR', 'TD', 'TH', 'BR', 'HR', 'H2', 'H3', 'UL', 'LI', 'P', 'CODE', 'THEAD', 'TBODY'];
        
        function clean(node) {
            const children = Array.from(node.childNodes);
            for (let child of children) {
                if (child.nodeType === 1) { // Element node
                    if (!allowedTags.includes(child.nodeName)) {
                        child.remove();
                        continue;
                    }
                    // Clean attributes: remove anything starting with "on" or containing "javascript:"
                    const attrs = Array.from(child.attributes);
                    for (let attr of attrs) {
                        if (attr.name.startsWith('on') || attr.value.toLowerCase().includes('javascript:')) {
                            child.removeAttribute(attr.name);
                        }
                    }
                    clean(child);
                }
            }
        }
        clean(doc.body);
        return doc.body.innerHTML;
    }

    // --------------------------------------------------------
    // HELPERS
    // --------------------------------------------------------
    function escapeAttr(str) {
        return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function getTime() {
        return new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    function scrollToBottom(messagesEl) {
        requestAnimationFrame(() => { messagesEl.scrollTop = messagesEl.scrollHeight; });
    }

    // --------------------------------------------------------
    // MESSAGE RENDERING
    // --------------------------------------------------------
    function appendMessage(messagesEl, role, text) {
        // Remove welcome screen if present
        const welcome = document.getElementById('agent-welcome');
        if (welcome) welcome.remove();

        const wrapper = document.createElement('div');
        wrapper.className = `agent-msg ${role}`;

        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble';

        if (role === 'bot') {
            bubble.innerHTML = renderMarkdown(text);
        } else {
            bubble.textContent = text;
        }

        const time = document.createElement('div');
        time.className = 'msg-time';
        time.textContent = getTime();

        wrapper.appendChild(bubble);
        wrapper.appendChild(time);
        messagesEl.appendChild(wrapper);
        scrollToBottom(messagesEl);
        return wrapper;
    }

    function showTyping(messagesEl) {
        const wrapper = document.createElement('div');
        wrapper.className = 'agent-msg bot';
        wrapper.id = 'agent-typing';
        wrapper.innerHTML = `
            <div class="msg-bubble typing-bubble">
                <span></span><span></span><span></span>
            </div>`;
        messagesEl.appendChild(wrapper);
        scrollToBottom(messagesEl);
        return wrapper;
    }

    function removeTyping() {
        const el = document.getElementById('agent-typing');
        if (el) el.remove();
    }

    // --------------------------------------------------------
    // HISTORY (session storage)
    // --------------------------------------------------------
    function loadHistory() {
        try { return JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]'); }
        catch { return []; }
    }

    function saveHistory(history) {
        try {
            const trimmed = history.slice(-MAX_HISTORY);
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(trimmed));
        } catch {}
    }

    function clearHistory() {
        sessionStorage.removeItem(STORAGE_KEY);
    }

    function restoreHistory(messagesEl, history) {
        history.forEach(h => appendMessage(messagesEl, h.role, h.text));
    }

    // --------------------------------------------------------
    // API CALL
    // --------------------------------------------------------
    async function sendToAgent(message) {
        const response = await fetch(AGENT_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message }),
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return await response.json();
    }

    // --------------------------------------------------------
    // AUTO-RESIZE TEXTAREA
    // --------------------------------------------------------
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 90) + 'px';
    }

    // --------------------------------------------------------
    // MAIN INIT
    // --------------------------------------------------------
    function init() {
        injectStyles();
        const { fab, win } = buildHTML();

        const messagesEl = document.getElementById('agent-messages');
        const inputEl    = document.getElementById('agent-input');
        const sendBtn    = document.getElementById('agent-send');
        const clearBtn   = document.getElementById('agent-clear-btn');
        const badge      = document.getElementById('agent-badge');

        let isOpen    = false;
        let history   = loadHistory();
        let isLoading = false;

        // Restore history
        if (history.length > 0) {
            restoreHistory(messagesEl, history);
            badge.style.display = 'flex';
        }

        // Toggle chat window
        function toggleWindow() {
            isOpen = !isOpen;
            fab.classList.toggle('open', isOpen);
            win.classList.toggle('open', isOpen);
            if (isOpen) {
                badge.style.display = 'none';
                setTimeout(() => inputEl.focus(), 300);
                scrollToBottom(messagesEl);
            }
        }

        fab.addEventListener('click', toggleWindow);

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (isOpen && !win.contains(e.target) && !fab.contains(e.target)) {
                toggleWindow();
            }
        });

        // Send message logic
        async function handleSend(msgText) {
            msgText = msgText.trim();
            if (!msgText || isLoading) return;

            isLoading = true;
            sendBtn.disabled = true;
            inputEl.value = '';
            autoResize(inputEl);

            // User message
            appendMessage(messagesEl, 'user', msgText);
            history.push({ role: 'user', text: msgText });
            saveHistory(history);

            // Typing indicator
            const typingEl = showTyping(messagesEl);

            try {
                const data = await sendToAgent(msgText);
                removeTyping();
                const botText = data.message || 'I could not process your request.';
                appendMessage(messagesEl, 'bot', botText);
                history.push({ role: 'bot', text: botText });
                saveHistory(history);
            } catch (err) {
                removeTyping();
                console.error("Shift Intelligence Agent Error:", err, "| URL:", AGENT_API_URL);
                const errMsg = '⚠️ Connection error. Please check the database is running and try again.';
                appendMessage(messagesEl, 'bot', errMsg);
                history.push({ role: 'bot', text: errMsg });
                saveHistory(history);
            }

            isLoading = false;
            sendBtn.disabled = false;
            inputEl.focus();
        }

        // Send button
        sendBtn.addEventListener('click', () => handleSend(inputEl.value));

        // Enter key
        inputEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSend(inputEl.value);
            }
        });

        // Auto-resize textarea
        inputEl.addEventListener('input', () => autoResize(inputEl));

        // Quick prompts
        document.querySelectorAll('.quick-prompt-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const msg = btn.dataset.msg;
                if (!isOpen) toggleWindow();
                setTimeout(() => handleSend(msg), isOpen ? 0 : 350);
            });
        });

        // Clear chat
        clearBtn.addEventListener('click', () => {
            clearHistory();
            history = [];
            messagesEl.innerHTML = `
                <div id="agent-welcome">
                    <div class="welcome-icon">🤖</div>
                    <h3>Chat cleared!</h3>
                    <p>Ask me anything about the current shift.</p>
                </div>`;
        });

        // Delegate clicks on action trigger buttons in the chat
        messagesEl.addEventListener('click', (e) => {
            const btn = e.target.closest('.action-trigger-btn');
            if (btn) {
                const action = btn.dataset.action;
                if (action) {
                    if (!isOpen) toggleWindow();
                    handleSend(action);
                }
            }
        });

        // Keyboard shortcut: Ctrl+Shift+A to toggle
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'A') {
                e.preventDefault();
                toggleWindow();
            }
        });

        // Proactive check / Auto-alert on login
        async function runProactiveCheck() {
            try {
                const data = await sendToAgent('proactive_check');
                if (data && data.has_alerts) {
                    badge.textContent = data.alert_count;
                    badge.style.display = 'flex';
                    
                    // If history is empty, pre-populate the chat with this alert
                    if (history.length === 0) {
                        const botText = data.message;
                        appendMessage(messagesEl, 'bot', botText);
                        history.push({ role: 'bot', text: botText });
                        saveHistory(history);
                    }
                } else {
                    // Auto-greet if first time
                    if (history.length === 0 && !isOpen) {
                        badge.style.display = 'flex';
                    }
                }
            } catch (err) {
                console.warn("Proactive check failed", err);
            }
        }

        setTimeout(runProactiveCheck, 1500);
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
