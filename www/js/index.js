document.addEventListener('DOMContentLoaded', function() {
    var userEnterpriseId = localStorage.getItem('userEnterpriseId');
    console.log('EnterpriseID:', userEnterpriseId); // Debug: Afficher l'ID de l'entreprise
  
    if (userEnterpriseId !== '1') {
        console.log('Non entreprise 1, affichage du contenu spécifique'); // Debug: Afficher si l'entreprise n'est pas 1
        // Cacher les éléments spécifiques à l'entreprise 1
        document.getElementById('map').style.display = 'none';
        document.getElementById('startRaceBtn').style.display = 'none';
        document.querySelector('.modal').style.display = 'none';
        // Afficher les éléments pour les autres entreprises
        initReservationBlock();
    } else {
        console.log('Entreprise 1, affichage du contenu principal'); // Debug: Afficher si l'entreprise est 1
        // Initialiser la carte et les fonctionnalités associées
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
  mapTypeId: google.maps.MapTypeId.ROADMAP,  // Assurez-vous que c'est bien ROADMAP pour la vue routière
  streetViewControl: false  // Optionnel: désactivez le contrôle Street View si non nécessaire
});

directionsService = new google.maps.DirectionsService();
directionsRenderer = new google.maps.DirectionsRenderer({
  map: map,
  polylineOptions: { strokeColor: "blue" }
});

carMarker = new google.maps.Marker({
  map: map,
  icon: 'img/fourgon.avif'
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
if (navigator.geolocation) {
  navigator.geolocation.watchPosition(function(position) {
      var pos = {lat: position.coords.latitude, lng: position.coords.longitude};
      carMarker.setPosition(pos);
      calculateAndDisplayRoute(pos, "30 Rue des Lices, Angers");
      map.setCenter(pos);
      map.setZoom(15); // Ajuste le niveau de zoom pour voir environ 1km autour de la position
      initStreetView(pos);
  }, function() {
      handleLocationError(true, map.getCenter());
  });
} else {
  handleLocationError(false, map.getCenter());
}
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