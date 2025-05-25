// Import du CSS
import './styles/app.scss';

// Import de Bootstrap
import 'bootstrap';

// Activation des fonctionnalités Bootstrap
document.addEventListener('DOMContentLoaded', () => {
    // Activation des tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Activation des popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Gestion des alertes avec fermeture automatique
    const alerts = document.querySelectorAll('.alert-auto-close');
    alerts.forEach(alert => {
        setTimeout(() => {
            const closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            }
        }, 5000);
    });
});

/*

// Chargement conditionnel de Leaflet pour la carte
if (document.getElementById('map')) {
    import('leaflet').then(({ default: L }) => {
        // Initialisation de la carte
        const map = L.map('map').setView([46.603354, 1.888334], 6); // Centre sur la France
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Récupération des signalements depuis l'API
        fetch('/api/signalements/valides')
            .then(response => response.json())
            .then(signalements => {
                signalements.forEach(signalement => {
                    // Déterminer la couleur du marqueur en fonction du statut
                    let markerColor;
                    switch(signalement.statut) {
                        case 'nouveau': markerColor = '#f44336'; break; // Rouge
                        case 'en_cours': markerColor = '#ff9800'; break; // Orange
                        case 'resolu': markerColor = '#4caf50'; break; // Vert
                        default: markerColor = '#666666'; // Gris
                    }
                    
                    // Créer un marqueur personnalisé
                    const markerIcon = L.divIcon({
                        className: 'custom-marker',
                        html: `<div style="background-color: ${markerColor}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>`,
                        iconSize: [16, 16],
                        iconAnchor: [8, 8]
                    });
                    
                    // Ajouter le marqueur à la carte
                    L.marker([signalement.latitude, signalement.longitude], { icon: markerIcon })
                        .addTo(map)
                        .bindPopup(`
                            <strong>${signalement.titre}</strong><br>
                            Catégorie: ${signalement.categorie.nom}<br>
                            Statut: ${signalement.statut}<br>
                            <a href="/signalement/${signalement.id}" class="btn btn-sm btn-primary mt-2">Voir détails</a>
                        `);
                });
            });
        
        // Si on est sur la page de création de signalement, activer la sélection de position
        if (document.querySelector('#signalement_latitude') && document.querySelector('#signalement_longitude')) {
            let marker = null;
            
            // Fonction pour mettre à jour les champs du formulaire
            const updatePositionFields = (latlng) => {
                document.querySelector('#signalement_latitude').value = latlng.lat.toFixed(6);
                document.querySelector('#signalement_longitude').value = latlng.lng.toFixed(6);
            };
            
            // Gérer le clic sur la carte
            map.on('click', (e) => {
                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker(e.latlng).addTo(map);
                updatePositionFields(e.latlng);
            });
            
            // Si des coordonnées sont déjà définies (édition), afficher le marqueur
            const lat = document.querySelector('#signalement_latitude').value;
            const lng = document.querySelector('#signalement_longitude').value;
            if (lat && lng) {
                const latlng = L.latLng(parseFloat(lat), parseFloat(lng));
                marker = L.marker(latlng).addTo(map);
                map.setView(latlng, 15);
            }
        }
    });
}

*/
