/**
 * Chat Widget - Floating Chat Interface
 * 
 * Production-ready chat widget with recipient selection,
 * real-time updates, and conversation management.
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = window.CHAT_WIDGET_CONFIG || {};
    const CURRENT_USER_ID = CONFIG.currentUserId;
    const CURRENT_USER_NAME = CONFIG.currentUserName;
    const API_ENDPOINT = CONFIG.apiEndpoint || '/api/chat.php';
    const POLL_INTERVAL = CONFIG.pollInterval || 3000;
    const TYPING_TIMEOUT = CONFIG.typingTimeout || 5000;
    
    // Emoji list (most commonly used emojis)
    const EMOJIS = [
        '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂',
        '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩',
        '😘', '😗', '😚', '😙', '😋', '😛', '😜', '🤪',
        '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨',
        '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥',
        '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕',
        '🤢', '🤮', '🤧', '🥵', '🥶', '😵', '🤯', '🤠',
        '🥳', '😎', '🤓', '🧐', '😕', '😟', '🙁', '☹️',
        '😮', '😯', '😲', '😳', '🥺', '😦', '😧', '😨',
        '😰', '😥', '😢', '😭', '😱', '😖', '😣', '😞',
        '😓', '😩', '😫', '🥱', '😤', '😡', '😠', '🤬',
        '👍', '👎', '👌', '✌️', '🤞', '🤟', '🤘', '🤙',
        '👏', '🙌', '👐', '🤲', '🙏', '✍️', '💪', '🦾',
        '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍',
        '💯', '💢', '💥', '💫', '💦', '💨', '🕳️', '💬',
        '👁️', '🗨️', '🗯️', '💭', '💤', '👋', '🤚', '✋'
    ];

    // State
    const state = {
        isOpen: false,
        currentView: 'recipients', // 'recipients' or 'conversation'
        selectedUserId: null,
        selectedUserName: null,
        selectedUserAvatar: null,
        messages: [],
        lastMessageId: 0,
        isPolling: false,
        pollTimer: null,
        typingTimer: null,
        searchDebounce: null,
        recipients: [],
        totalUnreadCount: 0,
        photoFile: null,
        photoPreviewUrl: null,
        isEmojiPickerOpen: false,
        confirmCallback: null
    };

    // DOM Elements
    const elements = {
        widget: null,
        toggleBtn: null,
        popup: null,
        closeBtn: null,
        minimizeBtn: null,
        
        // Recipient view
        recipientView: null,
        recipientSearch: null,
        recipientsList: null,
        
        // Conversation view
        conversationView: null,
        backBtn: null,
        conversationName: null,
        conversationAvatar: null,
        messagesContainer: null,
        messageInput: null,
        sendBtn: null,
        typingIndicator: null,
        clearHistoryBtn: null,
        
        // Input tools
        emojiBtn: null,
        attachPhotoBtn: null,
        photoInput: null,
        photoPreview: null,
        photoPreviewImg: null,
        photoPreviewRemove: null,
        
        // Emoji picker
        emojiPicker: null,
        emojiGrid: null,
        
        // Photo modal
        photoModal: null,
        photoModalImg: null,
        photoModalClose: null,
        
        // Confirm modal
        confirmModal: null,
        confirmTitle: null,
        confirmMessage: null,
        confirmOk: null,
        confirmCancel: null,
        
        // Global
        globalUnreadBadge: null
    };

    // Initialize
    function init() {
        if (!CURRENT_USER_ID) {
            console.error('Chat widget: User ID not found');
            return;
        }

        cacheElements();
        renderEmojiPicker();
        attachEventListeners();
        loadInitialData();
        startGlobalPolling();
    }

    // Cache DOM elements
    function cacheElements() {
        elements.widget = document.getElementById('chatWidget');
        elements.toggleBtn = document.getElementById('chatToggleBtn');
        elements.popup = document.getElementById('chatPopupPanel');
        elements.closeBtn = document.getElementById('chatCloseBtn');
        elements.minimizeBtn = document.getElementById('chatMinimizeBtn');
        
        elements.recipientView = document.getElementById('chatRecipientView');
        elements.recipientSearch = document.getElementById('chatRecipientSearch');
        elements.recipientsList = document.getElementById('chatRecipientsList');
        
        elements.conversationView = document.getElementById('chatConversationView');
        elements.backBtn = document.getElementById('chatBackBtn');
        elements.conversationName = document.getElementById('chatConversationName');
        elements.conversationAvatar = document.getElementById('chatConversationAvatar');
        elements.messagesContainer = document.getElementById('chatMessagesContainer');
        elements.messageInput = document.getElementById('chatMessageInput');
        elements.sendBtn = document.getElementById('chatSendBtn');
        elements.typingIndicator = document.getElementById('chatTypingIndicator');
        elements.clearHistoryBtn = document.getElementById('chatClearHistoryBtn');
        
        // Input tools
        elements.emojiBtn = document.getElementById('chatEmojiBtn');
        elements.attachPhotoBtn = document.getElementById('chatAttachPhotoBtn');
        elements.photoInput = document.getElementById('chatPhotoInput');
        elements.photoPreview = document.getElementById('chatPhotoPreview');
        elements.photoPreviewImg = document.getElementById('chatPhotoPreviewImg');
        elements.photoPreviewRemove = document.getElementById('chatPhotoPreviewRemove');
        
        // Emoji picker
        elements.emojiPicker = document.getElementById('chatEmojiPicker');
        elements.emojiGrid = document.getElementById('chatEmojiGrid');
        
        // Photo modal
        elements.photoModal = document.getElementById('chatPhotoModal');
        elements.photoModalImg = document.getElementById('chatPhotoModalImg');
        elements.photoModalClose = document.getElementById('chatPhotoModalClose');
        
        // Confirm modal
        elements.confirmModal = document.getElementById('chatConfirmModal');
        elements.confirmTitle = document.getElementById('chatConfirmTitle');
        elements.confirmMessage = document.getElementById('chatConfirmMessage');
        elements.confirmOk = document.getElementById('chatConfirmOk');
        elements.confirmCancel = document.getElementById('chatConfirmCancel');
        
        elements.globalUnreadBadge = document.getElementById('chatGlobalUnreadBadge');
    }

    // Attach event listeners
    function attachEventListeners() {
        // Toggle button
        elements.toggleBtn?.addEventListener('click', togglePopup);
        
        // Close/minimize buttons
        elements.closeBtn?.addEventListener('click', closePopup);
        elements.minimizeBtn?.addEventListener('click', closePopup);
        
        // Back button
        elements.backBtn?.addEventListener('click', showRecipientsView);
        
        // Clear history button
        elements.clearHistoryBtn?.addEventListener('click', handleClearHistory);
        
        // Search
        elements.recipientSearch?.addEventListener('input', handleRecipientSearch);
        
        // Message input
        elements.messageInput?.addEventListener('input', handleMessageInput);
        elements.messageInput?.addEventListener('keydown', handleMessageKeyDown);
        
        // Send button
        elements.sendBtn?.addEventListener('click', sendMessage);
        
        // Emoji picker
        elements.emojiBtn?.addEventListener('click', toggleEmojiPicker);
        
        // Photo attachment
        elements.attachPhotoBtn?.addEventListener('click', () => elements.photoInput?.click());
        elements.photoInput?.addEventListener('change', handlePhotoSelect);
        elements.photoPreviewRemove?.addEventListener('click', removePhotoPreview);
        
        // Photo modal
        elements.photoModalClose?.addEventListener('click', closePhotoModal);
        elements.photoModal?.addEventListener('click', (e) => {
            if (e.target === elements.photoModal || e.target.classList.contains('chat-photo-modal-overlay')) {
                closePhotoModal();
            }
        });
        
        // Confirm modal
        elements.confirmOk?.addEventListener('click', handleConfirmOk);
        elements.confirmCancel?.addEventListener('click', closeConfirmModal);
        
        // Click outside to close
        document.addEventListener('click', handleOutsideClick);
    }

    // ============================================
    // POPUP MANAGEMENT
    // ============================================

    function togglePopup() {
        if (state.isOpen) {
            closePopup();
        } else {
            openPopup();
        }
    }

    function openPopup() {
        if (state.isOpen) return;
        
        state.isOpen = true;
        elements.popup?.classList.add('active');
        document.body.classList.add('chat-widget-open');
        
        // Load recipients if not loaded
        if (state.recipients.length === 0) {
            loadRecipients();
        }
        
        // Start polling if conversation is open
        if (state.selectedUserId) {
            startPolling();
        }
    }

    function closePopup() {
        state.isOpen = false;
        elements.popup?.classList.remove('active');
        document.body.classList.remove('chat-widget-open');
        stopPolling();
        closeEmojiPicker();
    }

    function handleOutsideClick(e) {
        // Close emoji picker if clicking outside
        if (state.isEmojiPickerOpen && elements.emojiPicker) {
            if (!elements.emojiPicker.contains(e.target) && !elements.emojiBtn?.contains(e.target)) {
                closeEmojiPicker();
            }
        }
        
        // Close popup if clicking outside
        if (!state.isOpen) return;
        if (!elements.widget) return;
        
        if (!elements.widget.contains(e.target) && !elements.emojiPicker?.contains(e.target)) {
            closePopup();
        }
    }

    // ============================================
    // VIEW MANAGEMENT
    // ============================================

    function showRecipientsView() {
        state.currentView = 'recipients';
        elements.recipientView?.classList.add('active');
        elements.conversationView?.classList.remove('active');
        
        // Reset conversation state
        state.selectedUserId = null;
        state.selectedUserName = null;
        state.selectedUserAvatar = null;
        state.messages = [];
        state.lastMessageId = 0;
        
        stopPolling();
        
        // Refresh recipients list
        loadRecipients();
    }

    function showConversationView(userId, userName, userAvatar) {
        state.currentView = 'conversation';
        state.selectedUserId = userId;
        state.selectedUserName = userName;
        state.selectedUserAvatar = userAvatar;
        
        elements.recipientView?.classList.remove('active');
        elements.conversationView?.classList.add('active');
        
        // Update conversation header
        updateConversationHeader(userName, userAvatar);
        
        // Load messages
        loadMessages(userId);
        
        // Start polling
        startPolling();
        
        // Focus input
        elements.messageInput?.focus();
    }

    function updateConversationHeader(userName, userAvatar) {
        if (elements.conversationName) {
            elements.conversationName.textContent = userName;
        }
        
        if (elements.conversationAvatar) {
            if (userAvatar) {
                const initials = getInitials(userName);
                elements.conversationAvatar.innerHTML = `<img src="${escapeHtml(userAvatar)}" alt="${escapeHtml(userName)}" onerror="this.onerror=null; this.parentElement.textContent='${escapeHtml(initials)}';">`;
            } else {
                elements.conversationAvatar.textContent = getInitials(userName);
            }
        }
    }

    // ============================================
    // DATA LOADING
    // ============================================

    function loadInitialData() {
        loadGlobalUnreadCount();
    }

    async function loadRecipients(search = '') {
        try {
            const url = new URL(API_ENDPOINT, window.location.origin);
            url.searchParams.set('action', 'get_users');
            if (search) {
                url.searchParams.set('search', search);
            }
            
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success && data.users) {
                state.recipients = data.users;
                renderRecipients(data.users);
            } else {
                showRecipientsError('Failed to load contacts');
            }
        } catch (error) {
            console.error('Error loading recipients:', error);
            showRecipientsError('Network error. Please try again.');
        }
    }

    async function loadMessages(userId) {
        try {
            const url = new URL(API_ENDPOINT, window.location.origin);
            url.searchParams.set('action', 'get_messages');
            url.searchParams.set('user_id', userId);
            url.searchParams.set('limit', '50');
            
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success && data.messages) {
                state.messages = data.messages;
                state.lastMessageId = data.messages.length > 0 
                    ? data.messages[data.messages.length - 1].id 
                    : 0;
                
                renderMessages(data.messages);
                scrollToBottom(true);
                
                // Mark messages as read
                markMessagesAsRead(userId);
            } else {
                showMessagesError('Failed to load messages');
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            showMessagesError('Network error. Please try again.');
        }
    }

    async function loadGlobalUnreadCount() {
        try {
            const url = new URL(API_ENDPOINT, window.location.origin);
            url.searchParams.set('action', 'get_unread_count');
            
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                state.totalUnreadCount = data.unread_count || 0;
                updateGlobalUnreadBadge();
            }
        } catch (error) {
            console.error('Error loading unread count:', error);
        }
    }

    // ============================================
    // RENDERING
    // ============================================

    function renderRecipients(users) {
        if (!elements.recipientsList) return;

        if (users.length === 0) {
            elements.recipientsList.innerHTML = `
                <div class="chat-empty-state">
                    <i class="fas fa-users"></i>
                    <p>No contacts found</p>
                </div>
            `;
            return;
        }

        const html = users.map(user => {
            const initials = getInitials(user.name);
            const unreadBadge = user.unread_count > 0 
                ? `<span class="chat-recipient-badge">${user.unread_count}</span>` 
                : '';
            
            let preview = '';
            if (user.last_message) {
                const prefix = user.last_message_from_me ? 'You: ' : '';
                const message = escapeHtml(user.last_message);
                const truncated = message.length > 35 ? message.substring(0, 32) + '...' : message;
                preview = `${prefix}${truncated}`;
            } else {
                preview = 'No messages yet';
            }
            
            return `
                <div class="chat-recipient-item" 
                     data-user-id="${user.id}"
                     data-user-name="${escapeHtml(user.name)}"
                     data-user-avatar="${user.avatar_url || ''}">
                    <div class="chat-recipient-avatar">
                        ${user.avatar_url 
                            ? `<img src="${escapeHtml(user.avatar_url)}" alt="${escapeHtml(user.name)}" onerror="this.onerror=null; this.parentElement.textContent='${escapeHtml(initials)}';">` 
                            : initials
                        }
                    </div>
                    <div class="chat-recipient-info">
                        <div class="chat-recipient-name">${escapeHtml(user.name)}</div>
                        <div class="chat-recipient-preview ${!user.last_message ? 'text-muted' : ''}">${preview}</div>
                    </div>
                    ${unreadBadge}
                </div>
            `;
        }).join('');

        elements.recipientsList.innerHTML = html;

        // Attach click handlers
        elements.recipientsList.querySelectorAll('.chat-recipient-item').forEach(item => {
            item.addEventListener('click', function() {
                const userId = parseInt(this.dataset.userId);
                const userName = this.dataset.userName;
                const userAvatar = this.dataset.userAvatar;
                showConversationView(userId, userName, userAvatar);
            });
        });
    }

    function renderMessages(messages) {
        if (!elements.messagesContainer) return;

        if (messages.length === 0) {
            elements.messagesContainer.innerHTML = `
                <div class="chat-empty-state">
                    <i class="fas fa-comments"></i>
                    <p>No messages yet. Start the conversation!</p>
                </div>
            `;
            return;
        }

        const html = messages.map(msg => createMessageHTML(msg)).join('');
        elements.messagesContainer.innerHTML = html;
    }

    function createMessageHTML(msg) {
        const isSent = msg.sender_id == CURRENT_USER_ID;
        const messageClass = isSent ? 'sent' : 'received';
        const time = formatTime(msg.created_at);
        
        let statusIcon = '';
        if (isSent) {
            if (msg.is_read == 1) {
                statusIcon = '<span class="chat-message-status read"><i class="fas fa-check"></i><i class="fas fa-check"></i></span>';
            } else {
                statusIcon = '<span class="chat-message-status"><i class="fas fa-check"></i></span>';
            }
        }
        
        let attachmentHTML = '';
        if (msg.attachment_url && msg.attachment_type === 'image') {
            attachmentHTML = `
                <div class="chat-message-attachment" onclick="window.chatWidget.openPhotoModal('${escapeHtml(msg.attachment_url)}')">
                    <img src="${escapeHtml(msg.attachment_url)}" alt="Attachment">
                </div>
            `;
        }
        
        const messageText = msg.message !== '[Photo]' 
            ? `<div class="chat-message-bubble">${escapeHtml(msg.message).replace(/\n/g, '<br>')}</div>` 
            : '';
        
        return `
            <div class="chat-message ${messageClass}" data-message-id="${msg.id}">
                ${messageText}
                ${attachmentHTML}
                <div class="chat-message-meta">
                    <span class="chat-message-time">${time}</span>
                    ${statusIcon}
                </div>
            </div>
        `;
    }

    function appendMessage(msg) {
        if (!elements.messagesContainer) return;
        
        // Remove empty state if exists
        const emptyState = elements.messagesContainer.querySelector('.chat-empty-state');
        if (emptyState) {
            emptyState.remove();
        }
        
        const messageHTML = createMessageHTML(msg);
        elements.messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
    }

    function updateGlobalUnreadBadge() {
        if (!elements.globalUnreadBadge) return;
        
        if (state.totalUnreadCount > 0) {
            elements.globalUnreadBadge.textContent = state.totalUnreadCount > 99 
                ? '99+' 
                : state.totalUnreadCount;
            elements.globalUnreadBadge.style.display = 'flex';
        } else {
            elements.globalUnreadBadge.style.display = 'none';
        }
    }

    // ============================================
    // MESSAGE SENDING
    // ============================================

    async function sendMessage() {
        const message = elements.messageInput?.value.trim();
        const hasPhoto = state.photoFile !== null;
        
        if ((!message && !hasPhoto) || !state.selectedUserId) {
            return;
        }
        
        // Disable input while sending
        if (elements.messageInput) elements.messageInput.disabled = true;
        if (elements.sendBtn) elements.sendBtn.disabled = true;
        if (elements.emojiBtn) elements.emojiBtn.disabled = true;
        if (elements.attachPhotoBtn) elements.attachPhotoBtn.disabled = true;

        try {
            const formData = new FormData();
            
            if (hasPhoto) {
                // Upload photo with optional caption
                formData.append('action', 'upload_photo');
                formData.append('receiver_id', state.selectedUserId);
                formData.append('photo', state.photoFile);
                formData.append('caption', message || '');
            } else {
                // Send text message
                formData.append('action', 'send_message');
                formData.append('receiver_id', state.selectedUserId);
                formData.append('message', message);
            }
            
            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success && data.message) {
                // Clear input and photo preview
                if (elements.messageInput) {
                    elements.messageInput.value = '';
                    elements.messageInput.style.height = 'auto';
                }
                
                if (hasPhoto) {
                    removePhotoPreview();
                }
                
                // Add message to state and UI
                state.messages.push(data.message);
                state.lastMessageId = data.message.id;
                appendMessage(data.message);
                scrollToBottom(true);
                
                // Stop typing indicator
                stopTypingIndicator();
                
                // Refresh recipients list (updates order)
                if (!state.isOpen || state.currentView === 'recipients') {
                    loadRecipients();
                }
                
                // Update global unread (other conversations might have new messages)
                loadGlobalUnreadCount();
            } else {
                alert(data.error || 'Failed to send message');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            alert('Network error. Please try again.');
        } finally {
            // Re-enable input
            if (elements.messageInput) elements.messageInput.disabled = false;
            if (elements.sendBtn) elements.sendBtn.disabled = false;
            if (elements.emojiBtn) elements.emojiBtn.disabled = false;
            if (elements.attachPhotoBtn) elements.attachPhotoBtn.disabled = false;
            elements.messageInput?.focus();
        }
    }

    // ============================================
    // POLLING & REAL-TIME UPDATES
    // ============================================

    function startPolling() {
        if (state.isPolling || !state.selectedUserId) return;
        
        state.isPolling = true;
        state.pollTimer = setInterval(() => {
            pollNewMessages();
            pollTypingStatus();
        }, POLL_INTERVAL);
    }

    function stopPolling() {
        if (!state.isPolling) return;
        
        state.isPolling = false;
        if (state.pollTimer) {
            clearInterval(state.pollTimer);
            state.pollTimer = null;
        }
    }

    function startGlobalPolling() {
        // Poll for global unread count every 5 seconds
        setInterval(() => {
            loadGlobalUnreadCount();
            
            // Refresh recipients if on recipients view and popup is open
            if (state.isOpen && state.currentView === 'recipients') {
                loadRecipients();
            }
        }, 5000);
    }

    async function pollNewMessages() {
        if (!state.selectedUserId || !state.lastMessageId) return;

        try {
            const url = new URL(API_ENDPOINT, window.location.origin);
            url.searchParams.set('action', 'get_messages');
            url.searchParams.set('user_id', state.selectedUserId);
            url.searchParams.set('limit', '50');
            
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
                    
                    // Mark new messages as read
                    markMessagesAsRead(state.selectedUserId);
                    
                    // Update global unread count
                    loadGlobalUnreadCount();
                }
            }
        } catch (error) {
            console.error('Error polling messages:', error);
        }
    }

    async function pollTypingStatus() {
        if (!state.selectedUserId) return;

        try {
            const url = new URL(API_ENDPOINT, window.location.origin);
            url.searchParams.set('action', 'get_typing_status');
            url.searchParams.set('user_id', state.selectedUserId);
            
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                if (data.is_typing) {
                    elements.typingIndicator?.classList.add('active');
                } else {
                    elements.typingIndicator?.classList.remove('active');
                }
            }
        } catch (error) {
            console.error('Error polling typing status:', error);
        }
    }

    async function markMessagesAsRead(userId) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_as_read');
            formData.append('sender_id', userId);
            
            await fetch(API_ENDPOINT, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            // Update UI to show messages as read
            updateMessageReadStatus();
            
            // Update global unread count
            loadGlobalUnreadCount();
        } catch (error) {
            console.error('Error marking messages as read:', error);
        }
    }

    function updateMessageReadStatus() {
        // Update all sent messages to show as read
        const sentMessages = elements.messagesContainer?.querySelectorAll('.chat-message.sent');
        sentMessages?.forEach(msg => {
            const statusSpan = msg.querySelector('.chat-message-status');
            if (statusSpan && !statusSpan.classList.contains('read')) {
                statusSpan.classList.add('read');
                statusSpan.innerHTML = '<i class="fas fa-check"></i><i class="fas fa-check"></i>';
            }
        });
    }

    // ============================================
    // TYPING INDICATOR
    // ============================================

    function handleMessageInput(e) {
        autoResizeTextarea(e.target);
        sendTypingStatus();
    }

    function sendTypingStatus() {
        if (!state.selectedUserId) return;
        
        // Clear existing timer
        if (state.typingTimer) {
            clearTimeout(state.typingTimer);
        }
        
        // Send typing status
        const formData = new FormData();
        formData.append('action', 'set_typing_status');
        formData.append('recipient_id', state.selectedUserId);
        formData.append('is_typing', '1');
        
        fetch(API_ENDPOINT, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).catch(err => console.error('Error sending typing status:', err));
        
        // Set timer to stop typing
        state.typingTimer = setTimeout(stopTypingIndicator, TYPING_TIMEOUT);
    }

    function stopTypingIndicator() {
        if (!state.selectedUserId) return;
        
        const formData = new FormData();
        formData.append('action', 'set_typing_status');
        formData.append('recipient_id', state.selectedUserId);
        formData.append('is_typing', '0');
        
        fetch(API_ENDPOINT, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).catch(err => console.error('Error stopping typing status:', err));
    }

    // ============================================
    // SEARCH
    // ============================================

    function handleRecipientSearch(e) {
        const search = e.target.value.trim();
        
        // Clear existing debounce
        if (state.searchDebounce) {
            clearTimeout(state.searchDebounce);
        }
        
        // Debounce search
        state.searchDebounce = setTimeout(() => {
            loadRecipients(search);
        }, 300);
    }

    // ============================================
    // INPUT HANDLING
    // ============================================

    function handleMessageKeyDown(e) {
        // Send on Enter (without Shift)
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }

    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    }

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================

    function scrollToBottom(immediate = false) {
        if (!elements.messagesContainer) return;
        
        const scrollOptions = immediate 
            ? { behavior: 'auto' }
            : { behavior: 'smooth' };
        
        elements.messagesContainer.scrollTop = elements.messagesContainer.scrollHeight;
    }

    function getInitials(name) {
        if (!name) return '?';
        return name
            .split(' ')
            .map(word => word[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        // Less than 1 minute
        if (diff < 60000) {
            return 'Just now';
        }
        
        // Less than 1 hour
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `${minutes}m ago`;
        }
        
        // Today
        if (date.toDateString() === now.toDateString()) {
            return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        }
        
        // Yesterday
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        if (date.toDateString() === yesterday.toDateString()) {
            return 'Yesterday';
        }
        
        // This week
        if (diff < 604800000) {
            return date.toLocaleDateString('en-US', { weekday: 'short' });
        }
        
        // Older
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    // ============================================
    // EMOJI PICKER
    // ============================================

    function renderEmojiPicker() {
        if (!elements.emojiGrid) return;
        
        const html = EMOJIS.map(emoji => `
            <button class="chat-emoji-item" type="button" data-emoji="${emoji}">
                ${emoji}
            </button>
        `).join('');
        
        elements.emojiGrid.innerHTML = html;
        
        // Attach click handlers
        elements.emojiGrid.querySelectorAll('.chat-emoji-item').forEach(btn => {
            btn.addEventListener('click', function() {
                insertEmoji(this.dataset.emoji);
            });
        });
    }

    function toggleEmojiPicker() {
        if (!elements.emojiPicker) return;
        
        if (state.isEmojiPickerOpen) {
            closeEmojiPicker();
        } else {
            openEmojiPicker();
        }
    }

    function openEmojiPicker() {
        if (!elements.emojiPicker) return;
        state.isEmojiPickerOpen = true;
        elements.emojiPicker.style.display = 'block';
    }

    function closeEmojiPicker() {
        if (!elements.emojiPicker) return;
        state.isEmojiPickerOpen = false;
        elements.emojiPicker.style.display = 'none';
    }

    function insertEmoji(emoji) {
        if (!elements.messageInput) return;
        
        const input = elements.messageInput;
        const start = input.selectionStart;
        const end = input.selectionEnd;
        const text = input.value;
        
        // Insert emoji at cursor position
        const before = text.substring(0, start);
        const after = text.substring(end);
        input.value = before + emoji + after;
        
        // Move cursor after emoji
        const newPos = start + emoji.length;
        input.setSelectionRange(newPos, newPos);
        input.focus();
        
        // Close emoji picker
        closeEmojiPicker();
        
        // Trigger input event for auto-resize
        input.dispatchEvent(new Event('input'));
    }

    // ============================================
    // PHOTO ATTACHMENT
    // ============================================

    function handlePhotoSelect(e) {
        const file = e.target.files?.[0];
        if (!file) return;
        
        // Validate file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, WEBP, or GIF)');
            return;
        }
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Image is too large. Maximum size is 5MB.');
            return;
        }
        
        // Store file
        state.photoFile = file;
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            state.photoPreviewUrl = e.target.result;
            if (elements.photoPreviewImg) {
                elements.photoPreviewImg.src = e.target.result;
            }
            if (elements.photoPreview) {
                elements.photoPreview.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
        
        // Clear file input
        if (elements.photoInput) {
            elements.photoInput.value = '';
        }
    }

    function removePhotoPreview() {
        state.photoFile = null;
        state.photoPreviewUrl = null;
        
        if (elements.photoPreview) {
            elements.photoPreview.style.display = 'none';
        }
        if (elements.photoPreviewImg) {
            elements.photoPreviewImg.src = '';
        }
        if (elements.photoInput) {
            elements.photoInput.value = '';
        }
    }

    // ============================================
    // PHOTO MODAL
    // ============================================

    function openPhotoModal(imageUrl) {
        if (!elements.photoModal || !elements.photoModalImg) return;
        
        elements.photoModalImg.src = imageUrl;
        elements.photoModal.style.display = 'flex';
    }

    function closePhotoModal() {
        if (!elements.photoModal) return;
        
        elements.photoModal.style.display = 'none';
        if (elements.photoModalImg) {
            elements.photoModalImg.src = '';
        }
    }

    // ============================================
    // CLEAR HISTORY
    // ============================================

    function handleClearHistory() {
        if (!state.selectedUserId || !state.selectedUserName) return;
        
        showConfirmModal(
            'Clear Chat History',
            `Clear all messages with ${state.selectedUserName} from your view? They will still see the conversation. This action cannot be undone.`,
            async () => {
                await clearChatHistory(state.selectedUserId);
            }
        );
    }

    async function clearChatHistory(userId) {
        try {
            const formData = new FormData();
            formData.append('action', 'clear_history');
            formData.append('user_id', userId);
            
            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                // Check if feature is actually available
                if (data.feature_available === false) {
                    // Feature not available - show error
                    alert(data.error || 'Clear history feature is not available. Please run the migration.');
                    return;
                }
                
                // Clear messages from UI
                state.messages = [];
                state.lastMessageId = 0;
                
                if (elements.messagesContainer) {
                    elements.messagesContainer.innerHTML = `
                        <div class="chat-empty-state">
                            <i class="fas fa-comments"></i>
                            <p>Chat history cleared from your view. Start a new conversation!</p>
                        </div>
                    `;
                }
                
                // Refresh recipients list
                loadRecipients();
                
                // Show success message
                const count = data.cleared_count || 0;
                console.log(`Chat history cleared successfully (${count} messages hidden from your view)`);
            } else {
                alert(data.error || 'Failed to clear chat history');
            }
        } catch (error) {
            console.error('Error clearing chat history:', error);
            alert('Network error. Please try again.');
        }
    }

    // ============================================
    // CONFIRMATION MODAL
    // ============================================

    function showConfirmModal(title, message, callback) {
        if (!elements.confirmModal) return;
        
        state.confirmCallback = callback;
        
        if (elements.confirmTitle) {
            elements.confirmTitle.textContent = title;
        }
        if (elements.confirmMessage) {
            elements.confirmMessage.textContent = message;
        }
        
        elements.confirmModal.style.display = 'flex';
    }

    function closeConfirmModal() {
        if (!elements.confirmModal) return;
        
        elements.confirmModal.style.display = 'none';
        state.confirmCallback = null;
    }

    function handleConfirmOk() {
        if (state.confirmCallback) {
            state.confirmCallback();
        }
        closeConfirmModal();
    }

    // ============================================
    // ERROR HANDLING
    // ============================================

    function showRecipientsError(message) {
        if (!elements.recipientsList) return;
        elements.recipientsList.innerHTML = `
            <div class="chat-empty-state">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${escapeHtml(message)}</p>
            </div>
        `;
    }

    function showMessagesError(message) {
        if (!elements.messagesContainer) return;
        elements.messagesContainer.innerHTML = `
            <div class="chat-empty-state">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${escapeHtml(message)}</p>
            </div>
        `;
    }

    // ============================================
    // INITIALIZATION
    // ============================================

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ============================================
    // EXPOSE PUBLIC API
    // ============================================

    // Expose functions that need to be called from HTML
    window.chatWidget = {
        openPhotoModal: openPhotoModal
    };

})();
