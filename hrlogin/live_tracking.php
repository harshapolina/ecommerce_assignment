<?php
require_once 'htmlopen.php';
require_once 'header.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'hradminuser') {
    echo '<script>alert("Access Denied"); window.location="index1.html";</script>';
    exit;
}
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .tracking-container {
        padding: 20px;
        background: #f8fafc;
        min-height: calc(100vh - 60px);
    }
    .tracking-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .tracking-header h2 {
        color: #1e293b;
        margin: 0;
        font-weight: 700;
        font-family: 'Lexend Deca', sans-serif;
    }
    .map-container {
        height: 600px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    #map {
        height: 100%;
        width: 100%;
    }
    .controls-panel {
        background: white;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        display: flex;
        gap: 15px;
        align-items: flex-end;
    }
    .control-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .control-group label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
    }
    .control-group select, .control-group input {
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        outline: none;
        font-family: inherit;
    }
    .btn-primary {
        background: #227477;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.2s;
    }
    .btn-primary:hover {
        background: #1a5a5c;
    }
</style>

<div class="tracking-container">
    <div class="tracking-header">
        <h2>Live Employee Tracking</h2>
    </div>

    <div class="controls-panel">
        <div class="control-group">
            <label>Select Employee</label>
            <select id="employeeSelect">
                <option value="all">All Active Employees (Live)</option>
                <!-- Filled via JS -->
            </select>
        </div>
        <div class="control-group">
            <label>Date (For History)</label>
            <input type="date" id="historyDate" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <button class="btn-primary" onclick="loadHistory()">View History Route</button>
        <button class="btn-primary" style="background:#475569" onclick="resetToLive()">Back to Live</button>
    </div>

    <div class="map-container">
        <div id="map"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script>
    const firebaseConfig = {
        apiKey: "AIzaSyBg7-DeKj7PQ3h6Q1Nf3YBLxDEWN3J5_8U",
        authDomain: "hrms-live-tracking.firebaseapp.com",
        databaseURL: "https://hrms-live-tracking-default-rtdb.asia-southeast1.firebasedatabase.app",
        projectId: "hrms-live-tracking",
        storageBucket: "hrms-live-tracking.firebasestorage.app",
        messagingSenderId: "578036127062",
        appId: "1:578036127062:web:bfff8efff0c627d4f072f7",
        measurementId: "G-RX2SWP5RKK"
    };
    if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
    }
    const db = firebase.database();

    // Map Initialization
    const map = L.map('map').setView([20.5937, 78.9629], 5); // Default to India
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let liveMarkers = {};
    let historyLayer = L.layerGroup().addTo(map);
    let isViewingHistory = false;

    // Custom Icons
    const onlineIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `<div style='background-color:#22c55e;width:15px;height:15px;border-radius:50%;border:2px solid white;box-shadow:0 0 5px rgba(0,0,0,0.5);'></div>`,
        iconSize: [15, 15],
        iconAnchor: [7, 7]
    });
    
    const offlineIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `<div style='background-color:#94a3b8;width:15px;height:15px;border-radius:50%;border:2px solid white;box-shadow:0 0 5px rgba(0,0,0,0.5);'></div>`,
        iconSize: [15, 15],
        iconAnchor: [7, 7]
    });

    // Populate Employee Dropdown
    fetch('action.php?fetch_users_json=1')
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('employeeSelect');
            data.forEach(emp => {
                let opt = document.createElement('option');
                opt.value = emp.id;
                opt.textContent = emp.username;
                select.appendChild(opt);
            });
        });

    // Start Listening to Live Data
    db.ref('live_locations').on('value', (snapshot) => {
        if (isViewingHistory) return; // Don't update live markers if viewing history
        
        const data = snapshot.val();
        if (!data) return;

        // Clear existing markers that aren't in Firebase anymore
        for (let uid in liveMarkers) {
            if (!data[uid]) {
                map.removeLayer(liveMarkers[uid]);
                delete liveMarkers[uid];
            }
        }

        let bounds = [];
        for (let uid in data) {
            let user = data[uid];
            if (user.latitude && user.longitude) {
                let latlng = [user.latitude, user.longitude];
                bounds.push(latlng);
                
                let popupContent = `<b>${user.employee_name}</b><br>
                                    Status: ${user.status}<br>
                                    Speed: ${Math.round(user.speed * 3.6)} km/h<br>
                                    Updated: ${new Date(user.updated_at).toLocaleTimeString()}`;
                
                let icon = user.status === 'online' ? onlineIcon : offlineIcon;

                if (liveMarkers[uid]) {
                    liveMarkers[uid].setLatLng(latlng);
                    liveMarkers[uid].setIcon(icon);
                    liveMarkers[uid].setPopupContent(popupContent);
                } else {
                    liveMarkers[uid] = L.marker(latlng, {icon: icon})
                        .bindPopup(popupContent)
                        .addTo(map);
                }
            }
        }

        // Auto zoom if viewing all and we have points
        if (bounds.length > 0 && Object.keys(liveMarkers).length === bounds.length) {
            map.fitBounds(bounds, {padding: [50, 50], maxZoom: 15});
        }
    });

    // Fetch History
    function loadHistory() {
        const empId = document.getElementById('employeeSelect').value;
        const date = document.getElementById('historyDate').value;
        
        if (empId === 'all') {
            alert('Please select a specific employee to view history.');
            return;
        }

        isViewingHistory = true;
        
        // Hide live markers
        for (let uid in liveMarkers) {
            map.removeLayer(liveMarkers[uid]);
        }
        historyLayer.clearLayers();

        // Fetch from PHP
        fetch(`fetch_location_history.php?employee_id=${empId}&date=${date}`)
            .then(res => res.json())
            .then(data => {
                if (!data || data.length === 0) {
                    alert('No location history found for this date.');
                    return;
                }

                let latlngs = [];
                data.forEach(point => {
                    let latlng = [point.latitude, point.longitude];
                    latlngs.push(latlng);
                    
                    let time = new Date(point.captured_at).toLocaleTimeString();
                    L.circleMarker(latlng, {
                        radius: 5,
                        color: '#3b82f6',
                        fillOpacity: 0.8
                    }).bindPopup(`Time: ${time}`).addTo(historyLayer);
                });

                // Draw route line
                if (latlngs.length > 1) {
                    L.polyline(latlngs, {color: '#ef4444', weight: 3, opacity: 0.7}).addTo(historyLayer);
                }

                map.fitBounds(latlngs, {padding: [50, 50]});
            })
            .catch(err => {
                console.error(err);
                alert('Error fetching history data.');
            });
    }

    function resetToLive() {
        isViewingHistory = false;
        historyLayer.clearLayers();
        
        let bounds = [];
        for (let uid in liveMarkers) {
            liveMarkers[uid].addTo(map);
            bounds.push(liveMarkers[uid].getLatLng());
        }
        if (bounds.length > 0) {
            map.fitBounds(bounds, {padding: [50, 50], maxZoom: 15});
        }
    }
</script>

<?php require_once 'htmlclose.php'; ?>
