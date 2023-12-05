const getCurrentUser = async function() {
    let user = JSON.parse(sessionStorage.getItem('user'));

    if (!user) {
        try {
            user = await window.axios
                .get('/api/users/current')
                .then(response => response.data.data)
            sessionStorage.setItem('user', JSON.stringify(user));
        } catch (error) {
            if (error.response && error.response.status == 401) {
                window.clearSessionStorage();
                return;
            }
            throw error
        }
    }

    return user;
}

export default getCurrentUser;