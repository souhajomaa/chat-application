// conversationActions.js
const conversationActions = {
    getConversations: () => {
        return fetch(`/conversations`)
            .then(result => {
                const linkHeader = result.headers.get('Link');
                if (linkHeader) {
                    const hubUrlMatch = linkHeader.match(/<([^>]+)>;\s+rel=(?:mercure|"[^"]*mercure[^"]*")/);
                    if (hubUrlMatch) {
                        conversationStore.setHubUrl(hubUrlMatch[1]);
                    }
                }
                return result.json();
            })
            .then(data => {
                conversationStore.setConversations(data);
            });
    },

    getMessages: (conversationId) => {
        const alreadyLoaded = conversationStore.getMessages(conversationId);
        if (alreadyLoaded.length === 0) {
            return fetch(`/messages/${conversationId}`)
                .then(res => res.json())
                .then(messages => {
                    conversationStore.setMessages(conversationId, messages);
                });
        }
        return Promise.resolve(); // déjà chargé
    },

    postMessage: (conversationId, content) => {
        const formData = new FormData();
        formData.append('content', content);

        return fetch(`/messages/${conversationId}`, {
            method: "POST",
            body: formData
        })
            .then(res => res.json())
            .then(message => {
                conversationStore.addMessage(conversationId, message);
                conversationStore.setLastMessage(conversationId, message);
            });
    }
};
