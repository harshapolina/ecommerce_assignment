/**
 * Geolocation Tracker
 * Blocks a form submission until valid GPS coordinates are acquired.
 * 
 * Usage:
 * <form id="myForm">
 *   <button id="myBtn">Submit</button>
 * </form>
 * <script>
 *   initGeolocationTracker('myForm', 'myBtn');
 * </script>
 */
function initGeolocationTracker(formId, buttonId, officePos = null) {
    const form = document.getElementById(formId);
    const btn = document.getElementById(buttonId);
    if (!form || !btn) return;
    // Initially disable or preserve state
    btn.setAttribute('disabled', 'true');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-geo-alt"></i> Fetching Location...';
    // Request Location
    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser. Please use a modern browser.");
        btn.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Location Unsupported';
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            // Update UI Location Display if it exists
            const locationDisplay = document.getElementById('userLocationDisplay');
            const locationText = document.getElementById('locationText');
            if (locationDisplay && locationText) {
                locationDisplay.style.display = 'flex';
                locationText.innerText = `Acquiring address...`;
                
                // Try reverse geocoding with OpenStreetMap (Free)
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.display_name) {
                            // Extract a cleaner address (City, State/Country)
                            const addr = data.address;
                            const city = addr.city || addr.town || addr.village || addr.suburb || '';
                            const state = addr.state || addr.country || '';
                            locationText.innerText = city ? `${city}, ${state}` : data.display_name.split(',').slice(0, 3).join(',');
                        } else {
                            locationText.innerText = `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`;
                        }
                    })
                    .catch(() => {
                        locationText.innerText = `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`;
                    });
            }

            // Geofencing Check
            if (officePos && officePos.lat && officePos.lng) {
                const distance = getDistance(
                    lat, lng, 
                    parseFloat(officePos.lat), 
                    parseFloat(officePos.lng)
                );
                if (distance > officePos.radius) {
                    const distKm = (distance / 1000).toFixed(2);
                    alert(`Access Denied: You are ${distance.toFixed(0)}m away from the office. You must be within ${officePos.radius}m to punch.`);
                    btn.innerHTML = `<i class="bi bi-geo-fill"></i> Too Far (${distKm}km)`;
                    btn.setAttribute('disabled', 'true');
                    
                    if (locationDisplay) {
                        locationDisplay.style.background = 'rgba(239, 68, 68, 0.05)';
                        locationDisplay.style.color = '#ef4444';
                        locationDisplay.style.borderColor = 'rgba(239, 68, 68, 0.1)';
                    }
                    return;
                }
            }
            // Create or update hidden inputs
            updateHiddenInput(form, 'latitude', lat);
            updateHiddenInput(form, 'longitude', lng);
            // Enable button
            btn.removeAttribute('disabled');
            btn.innerHTML = originalText;
            console.log("Location acquired:", lat, lng);
        },
        (error) => {
            let errorMsg = "Please allow location access to continue.";
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMsg = "Location access denied. You must allow location permissions to Punch In/Out.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMsg = "Location information is unavailable.";
                    break;
                case error.TIMEOUT:
                    errorMsg = "The request to get user location timed out.";
                    break;
            }
            alert(errorMsg);
            btn.innerHTML = '<i class="bi bi-lock-fill"></i> Location Required';

            const locationDisplay = document.getElementById('userLocationDisplay');
            const locationText = document.getElementById('locationText');
            if (locationDisplay && locationText) {
                locationDisplay.style.display = 'flex';
                locationDisplay.style.background = 'rgba(239, 68, 68, 0.05)';
                locationDisplay.style.color = '#ef4444';
                locationDisplay.style.borderColor = 'rgba(239, 68, 68, 0.1)';
                locationText.innerText = "Location access required";
            }
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}
/**
 * Haversine Formula to calculate distance in meters
 */
function getDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Earth radius in metres
    const φ1 = lat1 * Math.PI/180;
    const φ2 = lat2 * Math.PI/180;
    const Δφ = (lat2-lat1) * Math.PI/180;
    const Δλ = (lon2-lon1) * Math.PI/180;
    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c; 
}
function updateHiddenInput(form, name, value) {
    let input = form.querySelector(`input[name="${name}"]`);
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.appendChild(input);
    }
    input.value = value;
}

/**
 * Continuous Live Tracking using watchPosition
 * Sends location to Firebase and occasionally to MySQL
 */
let liveTrackingWatcher = null;
let lastFirebaseUpdate = 0;
let lastMySQLUpdate = 0;
let lastPosition = { lat: 0, lng: 0 };

function startLiveTracking(userId, username, sessionId) {
    if (liveTrackingWatcher !== null) return; // Already running

    console.log("Starting live tracking for user:", userId);

    const indicator = document.getElementById('liveTrackingIndicator');
    if (indicator) indicator.style.display = 'flex';

    if (!navigator.geolocation) {
        console.error("Geolocation is not supported by your browser.");
        return;
    }

    // Reference to Firebase for this user
    const dbRef = firebase.database().ref('live_locations/' + userId);

    // Initial check-in location logic handled by punch in, but let's push the very first location
    liveTrackingWatcher = navigator.geolocation.watchPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            const speed = position.coords.speed || 0;
            const now = Date.now();

            const distance = getDistance(lat, lng, lastPosition.lat, lastPosition.lng);

            // 1. Firebase Optimization: Update if moved > 50 meters OR 2 minutes passed
            if (distance > 50 || (now - lastFirebaseUpdate) > 120000 || lastFirebaseUpdate === 0) {
                dbRef.set({
                    latitude: lat,
                    longitude: lng,
                    accuracy: accuracy,
                    speed: speed,
                    updated_at: now,
                    status: "online",
                    employee_name: username
                });
                lastFirebaseUpdate = now;
                lastPosition = { lat, lng };
                console.log("Firebase location updated.");
            }

            // 2. MySQL History Storage: Save if moved > 100 meters OR 10 minutes passed
            if (distance > 100 || (now - lastMySQLUpdate) > 600000 || lastMySQLUpdate === 0) {
                sendLocationToMySQL(userId, lat, lng, accuracy, sessionId);
                lastMySQLUpdate = now;
            }
        },
        (error) => {
            console.error("WatchPosition error:", error);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

function stopLiveTracking(userId) {
    if (liveTrackingWatcher !== null) {
        navigator.geolocation.clearWatch(liveTrackingWatcher);
        liveTrackingWatcher = null;
        console.log("Live tracking stopped.");
        
        // Mark user offline in Firebase
        if (userId && typeof firebase !== 'undefined') {
            firebase.database().ref('live_locations/' + userId).update({
                status: "offline",
                updated_at: Date.now()
            });
        }
    }
    const indicator = document.getElementById('liveTrackingIndicator');
    if (indicator) indicator.style.display = 'none';
}

function sendLocationToMySQL(userId, lat, lng, accuracy, sessionId) {
    const formData = new FormData();
    formData.append('record_live_location', '1');
    formData.append('latitude', lat);
    formData.append('longitude', lng);
    formData.append('accuracy', accuracy);
    formData.append('session_id', sessionId);

    fetch('action.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text()) // Use text() first in case of PHP errors
    .then(data => {
        try {
            const json = JSON.parse(data);
            console.log("MySQL history ping sent:", json);
        } catch(e) {
            console.log("MySQL history ping sent (non-JSON):", data);
        }
    })
    .catch(err => console.error("Tracking error:", err));
}

