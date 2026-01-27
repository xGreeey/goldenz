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
                    <!-- Photo Preview (hidden by default) -->
                    <div id="chatPhotoPreview" class="chat-photo-preview" style="display: none;">
                        <img id="chatPhotoPreviewImg" src="" alt="Preview">
                        <button id="chatPhotoPreviewRemove" class="chat-photo-preview-remove" type="button" title="Remove photo">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Input Toolbar -->
                    <div class="chat-input-toolbar">
                        <button id="chatEmojiBtn" class="chat-input-tool-btn" type="button" title="Add emoji">
                            <i class="fas fa-smile"></i>
                        </button>
                        <button id="chatAttachPhotoBtn" class="chat-input-tool-btn" type="button" title="Attach photo">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <input 
                            type="file" 
                            id="chatPhotoInput" 
                            accept="image/jpeg,image/jpg,image/png,image/webp,image/gif" 
                            style="display: none;"
                        >
                    </div>
                    
                    <div class="chat-input-wrapper">
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
                    
                    <!-- Emoji Picker Panel - Positioned relative to input container -->
                    <div id="chatEmojiPicker" class="chat-emoji-picker" style="display: none;">
                        <div class="chat-emoji-grid" id="chatEmojiGrid">
                            <!-- Emojis will be rendered here -->
                        </div>
                    </div>
                </form>
            </div>
        </div>
    
    <!-- Photo Preview Modal -->
    <div id="chatPhotoModal" class="chat-photo-modal" style="display: none;">
        <div class="chat-photo-modal-overlay"></div>
        <div class="chat-photo-modal-content">
            <button id="chatPhotoModalClose" class="chat-photo-modal-close" type="button">
                <i class="fas fa-times"></i>
            </button>
            <img id="chatPhotoModalImg" src="" alt="Full size">
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
/* Global Emoji Support - Ensure proper rendering across all browsers */
@supports (font-variant-emoji: emoji) {
    * {
        font-variant-emoji: emoji;
    }
}

/* Emoji fallback font stack for all chat elements */
.chat-system-container,
.chat-system-container * {
    /* Emoji-compatible font stack - system fonts first for best performance */
    font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", "Android Emoji", "EmojiSymbols", "EmojiOne Mozilla", "Twemoji Mozilla", "Segoe UI", -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
}

/* Ensure text inputs preserve emoji rendering */
input[type="text"],
textarea {
    font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", "Android Emoji", "EmojiSymbols", "EmojiOne Mozilla", "Twemoji Mozilla", "Segoe UI", -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
}

/* Modern Chat System Styles */
.chat-system-container {
    display: flex;
    flex-direction: column;
}

.chat-main-wrapper {
    display: flex;
    gap: 1rem;
    flex: 1;
    min-height: 600px;
    height: calc(100vh - 200px);
    max-height: calc(100vh - 200px);
}

/* Users Panel */
.chat-users-panel {
    width: 320px;
    background: #ffffff;
    border: 1px solid #e8ecf1;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
    max-height: 100%;
    overflow: hidden;
}

.chat-users-header {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid #f1f4f8;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.chat-users-header h5 {
    font-weight: 600;
    color: #0f172a;
}

.chat-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e8ecf1;
    background: #ffffff;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    font-size: 0.875rem;
}

.chat-icon-btn:hover {
    background: #fafbfc;
    border-color: #d1d9e6;
    color: #0a0e27;
}

/* Ensure icons inside chat-icon-btn are visible */
.chat-icon-btn .hr-icon {
    display: inline-block !important;
    width: 16px !important;
    height: 16px !important;
    background-size: contain !important;
    background-repeat: no-repeat !important;
    background-position: center !important;
    opacity: 1 !important;
    visibility: visible !important;
    filter: brightness(0) saturate(100%) invert(40%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%);
}

.chat-icon-btn .hr-icon-plus {
    background-image: url('../assets/icons/plus-icon.png') !important;
}

.chat-icon-btn:hover .hr-icon {
    filter: brightness(0) saturate(100%) invert(15%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%);
}

.chat-icon-btn:hover {
    background: #f8fafc;
    border-color: #d1d5db;
    color: #0f172a;
}

.chat-avatars-strip {
    padding: 0.625rem 1rem 0.375rem 1rem;
    display: flex;
    gap: 0.5rem;
    overflow: hidden;
    flex-wrap: nowrap;
    flex-shrink: 0;
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
    padding: 0.5rem 1rem 0.375rem 1rem;
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
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
    padding: 0.625rem 1rem;
    border-bottom: 1px solid #f1f4f8;
    position: relative;
    flex-shrink: 0;
}

.chat-search-box .search-icon {
    position: absolute;
    left: 1.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    width: 16px;
    height: 16px;
    display: inline-block !important;
    background-size: contain !important;
    background-repeat: no-repeat !important;
    background-position: center !important;
    opacity: 0.6 !important;
    visibility: visible !important;
    filter: brightness(0) saturate(100%) invert(40%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%);
    z-index: 2;
}

.chat-search-box .search-icon.hr-icon-search {
    background-image: url('../assets/icons/search-icon.svg') !important;
}

.chat-search-box:focus-within .search-icon {
    opacity: 0.8 !important;
    filter: brightness(0) saturate(100%) invert(15%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%);
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
    padding: 0.75rem 0.875rem;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 0.125rem;
}

.chat-user-item:hover {
    background: #f1f5f9;
}

.chat-user-item.active {
    background: #f1f5ff;
    border-left: 3px solid #6366f1;
}

.chat-user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    margin-right: 0.75rem;
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
    /* Emoji-compatible font stack for message preview */
    font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", "Android Emoji", "EmojiSymbols", "EmojiOne Mozilla", "Twemoji Mozilla", "Segoe UI", -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
    font-feature-settings: "liga" 1, "calt" 1;
    text-rendering: optimizeLegibility;
    font-variant-emoji: emoji;
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
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
    min-height: 0;
    max-height: 100%;
    overflow: hidden;
    position: relative;
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
    padding: 0.875rem 1rem;
    border-bottom: 1px solid #f1f4f8;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
    background: #ffffff;
    z-index: 10;
}

.chat-header-user {
    display: flex;
    align-items: center;
}

.chat-header-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    margin-right: 0.75rem;
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
    overflow: hidden;
    position: relative;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    scroll-behavior: smooth;
}

.chat-message {
    display: flex;
    gap: 0.625rem;
    max-width: 75%;
    animation: messageSlideIn 0.2s ease;
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
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.8125rem;
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
    padding: 0.75rem 1rem;
    border-radius: 12px;
    word-wrap: break-word;
    white-space: pre-wrap;
    font-size: 0.875rem;
    line-height: 1.5;
    /* Emoji-compatible font stack for proper rendering */
    font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", "Android Emoji", "EmojiSymbols", "EmojiOne Mozilla", "Twemoji Mozilla", "Segoe UI", -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
    /* Ensure emojis render correctly */
    font-feature-settings: "liga" 1, "calt" 1;
    text-rendering: optimizeLegibility;
    /* Proper emoji sizing and alignment */
    font-variant-emoji: emoji;
}

/* Ensure emojis are properly sized and aligned */
.chat-message-bubble * {
    font-family: inherit;
}

.chat-message-bubble img.emoji,
.chat-message-bubble .emoji {
    display: inline-block;
    vertical-align: baseline;
    height: 1.2em;
    width: 1.2em;
    margin: 0 0.05em;
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
    gap: 0.375rem;
    margin-top: 0.25rem;
    font-size: 0.6875rem;
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
    padding: 0 1rem 0.75rem 1rem;
    color: #64748b;
    font-size: 0.8125rem;
    flex-shrink: 0;
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

/* Message Input - Fixed at Bottom */
.chat-input-container {
    border-top: 1px solid #f1f4f8;
    padding: 0.75rem 1rem;
    background: #ffffff;
    flex-shrink: 0;
    position: relative; /* Required for emoji picker absolute positioning */
    z-index: 10;
    box-shadow: 0 -2px 8px rgba(15, 23, 42, 0.04);
}

.chat-input-wrapper {
    display: flex;
    gap: 0.625rem;
    align-items: flex-end;
}

.chat-plus-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid #e8ecf1;
    background: #ffffff;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.chat-plus-btn:hover {
    background: #fafbfc;
    border-color: #d1d9e6;
}

/* Ensure plus icon in input area is visible */
.chat-plus-btn .hr-icon {
    display: inline-block !important;
    width: 18px !important;
    height: 18px !important;
    background-size: contain !important;
    background-repeat: no-repeat !important;
    background-position: center !important;
    opacity: 1 !important;
    visibility: visible !important;
    filter: brightness(0) saturate(100%) invert(40%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%);
}

.chat-plus-btn .hr-icon-plus {
    background-image: url('../assets/icons/plus-icon.png') !important;
}

.chat-plus-btn:hover .hr-icon {
    filter: brightness(0) saturate(100%) invert(15%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%);
}

.chat-plus-btn:hover {
    background: #f8fafc;
    color: #0f172a;
}

.chat-message-input {
    flex: 1;
    resize: none;
    border-radius: 12px;
    border: 1px solid #e8ecf1;
    background: #fafbfc;
    padding: 0.75rem 1rem;
    max-height: 100px;
    font-size: 0.875rem;
    line-height: 1.5;
    transition: all 0.2s ease;
    /* Emoji-compatible font stack for input */
    font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", "Android Emoji", "EmojiSymbols", "EmojiOne Mozilla", "Twemoji Mozilla", "Segoe UI", -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
    font-feature-settings: "liga" 1, "calt" 1;
    text-rendering: optimizeLegibility;
    font-variant-emoji: emoji;
}

.chat-message-input:focus {
    background: #ffffff;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.chat-send-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border: none;
    transition: all 0.2s ease;
    color: #ffffff;
}

.chat-send-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.chat-send-btn:active {
    transform: translateY(0);
}

.chat-input-footer {
    margin-top: 0.375rem;
    text-align: center;
}

.chat-input-footer small {
    font-size: 0.75rem;
    color: #94a3b8;
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

/* Ensure proper flex layout for conversation panel */
.chat-conversation-panel > * {
    min-height: 0;
}

/* Ensure messages container takes available space */
.chat-conversation-panel:has(.chat-messages-container) {
    display: flex;
    flex-direction: column;
}

/* Responsive Design */
@media (max-width: 768px) {
    .chat-main-wrapper {
        flex-direction: column;
        height: auto;
        max-height: none;
    }
    
    .chat-users-panel {
        width: 100%;
        max-height: 250px;
    }
    
    .chat-message {
        max-width: 85%;
    }
    
    .chat-input-container {
        position: sticky;
        bottom: 0;
    }
}

/* Additional fixes for proper scrolling */
.chat-conversation-panel {
    display: flex !important;
    flex-direction: column !important;
}

.chat-messages-container {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    overflow: hidden !important;
}

.chat-messages {
    height: 100% !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
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

/* Additional icon visibility fixes */
.chat-users-header .hr-icon,
.chat-header-actions .hr-icon {
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Ensure Font Awesome icons in chat header are visible */
.chat-header-actions .chat-icon-btn i,
.chat-icon-btn i.fas {
    display: inline-block !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    line-height: 1 !important;
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    font-size: 14px !important;
    color: #475569 !important;
    opacity: 1 !important;
    visibility: visible !important;
    width: auto !important;
    height: auto !important;
}

.chat-icon-btn:hover i {
    color: #0f172a !important;
}

/* Ensure send button icon is visible */
.chat-send-btn i.fa-paper-plane {
    display: inline-block !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    line-height: 1 !important;
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    font-size: 16px !important;
    color: white !important;
    opacity: 1 !important;
    visibility: visible !important;
}

.chat-send-btn i.fa-paper-plane::before {
    content: "\f1d8" !important;
}

/* Typing indicator improvements */
.chat-typing-indicator {
    display: none !important; /* Hidden by default, shown only when someone is typing */
    align-items: center !important;
    gap: 0.5rem !important;
    padding: 0 1.5rem 1rem 1.5rem !important;
    color: #64748b !important;
    font-size: 0.875rem !important;
    visibility: visible !important;
}

.chat-typing-indicator[style*="display: flex"],
.chat-typing-indicator[style*="display:flex"] {
    display: flex !important;
}

.chat-typing-indicator .typing-dot {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.chat-typing-indicator .typing-text {
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* ============================================
   EMOJI PICKER
   ============================================ */

.chat-emoji-picker {
    position: absolute;
    bottom: calc(100% + 10px);
    left: 0;
    width: 320px;
    max-height: 300px;
    min-height: 150px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    z-index: 1001; /* Higher than input container z-index */
    overflow: visible; /* Changed from hidden to visible to see emojis */
    border: 1px solid #e2e8f0;
    /* Ensure picker is visible */
    visibility: visible !important;
    opacity: 1 !important;
    display: block !important;
}

.chat-emoji-grid {
    display: grid !important;
    grid-template-columns: repeat(8, 1fr);
    gap: 4px;
    padding: 12px;
    max-height: 300px;
    overflow-y: auto;
    min-height: 100px;
    /* Ensure grid items are visible */
    visibility: visible !important;
    opacity: 1 !important;
    width: 100%;
    height: auto;
}

/* Ensure emoji grid children are visible */
.chat-emoji-grid > * {
    visibility: visible !important;
    opacity: 1 !important;
    display: flex !important;
}

/* Ensure emoji buttons contain visible text/emojis */
.chat-emoji-item {
    color: #000 !important;
    font-size: 24px !important;
}

.chat-emoji-item::before,
.chat-emoji-item::after {
    display: none; /* Remove any pseudo-elements that might interfere */
}

.chat-emoji-item {
    width: 36px;
    height: 36px;
    min-width: 36px;
    min-height: 36px;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    display: flex !important;
    align-items: center;
    justify-content: center;
    font-size: 24px !important;
    line-height: 1 !important;
    transition: all 0.2s;
    padding: 0;
    margin: 0;
    /* Emoji-compatible font stack for emoji picker */
    font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", "Android Emoji", "EmojiSymbols", "EmojiOne Mozilla", "Twemoji Mozilla", "Segoe UI", -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif !important;
    font-feature-settings: "liga" 1, "calt" 1;
    text-rendering: optimizeLegibility;
    font-variant-emoji: emoji;
    /* Ensure emojis are visible */
    color: inherit;
    opacity: 1;
    visibility: visible;
    /* Prevent text selection */
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

.chat-emoji-item:hover {
    background: #f1f5f9;
    transform: scale(1.1);
}

/* ============================================
   PHOTO PREVIEW & UPLOAD
   ============================================ */

.chat-input-toolbar {
    display: flex;
    gap: 8px;
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
}

.chat-input-tool-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: transparent;
    color: #64748b;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 18px;
}

.chat-input-tool-btn:hover {
    background: #f1f5f9;
    color: #475569;
}

/* Ensure Font Awesome icons in toolbar buttons are visible */
.chat-input-tool-btn i,
.chat-input-tool-btn i.fas {
    display: inline-block !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    line-height: 1 !important;
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    font-size: 18px !important;
    color: inherit !important;
    opacity: 1 !important;
    visibility: visible !important;
    width: auto !important;
    height: auto !important;
    min-width: 18px !important;
    min-height: 18px !important;
}

/* Ensure specific icon content is visible */
.chat-input-tool-btn i.fa-smile::before {
    content: "\f118" !important; /* Font Awesome smile icon unicode */
}

.chat-input-tool-btn i.fa-paperclip::before {
    content: "\f0c6" !important; /* Font Awesome paperclip icon unicode */
}

.chat-photo-preview {
    position: relative;
    padding: 12px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.chat-photo-preview img {
    max-width: 200px;
    max-height: 200px;
    border-radius: 8px;
    object-fit: cover;
}

.chat-photo-preview-remove {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 32px;
    height: 32px;
    border: none;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.chat-photo-preview-remove:hover {
    background: rgba(0, 0, 0, 0.8);
    transform: scale(1.1);
}

/* Ensure Font Awesome icon in photo preview remove button is visible */
.chat-photo-preview-remove i,
.chat-photo-preview-remove i.fas {
    display: inline-block !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    line-height: 1 !important;
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    font-size: 14px !important;
    color: white !important;
    opacity: 1 !important;
    visibility: visible !important;
    width: auto !important;
    height: auto !important;
}

.chat-photo-preview-remove i.fa-times::before {
    content: "\f00d" !important; /* Font Awesome times icon unicode */
}

/* ============================================
   PHOTO MODAL
   ============================================ */

.chat-photo-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.9);
}

.chat-photo-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.chat-photo-modal-content {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-photo-modal-content img {
    max-width: 100%;
    max-height: 90vh;
    object-fit: contain;
    border-radius: 8px;
}

.chat-photo-modal-close {
    position: absolute;
    top: -40px;
    right: 0;
    width: 36px;
    height: 36px;
    border: none;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 18px;
}

.chat-photo-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Ensure Font Awesome icon in photo modal close button is visible */
.chat-photo-modal-close i,
.chat-photo-modal-close i.fas {
    display: inline-block !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    line-height: 1 !important;
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    font-size: 18px !important;
    color: white !important;
    opacity: 1 !important;
    visibility: visible !important;
    width: auto !important;
    height: auto !important;
}

.chat-photo-modal-close i.fa-times::before {
    content: "\f00d" !important; /* Font Awesome times icon unicode */
}

/* General rule to ensure all Font Awesome icons in chat are visible */
.chat-input-container i.fas,
.chat-input-container i.fa,
.chat-emoji-picker i.fas,
.chat-emoji-picker i.fa {
    display: inline-block !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    line-height: 1 !important;
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    opacity: 1 !important;
    visibility: visible !important;
    width: auto !important;
    height: auto !important;
}

/* ============================================
   MESSAGE ATTACHMENTS
   ============================================ */

.chat-message-attachment {
    margin-bottom: 8px;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.2s;
    max-width: 300px;
}

.chat-message-attachment:hover {
    transform: scale(1.02);
}

.chat-message-attachment img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 12px;
}

/* Message input focus state improvements */
.chat-message-input:focus {
    background: #ffffff !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    outline: none !important;
}

/* Ensure empty state icon is visible */
.chat-empty-state i.fa-comments {
    display: inline-block !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    line-height: 1 !important;
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    font-size: 4rem !important;
    color: #94a3b8 !important;
    opacity: 0.5 !important;
    visibility: visible !important;
}

.chat-empty-state i.fa-comments::before {
    content: "\f086" !important;
}

/* ============================================
   EMOJI RENDERING ENHANCEMENTS
   ============================================ */

/* Ensure emojis render with proper baseline alignment */
.chat-message-bubble,
.chat-user-last-message,
.chat-message-input,
.chat-emoji-item {
    /* Proper vertical alignment for emojis */
    vertical-align: baseline;
    /* Prevent emoji clipping */
    overflow: visible;
}

/* Emoji-specific styling for better rendering */
.chat-message-bubble::before,
.chat-message-bubble::after {
    font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", "Android Emoji", "EmojiSymbols", "EmojiOne Mozilla", "Twemoji Mozilla", "Segoe UI", -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
}

/* Ensure emojis don't break layout */
.chat-message-bubble,
.chat-user-last-message {
    word-break: break-word;
    overflow-wrap: break-word;
    /* Allow emojis to display fully */
    min-height: 1.5em;
}

/* Better emoji rendering in different contexts */
.chat-message-bubble emoji,
.chat-user-last-message emoji,
.chat-message-input emoji {
    display: inline-block;
    vertical-align: middle;
    line-height: 1;
    font-size: 1em;
}

/* Cross-browser emoji rendering fixes */
@supports (-webkit-appearance: none) {
    /* WebKit/Blink browsers */
    .chat-message-bubble,
    .chat-user-last-message,
    .chat-message-input {
        -webkit-font-feature-settings: "liga" 1, "calt" 1;
        -webkit-font-smoothing: antialiased;
    }
}

@supports (-moz-appearance: none) {
    /* Firefox */
    .chat-message-bubble,
    .chat-user-last-message,
    .chat-message-input {
        -moz-font-feature-settings: "liga" 1, "calt" 1;
        -moz-osx-font-smoothing: grayscale;
    }
}
</style>
