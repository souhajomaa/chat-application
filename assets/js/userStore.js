// userStore.js
const userStore = (function () {
    let username = null;

    return {
        setUsername: (newUsername) => {
            username = newUsername;
        },
        getUsername: () => username
    };
})();
