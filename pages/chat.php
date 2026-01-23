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

<div class="container-fluid hrdash chat-system-container">
    <div class="chat-main-wrapper">
        <!-- Left Panel: User List -->
        <div class="chat-users-panel">
            <div class="chat-users-header">
                <h5 class="mb-0">Messages</h5>
                <button class="chat-icon-btn" id="refreshUsersBtn" title="New message / refresh">
                    <span class="hr-icon hr-icon-plus"></span>
                </button>
            </div>

            <!-- Top avatars strip (populated by JS) -->
            <div class="chat-avatars-strip" id="chatTopAvatars" aria-label="Recent contacts">
                <div class="chat-avatars-strip__loading">Loading…</div>
            </div>
            
            <div class="chat-search-box">
                <span class="hr-icon hr-icon-search search-icon"></span>
                <input type="text" 
                       class="form-control" 
                       id="userSearchInput" 
                       placeholder="Search message">
            </div>

            <!-- Tabs: All / Pinned -->
            <div class="chat-list-tabs" role="tablist">
                <button type="button"
                        class="chat-list-tab active"
                        data-tab="all"
                        aria-selected="true">
                    All Messages
                </button>
                <button type="button"
                        class="chat-list-tab"
                        data-tab="pinned"
                        aria-selected="false">
                    Pinned <span class="chat-list-section-count" id="chatPinnedCount">0</span>
                </button>
            </div>

            <!-- Pinned conversations (hidden by default, shown when Pinned tab active) -->
            <div class="chat-users-list chat-users-list--pinned" id="chatPinnedList" style="display: none;">
                <div class="text-center text-muted py-2 small">No pinned conversations</div>
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
                    <button class="chat-icon-btn" id="refreshMessagesBtn" title="Refresh messages">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="chat-icon-btn" type="button" title="Call (demo)">
                        <i class="fas fa-phone"></i>
                    </button>
                    <button class="chat-icon-btn" type="button" title="Video (demo)">
                        <i class="fas fa-video"></i>
                    </button>
                    <button class="chat-icon-btn" type="button" title="More (demo)">
                        <i class="fas fa-ellipsis-h"></i>
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
                        <button class="chat-plus-btn" type="button" title="Attach (demo)" aria-label="Attach">
                            <span class="hr-icon hr-icon-plus"></span>
                        </button>
                        <textarea 
                            class="form-control chat-message-input" 
                            id="messageInput" 
                            placeholder="Type your message"
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

        <!-- Right Panel: Info (UI-only; populated by JS) -->
        <aside class="chat-info-panel" id="chatInfoPanel" aria-label="Conversation info">
            <div class="chat-info-header">
                <div class="chat-info-title">Group Info</div>
                <button class="chat-icon-btn" type="button" title="Open in new (demo)">
                    <i class="fas fa-external-link-alt"></i>
                </button>
            </div>

            <div class="chat-info-card">
                <div class="chat-info-user">
                    <div class="chat-info-avatar" id="chatInfoAvatar">U</div>
                    <div class="chat-info-user-meta">
                        <div class="chat-info-user-name" id="chatInfoName">Select a conversation</div>
                        <div class="chat-info-user-sub" id="chatInfoSub">Details will appear here</div>
                    </div>
                </div>

                <div class="chat-info-stats">
                    <div class="chat-info-stat">
                        <div class="chat-info-stat__label">Photos</div>
                        <div class="chat-info-stat__value">—</div>
                    </div>
                    <div class="chat-info-stat">
                        <div class="chat-info-stat__label">Audio</div>
                        <div class="chat-info-stat__value">—</div>
                    </div>
                    <div class="chat-info-stat">
                        <div class="chat-info-stat__label">Docs</div>
                        <div class="chat-info-stat__value">—</div>
                    </div>
                </div>

                <div class="chat-info-section">
                    <div class="chat-info-section__label">Members</div>
                    <div class="chat-info-members" id="chatInfoMembers">
                        <div class="text-muted small">—</div>
                    </div>
                </div>
            </div>
        </aside>
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
<script src="<?php echo asset_url('js/chat.js'); ?>"></script>

<!-- Chat System Styles -->
<style>
/* Modern Chat System Styles */
.chat-system-container {
    display: flex;
    flex-direction: column;
}

.chat-main-wrapper {
    display: flex;
    gap: 1.25rem;
    flex: 1;
    min-height: 600px;
    height: calc(100vh - 280px);
}

/* Users Panel */
.chat-users-panel {
    width: 360px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 28px rgba(15, 23, 42, 0.06);
}

.chat-users-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #eef2f7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-users-header h5 {
    font-weight: 600;
    color: #0f172a;
}

.chat-icon-btn {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.chat-icon-btn:hover {
    background: #f8fafc;
    border-color: #d1d5db;
    color: #0f172a;
}

.chat-avatars-strip {
    padding: 0.75rem 1.25rem 0.25rem 1.25rem;
    display: flex;
    gap: 0.5rem;
    overflow: hidden;
    flex-wrap: nowrap;
}

.chat-avatars-strip__loading {
    font-size: 0.8125rem;
    color: #64748b;
}

.chat-avatars-strip .chat-avatar-pill {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #0f172a;
    flex: 0 0 auto;
    cursor: pointer;
}

.chat-avatars-strip .chat-avatar-pill img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.chat-list-tabs {
    padding: 0.5rem 1.25rem 0.25rem 1.25rem;
    display: flex;
    gap: 0.5rem;
}

.chat-list-tab {
    border-radius: 999px;
    border: none;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 0.8125rem;
    padding: 0.35rem 0.9rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    cursor: pointer;
}

.chat-list-tab.active {
    background: #111827;
    color: #f9fafb;
}

.chat-list-section-count {
    background: rgba(249, 250, 251, 0.15);
    color: inherit;
    font-weight: 600;
    border-radius: 999px;
    padding: 0.05rem 0.45rem;
    font-size: 0.75rem;
}

.chat-users-list--pinned {
    padding: 0.35rem 0.5rem 0.25rem 0.5rem;
    max-height: 200px;
    overflow-y: auto;
    border-bottom: 1px solid #eef2f7;
}

.chat-search-box {
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid #eef2f7;
    position: relative;
}

.chat-search-box .search-icon {
    position: absolute;
    left: 1.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    width: 16px;
    height: 16px;
}

.chat-search-box input {
    padding-left: 2.5rem;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    background: #f8fafc;
}

.chat-search-box input:focus {
    background: #ffffff;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
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
    background: #f1f5ff;
    border-left: 3px solid #6366f1;
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
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 28px rgba(15, 23, 42, 0.06);
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
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #eef2f7;
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
    padding: 1.25rem;
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
    border-top: 1px solid #eef2f7;
    padding: 0.9rem 1.0rem;
    background: #ffffff;
}

.chat-input-wrapper {
    display: flex;
    gap: 0.75rem;
    align-items: flex-end;
}

.chat-plus-btn {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.chat-plus-btn:hover {
    background: #f8fafc;
    color: #0f172a;
}

.chat-message-input {
    flex: 1;
    resize: none;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    background: #f8fafc;
    padding: 0.875rem 1rem;
    max-height: 120px;
    font-size: 0.9375rem;
}

.chat-message-input:focus {
    background: #ffffff;
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

/* Info Panel */
.chat-info-panel {
    width: 340px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 8px 28px rgba(15, 23, 42, 0.06);
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.chat-info-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #eef2f7;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-info-title {
    font-weight: 700;
    color: #0f172a;
}

.chat-info-card {
    padding: 1rem 1.25rem 1.25rem 1.25rem;
    overflow-y: auto;
}

.chat-info-user {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eef2f7;
}

.chat-info-avatar {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    color: #0f172a;
    overflow: hidden;
}

.chat-info-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.chat-info-user-name {
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.chat-info-user-sub {
    font-size: 0.8125rem;
    color: #64748b;
}

.chat-info-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    padding: 1rem 0;
    border-bottom: 1px solid #eef2f7;
}

.chat-info-stat {
    background: #f8fafc;
    border: 1px solid #eef2f7;
    border-radius: 14px;
    padding: 0.75rem;
}

.chat-info-stat__label {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.chat-info-stat__value {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
}

.chat-info-section {
    padding-top: 1rem;
}

.chat-info-section__label {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 0.5rem;
}

.chat-info-members .chat-info-member {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem 0;
    border-top: 1px solid #f1f5f9;
}

.chat-info-member:first-child {
    border-top: none;
}

.chat-info-member__avatar {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    color: #0f172a;
    overflow: hidden;
    flex: 0 0 auto;
}

.chat-info-member__meta {
    min-width: 0;
}

.chat-info-member__name {
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-info-member__sub {
    font-size: 0.8125rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (max-width: 1200px) {
    .chat-info-panel {
        display: none;
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
