import { getUsername } from './userStore.js';
import { setConversations, getConversationById } from './conversationStore.js';
import { fetchConversations, fetchMessages, sendMessage } from './conversationActions.js';

document.addEventListener('DOMContentLoaded', async () => {
    const username = getUsername(); // Récupéré depuis data-username dans le template
    if (!username) {
        console.error('Utilisateur non identifié.');
        return;
    }

    const conversationListEl = document.getElementById('conversationList');
    const chatBox = document.getElementById('chatBox');
    const selectedUserLabel = document.getElementById('selectedUserLabel');
    const sendButton = document.getElementById('sendButton');
    const messageField = document.getElementById('messageField');

    let selectedConversationId = null;

    // Initialiser les conversations
    try {
        const conversations = await fetchConversations();
        setConversations(conversations);

        conversationListEl.innerHTML = ''; // Vider avant d'injecter
        conversations.forEach(convo => {
            const item = document.createElement('div');
            item.classList.add('list-group-item', 'list-group-item-action', 'border-0');
            item.innerHTML = `
                <div class="d-flex align-items-start">
                    <img src="/images/default-avatar.png" class="rounded-circle mr-1" width="40" height="40">
                    <div class="flex-grow-1 ml-3">
                        ${convo.user}
                        <div class="small"><em>${convo.lastMessage || 'No messages yet'}</em></div>
                    </div>
                </div>
            `;
            item.addEventListener('click', async () => {
                selectedConversationId = convo.id;
                selectedUserLabel.textContent = convo.user;
                chatBox.innerHTML = '<p>Loading messages...</p>';

                const messages = await fetchMessages(convo.id);

                chatBox.innerHTML = '';
                messages.forEach(msg => {
                    const msgDiv = document.createElement('div');
                    msgDiv.classList.add('message');
                    msgDiv.classList.add(msg.sender === username ? 'sent' : 'received');
                    msgDiv.textContent = msg.content;
                    chatBox.appendChild(msgDiv);
                });

                chatBox.scrollTop = chatBox.scrollHeight;
            });

            conversationListEl.appendChild(item);
        });
    } catch (error) {
        console.error('Erreur lors de la récupération des conversations :', error);
    }

    // Envoyer un message
    sendButton.addEventListener('click', async () => {
        const content = messageField.value.trim();
        if (!content || !selectedConversationId) return;

        try {
            await sendMessage(selectedConversationId, content);

            // Ajouter le message localement
            const msgDiv = document.createElement('div');
            msgDiv.classList.add('message', 'sent');
            msgDiv.textContent = content;
            chatBox.appendChild(msgDiv);
            messageField.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;
        } catch (error) {
            console.error('Erreur lors de l\'envoi du message :', error);
        }
    });
});
