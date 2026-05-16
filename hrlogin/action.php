<?php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  session_start();
  require_once 'db.php';
  require_once 'util.php';
  $db = new Database;
  $util = new Util;
  // Ensure Payroll table exists
  // $db->createPayrollTable();
  // $db->createDeductionsTable();

  if (isset($_GET['check_db'])) {
    try {
        $res = $db->getConnection()->query('DESC accounts')->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($res);
    } catch (Exception $e) {
        echo "DB Error: " . $e->getMessage();
    }
    exit;
  }
  if (isset($_POST['add'])) {
    $doj = $util->testInput($_POST['doj']);
    $dob = $util->testInput($_POST['dob']);
    $ename = $util->testInput($_POST['ename']);
    $eemail = $util->testInput($_POST['eemail']);
    $enumber = $util->testInput($_POST['enumber']);
    $epass = $util->testInput($_POST['epass']);
    $esalary = $util->testInput($_POST['esalary']);
    $etable = $util->testInput($_POST['etable']);
    $emid = $util->testInput($_POST['emid']);
    $ecode = $util->testInput($_POST['ecode'] ?? '');
    $amountO = $util->testInput($_POST['amountO'] ?? 0);
    $amountT = $util->testInput($_POST['amountT'] ?? 0);
    $amountTh = $util->testInput($_POST['amountTh'] ?? 0);
    $amountF = $util->testInput($_POST['amountF'] ?? 0);
    $amountFf = $util->testInput($_POST['amountFf'] ?? 0);
    $amountS = $util->testInput($_POST['amountS'] ?? 0);
    $project_name = $util->testInput($_POST['project_name'] ?? '');
    $D_project = $util->testInput($_POST['D_project'] ?? '');
    $user_type = $util->testInput($_POST['user_type'] ?? '');
    $assign_user = $_POST['assign_user'] ?? '';
    $is_active = 0; // Forced Inactive for New User Application

    if ($db->insert($doj, $dob, $ename, $eemail, $enumber, $epass, $esalary, $etable, $emid, $ecode, $amountO, $amountT, $amountTh, $amountF, $amountFf, $amountS, $project_name, $D_project, $user_type, $assign_user, $is_active)) {
      echo $util->showMessage('success', 'Application submitted successfully! User is currently inactive.');
    } else {
      echo $util->showMessage('danger', 'Something went wrong!');
    }
  }
  // Handle Fetch All Users Ajax Request
  if (isset($_GET['read'])) {
    $users = $db->read();
    $output = '';
    if ($users) {
        $rowCount = 0; // Initialize the rowCount variable
        foreach ($users as $row) {
          $rowCount++; // Increment the rowCount for each iteration
      }
      foreach ($users as $row) {
        $statusText = ($row['is_active'] == 1) ? 'Active' : 'Inactive';
        $statusClass = ($row['is_active'] == 1) ? 'text-success fw-bold' : 'text-danger fw-bold';
        
        $inactive_at = ($row['deactivated_at'] === null) ? '<span>NA</span>' : '<span>' . $row['deactivated_at'] . '</span>';
        $output .= '<tr class="user-data-row">
                      <td class="checkbox-col"><input type="checkbox" class="user-row-checkbox"></td>
                      <td>' . $row['id'] . '</td>
                      <td class="' . $statusClass . '">' . $statusText . '</td>
                      <td>' . $row['username'] . '</td>
                      <td>' . $row['useremail'] . '</td>
                      <td>' . $row['phonenumber'] . '</td>
                      <td>••••••••</td>
                      <td>' . $row['salary'] . '</td>
                      <td>' . $row['doj'] . '</td>
                      <td>' . $row['dob'] . '</td>
                      <td>' . $row['tablename'] . '</td>
                      <td>' . $row['employee_id'] . '</td>
                      <td>' . $row['one_amt'] . '</td>
                      <td>' . $row['two_amt'] . '</td>
                      <td>' . $row['thrid_amt'] . '</td>
                      <td>' . $row['forth_amt'] . '</td>
                      <td>' . $row['fifth_amt'] . '</td>
                      <td>' . $row['sixth_amt'] . '</td>
                      <td>' . $row['project_name'] . '</td>
                      <td>' . $row['project_type'] . '</td>
                      <td>' . $row['user_type'] . '</td>
                      <td>' . $row['assign_user'] . '</td>
                      <td>' . $row['created_at'] . '</td>
                      <td>' . $inactive_at . '</td>';
        $output .= '<td>
                      <div class="action-buttons">
                        <a href="#" id="' . $row['id'] . '" class="action-btn edit-btn editLink" data-bs-toggle="modal" data-bs-target="#editUserModal">
                          <i class="bi bi-pencil-square"></i>
                        </a>
                        <a href="#" id="' . $row['id'] . '" class="action-btn delete-btn deleteLink">
                          <i class="bi bi-trash"></i>
                        </a>
                      </div>
                    </td>
                  </tr>';
    }
    
      echo $output;
    } else {
      echo '<tr>
              <td colspan="20">No Users Found in the Database!</td>
            </tr>';
    }
  }
  // Handle Edit User Ajax Request
  if (isset($_GET['edit'])) {
    $id = $_GET['id'];
    $user = $db->readOne($id);
    echo json_encode($user);
  }
  // Handle Update User Ajax Request
  if (isset($_POST['update'])) {
    $id = $util->testInput($_POST['id']);
    $doj = $util->testInput($_POST['doj'] ?? '');
    $dob = $util->testInput($_POST['dob'] ?? '');
    $ename = $util->testInput($_POST['ename']);
    $eemail = $util->testInput($_POST['eemail']);
    $enumber = $util->testInput($_POST['enumber']);
    $epass = $util->testInput($_POST['epass']);
    $esalary = $util->testInput($_POST['esalary'] ?? 0);
    $etable = $util->testInput($_POST['etable'] ?? '');
    $emid = $util->testInput($_POST['emid'] ?? '');
    $amountO = $util->testInput($_POST['amountO'] ?? 0);
    $amountT = $util->testInput($_POST['amountT'] ?? 0);
    $amountTh = $util->testInput($_POST['amountTh'] ?? 0);
    $amountF = $util->testInput($_POST['amountF'] ?? 0);
    $amountFf = $util->testInput($_POST['amountFf'] ?? 0);
    $amountS = $util->testInput($_POST['amountS'] ?? 0);
    $project_name = $util->testInput($_POST['project_name'] ?? '');
    $D_project = $util->testInput($_POST['D_project'] ?? '');
    $user_type = $util->testInput($_POST['user_type'] ?? 'employee');
    $assign_user = $_POST['assign_user'] ?? '';
    $is_active = $util->testInput($_POST['is_active'] ?? 1);
    if ($db->update($id, $doj, $dob, $ename, $eemail, $enumber, 
    $epass, $esalary, $etable, $emid, $amountO, $amountT, 
    $amountTh, $amountF, $amountFf, $amountS, $project_name, 
    $D_project, $user_type, $assign_user, $is_active)) {
      echo $util->showMessage('success', 'User updated successfully!');
    } else {
      echo $util->showMessage('danger', 'Something went wrong!');
    }
  }
  // Handle Delete User Ajax Request
  if (isset($_GET['delete'])) {
    $id = $_GET['id'];
    if ($db->delete($id)) {
      echo $util->showMessage('info', 'User deleted successfully!');
    } else {
      echo $util->showMessage('danger', 'Something went wrong!');
    }
  }
  // Get the user count function
  if (isset($_GET['active_users']) && $_GET['active_users'] == 1) {
    $result = $db->active_users(); // Call the read function
    // Prepare a response array with Active and Inactive counts
    $response = [
        'active' => 0,
        'inactive' => 0,
    ];
    foreach ($result as $row) {
        if ($row['is_active'] == 1) {
            $response['active'] = $row['user_count']; // Active users
        } else if ($row['is_active'] == 0) {
            $response['inactive'] = $row['user_count']; // Inactive users
        }
    }
    echo json_encode($response); // Send the result as a JSON response
}
if (isset($_POST['action']) && $_POST['action'] == 'update_advance_pay') {
    $id = $_POST['id'];
    $newAdvancePay = $_POST['newAdvancePay'];
    $db->updateAdvancePay($id, $newAdvancePay);
    echo "Advance Pay updated successfully.";
}

// Handle Update User Status (Activation Toggle)
if (isset($_POST['action']) && $_POST['action'] == 'update_user_status') {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? 0;
    
    // Use database method for status update
    $success = $db->updateUserStatus($id, $status);
    
    if ($success) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'User status updated successfully.']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Failed to update user status.']);
    }
    exit;
}

// Handle Update User Salary (Salary Structure Update)
if (isset($_POST['action']) && $_POST['action'] == 'update_user_salary') {
    $id = $_POST['id'] ?? 0;
    $salary = $_POST['salary'] ?? 0;
    $basic = $_POST['basic'] ?? 0;
    $hra = $_POST['hra'] ?? 0;
    $conveyance = $_POST['conveyance'] ?? 0;
    $special = $_POST['special'] ?? 0;
    $pf_employer = $_POST['pf_employer'] ?? 0;
    $deductions = $_POST['deductions'] ?? 0;
    
    // Update salary and components in accounts table
    $query = "UPDATE accounts SET 
                old_salary = salary, 
                salary = :salary,
                one_amt = :basic,
                two_amt = :hra,
                thrid_amt = :conveyance,
                forth_amt = :special,
                fifth_amt = :pf_employer,
                sixth_amt = :deductions
              WHERE id = :id";
    $stmt = $db->getConnection()->prepare($query);
    $success = $stmt->execute([
        'salary' => $salary, 
        'basic' => $basic,
        'hra' => $hra,
        'conveyance' => $conveyance,
        'special' => $special,
        'pf_employer' => $pf_employer,
        'deductions' => $deductions,
        'id' => $id
    ]);
    
    if ($success) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Salary structure updated successfully.']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Failed to update salary structure.']);
    }
    exit;
}
// --- Payroll AJAX Routes ---
// Fetch Payroll Data for a specific month (JSON)
if (isset($_GET['fetch_payroll'])) {
    $month = $_GET['month'];
    header('Content-Type: application/json');
    $payroll = $db->getPayrollByMonth($month);
    echo json_encode($payroll);
    exit;
}
// Run Payroll for a specific month (Process all employees with REAL Attendance)
if (isset($_POST['run_payroll'])) {
    $month = $_POST['month'];
    $users = $db->read(); // Get all employees
    
    // Total days in the selected month
    $dateObj = DateTime::createFromFormat('M Y', $month);
    if (!$dateObj) $dateObj = new DateTime();
    $totalDays = (int)$dateObj->format('t');
    
    $successCount = 0;
    foreach ($users as $user) {
        // Only process active users
        if ($user['is_active'] != 1) continue;
        $baseSalary = (float)($user['salary'] ?? 0);
        
        // --- REAL DATA INTEGRATION ---
        // Fetch real attendance count for this user in this month
        $presentDays = $db->getPresentDaysCount($user['id'], $month);
        
        // --- DEDUCTIONS INTEGRATION ---
        $activeDeductions = $db->getActiveDeductions($_SESSION['id'] ?? 0);
        $totalDeductionAmount = 0;
        foreach ($activeDeductions as $ded) {
            if ($ded['value'] <= 0) continue;
            if ($ded['type'] === 'percentage') {
                $totalDeductionAmount += ($baseSalary * (float)$ded['value']) / 100;
            } else {
                $totalDeductionAmount += (float)$ded['value'];
            }
        }
        $deductions = $totalDeductionAmount; 
        
        // Calculate Net Salary: (Daily Rate) * (Present Days) - Deductions
        $netSalary = ($totalDays > 0) ? ($baseSalary / $totalDays) * $presentDays - $deductions : 0;
        
        $payrollData = [
            'eid' => $user['id'],
            'ename' => $user['username'],
            'month' => $month,
            'base' => $baseSalary,
            'present' => $presentDays,
            'total' => $totalDays,
            'deductions' => $deductions,
            'net' => $netSalary,
            'status' => 'Processed'
        ];
        
        if ($db->upsertPayroll($payrollData)) {
            $successCount++;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'processed' => $successCount]);
    exit;
}
// Fetch single record for Payslip
if (isset($_GET['fetch_payslip_data'])) {
    $pid = $_GET['id'];
    header('Content-Type: application/json');
    $record = $db->getPayrollRecord($pid);
    if ($record) {
        $record['deduction_list'] = $db->getActiveDeductions($_SESSION['id'] ?? 0);
    }
    echo json_encode($record);
    exit;
}
// --- Company Asset AJAX Routes (Group D) ---
// Fetch All Assets from registry
if (isset($_GET['fetch_assets_list'])) {
    header('Content-Type: application/json');
    $assets = $db->getAllAssets();
    echo json_encode($assets);
    exit;
}
// Fetch Active Asset Assignments
if (isset($_GET['fetch_active_assignments'])) {
    header('Content-Type: application/json');
    $assignments = $db->getActiveAssignments();
    echo json_encode($assignments);
    exit;
}
// Fetch Active Asset Assignments for a specific User
if (isset($_GET['fetch_user_assets'])) {
    $uid = (int)$_GET['user_id'];
    header('Content-Type: application/json');
    $assignments = $db->getActiveAssignmentsByUser($uid);
    echo json_encode($assignments);
    exit;
}
// Add New Asset
if (isset($_POST['add_asset_action'])) {
    $data = [
        'name' => $util->testInput($_POST['asset_name']),
        'type' => $util->testInput($_POST['asset_type']),
        'sn'   => $util->testInput($_POST['serial_number'])
    ];
    if ($db->addAsset($data)) {
        echo $util->showMessage('success', 'Asset registered successfully!');
    } else {
        echo $util->showMessage('danger', 'Failed to register asset. Serial number might be duplicate.');
    }
    exit;
}
// Assign Asset to Employee
if (isset($_POST['assign_asset_action'])) {
    $aid   = (int)$_POST['asset_id'];
    $eid   = (int)$_POST['employee_id'];
    $date  = $util->testInput($_POST['assigned_date']);
    $notes = $util->testInput($_POST['notes']);
    if ($db->assignAsset($aid, $eid, $date, $notes)) {
        echo $util->showMessage('success', 'Asset assigned successfully!');
    } else {
        echo $util->showMessage('danger', 'Failed to assign asset.');
    }
    exit;
}
// Process Asset Return
if (isset($_POST['return_asset_action'])) {
    $id    = (int)$_POST['assignment_id'];
    $date  = $util->testInput($_POST['returned_date']);
    if ($db->returnAsset($id, $date)) {
        echo $util->showMessage('success', 'Asset returned successfully!');
    } else {
        echo $util->showMessage('danger', 'Failed to process return.');
    }
    exit;
}
// Fetch Employees List for Payslip Dropdown (JSON)
if (isset($_GET['fetch_users_json'])) {
    header('Content-Type: application/json');
    $users = $db->read();
    $data = [];
    foreach ($users as $u) {
        if ($u['is_active'] == 1) {
            $data[] = [
                'id'       => $u['id'],
                'username' => $u['username'],
                'emp_code' => $u['employee_id']
            ];
        }
    }
    echo json_encode($data);
    exit;
}
// Search Payslips History (JSON)
if (isset($_GET['search_payslips'])) {
    header('Content-Type: application/json');
    $eid = !empty($_GET['eid']) ? $_GET['eid'] : null;
    $month = !empty($_GET['month']) ? $_GET['month'] : null;
    $records = $db->searchPayslips($month, $eid);
    $activeDeds = $db->getActiveDeductions($_SESSION['id'] ?? 0);
    foreach ($records as &$r) {
        $r['deduction_list'] = $activeDeds;
    }
    echo json_encode($records);
    exit;
}
// Fetch Salary Summary statistics (JSON)
if (isset($_GET['fetch_salary_summary'])) {
    header('Content-Type: application/json');
    $eid = !empty($_GET['eid']) ? $_GET['eid'] : null;
    $month = !empty($_GET['month']) ? $_GET['month'] : null;
    $summary = $db->getSalarySummary($month, $eid);
    echo json_encode($summary);
    exit;
}

// --- Live Location Tracking (Periodic) ---
if (isset($_POST['record_live_location'])) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $db->createLocationHistoryTable();
    $uid = $_SESSION['id'] ?? null;
    $lat = $_POST['latitude'] ?? null;
    $lng = $_POST['longitude'] ?? null;
    $acc = $_POST['accuracy'] ?? null;
    $sid = $_POST['session_id'] ?? null;
    
    if ($uid && $lat && $lng) {
        $db->recordLocation($uid, $lat, $lng, $acc, $sid);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data or session']);
    }
    exit;
}
?>