/**
 * Chat System - Real-time Messaging JavaScript
 * Handles AJAX polling, message sending, and UI updates
 */

(function() {
    'use strict';

    // Configuration
    const config = window.CHAT_CONFIG || {};
    const API_ENDPOINT = config.apiEndpoint || '/api/chat.php';
    const POLL_INTERVAL = config.pollInterval || 3000;
    const TYPING_TIMEOUT = config.typingTimeout || 5000;
    const CURRENT_USER_ID = config.currentUserId;

    // State
    const state = {
        selectedUserId: null,
        selectedUserName: null,
        selectedUserAvatar: null,
        users: [],
        messages: [],
        lastMessageId: 0,
        isPolling: false,
        pollTimer: null,
        typingTimer: null,
        isTyping: false,
        scrolledToBottom: true
    };

    // DOM Elements
    const elements = {
        usersList: document.getElementById('chatUsersList'),
        searchInput: document.getElementById('userSearchInput'),
        messagesContainer: document.getElementById('chatMessages'),
        messageInput: document.getElementById('messageInput'),
        messageForm: document.getElementById('chatMessageForm'),
        sendBtn: document.getElementById('sendMessageBtn'),
        chatHeader: document.getElementById('chatHeader'),
        chatHeaderName: document.getElementById('chatHeaderName'),
        chatHeaderAvatar: document.getElementById('chatHeaderAvatar'),
        chatHeaderStatus: document.getElementById('chatHeaderStatus'),
        chatMessagesContainer: document.getElementById('chatMessagesContainer'),
        chatInputContainer: document.getElementById('chatInputContainer'),
        chatEmptyState: document.getElementById('chatEmptyState'),
        typingIndicator: document.getElementById('chatTypingIndicator'),
        refreshUsersBtn: document.getElementById('refreshUsersBtn'),
        refreshMessagesBtn: document.getElementById('refreshMessagesBtn'),
        topAvatars: document.getElementById('chatTopAvatars'),
        pinnedList: document.getElementById('chatPinnedList'),
        pinnedCount: document.getElementById('chatPinnedCount'),
        infoAvatar: document.getElementById('chatInfoAvatar'),
        infoName: document.getElementById('chatInfoName'),
        infoSub: document.getElementById('chatInfoSub'),
        infoMembers: document.getElementById('chatInfoMembers')
    };

    // Initialize
    function init() {
        if (!CURRENT_USER_ID) {
            console.error('Current user ID not found');
            return;
        }

        // Load users
        loadUsers();

        // Set up event listeners
        setupEventListeners();

        // Start polling for unread counts
        startPolling();
    }

    // Setup Event Listeners
    function setupEventListeners() {
        // Search users
        elements.searchInput?.addEventListener('input', debounce(function() {
            filterUsers(this.value);
        }, 300));

        // Send message
        elements.messageForm?.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });

        // Textarea auto-resize and Enter key handling
        elements.messageInput?.addEventListener('input', function() {
            autoResizeTextarea(this);
            handleTypingIndicator();
        });

        elements.messageInput?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Refresh buttons
        elements.refreshUsersBtn?.addEventListener('click', function() {
            loadUsers(true);
        });

        elements.refreshMessagesBtn?.addEventListener('click', function() {
            if (state.selectedUserId) {
                loadMessages(state.selectedUserId, true);
            }
        });

        // Tabs: All / Pinned
        document.querySelectorAll('.chat-list-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const selected = this.dataset.tab || 'all';

                document.querySelectorAll('.chat-list-tab').forEach(btn => {
                    btn.classList.toggle('active', btn === this);
                    btn.setAttribute('aria-selected', btn === this ? 'true' : 'false');
                });

                if (elements.usersList) {
                    elements.usersList.style.display = selected === 'all' ? 'block' : 'none';
                }
                if (elements.pinnedList) {
                    elements.pinnedList.style.display = selected === 'pinned' ? 'block' : 'none';
                }
            });
        });

        // Scroll detection
        elements.messagesContainer?.addEventListener('scroll', function() {
            const container = this;
            const scrolledToBottom = Math.abs(
                container.scrollHeight - container.scrollTop - container.clientHeight
            ) < 50;
            state.scrolledToBottom = scrolledToBottom;
        });
    }

    // Load Users
    async function loadUsers(showLoading = false) {
        if (showLoading && elements.usersList) {
            elements.usersList.innerHTML = `
                <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm mb-2" role="status"></div>
                    <div>Loading contacts...</div>
                </div>
            `;
        }

        try {
            const searchTerm = elements.searchInput?.value || '';
            const url = `${API_ENDPOINT}?action=get_users${searchTerm ? '&search=' + encodeURIComponent(searchTerm) : ''}`;
            
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success && data.users) {
                state.users = data.users;
                renderUsers(data.users);
            } else {
                throw new Error(data.error || 'Failed to load users');
            }
        } catch (error) {
            console.error('Error loading users:', error);
            if (elements.usersList) {
                elements.usersList.innerHTML = `
                    <div class="text-center text-danger py-4">
                        <i class="fas fa-exclamation-triangle mb-2"></i>
                        <div>Failed to load contacts</div>
                    </div>
                `;
            }
        }
    }

    // Render Users List
    function renderUsers(users) {
        if (!elements.usersList) return;

        if (users.length === 0) {
            elements.usersList.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-users mb-2"></i>
                    <div>No users found</div>
                </div>
            `;
            return;
        }

        elements.usersList.innerHTML = users.map(user => {
            const initials = getInitials(user.name);
            const unreadBadge = user.unread_count > 0 
                ? `<span class="chat-user-badge">${user.unread_count}</span>` 
                : '';
            
            // Format last message preview
            let lastMessageHtml = '';
            if (user.last_message) {
                const prefix = user.last_message_from_me ? 'You: ' : '';
                const message = escapeHtml(user.last_message);
                const truncated = message.length > 50 ? message.substring(0, 47) + '...' : message;
                lastMessageHtml = `<div class="chat-user-last-message">${prefix}${truncated}</div>`;
            } else {
                lastMessageHtml = `<div class="chat-user-last-message text-muted">No messages yet</div>`;
            }
            
            return `
                <div class="chat-user-item ${state.selectedUserId === user.id ? 'active' : ''}" 
                     data-user-id="${user.id}"
                     data-user-name="${escapeHtml(user.name)}"
                     data-user-avatar="${user.avatar_url || ''}">
                    <div class="chat-user-avatar">
                        ${user.avatar_url ? `<img src="${user.avatar_url}" alt="${escapeHtml(user.name)}">` : initials}
                    </div>
                    <div class="chat-user-info">
                        <div class="chat-user-name">${escapeHtml(user.name)}</div>
                        ${lastMessageHtml}
                    </div>
                    ${unreadBadge}
                </div>
            `;
        }).join('');

        // Update top avatar strip (UI only)
        renderTopAvatars(users);

        // Update pinned list (conversations with unread messages)
        const pinnedUsers = (users || []).filter(u => (u.unread_count || 0) > 0);
        renderPinnedUsers(pinnedUsers);

        // Add click handlers
        document.querySelectorAll('.chat-user-item').forEach(item => {
            item.addEventListener('click', function() {
                const userId = parseInt(this.dataset.userId);
                const userName = this.dataset.userName;
                const userAvatar = this.dataset.userAvatar;
                selectUser(userId, userName, userAvatar);
            });
        });
    }

    function renderTopAvatars(users) {
        if (!elements.topAvatars) return;
        const top = (users || []).slice(0, 8);
        if (top.length === 0) {
            elements.topAvatars.innerHTML = '<div class="chat-avatars-strip__loading">No contacts</div>';
            return;
        }

        elements.topAvatars.innerHTML = top.map(u => {
            const initials = getInitials(u.name);
            const avatar = u.avatar_url
                ? `<img src="${u.avatar_url}" alt="${escapeHtml(u.name)}">`
                : `${initials}`;
            return `<button class="chat-avatar-pill" type="button" title="${escapeHtml(u.name)}" data-user-id="${u.id}" data-user-name="${escapeHtml(u.name)}" data-user-avatar="${u.avatar_url || ''}">${avatar}</button>`;
        }).join('');

        elements.topAvatars.querySelectorAll('.chat-avatar-pill').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = parseInt(this.dataset.userId);
                const userName = this.dataset.userName;
                const userAvatar = this.dataset.userAvatar;
                selectUser(userId, userName, userAvatar);
            });
        });
    }

    function renderPinnedUsers(users) {
        if (!elements.pinnedList) return;

        if (!users || users.length === 0) {
            elements.pinnedList.innerHTML = `
                <div class="text-center text-muted py-2 small">No pinned conversations</div>
            `;
            if (elements.pinnedCount) {
                elements.pinnedCount.textContent = '0';
            }
            return;
        }

        if (elements.pinnedCount) {
            elements.pinnedCount.textContent = String(users.length);
        }

        elements.pinnedList.innerHTML = users.map(user => {
            const initials = getInitials(user.name);
            const unreadBadge = user.unread_count > 0 
                ? `<span class="chat-user-badge">${user.unread_count}</span>` 
                : '';

            let lastMessageHtml = '';
            if (user.last_message) {
                const prefix = user.last_message_from_me ? 'You: ' : '';
                const message = escapeHtml(user.last_message);
                const truncated = message.length > 40 ? message.substring(0, 37) + '...' : message;
                lastMessageHtml = `<div class="chat-user-last-message">${prefix}${truncated}</div>`;
            } else {
                lastMessageHtml = `<div class="chat-user-last-message text-muted">No messages yet</div>`;
            }

            return `
                <div class="chat-user-item ${state.selectedUserId === user.id ? 'active' : ''}" 
                     data-user-id="${user.id}"
                     data-user-name="${escapeHtml(user.name)}"
                     data-user-avatar="${user.avatar_url || ''}">
                    <div class="chat-user-avatar">
                        ${user.avatar_url ? `<img src="${user.avatar_url}" alt="${escapeHtml(user.name)}">` : initials}
                    </div>
                    <div class="chat-user-info">
                        <div class="chat-user-name">${escapeHtml(user.name)}</div>
                        ${lastMessageHtml}
                    </div>
                    ${unreadBadge}
                </div>
            `;
        }).join('');

        elements.pinnedList.querySelectorAll('.chat-user-item').forEach(item => {
            item.addEventListener('click', function() {
                const userId = parseInt(this.dataset.userId);
                const userName = this.dataset.userName;
                const userAvatar = this.dataset.userAvatar;
                selectUser(userId, userName, userAvatar);
            });
        });
    }

    // Filter Users
    function filterUsers(searchTerm) {
        loadUsers();
    }

    // Select User
    function selectUser(userId, userName, userAvatar) {
        if (state.selectedUserId === userId) return;

        state.selectedUserId = userId;
        state.selectedUserName = userName;
        state.selectedUserAvatar = userAvatar;
        state.messages = [];
        state.lastMessageId = 0;

        // Update UI
        updateActiveUser();
        showChatInterface();
        loadMessages(userId);

        // Clear and focus input
        if (elements.messageInput) {
            elements.messageInput.value = '';
            elements.messageInput.focus();
        }
    }

    // Update Active User in List
    function updateActiveUser() {
        document.querySelectorAll('.chat-user-item').forEach(item => {
            const userId = parseInt(item.dataset.userId);
            if (userId === state.selectedUserId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    // Show Chat Interface
    function showChatInterface() {
        // Hide empty state
        if (elements.chatEmptyState) {
            elements.chatEmptyState.style.display = 'none';
        }

        // Show chat components
        if (elements.chatHeader) {
            elements.chatHeader.style.display = 'flex';
        }
        if (elements.chatMessagesContainer) {
            elements.chatMessagesContainer.style.display = 'flex';
        }
        if (elements.chatInputContainer) {
            elements.chatInputContainer.style.display = 'block';
        }

        // Update header
        updateChatHeader();
    }

    // Update Chat Header
    function updateChatHeader() {
        if (!state.selectedUserId) return;

        const initials = getInitials(state.selectedUserName);

        if (elements.chatHeaderName) {
            elements.chatHeaderName.textContent = state.selectedUserName;
        }

        if (elements.chatHeaderAvatar) {
            if (state.selectedUserAvatar) {
                elements.chatHeaderAvatar.innerHTML = `<img src="${state.selectedUserAvatar}" alt="${escapeHtml(state.selectedUserName)}">`;
            } else {
                elements.chatHeaderAvatar.innerHTML = initials;
            }
        }

        if (elements.chatHeaderStatus) {
            elements.chatHeaderStatus.textContent = 'Active';
        }

        // Update right-side info panel (UI only)
        updateInfoPanel();
    }

    function updateInfoPanel() {
        if (!state.selectedUserId) return;
        const initials = getInitials(state.selectedUserName);

        if (elements.infoName) elements.infoName.textContent = state.selectedUserName || '—';
        if (elements.infoSub) elements.infoSub.textContent = 'Active now';

        if (elements.infoAvatar) {
            if (state.selectedUserAvatar) {
                elements.infoAvatar.innerHTML = `<img src="${state.selectedUserAvatar}" alt="${escapeHtml(state.selectedUserName)}">`;
            } else {
                elements.infoAvatar.textContent = initials;
            }
        }

        if (elements.infoMembers) {
            // One-to-one chat: show current user + selected user
            const meName = config.currentUserName || 'You';
            const meInitials = getInitials(meName);
            const otherInitials = initials;
            const otherName = state.selectedUserName || '—';

            elements.infoMembers.innerHTML = `
                <div class="chat-info-member">
                    <div class="chat-info-member__avatar">${meInitials}</div>
                    <div class="chat-info-member__meta">
                        <div class="chat-info-member__name">${escapeHtml(meName)}</div>
                        <div class="chat-info-member__sub">You</div>
                    </div>
                </div>
                <div class="chat-info-member">
                    <div class="chat-info-member__avatar">${otherInitials}</div>
                    <div class="chat-info-member__meta">
                        <div class="chat-info-member__name">${escapeHtml(otherName)}</div>
                        <div class="chat-info-member__sub">Contact</div>
                    </div>
                </div>
            `;
        }
    }

    // Load Messages
    async function loadMessages(userId, showLoading = false) {
        if (!userId) return;

        if (showLoading && elements.messagesContainer) {
            elements.messagesContainer.innerHTML = `
                <div class="chat-loading">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div>Loading messages...</div>
                </div>
            `;
        }

        try {
            const url = `${API_ENDPOINT}?action=get_messages&user_id=${userId}&limit=50`;
            
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success && data.messages) {
                state.messages = data.messages;
                if (data.messages.length > 0) {
                    state.lastMessageId = data.messages[data.messages.length - 1].id;
                }
                renderMessages(data.messages);
                scrollToBottom(true);
            } else {
                throw new Error(data.error || 'Failed to load messages');
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            if (elements.messagesContainer) {
                elements.messagesContainer.innerHTML = `
                    <div class="text-center text-danger py-4">
                        <i class="fas fa-exclamation-triangle mb-2"></i>
                        <div>Failed to load messages</div>
                    </div>
                `;
            }
        }
    }

    // Render Messages
    function renderMessages(messages) {
        if (!elements.messagesContainer) return;

        if (messages.length === 0) {
            elements.messagesContainer.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox mb-2"></i>
                    <div>No messages yet. Start the conversation!</div>
                </div>
            `;
            return;
        }

        elements.messagesContainer.innerHTML = messages.map(msg => {
            return renderMessage(msg);
        }).join('');
    }

    // Render Single Message
    function renderMessage(msg) {
        const isMine = msg.is_mine || (msg.sender_id == CURRENT_USER_ID);
        const messageClass = isMine ? 'sent' : 'received';
        const initials = getInitials(isMine ? config.currentUserName : state.selectedUserName);
        const avatarUrl = isMine ? null : state.selectedUserAvatar;
        
        const timestamp = formatMessageTime(msg.created_at);
        const readStatus = isMine && msg.is_read 
            ? '<span class="chat-message-status read" title="Read"><i class="fas fa-check-double"></i></span>'
            : isMine 
            ? '<span class="chat-message-status" title="Sent"><i class="fas fa-check"></i></span>'
            : '';

        return `
            <div class="chat-message ${messageClass}" data-message-id="${msg.id}">
                <div class="chat-message-avatar">
                    ${avatarUrl ? `<img src="${avatarUrl}" alt="${escapeHtml(msg.sender_name)}">` : initials}
                </div>
                <div class="chat-message-content">
                    <div class="chat-message-bubble">${escapeHtml(msg.message)}</div>
                    <div class="chat-message-meta">
                        <span class="chat-message-time">${timestamp}</span>
                        ${readStatus}
                    </div>
                </div>
            </div>
        `;
    }

    // Send Message
    async function sendMessage() {
        if (!state.selectedUserId) return;

        const message = elements.messageInput?.value.trim();
        if (!message) return;

        // Disable input while sending
        if (elements.messageInput) elements.messageInput.disabled = true;
        if (elements.sendBtn) elements.sendBtn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_id', state.selectedUserId);
            formData.append('message', message);

            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            const data = await response.json();

            if (data.success && data.message) {
                // Add message to state
                state.messages.push(data.message);
                state.lastMessageId = data.message.id;

                // Append message to UI
                appendMessage(data.message);

                // Clear input
                if (elements.messageInput) {
                    elements.messageInput.value = '';
                    autoResizeTextarea(elements.messageInput);
                }

                // Scroll to bottom
                scrollToBottom(true);

                // Stop typing indicator
                stopTypingIndicator();
                
                // Refresh user list to update conversation order
                loadUsers(false);
            } else {
                throw new Error(data.error || 'Failed to send message');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            alert('Failed to send message. Please try again.');
        } finally {
            // Re-enable input
            if (elements.messageInput) elements.messageInput.disabled = false;
            if (elements.sendBtn) elements.sendBtn.disabled = false;
            if (elements.messageInput) elements.messageInput.focus();
        }
    }

    // Append Message to UI
    function appendMessage(msg) {
        if (!elements.messagesContainer) return;

        // Remove empty state if present
        const emptyState = elements.messagesContainer.querySelector('.text-center');
        if (emptyState) {
            emptyState.remove();
        }

        // Append message
        const messageHtml = renderMessage(msg);
        elements.messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
    }

    // Scroll to Bottom
    function scrollToBottom(force = false) {
        if (!elements.messagesContainer) return;
        if (!force && !state.scrolledToBottom) return;

        const container = elements.messagesContainer;
        container.scrollTop = container.scrollHeight;
        state.scrolledToBottom = true;
    }

    // Polling for new messages and updates
    function startPolling() {
        if (state.isPolling) return;
        state.isPolling = true;

        state.pollTimer = setInterval(() => {
            // Poll for new messages if user selected
            if (state.selectedUserId) {
                pollNewMessages();
            } else {
                // Only refresh user list when no chat is open
                // (to catch new conversations from other users)
                loadUsers(false);
            }

            // Poll for unread counts
            pollUnreadCounts();
        }, POLL_INTERVAL);
    }

    function stopPolling() {
        if (state.pollTimer) {
            clearInterval(state.pollTimer);
            state.pollTimer = null;
        }
        state.isPolling = false;
    }

    // Poll New Messages
    async function pollNewMessages() {
        if (!state.selectedUserId || !state.lastMessageId) return;

        try {
            const url = `${API_ENDPOINT}?action=get_messages&user_id=${state.selectedUserId}&limit=50`;
            
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success && data.messages) {
                const newMessages = data.messages.filter(msg => msg.id > state.lastMessageId);
                
                if (newMessages.length > 0) {
                    newMessages.forEach(msg => {
                        state.messages.push(msg);
                        appendMessage(msg);
                    });
                    
                    state.lastMessageId = data.messages[data.messages.length - 1].id;
                    scrollToBottom();
                    
                    // Refresh user list to update conversation order
                    // This ensures the sender moves to top when they message us
                    loadUsers(false);
                }
            }
        } catch (error) {
            console.error('Error polling messages:', error);
        }
    }

    // Poll Unread Counts
    async function pollUnreadCounts() {
        try {
            const url = `${API_ENDPOINT}?action=get_unread_count`;
            
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success && data.unread_by_user) {
                updateUnreadBadges(data.unread_by_user);
            }
        } catch (error) {
            console.error('Error polling unread counts:', error);
        }
    }

    // Update Unread Badges
    function updateUnreadBadges(unreadMap) {
        document.querySelectorAll('.chat-user-item').forEach(item => {
            const userId = parseInt(item.dataset.userId);
            const existingBadge = item.querySelector('.chat-user-badge');
            const unreadCount = unreadMap[userId] || 0;

            if (unreadCount > 0) {
                if (existingBadge) {
                    existingBadge.textContent = unreadCount;
                } else {
                    const badge = document.createElement('span');
                    badge.className = 'chat-user-badge';
                    badge.textContent = unreadCount;
                    item.appendChild(badge);
                }
            } else {
                if (existingBadge) {
                    existingBadge.remove();
                }
            }
        });
    }

    // Typing Indicator
    function handleTypingIndicator() {
        if (!state.selectedUserId) return;

        if (!state.isTyping) {
            state.isTyping = true;
            sendTypingStatus(true);
        }

        // Reset typing timeout
        clearTimeout(state.typingTimer);
        state.typingTimer = setTimeout(() => {
            stopTypingIndicator();
        }, TYPING_TIMEOUT);
    }

    function stopTypingIndicator() {
        if (state.isTyping) {
            state.isTyping = false;
            sendTypingStatus(false);
        }
        clearTimeout(state.typingTimer);
    }

    async function sendTypingStatus(isTyping) {
        if (!state.selectedUserId) return;

        try {
            const formData = new FormData();
            formData.append('action', 'set_typing_status');
            formData.append('recipient_id', state.selectedUserId);
            formData.append('is_typing', isTyping ? '1' : '0');

            await fetch(API_ENDPOINT, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
        } catch (error) {
            console.error('Error sending typing status:', error);
        }
    }

    // Utility Functions
    function getInitials(name) {
        if (!name) return '?';
        const parts = name.trim().split(' ');
        if (parts.length >= 2) {
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatMessageTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

        if (diffDays === 0) {
            return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        } else if (diffDays === 1) {
            return 'Yesterday ' + date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        } else if (diffDays < 7) {
            return date.toLocaleDateString('en-US', { weekday: 'short' }) + ' ' + 
                   date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        } else {
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' ' +
                   date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }
    }

    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        stopPolling();
        stopTypingIndicator();
    });

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
