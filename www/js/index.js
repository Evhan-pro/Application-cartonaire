document.addEventListener('DOMContentLoaded', function() {
    var userEnterpriseId = localStorage.getItem('userEnterpriseId');
    console.log('EnterpriseID:', userEnterpriseId); // Debug: Affiche l'ID de l'entreprise
  
    if (userEnterpriseId !== '1') {
        console.log('Non entreprise 1, affichage du contenu spécifique'); // Debug: Affiche si l'entreprise n'est pas 1
        // Cache les éléments spécifiques à l'entreprise 1
        document.getElementById('map').style.display = 'none';
        document.getElementById('startRaceBtn').style.display = 'none';
        document.querySelector('.modal').style.display = 'none';
        // Affiche les éléments pour les autres entreprises
        initReservationBlock();
    } else {
        console.log('Entreprise 1, affichage du contenu principal'); // Debug: Affiche si l'entreprise est 1
        // Initialise la carte et les fonctionnalités associées
        initMap();
        setupRaceFeatures();
    }
  });
  
  function initReservationBlock() {
    // Initialisation du bloc de réservation et des carrousels
    document.querySelector('.reservation-container').style.display = 'block';
    document.querySelector('#carouselNextPassages').style.display = 'block';
    document.querySelector('#carouselLastPassages').style.display = 'block';
  }
  
  function setupRaceFeatures() {
    document.getElementById('startRaceBtn').addEventListener('click', function() {
      document.getElementById('myModal').style.display = 'block';
    });
  
    document.querySelectorAll('.close').forEach(function(btn) {
      btn.addEventListener('click', function() {
        document.getElementById('myModal').style.display = 'none';
      });
    });
  
    document.getElementById('startRace').addEventListener('click', function() {
      startNavigation();
      document.getElementById('myModal').style.display = 'none';
    });
  }

  var map, directionsService, directionsRenderer, carMarker;
        
  function initMap() {
map = new google.maps.Map(document.getElementById('map'), {
  center: {lat: 47.4712, lng: -0.5518},
  zoom: 14,
  mapTypeId: google.maps.MapTypeId.ROADMAP,
  streetViewControl: false
});

directionsService = new google.maps.DirectionsService();
directionsRenderer = new google.maps.DirectionsRenderer({
  map: map,
  polylineOptions: { strokeColor: "blue" }
});

carMarker = new google.maps.Marker({
  map: map,
  
});
  
      document.getElementById('startRaceBtn').addEventListener('click', function() {
          document.getElementById('myModal').style.display = 'block';
      });
  
      document.querySelectorAll('.close').forEach(function(btn) {
          btn.addEventListener('click', function() {
              document.getElementById('myModal').style.display = 'none';
          });
      });
  
      document.getElementById('startRace').addEventListener('click', function() {
          startNavigation();
          document.getElementById('myModal').style.display = 'none';
      });
  }
  
  function startNavigation() {
    // Vérifie si la géolocalisation est disponible
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var currentPos = {lat: position.coords.latitude, lng: position.coords.longitude};
            carMarker.setPosition(currentPos);
            map.setCenter(currentPos);
            map.setZoom(15); // Ajuste le zoom pour une vue détaillée

            // Récupére les réservations du jour et calculer l'itinéraire
            fetchDailyReservations(currentPos);
        }, function() {
            handleLocationError(true, map.getCenter());
        });
    } else {
        handleLocationError(false, map.getCenter());
    }
}


function fetchDailyReservations(currentPos) {
  var today = new Date().toISOString().slice(0, 10); // Format YYYY-MM-DD pour correspondre à SQL DATE
  $.ajax({
      url: '../index.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({
          action: "getTodayReservations",
          userId: localStorage.getItem('userId'),
          date: today // Envoi de la date actuelle
      }),
      success: function(response) {
          if (response && response.length > 0) {
              // Calcule l'itinéraire avec les réservations comme arrêts
              calculateAndDisplayRouteWithStops(currentPos, response);
          } else {
              console.log("Pas de réservations pour aujourd'hui");
              alert("Aucune réservation pour aujourd'hui.");
          }
      },
      error: function(xhr, status, error) {
          console.error("Erreur lors de la récupération des réservations: ", error);
      }
  });
}


function calculateAndDisplayRouteWithStops(start, reservations) {
  var waypoints = reservations.map(reservation => ({
      location: reservation.adresse,
      stopover: true
  }));

  directionsService.route({
      origin: start,
      destination: "30 Rue des Lices, Angers", // Dernier arrêt (à modifier selon lieu entrepot)
      waypoints: waypoints,
      optimizeWaypoints: true,
      travelMode: 'DRIVING'
  }, function(response, status) {
      if (status === 'OK') {
          directionsRenderer.setDirections(response);
      } else {
          alert('La demande d\'itinéraire a échoué en raison de ' + status);
      }
  });
}


function initStreetView(location) {
var panorama = new google.maps.StreetViewPanorama(
  document.getElementById('pano'), {
      position: location,
      pov: {heading: 165, pitch: 0},
      zoom: 1
  });
map.setStreetView(panorama);
}


function updateSpeedDisplay(speed) {
document.getElementById('speedDisplay').innerText = `Vitesse: ${speed.toFixed(2)} km/h`;
}

  
  function calculateAndDisplayRoute(start, end) {
      directionsService.route({
          origin: start,
          destination: end,
          travelMode: 'DRIVING'
      }, function(response, status) {
          if (status === 'OK') {
              directionsRenderer.setDirections(response);
          } else {
              alert('Directions request failed due to ' + status);
          }
      });
  }
  
  function handleLocationError(browserHasGeolocation, pos) {
      alert(browserHasGeolocation ?
          'Error: The Geolocation service failed.' :
          'Error: Your browser doesnt support geolocation.');
      map.setCenter(pos);
  }