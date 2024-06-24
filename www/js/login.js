document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    fetch('http://localhost:8001/index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ action: "login", identifiant: username, mot_de_passe: password }),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Réseau ou réponse problème');
        }
        return response.json();
    })
    .then(data => {
        if (data.message) {
            // Stocke l'ID utilisateur et l'ID entreprise dans le localStorage
            localStorage.setItem('userId', data.userId);
            localStorage.setItem('userEnterpriseId', data.userEnterpriseId); // Assure que cet ID est envoyé du backend

            window.location.href = 'http://localhost:8001/www/index.html';
        } else if (data.error) {
            alert('Erreur de connexion: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la tentative de connexion.');
    });
});
