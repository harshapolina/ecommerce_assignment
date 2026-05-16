<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
// Redirect to login if not logged in or not an employee
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'employee') {
    header('Location: index1.html');
    exit;
}
$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'u797909128_demoproject';
$DATABASE_PASS = 'QK&0/aF@5';
$DATABASE_NAME = 'u797909128_demo';
$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
if (mysqli_connect_errno()) {
    exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}
$user_id = $_SESSION['id'];
$today = date('Y-m-d');
// Check if user has already punched in today
$punch_in_time = null;
$punch_out_time = null;
$status = 'Not Logged';
$stmt = $con->prepare('SELECT punch_in, punch_out, status FROM attendance_logs WHERE user_id = ? AND punch_date = ?');
$stmt->bind_param('is', $user_id, $today);
$stmt->execute();
$stmt->bind_result($punch_in_time, $punch_out_time, $status);
$stmt->fetch();
$stmt->close();

// Ensure history table exists
$sql = "CREATE TABLE IF NOT EXISTS location_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    accuracy FLOAT,
    recorded_at DATETIME,
    session_id VARCHAR(100),
    INDEX(user_id, recorded_at)
)";
mysqli_query($con, $sql);

// Fetch Location Settings for Geofencing
$office_lat = null;
$office_lng = null;
$office_rad = 100;
$res_loc = $con->query("SELECT setting_key, setting_value FROM hr_settings WHERE setting_key IN ('office_latitude', 'office_longitude', 'office_radius')");
while($row_l = $res_loc->fetch_assoc()) {
    if ($row_l['setting_key'] == 'office_latitude') $office_lat = $row_l['setting_value'];
    if ($row_l['setting_key'] == 'office_longitude') $office_lng = $row_l['setting_value'];
    if ($row_l['setting_key'] == 'office_radius') $office_rad = (int)$row_l['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - Attendance</title>
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
      // Initialize Firebase
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
            overflow: hidden;
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
        .welcome-section h1 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .welcome-section p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }
        .clock-display {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-teal);
            margin-bottom: 0.5rem;
        }
        .date-display {
            color: #888;
            font-size: 1rem;
            margin-bottom: 2.5rem;
        }
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
        }
        .btn-in {
            background: var(--primary-teal);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 116, 119, 0.3);
        }
        .btn-in:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(34, 116, 119, 0.4);
        }
        .btn-out {
            background: #ef4444;
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        .btn-out:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        .btn-done {
            background: #cbd5e1;
            color: #64748b;
            cursor: not-allowed;
        }
        .logout-link {
            display: inline-block;
            margin-top: 2rem;
            color: #ef4444;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: opacity 0.2s;
        }
        .logout-link:hover {
            opacity: 0.7;
        }
        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 1rem;
        }
        .status-present { background: #dcfce7; color: #166534; }
        .status-late { background: #fef9c3; color: #854d0e; }
        .status-absent { background: #fee2e2; color: #991b1b; }
        
        /* New Leave Section Styles */
        .portal-tabs { 
            display: flex; 
            justify-content: center; 
            gap: 15px; 
            margin-bottom: 1.5rem; 
        }
        .tab-btn {
            background: rgba(255,255,255,0.5);
            border: 1px solid rgba(226, 232, 240, 0.8);
            padding: 8px 20px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab-btn.active {
            background: var(--primary-teal);
            color: white;
            border-color: var(--primary-teal);
        }
        .portal-section { display: none; }
        .portal-section.active { display: block; }
        
        .leave-balance-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        .balance-item {
            background: white;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
        }
        .balance-item .label { font-size: 0.7rem; color: #64748b; display: block; }
        .balance-item .value { font-size: 1.1rem; font-weight: 700; color: var(--primary-teal); }
        
        .leave-form input, .leave-form select, .leave-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            font-family: inherit;
            box-sizing: border-box;
        }
        .leave-form label {
            display: block;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #475569;
        }
        .history-list {
            text-align: left;
            margin-top: 1.5rem;
            max-height: 150px;
            overflow-y: auto;
        }
        .history-item {
            padding: 8px 12px;
            border-radius: 10px;
            background: white;
            margin-bottom: 5px;
            font-size: 0.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pill-rejected { background: #fee2e2; color: #991b1b; }

        /* Location Badge Style */
        .location-badge {
            background: rgba(34, 116, 119, 0.05);
            border: 1px solid rgba(34, 116, 119, 0.1);
            border-radius: 12px;
            padding: 8px 12px;
            margin-bottom: 1.5rem;
            display: none; /* Hidden by default */
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.8rem;
            color: var(--primary-teal);
            font-weight: 500;
            animation: fadeInDown 0.5s ease-out;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Pulsating Indicator for Live Tracking */
        .live-indicator {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #166534;
            background: #dcfce7;
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 1rem;
            text-transform: uppercase;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
    </style>
</head>
<body>
<div class="portal-card">
    <div id="liveTrackingIndicator" class="live-indicator">
        <div class="pulse-dot"></div>
        Live Tracking Active
    </div>
    <div id="userLocationDisplay" class="location-badge">
        <i class="bi bi-geo-alt-fill"></i>
        <span id="locationText">Detecting location...</span>
    </div>
    <div class="welcome-section">
        <h1>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        <p>Manage your work attendance today</p>
    </div>
    <?php if (isset($_SESSION['leave_success'])): ?>
        <div style="background: #dcfce7; color: #166534; padding: 10px; border-radius: 12px; font-size: 0.8rem; margin-bottom: 1rem; font-weight: 600; text-align: center;">
            <i class="bi bi-check-circle"></i> <?php echo $_SESSION['leave_success']; unset($_SESSION['leave_success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['leave_error'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 12px; font-size: 0.8rem; margin-bottom: 1rem; font-weight: 600; text-align: center;">
            <i class="bi bi-exclamation-circle"></i> <?php echo $_SESSION['leave_error']; unset($_SESSION['leave_error']); ?>
        </div>
    <?php endif; ?>
    <div class="portal-tabs">
        <button class="tab-btn active" onclick="switchTab('attendance')">Attendance</button>
        <button class="tab-btn" onclick="switchTab('leaves')">Leave Module</button>
    </div>
    <!-- Attendance Section -->
    <div id="attendance-section" class="portal-section active">
        <div class="clock-display" id="clock">00:00:00</div>
        <div class="date-display"><?php echo date('l, F j, Y'); ?></div>
        <?php if (!$punch_in_time): ?>
            <form id="punchInForm" action="punch_action.php" method="POST">
                <input type="hidden" name="action" value="punch_in">
                <button type="submit" id="punchInBtn" class="punch-btn btn-in">
                    <i class="bi bi-box-arrow-in-right"></i> Punch In
                </button>
            </form>
        <?php elseif (!$punch_out_time): ?>
            <form id="punchOutForm" action="punch_action.php" method="POST">
                <input type="hidden" name="action" value="punch_out">
                <button type="submit" id="punchOutBtn" class="punch-btn btn-out">
                    <i class="bi bi-box-arrow-right"></i> Punch Out
                </button>
            </form>
            <div class="status-badge status-present">
                Punched In at: <?php echo date('h:i A', strtotime($punch_in_time)); ?>
            </div>
        <?php else: ?>
            <button class="punch-btn btn-done">
                <i class="bi bi-check-circle-fill"></i> Work Completed
            </button>
            <div class="status-badge status-present">
                Done (<?php echo date('h:i A', strtotime($punch_in_time)); ?> - <?php echo date('h:i A', strtotime($punch_out_time)); ?>)
            </div>
        <?php endif; ?>
    </div>
    <!-- Leaves Section -->
    <div id="leaves-section" class="portal-section">
        <?php
            // Fetch Leave Balances
            $balances = [];
            $res_b = $con->query("SELECT t.leave_name, b.used_count, t.annual_limit 
                                  FROM leave_types t 
                                  LEFT JOIN leave_balances b ON t.id = b.leave_type_id AND b.user_id = $user_id AND b.year = ".date('Y'));
            while($row_b = $res_b->fetch_assoc()) {
                $rem = $row_b['annual_limit'] - ($row_b['used_count'] ?? 0);
                $balances[$row_b['leave_name']] = $rem;
            }
        ?>
        <div class="leave-balance-grid">
            <div class="balance-item">
                <span class="label">Sick</span>
                <span class="value"><?php echo $balances['Sick Leave'] ?? 12; ?></span>
            </div>
            <div class="balance-item">
                <span class="label">Casual</span>
                <span class="value"><?php echo $balances['Casual Leave'] ?? 12; ?></span>
            </div>
            <div class="balance-item">
                <span class="label">Paid</span>
                <span class="value"><?php echo $balances['Paid Leave'] ?? 15; ?></span>
            </div>
        </div>
        <form action="leave_action.php" method="POST" class="leave-form">
            <input type="hidden" name="action" value="request_leave">
            <label>Leave Type</label>
            <select name="leave_type_id" required>
                <?php
                    $res_t = $con->query("SELECT id, leave_name FROM leave_types");
                    while($row_t = $res_t->fetch_assoc()) {
                        echo "<option value='".$row_t['id']."'>".$row_t['leave_name']."</option>";
                    }
                ?>
            </select>
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label>From</label>
                    <input type="date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div style="flex: 1;">
                    <label>To</label>
                    <input type="date" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <label>Reason</label>
            <textarea name="reason" rows="2" placeholder="Brief reason..." required></textarea>
            <button type="submit" class="punch-btn btn-in" style="padding: 0.8rem;">Submit Request</button>
        </form>
        <div class="history-list">
            <p style="font-size: 0.75rem; font-weight: 700; margin-bottom: 5px; color: #475569;">My Recent Requests</p>
            <?php
                $res_h = $con->query("SELECT l.*, t.leave_name FROM leave_requests l JOIN leave_types t ON l.leave_type_id = t.id WHERE l.user_id = $user_id ORDER BY l.created_at DESC LIMIT 5");
                while($row_h = $res_h->fetch_assoc()) {
                    $p_class = 'pill-'.strtolower($row_h['status']);
                    echo "
                    <div class='history-item'>
                        <div>
                            <div style='font-weight: 700;'>{$row_h['leave_name']}</div>
                            <div style='font-size: 0.65rem; color: #64748b;'>".date('M d', strtotime($row_h['start_date']))." - ".date('M d', strtotime($row_h['end_date']))."</div>
                        </div>
                        <span class='status-pill {$p_class}'>{$row_h['status']}</span>
                    </div>";
                }
            ?>
        </div>
    </div>
    <br>
    <a href="logout.php" class="logout-link">
        <i class="bi bi-power"></i> Log Out
    </a>
</div>
<script>
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        if(document.getElementById('clock')) document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
    }
    setInterval(updateClock, 1000);
    updateClock();
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.portal-section').forEach(sec => sec.classList.remove('active'));
        
        event.currentTarget.classList.add('active');
        document.getElementById(tab + '-section').classList.add('active');
    }
    // Initialize Geolocation Tracking
    document.addEventListener('DOMContentLoaded', () => {
        const officePos = {
            lat: <?php echo json_encode($office_lat); ?>,
            lng: <?php echo json_encode($office_lng); ?>,
            radius: <?php echo json_encode($office_rad); ?>
        };
        if (document.getElementById('punchInBtn')) {
            initGeolocationTracker('punchInForm', 'punchInBtn', officePos);
        }
        if (document.getElementById('punchOutBtn')) {
            initGeolocationTracker('punchOutForm', 'punchOutBtn', officePos);
        }

        // Start Live Tracking if already Punched In but NOT Punched Out
        <?php if ($punch_in_time && !$punch_out_time): ?>
            startLiveTracking(<?php echo $user_id; ?>, '<?php echo htmlspecialchars($_SESSION['username']); ?>', '<?php echo $today; ?>');
        <?php endif; ?>

        // Intercept logout to cleanly stop tracking
        const logoutLink = document.querySelector('.logout-link');
        if (logoutLink) {
            logoutLink.addEventListener('click', function(e) {
                e.preventDefault();
                stopLiveTracking(<?php echo $user_id; ?>);
                setTimeout(() => {
                    window.location.href = this.href;
                }, 500); // Give it a moment to send Firebase update
            });
        }
    });
</script>
</body>
</html>