<?php
/**
 * Private Chat System - One-to-One Messaging
 * Secure, real-time messaging between authenticated users
 */

// Check authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo '<div class="alert alert-danger">Access denied. Please log in.</div>';
    return;
}

$current_user_id = $_SESSION['user_id'] ?? null;
$current_user_name = $_SESSION['name'] ?? 'User';

if (!$current_user_id) {
    echo '<div class="alert alert-danger">User session invalid.</div>';
    return;
}
?>

<div class="container-fluid chat-system-container">
    <div class="page-header-modern mb-4">
        <div class="page-title-modern">
            <h1 class="page-title-main">
                <i class="fas fa-comments me-2"></i>Private Messages
            </h1>
            <p class="page-subtitle">Secure one-to-one communication with team members</p>
        </div>
        <div class="page-actions-modern">
            <span class="badge bg-success" id="onlineStatus">
                <i class="fas fa-circle me-1"></i>Online
            </span>
        </div>
    </div>

    <div class="chat-main-wrapper">
        <!-- Left Panel: User List -->
        <div class="chat-users-panel">
            <div class="chat-users-header">
                <h5 class="mb-0">Contacts</h5>
                <button class="btn btn-sm btn-outline-primary" id="refreshUsersBtn" title="Refresh contacts">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            
            <div class="chat-search-box">
                <i class="fas fa-search"></i>
                <input type="text" 
                       class="form-control" 
                       id="userSearchInput" 
                       placeholder="Search users...">
            </div>
            
            <div class="chat-users-list" id="chatUsersList">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm mb-2" role="status"></div>
                    <div>Loading contacts...</div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Chat Area -->
        <div class="chat-conversation-panel">
            <!-- Default Empty State -->
            <div class="chat-empty-state" id="chatEmptyState">
                <i class="fas fa-comments"></i>
                <h4>Welcome to Private Messages</h4>
                <p>Select a contact from the left panel to start a conversation</p>
            </div>

            <!-- Chat Header -->
            <div class="chat-header" id="chatHeader" style="display: none;">
                <div class="chat-header-user">
                    <div class="chat-header-avatar" id="chatHeaderAvatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="chat-header-info">
                        <h5 class="mb-0" id="chatHeaderName">Select a user</h5>
                        <small class="text-muted" id="chatHeaderStatus">Offline</small>
                    </div>
                </div>
                <div class="chat-header-actions">
                    <button class="btn btn-sm btn-outline-secondary" id="refreshMessagesBtn" title="Refresh messages">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>

            <!-- Messages Container -->
            <div class="chat-messages-container" id="chatMessagesContainer" style="display: none;">
                <div class="chat-messages" id="chatMessages">
                    <!-- Messages will be inserted here -->
                </div>
                
                <!-- Typing Indicator -->
                <div class="chat-typing-indicator" id="chatTypingIndicator" style="display: none;">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <span class="typing-text">typing...</span>
                </div>
            </div>

            <!-- Message Input -->
            <div class="chat-input-container" id="chatInputContainer" style="display: none;">
                <form id="chatMessageForm">
                    <div class="chat-input-wrapper">
                        <textarea 
                            class="form-control chat-message-input" 
                            id="messageInput" 
                            placeholder="Type your message..."
                            rows="1"></textarea>
                        <button type="submit" 
                                class="btn btn-primary chat-send-btn" 
                                id="sendMessageBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div class="chat-input-footer">
                        <small class="text-muted">
                            Press Enter to send, Shift+Enter for new line
                        </small>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- User Data -->
<script>
    window.CHAT_CONFIG = {
        currentUserId: <?php echo json_encode($current_user_id); ?>,
        currentUserName: <?php echo json_encode($current_user_name); ?>,
        apiEndpoint: '/api/chat.php',
        pollInterval: 3000, // Poll every 3 seconds
        typingTimeout: 5000 // Typing indicator timeout
    };
</script>

<!-- Include Chat JavaScript -->
<script src="/assets/js/chat.js"></script>

<!-- Chat System Styles -->
<style>
/* Modern Chat System Styles */
.chat-system-container {
    height: calc(100vh - 140px);
    display: flex;
    flex-direction: column;
}

.page-header-modern {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.5rem 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title-main {
    font-size: 1.75rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.page-subtitle {
    color: #64748b;
    margin: 0.25rem 0 0 0;
    font-size: 0.875rem;
}

.chat-main-wrapper {
    display: flex;
    gap: 1.5rem;
    flex: 1;
    min-height: 0;
}

/* Users Panel */
.chat-users-panel {
    width: 320px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}

.chat-users-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-users-header h5 {
    font-weight: 600;
    color: #0f172a;
}

.chat-search-box {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    position: relative;
}

.chat-search-box i {
    position: absolute;
    left: 2rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.chat-search-box input {
    padding-left: 2.5rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.chat-search-box input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.chat-users-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem;
}

.chat-user-item {
    display: flex;
    align-items: center;
    padding: 0.875rem 1rem;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 0.25rem;
}

.chat-user-item:hover {
    background: #f1f5f9;
}

.chat-user-item.active {
    background: #eff6ff;
    border-left: 3px solid #3b82f6;
}

.chat-user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.125rem;
    margin-right: 0.875rem;
    flex-shrink: 0;
}

.chat-user-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.chat-user-info {
    flex: 1;
    min-width: 0;
}

.chat-user-name {
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.125rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-user-last-message {
    font-size: 0.8125rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}

.chat-user-item.active .chat-user-last-message {
    color: #475569;
}

.chat-user-last-message.text-muted {
    font-style: italic;
    color: #94a3b8;
}

.chat-user-role {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: capitalize;
}

.chat-user-badge {
    background: #ef4444;
    color: white;
    border-radius: 12px;
    padding: 0.125rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

/* Conversation Panel */
.chat-conversation-panel {
    flex: 1;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    min-height: 0;
}

.chat-empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
}

.chat-empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.chat-empty-state h4 {
    color: #475569;
    margin-bottom: 0.5rem;
}

.chat-empty-state p {
    color: #94a3b8;
}

.chat-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-header-user {
    display: flex;
    align-items: center;
}

.chat-header-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.125rem;
    margin-right: 0.875rem;
}

.chat-header-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.chat-header-name {
    font-weight: 600;
    color: #0f172a;
}

.chat-header-status {
    color: #64748b;
    font-size: 0.875rem;
}

.chat-messages-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.chat-message {
    display: flex;
    gap: 0.75rem;
    max-width: 70%;
    animation: messageSlideIn 0.3s ease;
}

@keyframes messageSlideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chat-message.sent {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.chat-message.received {
    align-self: flex-start;
}

.chat-message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.chat-message-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.chat-message-content {
    flex: 1;
}

.chat-message-bubble {
    padding: 0.875rem 1.125rem;
    border-radius: 16px;
    word-wrap: break-word;
    white-space: pre-wrap;
}

.chat-message.sent .chat-message-bubble {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.chat-message.received .chat-message-bubble {
    background: #f1f5f9;
    color: #0f172a;
    border-bottom-left-radius: 4px;
}

.chat-message-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: #94a3b8;
}

.chat-message.sent .chat-message-meta {
    justify-content: flex-end;
}

.chat-message-time {
    font-size: 0.75rem;
}

.chat-message-status {
    display: flex;
    align-items: center;
}

.chat-message-status i {
    font-size: 0.875rem;
}

.chat-message-status.read {
    color: #3b82f6;
}

/* Typing Indicator */
.chat-typing-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0 1.5rem 1rem 1.5rem;
    color: #64748b;
    font-size: 0.875rem;
}

.typing-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #94a3b8;
    animation: typingBounce 1.4s infinite;
}

.typing-dot:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typingBounce {
    0%, 60%, 100% {
        transform: translateY(0);
    }
    30% {
        transform: translateY(-10px);
    }
}

/* Message Input */
.chat-input-container {
    border-top: 1px solid #e2e8f0;
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
}

.chat-input-wrapper {
    display: flex;
    gap: 0.75rem;
    align-items: flex-end;
}

.chat-message-input {
    flex: 1;
    resize: none;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 0.875rem 1rem;
    max-height: 120px;
    font-size: 0.9375rem;
}

.chat-message-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.chat-send-btn {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border: none;
    transition: all 0.2s ease;
}

.chat-send-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.chat-send-btn:active {
    transform: translateY(0);
}

.chat-input-footer {
    margin-top: 0.5rem;
    text-align: center;
}

/* Scrollbar Styling */
.chat-users-list::-webkit-scrollbar,
.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-users-list::-webkit-scrollbar-track,
.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.chat-users-list::-webkit-scrollbar-thumb,
.chat-messages::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.chat-users-list::-webkit-scrollbar-thumb:hover,
.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive Design */
@media (max-width: 768px) {
    .chat-main-wrapper {
        flex-direction: column;
    }
    
    .chat-users-panel {
        width: 100%;
        max-height: 250px;
    }
    
    .chat-message {
        max-width: 85%;
    }
}

/* Loading State */
.chat-loading {
    text-align: center;
    padding: 2rem;
    color: #94a3b8;
}

/* Badge Styling */
#onlineStatus {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
}

#onlineStatus i {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>
