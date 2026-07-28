<?php require_once __DIR__ . '/includes/session.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>दैलोमा भरिया | Create Profile</title>
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Leaflet Mapping Engine CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        :root {
            --primary-color: #10b981;
            --primary-dark: #064e3b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, sans-serif; background: #f3f4f6; }
        .reg-wrap { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 1rem; }
        .reg-box { width: 100%; max-width: 500px; background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.9rem; color: #374151; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; outline: none; transition: border 0.2s; }
        .form-control:focus { border-color: var(--primary-color); }
        
        /* Map Layout Styling */
        #reg-map {
            width: 100%;
            height: 200px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            margin-top: 0.5rem;
        }
        .map-hint { font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; display: block; }

        .btn-submit { width: 100%; padding: 0.75rem; background: var(--primary-color); border: none; color: white; border-radius: 6px; font-weight: bold; margin-top: 1.5rem; cursor: pointer; font-size: 1rem; transition: background 0.2s; }
        .btn-submit:hover { background: #059669; }
    </style>
</head>
<body>

<div class="reg-wrap">
    <div class="reg-box">
        <h2 style="text-align: center; color: var(--primary-dark); margin-bottom: 1.5rem;">Join Daailoma Bhariya</h2>
        <div id="msg-banner" style="display:none; padding:0.75rem; border-radius:6px; margin-bottom:1rem; font-size:0.9rem; font-weight: 500;"></div>
        
        <form id="regForm">
            <div class="form-group">
                <label for="name">Full Legal Name</label>
                <input type="text" id="name" class="form-control" placeholder="John Doe" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" class="form-control" placeholder="name@domain.com" required>
            </div>
            <div class="form-group">
                <label for="mobile">Mobile Number</label>
                <input type="text" id="mobile" class="form-control" placeholder="98XXXXXXXX" pattern="^[9][6-8][0-9]{8}$" title="Please enter a valid 10-digit mobile number starting with 9" required>
            </div>
            <div class="form-group">
                <label for="password">Password Structure</label>
                <input type="password" id="password" class="form-control" placeholder="••••••••" minlength="6" required>
            </div>

            <!-- Geolocation Block Integration -->
            <div class="form-group">
                <label>Set Delivery Drop Location</label>
                <div id="reg-map"></div>
                <span class="map-hint"><i class="fa-solid fa-circle-info"></i> Drag the blue map marker directly over your home entry point.</span>
                
                <!-- Hidden inputs to securely hold positional variables -->
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">
            </div>

            <button type="submit" class="btn-submit">Register Customer Profile</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: #4b5563;">Already registered? <a href="login.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Sign In Instead</a></p>
    </div>
</div>

<script>
// Global Map Pointers
let registrationMap;
let trackingMarker;
// Default Center Fallback (Kathmandu, Nepal coordinates)
let defaultLat = 27.7172;
let defaultLng = 85.3240;

document.addEventListener("DOMContentLoaded", function() {
    // Instantiate Leaflet baseline map
    registrationMap = L.map('reg-map').setView([defaultLat, defaultLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(registrationMap);

    // Instantiate draggable tracking marker placement pin
    trackingMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(registrationMap);
    
    // Push defaults into forms immediately
    updateCoordinatesInputs(defaultLat, defaultLng);

    // Sync form inputs when users finish dragging the marker
    trackingMarker.on('dragend', function (e) {
        let position = trackingMarker.getLatLng();
        updateCoordinatesInputs(position.lat, position.lng);
    });

    // Native HTML5 Auto-Location request hook
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            let currentLat = position.coords.latitude;
            let currentLng = position.coords.longitude;
            
            registrationMap.setView([currentLat, currentLng], 16);
            trackingMarker.setLatLng([currentLat, currentLng]);
            updateCoordinatesInputs(currentLat, currentLng);
        }, function(error) {
            console.warn("Location permission denied. Defaulting map context target.");
        });
    }
});

function updateCoordinatesInputs(lat, lng) {
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
}

document.getElementById('regForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const banner = document.getElementById('msg-banner');
    banner.style.display = 'none';

    const payload = {
        name: document.getElementById('name').value.trim(),
        email: document.getElementById('email').value.trim(),
        mobile: document.getElementById('mobile').value.trim(),
        password: document.getElementById('password').value,
        latitude: document.getElementById('latitude').value,
        longitude: document.getElementById('longitude').value
    };

    try {
        const response = await fetch('api/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const res = await response.json();
        
        if(res.success) {
            banner.style.background = '#d1fae5';
            banner.style.color = '#065f46';
            banner.innerText = "Registration complete! Redirecting to login area...";
            banner.style.display = 'block';
            setTimeout(() => window.location.href = 'login.php', 2000);
        } else {
            banner.style.background = '#fee2e2';
            banner.style.color = '#b91c1c';
            banner.innerText = res.message || "Registration failed.";
            banner.style.display = 'block';
        }
    } catch(err) {
        banner.style.background = '#fee2e2';
        banner.style.color = '#b91c1c';
        banner.innerText = "Fatal connection error. Please try again later.";
        banner.style.display = 'block';
    }
});
</script>
</body>
</html>