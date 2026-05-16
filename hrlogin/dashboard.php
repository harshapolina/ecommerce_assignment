<?php 
session_start();
// Redirect to login if not logged in or not an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'hradminuser') {
    header('Location: /');
    exit;
}
$skip_superadmin_css = true;
include __DIR__ . '/htmlopen.php'; 
include __DIR__ . '/header.php'; 
?>
<link rel="stylesheet" href="../superadmin/assets/css/calender.css" />
<div class="content">
  <div class="container-fluid">
    <div class="summary-wrapper">
        <div class="summary-section">
            <div class="summary-card">
                <span class="summary-text">Active Employees : <span id="activeusers">0</span></span>
            </div>
            <div class="summary-card">
                <span class="summary-text">Today Present : <span id="todaypresent">0</span></span>
            </div>
            <div class="summary-card">
                <span class="summary-text">Total Projects : <span id="totalprojects">0</span></span>
            </div>
            <div class="summary-card">
                <span class="summary-text">Total Salary : <span id="totalsalary">0</span></span>
            </div>
        </div>
    </div>
    
    <div class="row mt-4 mb-4">
      <div class="col-lg-12">
          <!-- LIVE TRACKING UI START -->
          <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
          <style>
              .tracking-container { background: #f8fafc; border-radius:12px; padding:20px; box-shadow:0 4px 6px rgba(0,0,0,0.1); border:1px solid #e2e8f0; }
              .tracking-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
              .tracking-header h2 { color: #1e293b; margin: 0; font-weight: 700; font-family: 'Lexend Deca', sans-serif; font-size:1.5rem; }
              .map-container { height: 500px; background: white; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
              #map { height: 100%; width: 100%; }
              .controls-panel { background: white; padding: 15px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; gap: 15px; align-items: flex-end; }
              .control-group { display: flex; flex-direction: column; gap: 5px; }
              .control-group label { font-size: 0.85rem; font-weight: 600; color: #475569; }
              .control-group select, .control-group input { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-family: inherit; }
              .btn-primary-tracking { background: #227477; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: background 0.2s; }
              .btn-primary-tracking:hover { background: #1a5a5c; }
          </style>

          <div class="tracking-container">
              <div class="tracking-header">
                  <h2>📍 Live Employee Tracking</h2>
              </div>
              <div class="controls-panel">
                  <div class="control-group">
                      <label>Select Employee</label>
                      <select id="employeeSelect">
                          <option value="all">All Active Employees (Live)</option>
                      </select>
                  </div>
                  <div class="control-group">
                      <label>Date (For History)</label>
                      <input type="date" id="historyDate" value="<?php echo date('Y-m-d'); ?>">
                  </div>
                  <button class="btn-primary-tracking" onclick="loadHistory()">View History Route</button>
                  <button class="btn-primary-tracking" style="background:#475569" onclick="resetToLive()">Back to Live</button>
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
              if (!firebase.apps.length) { firebase.initializeApp(firebaseConfig); }
              const db = firebase.database();

              const map = L.map('map').setView([20.5937, 78.9629], 5);
              L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);

              let liveMarkers = {};
              let historyLayer = L.layerGroup().addTo(map);
              let isViewingHistory = false;

              const onlineIcon = L.divIcon({ className: 'custom-div-icon', html: `<div style='background-color:#22c55e;width:15px;height:15px;border-radius:50%;border:2px solid white;box-shadow:0 0 5px rgba(0,0,0,0.5);'></div>`, iconSize: [15, 15], iconAnchor: [7, 7] });
              const offlineIcon = L.divIcon({ className: 'custom-div-icon', html: `<div style='background-color:#94a3b8;width:15px;height:15px;border-radius:50%;border:2px solid white;box-shadow:0 0 5px rgba(0,0,0,0.5);'></div>`, iconSize: [15, 15], iconAnchor: [7, 7] });

              fetch('action.php?fetch_users_json=1').then(res => res.json()).then(data => {
                  const select = document.getElementById('employeeSelect');
                  data.forEach(emp => { let opt = document.createElement('option'); opt.value = emp.id; opt.textContent = emp.username; select.appendChild(opt); });
              });

              db.ref('live_locations').on('value', (snapshot) => {
                  if (isViewingHistory) return;
                  const data = snapshot.val();
                  if (!data) return;
                  for (let uid in liveMarkers) {
                      if (!data[uid]) { map.removeLayer(liveMarkers[uid]); delete liveMarkers[uid]; }
                  }
                  let bounds = [];
                  for (let uid in data) {
                      let user = data[uid];
                      if (user.latitude && user.longitude) {
                          let latlng = [user.latitude, user.longitude];
                          bounds.push(latlng);
                          let popupContent = `<b>${user.employee_name}</b><br>Status: ${user.status}<br>Speed: ${Math.round(user.speed * 3.6)} km/h<br>Updated: ${new Date(user.updated_at).toLocaleTimeString()}`;
                          let icon = user.status === 'online' ? onlineIcon : offlineIcon;
                          if (liveMarkers[uid]) { liveMarkers[uid].setLatLng(latlng); liveMarkers[uid].setIcon(icon); liveMarkers[uid].setPopupContent(popupContent); }
                          else { liveMarkers[uid] = L.marker(latlng, {icon: icon}).bindPopup(popupContent).addTo(map); }
                      }
                  }
                  if (bounds.length > 0 && Object.keys(liveMarkers).length === bounds.length) { map.fitBounds(bounds, {padding: [50, 50], maxZoom: 15}); }
              });

              function loadHistory() {
                  const empId = document.getElementById('employeeSelect').value;
                  const date = document.getElementById('historyDate').value;
                  if (empId === 'all') { alert('Please select a specific employee to view history.'); return; }
                  isViewingHistory = true;
                  for (let uid in liveMarkers) { map.removeLayer(liveMarkers[uid]); }
                  historyLayer.clearLayers();
                  fetch(`fetch_location_history.php?employee_id=${empId}&date=${date}`).then(res => res.json()).then(data => {
                      if (!data || data.length === 0) { alert('No location history found for this date.'); return; }
                      let latlngs = [];
                      data.forEach(point => {
                          let latlng = [point.latitude, point.longitude];
                          latlngs.push(latlng);
                          let time = new Date(point.captured_at).toLocaleTimeString();
                          L.circleMarker(latlng, { radius: 5, color: '#3b82f6', fillOpacity: 0.8 }).bindPopup(`Time: ${time}`).addTo(historyLayer);
                      });
                      if (latlngs.length > 1) { L.polyline(latlngs, {color: '#ef4444', weight: 3, opacity: 0.7}).addTo(historyLayer); }
                      map.fitBounds(latlngs, {padding: [50, 50]});
                  }).catch(err => { console.error(err); alert('Error fetching history data.'); });
              }

              function resetToLive() {
                  isViewingHistory = false; historyLayer.clearLayers();
                  let bounds = [];
                  for (let uid in liveMarkers) { liveMarkers[uid].addTo(map); bounds.push(liveMarkers[uid].getLatLng()); }
                  if (bounds.length > 0) { map.fitBounds(bounds, {padding: [50, 50], maxZoom: 15}); }
              }
          </script>
          <!-- LIVE TRACKING UI END -->
      </div>
    </div>
    
    <div class="row">
  <div class="modal fade" tabindex="-1" id="addNewUserModal">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="add-user-form" name="myform" class="p-2" novalidate>
            <div class="row mb-3 gx-3">
              <div class="col">
                <label for="date of joining">DOJ</label>
                <input type="date" name="doj" class="form-control form-control-lg" required>
                <div class="invalid-feedback">Date of Joning is required!</div>
              </div>
              <div class="col">
              <label for="date of Birth">DOB</label>
                <input type="date" name="dob" class="form-control form-control-lg" required>
                <div class="invalid-feedback">Date of Birth required!</div>
              </div>
            </div>
            <div class="row mb-3 gx-3">
              <div class="col">
                <input type="text" name="ename" class="form-control form-control-lg" placeholder="Enter Employee Name" required>
                <div class="invalid-feedback">Employee name is required!</div>
              </div>
              <div class="col">
                <input type="text" name="eemail" class="form-control form-control-lg" placeholder="Enter Employee Email" required>
                <div class="invalid-feedback">Employee Email is required!</div>
              </div>
            </div>
            <div class="row mb-3 gx-3">
              <div class="col">
                <input type="tel" name="enumber" class="form-control form-control-lg" placeholder="Enter Employee Number" required>
                <div class="invalid-feedback">Employee number is required!</div>
              </div>
              <div class="col">
                <input type="text" name="epass" class="form-control form-control-lg" placeholder="Enter Employee Password" required>
                <div class="invalid-feedback">Employee Password is required!</div>
              </div>
            </div>
            <div class="mb-3">
              <input type="number" name="esalary" class="form-control form-control-lg" placeholder="Enter Employee Salary" required>
              <div class="invalid-feedback">Employee Salary is required!</div>
            </div>
            <div class="row mb-3 gx-3">
              <div class="col">
                <input type="text" name="etable" class="form-control form-control-lg" placeholder="Enter Employee Table Name" required>
                <div class="invalid-feedback">Table name is required!</div>
              </div>
              <div class="col">
                <input type="text" name="emid" class="form-control form-control-lg" placeholder="Enter Employee ID" required>
                <div class="invalid-feedback">Employee ID is required!</div>
              </div>
            </div>
            
            <div class="row mb-3 gx-3">
              <div class="col">
                <input type="number" name="amountO" class="form-control form-control-lg" placeholder="Enter 1st Amount" required>
                <div class="invalid-feedback">1st Amount is required!</div>
              </div>
              <div class="col">
                <input type="number" name="amountT" class="form-control form-control-lg" placeholder="Enter 2nd Amount" required>
                <div class="invalid-feedback">2nd Amount is required!</div>
              </div>
            </div>
            <div class="row mb-3 gx-3">
              <div class="col">
                <input type="number" name="amountTh" class="form-control form-control-lg" placeholder="Enter 3rd Amount" required>
                <div class="invalid-feedback">3rd Amount is required!</div>
              </div>
              <div class="col">
                <input type="number" name="amountF" class="form-control form-control-lg" placeholder="Enter 4th Amount" required>
                <div class="invalid-feedback">4th Amount is required!</div>
              </div>
            </div>
            <div class="row mb-3 gx-3">
              <div class="col">
                <input type="number" name="amountFf" class="form-control form-control-lg" placeholder="Enter 5th Amount" required>
                <div class="invalid-feedback">5th Amount is required!</div>
              </div>
              <div class="col">
                <input type="number" name="amountS" class="form-control form-control-lg" placeholder="Enter 6th Amount" required>
                <div class="invalid-feedback">6th Amount is required!</div>
              </div>
            </div>
            <div class="row mb-3 gx-3">
              <div class="col">
                <input type="text" name="project_name" class="form-control form-control-lg" placeholder="Enter Project Name" required>
                <div class="invalid-feedback">Project name is required!</div>
              </div>
              <div class="col">
              <select name="D_project" class="selection">
                    <option value="">Select Project Type</option>
                    <option value="mandate">Mandate</option>
                    <option value="retail">Retail</option>
                </select>
                <div class="invalid-feedback">Employee PJT is required!</div>
              </div>
            </div>
            <div class="mb-3">
              <input type="text" name="ecode" class="form-control form-control-lg" placeholder="Enter Employee Code" required>
              <div class="invalid-feedback">Employee code is required!</div>
            </div>
            <div class="mb-3">
              <input type="submit" value="Add Employee" class="btn btn-primary btn-block btn-lg" id="add-user-btn">
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <!-- Add New User Modal End -->
  <!-- Edit User Modal Start -->
  <div class="modal fade" tabindex="-1" id="editUserModal">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Employee Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="edit-user-form" name="myform" class="p-2" novalidate>
            <input type="hidden" name="id" id="id">
            <div class="row mb-3 gx-3">
              <div class="col">
                <label for="date of joining">DOJ</label>
                <input type="date" name="doj" id="doj" class="form-control form-control-lg" required>
                <div class="invalid-feedback">Date of Joning is required!</div>
              </div>
              <div class="col">
                <label for="date of joining">DOB</label>
                <input type="date" name="dob" id="dob" class="form-control form-control-lg" placeholder="Enter Last Name" required>
                <div class="invalid-feedback">Date of Birth is required!</div>
              </div>
            </div>
            <div class="row mb-3 gx-3">
              <div class="col">
              <input type="text" name="ename" id="ename" class="form-control form-control-lg" placeholder="Enter Employee Name" required>
                <div class="invalid-feedback">Employee name is required!</div>
              </div>
              <div class="col">
                <input type="text" name="eemail" id="eemail" class="form-control form-control-lg" placeholder="Enter Employee Email" required>
                <div class="invalid-feedback">Employee Email is required!</div>
              </div>
            </div>
            <div class="row mb-3 gx-3">
              <div class="col">
              <input type="tel" name="enumber" id="enumber" class="form-control form-control-lg" placeholder="Enter Employee Number" required>
                <div class="invalid-feedback">Employee Number is required!</div>
              </div>
              <div class="col">
                <input type="text" name="epass" id="epass" class="form-control form-control-lg" placeholder="Enter Employee Password" required>
                <div class="invalid-feedback">Employee Password is required!</div>
              </div>
            </div>
            <div class="mb-3">
              <input type="number" name="esalary" id="esalary" class="form-control form-control-lg" placeholder="Enter Employee Salary" required>
              <div class="invalid-feedback">Employee Salary is required!</div>
            </div>
            <div class="row mb-3 gx-3">
              <div class="col">
              <input type="text" name="etable" id="etable" class="form-control form-control-lg" placeholder="Enter Employee Table" required>
                <div class="invalid-feedback">Table Name is required!</div>
              </div>
              <div class="col">
                <input type="text" name="emid" id="emid" class="form-control form-control-lg" placeholder="Enter Employee ID" required>
                <div class="invalid-feedback">Employee Id is required!</div>
              </div>
            </div>
            
            <div class="row mb-3 gx-3">
              <div class="col">
                <input type="number" name="amountO" id="amountO" class="form-control form-control-lg" placeholder="Enter 1st Amount" required>
                <div class="invalid-feedback">1st Amount is required!</div>
              </div>
              <div class="col">
                <input type="number" name="amountT" id="amountT" class="form-control form-control-lg" placeholder="Enter 2nd Amount" required>
                <div class="invalid-feedback">2nd Amount is required!</div>
              </div>
            </div>
            <div class="row mb-3 gx-3">
              <div class="col">
                <input type="number" name="amountTh" id="amountTh" class="form-control form-control-lg" placeholder="Enter 3rd Amount" required>
                <div class="invalid-feedback">3rd Amount is required!</div>
              </div>
              <div class="col">
                <input type="number" name="amountF" id="amountF" class="form-control form-control-lg" placeholder="Enter 4th Amount" required>
                <div class="invalid-feedback">4th Amount is required!</div>
              </div>
            </div>
            <div class="row mb-3 gx-3">
              <div class="col">
                <input type="number" name="amountFf" id="amountFf" class="form-control form-control-lg" placeholder="Enter 5th Amount" required>
                <div class="invalid-feedback">5th Amount is required!</div>
              </div>
              <div class="col">
                <input type="number" name="amountS" id="amountS" class="form-control form-control-lg" placeholder="Enter 6th Amount" required>
                <div class="invalid-feedback">6th Amount is required!</div>
              </div>
            </div>
            <div class="row mb-3 gx-3">
              <div class="col">
                <input type="text" name="project_name" id="project_name" class="form-control form-control-lg" placeholder="Enter Project Name" required>
                <div class="invalid-feedback">Project name is required!</div>
              </div>
              <div class="col">
              <select name="D_project" id="D_project" class="selection">
                    <option value="">Select Project Type</option>
                    <option value="mandate">Mandate</option>
                    <option value="retail">Retail</option>
                </select>
                <div class="invalid-feedback">Employee PJT is required!</div>
              </div>
            </div>
           
            <div class="mb-3">
              <input type="text" name="ecode" id="ecode" class="form-control form-control-lg" placeholder="Enter Code" required>
              <div class="invalid-feedback">Employee Code is required!</div>
            </div>
            <div class="mb-3">
              <input type="submit" value="Update" class="btn btn-success btn-block btn-lg" id="edit-user-btn">
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <!-- Edit User Modal End -->
<!-- Main Content -->
<div class="content">
<div class="contentinside">
  <div class="container">
    <div class="row">
    <div class="col-lg-6 chart-col-lg">
        <div class="custom-select mb-2">
        <select>
            <option value="">Open this select menu</option>
            <option value="">2022</option>
            <option value="">2023</option>
            <option value="">2024</option>
          </select>
        </div>
        <div class="chartcmmnstyle" id="barchart_material"></div>
      </div>
      
      <div class="col-lg-6 chart-col-lg">
      <div class="custom-select mb-2">
          <select>
            <option value="">Open this select menu</option>
            <option value="">2022</option>
            <option value="">2023</option>
            <option value="">2024</option>
          </select>
        </div>
        <div class="chartcmmnstyle" id="chart_line"></div>
      </div>
    </div>
    <div class="row  mt-3">
    <div class="col-lg-6 chart-col-lg">
      <div class="chartcmmnstyle" id="line_top_x"></div>
    </div>
    <div class="col-lg-6 chart-col-lg">
      <div class="wrapper">
        <div class="container-calendar">
          <div id="right">
            <h3 id="monthAndYear"></h3>
            <div class="button-container-calendar">
              <button id="previous" onclick="previous()">
                ‹
              </button>
              <button id="next" onclick="next()">
                ›
              </button>
            </div>
            <table class="table-calendar" id="calendar" data-lang="en">
              <thead id="thead-month"></thead>
              <tbody id="calendar-body"></tbody>
            </table>
            <div class="footer-container-calendar">
              <label for="month">Jump To: </label>
              <select id="month" onchange="jump()">
                <option value=0>Jan</option>
                <option value=1>Feb</option>
                <option value=2>Mar</option>
                <option value=3>Apr</option>
                <option value=4>May</option>
                <option value=5>Jun</option>
                <option value=6>Jul</option>
                <option value=7>Aug</option>
                <option value=8>Sep</option>
                <option value=9>Oct</option>
                <option value=10>Nov</option>
                <option value=11>Dec</option>
              </select>
              <select id="year" onchange="jump()"></select>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
    <!-- <div class="row  mt-3">
      <div class="col-lg-12 indexbooktable">
        <h4>Users Database</h4>
        <a href="/incentiveapp_integration/hrlogin/incentiveuser.php">
          <div class="panel-body">
          <table id="example" class="stripe row-border order-column display" cellspacing="0" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Date Of Joining</th>
                <th>Date Of Birth</th>
                <th>Name</th>
                <th>Email</th>
                <th>Contact No.</th>
                <th>Password</th>
                <th>In Hand Salary</th>
                <th>Designation</th>
                <th>Employee Id</th>
                <th>1st Amount</th>
                <th>2nd Amount</th>
                <th>3rd Amount</th>
                <th>4th Amount</th>
                <th>5th Amount</th>
                <th>6th Amount</th>
                <th>Project Name</th>
                <th>Project Type</th>
                <th>Role Type</th>
              </tr>
            </thead>
            <tbody id="incentiveuser">
            </tbody>
            <tfoot>
              <td>ID</td>
              <td>Date Of Joining</td>
              <td>Date Of Birth</td>
              <td>Name</td>
              <td>Email</td>
              <td>Contact No.</td>
              <td>Password</td>
              <td>In Hand Salary</td>
              <td>Designation</td>
              <td>Employee Id</td>
              <td>1st Amount</td>
              <td>2nd Amount</td>
              <td>3rd Amount</td>
              <td>4th Amount</td>
              <td>5th Amount</td>
              <td>6th Amount</td>
              <td>Project Name</td>
              <td>Project Type</td>
              <td>Role Type</td>
            </tfoot>
          </table>
          </div>
        </a>
      </div>
    </div> -->
  </div>
</div>
</div>
<!--End Main Content -->
<!-- Filter Rows Modal Start -->
<!-- <div class="modal fade" tabindex="-1" id="filterModal">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Filter Data</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="closeFilter"></button>
            </div>
            <div class="modal-body">
              <div class="container p-0">
                <div class="row">
                  <div class="col-md-6">
                    <input type="text" class="form-control mb-2" id="filterID" placeholder="ID">
                    <input type="text" class="form-control mb-2" id="EmployeeId" placeholder="Employee Id">
                  </div>
                  <div class="col-md-6">
                  <input type="text" class="form-control mb-2" id="DateOfJoining" placeholder="Date Of Joining">
                    <input type="text" class="form-control mb-2" id="DateOfBirth" placeholder="Date Of Birth">
                  </div>
                  <div class="col-md-6">
                    <input type="text" class="form-control mb-2" id="username" placeholder="User name">
                    <input type="text" class="form-control mb-2" id="email" placeholder="Email Id">
                  </div>
                  <div class="col-md-6">
                    <input type="text" class="form-control mb-2" id="Contactnumber" placeholder="Contact No.">
                    <input type="text" class="form-control mb-2" id="Password" placeholder="Password">
                  </div>
                  <div class="col-md-6">
                    <input type="text" class="form-control mb-2" id="inhandsalary" placeholder="In hand salary">
                    <input type="text" class="form-control mb-2" id="designation" placeholder="Designation">
                  </div>
                  <div class="col-md-6">
                    <input type="text" class="form-control mb-2" id="firstamount" placeholder="1st amount">
                    <input type="text" class="form-control mb-2" id="scndamount" placeholder="2nd amount">
                  </div>
                  <div class="col-md-6">
                    <input type="text" class="form-control mb-2" id="thirdamount" placeholder="3rd amount">
                    <input type="text" class="form-control mb-2" id="fourthamount" placeholder="4th amount">
                  </div>
                  <div class="col-md-6">
                    <input type="text" class="form-control mb-2" id="fifthamount" placeholder="5th amount">
                    <input type="text" class="form-control mb-2" id="sixthamount" placeholder="6th amount">
                  </div>
                  <div class="col-md-6">
                    <input type="text" class="form-control mb-2" id="Projectname" placeholder="Project Name">
                  </div>
                  <div class="col-md-6">
                    <input type="text" class="form-control mb-2" id="Projecttype" placeholder="Project Type">
                  </div>
                  <div class="col-md-6">
                    <input type="text" class="form-control mb-2" id="Code" placeholder="Code">
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancleFilter">Close</button>
              <button type="button" class="btn btn-danger" id="clearFiltersBtn">Clear Filters</button>
              <button type="button" class="btn btn-primary" id="applyFiltersBtn">Apply Filters</button>
            </div>
          </div>
        </div>
      </div> -      <!-- Filter INput  -->
      <!-- filter rows Modal End -->
      <!-- <script src="main.js"></script>   -->
      <script type="text/javascript" src="../assets/js/chartloader.js"></script>
      <script src="./assets/js/chart.js"></script>
      <script src="../superadmin/assets/js/calender.js"></script>
      <script>
        function applyFilters(){var n=[{id:"filterID",columnIndex:0},{id:"EmployeeId",columnIndex:1},{id:"DateOfJoining",columnIndex:2},{id:"DateOfBirth",columnIndex:3},{id:"username",columnIndex:4},{id:"email",columnIndex:5},{id:"Contactnumber",columnIndex:6},{id:"Password",columnIndex:7},{id:"inhandsalary",columnIndex:8},{id:"designation",columnIndex:9},{id:"firstamount",columnIndex:10},{id:"scndamount",columnIndex:11},{id:"thirdamount",columnIndex:12},{id:"fourthamount",columnIndex:13},{id:"fifthamount",columnIndex:14},{id:"sixthamount",columnIndex:15},{id:"Projectname",columnIndex:16},{id:"Projecttype",columnIndex:17},{id:"Code",columnIndex:18},];activeFilters=[],$("#incentiveuser tr").each(function(){var e=$(this),i=!0;n.forEach(function(n){var t=$("#"+n.id).val().toLowerCase();if(-1===e.find("td:eq("+n.columnIndex+")").text().toLowerCase().indexOf(t))return i=!1,!1;""!==t.trim()&&activeFilters.push(t)}),i?e.addClass("custom-filtered-row"):e.removeClass("custom-filtered-row")}),$("#incentiveuser tr").hide(),applyCustomFilter()}function applyCustomFilter(){$(".custom-filtered-row").show()}applyCustomFilter(),$(".filterable .btn-filter1").click(function(){$("#filterModal").modal("show")}),$("#applyFiltersBtn").click(function(){$("#filterModal").modal("hide"),applyFilters()}),$("#filterModal").on("hidden.bs.modal",function(){$(".filterable .filters input").val(""),applyFilters()}),$("#closeFilter").click(function(){applyFilters(),$("#filterModal").modal("hide")}),$("#cancleFilter").click(function(){applyFilters(),$("#filterModal").modal("hide")}),$(document).ready(function(){$("#clearFiltersBtn").click(function(){$("#filterID, #EmployeeId, #DateOfJoining, #DateOfBirth, #username, #email, #Contactnumber, #Password, #inhandsalary, #designation, #firstamount, #scndamount, #thirdamount, #fourthamount, #fifthamount, #sixthamount, #Projectname, #Projecttype, #Code").val("")})}),$("#clearFiltersBtn").click(function(){applyFilters(),$("#filterModal").modal("hide")});
      </script>
    </div> <!-- .container-fluid -->
  </div> <!-- .content wrap -->
<?php include __DIR__ . '/htmlclose.php'; ?>
; ?>