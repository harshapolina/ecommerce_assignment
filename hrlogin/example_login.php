<?php
session_start();
require_once 'htmlopen.php';
// Standalone Example Employee Portal for Live Tracking Testing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Example Employee - Live Tracking</title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Firebase SDKs -->
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
    </script>
    <script src="assets/js/geolocation.js"></script>

    <style>
        :root {
            --primary-teal: #227477;
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            --card-glass: rgba(255, 255, 255, 0.9);
        }
        body {
            font-family: 'Lexend Deca', sans-serif;
            margin: 0;
            padding: 0;
            background: var(--bg-gradient);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .portal-card {
            background: var(--card-glass);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.4);
        }
        .welcome-section h1 { font-size: 1.5rem; color: #333; margin-bottom: 0.5rem; }
        .welcome-section p { color: #666; font-size: 0.9rem; margin-bottom: 2rem; }
        .clock-display { font-size: 2.5rem; font-weight: 700; color: var(--primary-teal); margin-bottom: 0.5rem; }
        .date-display { color: #888; font-size: 1rem; margin-bottom: 2.5rem; }
        .punch-btn {
            width: 100%;
            padding: 1.2rem;
            border: none;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--primary-teal);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 116, 119, 0.3);
        }
        .punch-btn:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(34, 116, 119, 0.4); }
        .live-indicator {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #166534;
            background: #dcfce7;
            padding: 4px 12px;
            border-radius: 20px;
            margin: 0 auto 1.5rem auto;
            width: max-content;
            border: 1px solid #bbf7d0;
        }
        .pulse-dot {
            width: 8px; height: 8px; background: #22c55e; border-radius: 50%; display: inline-block;
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); animation: pulse-green 1.5s infinite;
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
    </style>
</head>
<body>

<div class="portal-card">
    <div class="welcome-section">
        <h1>Welcome, Example Employee 👋</h1>
        <p>Testing Live Location Tracking</p>
    </div>

    <div class="clock-display" id="clock">--:--:--</div>
    <div class="date-display" id="date">Loading...</div>

    <div class="live-indicator" id="liveTrackingIndicator">
        <span class="pulse-dot"></span> LIVE TRACKING ACTIVE
    </div>

    <button id="trackBtn" class="punch-btn">
        <i class="bi bi-geo-alt-fill"></i> Allow Location & Start Tracking
    </button>
</div>

<script>
    // Live Clock
    function updateClock() {
        const now = new Date();
        document.getElementById('clock').textContent = now.toLocaleTimeString('en-US', { hour12: true });
        document.getElementById('date').textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Start Tracking
    document.getElementById('trackBtn').addEventListener('click', function() {
        const btn = this;
        if (navigator.geolocation) {
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Acquiring Signal...';
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    btn.style.display = 'none';
                    document.getElementById('liveTrackingIndicator').style.display = 'flex';
                    // Trigger actual Firebase/MySQL tracking from geolocation.js
                    startLiveTracking(9999, 'Example Employee', 'session_example_123');
                    alert("Tracking started successfully! Check the Admin Map.");
                },
                function(err) {
                    alert("Please allow location access to start tracking.");
                    btn.innerHTML = '<i class="bi bi-geo-alt-fill"></i> Allow Location & Start Tracking';
                }
            );
        } else {
            alert("Geolocation not supported.");
        }
    });
</script>

</body>
</html>
