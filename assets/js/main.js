document.addEventListener("DOMContentLoaded", () => {
    const username = document.querySelector('#app')?.dataset?.username;
    if (username) {
        userStore.setUsername(username);
    }

    conversationActions.getConversations().then(() => {
    });
});
