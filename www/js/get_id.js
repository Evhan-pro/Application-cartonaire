// Script pour gérer l'ID de l'utilisateur et ajouter à l'URL pour toutes les pages

document.addEventListener('DOMContentLoaded', function() {
    var userId = localStorage.getItem('userId'); // Obtenir l'ID utilisateur du stockage local
    console.log("User ID from storage:", userId); // Ajouter pour débugger
    if (!userId) {
        window.location.href = 'login.html'; // Rediriger vers la page de connexion si aucun ID n'est trouvé
    } else {
        // Sélectionner tous les liens qui nécessitent l'ID utilisateur
        var links = document.querySelectorAll('a.needs-user-id');
        links.forEach(function(link) {
            if (!link.href.includes('userId')) { // Ajouter l'ID seulement si ce n'est pas déjà fait
                link.href += (link.href.includes('?') ? '&' : '?') + 'userId=' + encodeURIComponent(userId);
            }
        });
    }
});


// Fonction pour rediriger avec l'ID utilisateur si nécessaire
function redirectTo(page) {
    var userId = localStorage.getItem('userId');
    if (userId) {
        window.location.href = page + '?userId=' + encodeURIComponent(userId);
    } else {
        window.location.href = 'login.html';
    }
}