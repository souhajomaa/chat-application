// conversationStore.js
const conversationStore = (function () {
    let conversations = [];
    let hubUrl = null;

    return {
        setConversations: (data) => {
            conversations = data;
        },
        getConversations: () => {
            return conversations.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
        },
        getMessages: (conversationId) => {
            const conv = conversations.find(c => c.conversationId === conversationId);
            return conv ? conv.messages : [];
        },
        setMessages: (conversationId, messages) => {
            const conv = conversations.find(c => c.conversationId === conversationId);
            if (conv) conv.messages = messages;
        },
        addMessage: (conversationId, message) => {
            const conv = conversations.find(c => c.conversationId === conversationId);
            if (conv) {
                conv.messages = conv.messages || [];
                conv.messages.push(message);
            }
        },
        setLastMessage: (conversationId, message) => {
            const conv = conversations.find(c => c.conversationId === conversationId);
            if (conv) {
                conv.content = message.content;
                conv.createdAt = message.createdAt;
            }
        },
        updateConversationPreview: (payload) => {
            const conv = conversations.find(c => c.conversationId === payload.conversation.id);
            if (conv) {
                conv.content = payload.content;
                conv.createdAt = payload.createdAt;
            }
        },
        setHubUrl: (url) => {
            hubUrl = url;
        },
        getHubUrl: () => hubUrl
    };
})();
