<?php
/**
 * Global Chat Widget
 * 
 * Floating chat interface accessible from all dashboard pages.
 * This file should be included in the main layout (after login check).
 */

// Ensure user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    return;
}

$current_user_id = $_SESSION['user_id'] ?? 0;
$current_user_name = $_SESSION['name'] ?? 'User';
$current_user_role = $_SESSION['user_role'] ?? 'employee';

if (!$current_user_id) {
    return;
}
?>

<!-- Chat Widget Container -->
<div id="chatWidget" class="chat-widget">
    
    <!-- Floating Chat Button -->
    <button id="chatToggleBtn" class="chat-toggle-btn" type="button" title="Open Messages" aria-label="Open Messages">
        <i class="fas fa-comments" aria-hidden="true"></i>
        <span id="chatGlobalUnreadBadge" class="chat-global-unread-badge" style="display: none;">0</span>
    </button>

    <!-- Chat Popup Panel -->
    <div id="chatPopupPanel" class="chat-popup-panel">
        
        <!-- Panel Header -->
        <div class="chat-popup-header">
            <div class="chat-popup-title">
                <i class="fas fa-comments"></i>
                <span>Messages</span>
            </div>
            <div class="chat-popup-actions">
                <button id="chatMinimizeBtn" class="chat-popup-action-btn" type="button" title="Minimize">
                    <i class="fas fa-minus"></i>
                </button>
                <button id="chatCloseBtn" class="chat-popup-action-btn" type="button" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Panel Body -->
        <div class="chat-popup-body">
            
            <!-- Recipient Selector View -->
            <div id="chatRecipientView" class="chat-view active">
                
                <!-- Search Bar -->
                <div class="chat-search-bar">
                    <i class="fas fa-search"></i>
                    <input 
                        type="text" 
                        id="chatRecipientSearch" 
                        placeholder="Search contacts..."
                        autocomplete="off"
                    >
                </div>

                <!-- Recipients List -->
                <div id="chatRecipientsList" class="chat-recipients-list">
                    <div class="chat-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Loading contacts...</span>
                    </div>
                </div>

            </div>

            <!-- Conversation View -->
            <div id="chatConversationView" class="chat-view">
                
                <!-- Conversation Header -->
                <div class="chat-conversation-header">
                    <button id="chatBackBtn" class="chat-back-btn" type="button">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="chat-conversation-user">
                        <div id="chatConversationAvatar" class="chat-conversation-avatar"></div>
                        <div class="chat-conversation-info">
                            <div id="chatConversationName" class="chat-conversation-name">Select a contact</div>
                            <div id="chatTypingIndicator" class="chat-typing-indicator">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    </div>
                    <button id="chatClearHistoryBtn" class="chat-header-action-btn" type="button" title="Clear conversation history">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>

                <!-- Messages Container -->
                <div id="chatMessagesContainer" class="chat-messages-container">
                    <div class="chat-empty-state">
                        <i class="fas fa-comments"></i>
                        <p>Select a contact to start messaging</p>
                    </div>
                </div>

                <!-- Message Input -->
                <div class="chat-message-input-container">
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
                    
                    <!-- Input Wrapper -->
                    <div class="chat-input-wrapper">
                        <textarea 
                            id="chatMessageInput" 
                            class="chat-message-input"
                            placeholder="Type a message..."
                            rows="1"
                            maxlength="5000"
                        ></textarea>
                        <button id="chatSendBtn" class="chat-send-btn" type="button" title="Send message">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>

            </div>

        </div>

    </div>
    
    <!-- Emoji Picker Panel -->
    <div id="chatEmojiPicker" class="chat-emoji-picker" style="display: none;">
        <div class="chat-emoji-grid" id="chatEmojiGrid">
            <!-- Emojis will be rendered here -->
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
    
    <!-- Confirmation Modal -->
    <div id="chatConfirmModal" class="chat-confirm-modal" style="display: none;">
        <div class="chat-confirm-overlay"></div>
        <div class="chat-confirm-content">
            <div class="chat-confirm-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 id="chatConfirmTitle">Confirm Action</h3>
            <p id="chatConfirmMessage">Are you sure?</p>
            <div class="chat-confirm-actions">
                <button id="chatConfirmCancel" class="chat-confirm-btn chat-confirm-btn-cancel" type="button">
                    Cancel
                </button>
                <button id="chatConfirmOk" class="chat-confirm-btn chat-confirm-btn-danger" type="button">
                    Confirm
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Chat Widget Styles -->
<style>
/* ============================================
   CHAT WIDGET - FLOATING BUTTON
   ============================================ */

.chat-widget {
    position: fixed;
    bottom: 20px;
    left: 20px;
    z-index: 9999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Ensure Font Awesome icons are always visible in chat widget */
.chat-widget i,
.chat-widget .fas,
.chat-widget [class*="fa-"] {
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    text-rendering: auto !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
}

.chat-toggle-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible;
}

.chat-toggle-btn i,
.chat-toggle-btn i.fas,
.chat-toggle-btn i.fa-comments {
    display: inline-block !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    line-height: 1 !important;
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    font-size: 24px !important;
    color: white !important;
    opacity: 1 !important;
    visibility: visible !important;
    z-index: 1;
    position: relative;
    width: auto !important;
    height: auto !important;
    min-width: 24px !important;
    min-height: 24px !important;
    text-align: center;
}

/* Ensure the icon content is visible */
.chat-toggle-btn i.fa-comments::before {
    content: "\f086"; /* Font Awesome comments icon unicode */
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    display: inline-block;
}

.chat-toggle-btn:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
}

.chat-toggle-btn:active {
    transform: translateY(0) scale(0.98);
}

.chat-global-unread-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 22px;
    height: 22px;
    background: #ef4444;
    color: white;
    border-radius: 11px;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* ============================================
   CHAT POPUP PANEL
   ============================================ */

.chat-popup-panel {
    position: fixed;
    bottom: 90px;
    left: 20px;
    width: 380px;
    max-width: calc(100vw - 40px);
    height: 600px;
    max-height: calc(100vh - 120px);
    background: white;
    border-radius: 16px;
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
    display: none;
    flex-direction: column;
    overflow: hidden;
    animation: chatSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.chat-popup-panel.active {
    display: flex;
}

@keyframes chatSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Panel Header */
.chat-popup-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
}

.chat-popup-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    font-size: 16px;
}

.chat-popup-title i {
    font-size: 18px;
}

.chat-popup-actions {
    display: flex;
    gap: 8px;
}

.chat-popup-action-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.chat-popup-action-btn:hover {
    background: rgba(255, 255, 255, 0.25);
}

.chat-popup-action-btn:active {
    transform: scale(0.95);
}

/* Panel Body */
.chat-popup-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
    background: #f8fafc;
}

/* ============================================
   VIEWS (RECIPIENT LIST & CONVERSATION)
   ============================================ */

.chat-view {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: none;
    flex-direction: column;
    background: #f8fafc;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.chat-view.active {
    display: flex;
}

/* ============================================
   RECIPIENT SELECTOR VIEW
   ============================================ */

.chat-search-bar {
    padding: 16px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.chat-search-bar i {
    color: #94a3b8;
    font-size: 16px;
}

.chat-search-bar input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 14px;
    color: #0f172a;
    background: transparent;
}

.chat-search-bar input::placeholder {
    color: #94a3b8;
}

.chat-recipients-list {
    flex: 1;
    overflow-y: auto;
    background: white;
}

.chat-recipient-item {
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 1px solid #f1f5f9;
}

.chat-recipient-item:hover {
    background: #f8fafc;
}

.chat-recipient-item:active {
    background: #f1f5f9;
}

.chat-recipient-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
    overflow: hidden;
}

.chat-recipient-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.chat-recipient-info {
    flex: 1;
    min-width: 0;
}

.chat-recipient-name {
    font-weight: 600;
    color: #0f172a;
    font-size: 14px;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-recipient-preview {
    font-size: 13px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-recipient-badge {
    min-width: 22px;
    height: 22px;
    background: #ef4444;
    color: white;
    border-radius: 11px;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    flex-shrink: 0;
}

/* Loading State */
.chat-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    color: #64748b;
    gap: 12px;
}

.chat-loading i {
    font-size: 24px;
}

/* Empty State */
.chat-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: #94a3b8;
    text-align: center;
}

.chat-empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.chat-empty-state p {
    margin: 0;
    font-size: 14px;
}

/* ============================================
   CONVERSATION VIEW
   ============================================ */

.chat-conversation-header {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.chat-header-action-btn {
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
    flex-shrink: 0;
    margin-left: auto;
}

.chat-header-action-btn:hover {
    background: #fef2f2;
    color: #ef4444;
}

.chat-header-action-btn:active {
    transform: scale(0.95);
}

.chat-back-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: #f1f5f9;
    color: #475569;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.chat-back-btn:hover {
    background: #e2e8f0;
}

.chat-conversation-user {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.chat-conversation-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    flex-shrink: 0;
    overflow: hidden;
}

.chat-conversation-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.chat-conversation-info {
    flex: 1;
    min-width: 0;
}

.chat-conversation-name {
    font-weight: 600;
    color: #0f172a;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-typing-indicator {
    display: none;
    gap: 3px;
    margin-top: 2px;
}

.chat-typing-indicator.active {
    display: flex;
}

.chat-typing-indicator span {
    width: 6px;
    height: 6px;
    background: #94a3b8;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.chat-typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.chat-typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-6px); }
}

/* Messages Container */
.chat-messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #f8fafc;
}

.chat-messages-container::-webkit-scrollbar {
    width: 6px;
}

.chat-messages-container::-webkit-scrollbar-track {
    background: transparent;
}

.chat-messages-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.chat-messages-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Message Bubbles */
.chat-message {
    display: flex;
    flex-direction: column;
    max-width: 75%;
    animation: messageSlideIn 0.2s ease-out;
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
}

.chat-message.received {
    align-self: flex-start;
}

.chat-message-bubble {
    padding: 10px 14px;
    border-radius: 16px;
    word-wrap: break-word;
    line-height: 1.5;
    font-size: 14px;
}

.chat-message.sent .chat-message-bubble {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.chat-message.received .chat-message-bubble {
    background: white;
    color: #0f172a;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.chat-message-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 4px;
    font-size: 11px;
    color: #94a3b8;
    padding: 0 4px;
}

.chat-message.sent .chat-message-meta {
    justify-content: flex-end;
}

.chat-message-time {
    font-size: 11px;
}

.chat-message-status {
    display: flex;
    gap: 1px;
}

.chat-message-status i {
    font-size: 12px;
}

.chat-message-status.read i {
    color: #3b82f6;
}

/* Message Attachments */
.chat-message-attachment {
    margin-top: 6px;
    border-radius: 12px;
    overflow: hidden;
    max-width: 250px;
    cursor: pointer;
    transition: transform 0.2s;
}

.chat-message-attachment:hover {
    transform: scale(1.02);
}

.chat-message-attachment img {
    width: 100%;
    height: auto;
    display: block;
}

/* Message Input */
.chat-message-input-container {
    background: white;
    border-top: 1px solid #e2e8f0;
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex-shrink: 0;
}

/* Photo Preview */
.chat-photo-preview {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e2e8f0;
    margin-bottom: 4px;
}

.chat-photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.chat-photo-preview-remove {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    border: none;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.2s;
}

.chat-photo-preview-remove:hover {
    background: rgba(239, 68, 68, 0.9);
}

/* Input Toolbar */
.chat-input-toolbar {
    display: flex;
    gap: 4px;
    align-items: center;
}

.chat-input-tool-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    color: #64748b;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: all 0.2s;
}

.chat-input-tool-btn:hover {
    background: #f1f5f9;
    color: #667eea;
}

.chat-input-tool-btn:active {
    transform: scale(0.95);
}

/* Input Wrapper */
.chat-input-wrapper {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.chat-message-input {
    flex: 1;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 10px 16px;
    font-size: 14px;
    color: #0f172a;
    resize: none;
    max-height: 120px;
    overflow-y: auto;
    font-family: inherit;
    transition: border-color 0.2s;
}

.chat-message-input:focus {
    outline: none;
    border-color: #667eea;
}

.chat-message-input::placeholder {
    color: #94a3b8;
}

.chat-send-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.chat-send-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
}

.chat-send-btn:active {
    transform: scale(0.95);
}

.chat-send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.chat-send-btn i,
.chat-popup-action-btn i,
.chat-back-btn i,
.chat-header-action-btn i,
.chat-input-tool-btn i {
    display: inline-block;
    font-style: normal;
    font-variant: normal;
    text-rendering: auto;
    line-height: 1;
    font-family: 'Font Awesome 6 Free' !important;
    font-weight: 900 !important;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* ============================================
   RESPONSIVE DESIGN
   ============================================ */

@media (max-width: 768px) {
    .chat-widget {
        left: 10px;
        bottom: 10px;
    }

    .chat-toggle-btn {
        width: 56px;
        height: 56px;
        font-size: 22px;
    }

    .chat-popup-panel {
        left: 10px;
        bottom: 76px;
        width: calc(100vw - 20px);
        height: calc(100vh - 96px);
        max-height: calc(100vh - 96px);
    }

    .chat-message {
        max-width: 85%;
    }
}

/* ============================================
   UTILITY CLASSES
   ============================================ */

.chat-widget .d-none {
    display: none !important;
}

.chat-widget .text-muted {
    color: #94a3b8 !important;
}

/* Prevent body scroll when chat is open on mobile */
body.chat-widget-open {
    overflow: hidden;
}

@media (min-width: 769px) {
    body.chat-widget-open {
        overflow: auto;
    }
}

/* ============================================
   EMOJI PICKER
   ============================================ */

.chat-emoji-picker {
    position: fixed;
    bottom: 160px;
    left: 36px;
    width: 320px;
    height: 280px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    overflow: hidden;
}

.chat-emoji-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 4px;
    padding: 12px;
    height: 100%;
    overflow-y: auto;
}

.chat-emoji-item {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s;
    background: transparent;
    border: none;
}

.chat-emoji-item:hover {
    background: #f1f5f9;
    transform: scale(1.2);
}

.chat-emoji-grid::-webkit-scrollbar {
    width: 6px;
}

.chat-emoji-grid::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
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
    z-index: 10001;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-photo-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
}

.chat-photo-modal-content {
    position: relative;
    z-index: 1;
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-photo-modal-close {
    position: absolute;
    top: -48px;
    right: 0;
    width: 40px;
    height: 40px;
    border: none;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: all 0.2s;
}

.chat-photo-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

.chat-photo-modal-content img {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

/* ============================================
   CONFIRMATION MODAL
   ============================================ */

.chat-confirm-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10002;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-confirm-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
}

.chat-confirm-content {
    position: relative;
    z-index: 1;
    background: white;
    border-radius: 16px;
    padding: 32px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.2);
    text-align: center;
    animation: chatModalSlideIn 0.2s ease-out;
}

@keyframes chatModalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.chat-confirm-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    background: #fef2f2;
    color: #ef4444;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
}

.chat-confirm-content h3 {
    font-size: 20px;
    font-weight: 600;
    color: #0f172a;
    margin: 0 0 12px 0;
}

.chat-confirm-content p {
    font-size: 14px;
    color: #64748b;
    margin: 0 0 24px 0;
    line-height: 1.6;
}

.chat-confirm-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.chat-confirm-btn {
    flex: 1;
    max-width: 150px;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.chat-confirm-btn-cancel {
    background: #f1f5f9;
    color: #475569;
}

.chat-confirm-btn-cancel:hover {
    background: #e2e8f0;
}

.chat-confirm-btn-danger {
    background: #ef4444;
    color: white;
}

.chat-confirm-btn-danger:hover {
    background: #dc2626;
}

.chat-confirm-btn:active {
    transform: scale(0.98);
}
</style>

<!-- Chat Widget JavaScript Config -->
<script>
window.CHAT_WIDGET_CONFIG = {
    currentUserId: <?php echo json_encode($current_user_id); ?>,
    currentUserName: <?php echo json_encode($current_user_name); ?>,
    currentUserRole: <?php echo json_encode($current_user_role); ?>,
    apiEndpoint: '/api/chat.php',
    pollInterval: 3000,
    typingTimeout: 5000
};

// Ensure chat button icon is visible
(function() {
    function ensureChatIcon() {
        const icon = document.querySelector('#chatToggleBtn i.fa-comments');
        if (!icon) return;
        
        // Check if Font Awesome is loaded by testing computed style
        const computedStyle = window.getComputedStyle(icon, '::before');
        const fontFamily = computedStyle.getPropertyValue('font-family');
        
        // If Font Awesome isn't working, add fallback
        if (!fontFamily.includes('Font Awesome') && !fontFamily.includes('FontAwesome')) {
            // Add emoji fallback
            if (!icon.textContent || icon.textContent.trim() === '') {
                icon.textContent = '💬';
                icon.style.fontFamily = 'Arial, sans-serif';
                icon.style.fontSize = '24px';
            }
        }
        
        // Force visibility
        icon.style.display = 'inline-block';
        icon.style.visibility = 'visible';
        icon.style.opacity = '1';
        icon.style.fontFamily = icon.style.fontFamily || '"Font Awesome 6 Free"';
        icon.style.fontWeight = '900';
    }
    
    // Run on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureChatIcon);
    } else {
        ensureChatIcon();
    }
    
    // Also run after a short delay to catch late-loading Font Awesome
    setTimeout(ensureChatIcon, 500);
})();
</script>
