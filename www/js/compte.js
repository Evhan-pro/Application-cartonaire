$(document).ready(function() {
    var userId = localStorage.getItem('userId');
    if (userId === "1") {
  // Pour l'utilisateur avec l'ID 1, afficher seulement l'option Entreprise
  $('.account-option:not(.entreprise, .profil, .parametres, .securite)').hide(); // Cache toutes les options sauf 'Entreprise'
  $('.account-image-full').hide(); // Cache l'image et les compteurs associés
  $('#userSubscriptionCard').hide(); // Cache la carte d'abonnement
} else {
  // Pour les autres utilisateurs, afficher toutes les options sauf 'Entreprise'
  $('.account-option').show(); 
  $('#userSubscriptionCard').show();
  $('.entreprise').hide(); // Cache spécifiquement l'option Entreprise
  $('.account-image-full').show(); // Affiche l'image et les compteurs
    }

    // Récupère les informations d'abonnement si nécessaire
    if (userId && userId !== "1") {
        $.ajax({
            url: '../index.php',
            type: 'POST',
            data: JSON.stringify({ action: 'getUserSubscription', userId: userId }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(response) {
                if (response) {
                    displaySubscriptionCard(response);
                }
            },
            error: function(xhr) {
                console.error("Erreur lors de la récupération de l'abonnement: " + xhr.responseText);
            }
        });
    }
});

function displaySubscriptionCard(response) {
    var abonnement = response.abonnement;
    var tripsMade = response.trips;
    var totalCartons = response.totalCartons;
    var cardClass = abonnement.type === 'premium' ? 'premium' : 'standard';
    var maxTrips = abonnement.type === 'premium' ? 3 : 1;

    $('#tripCount').text(tripsMade);
    $('#maxTrips').text(maxTrips);
    $('#cartonCount').text(totalCartons);

    var cardHtml = `<div class="card ${cardClass}">
        <div class="card-header ${cardClass}-header">${abonnement.type.charAt(0).toUpperCase() + abonnement.type.slice(1)}</div>
        <div class="card-body ${cardClass}-body">
            <p>${abonnement.description}</p>
        </div>
        <div class="card-footer ${cardClass}-footer">
            ${abonnement.prix}€/mois
            <button class="button ${cardClass}-button" onclick="window.location.href='abonnements.html'">Voir les options</button>
        </div>
    </div>`;
    $('#userSubscriptionCard').html(cardHtml);
}