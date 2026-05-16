<?php
  require_once 'config.php';
  class Database extends Config {
    // Ensure Offer Letters table exists
    public function createOfferLettersTable() {
      $sql = "CREATE TABLE IF NOT EXISTS offer_letters (
          id INT AUTO_INCREMENT PRIMARY KEY,
          candidate_name VARCHAR(100) NOT NULL,
          email VARCHAR(100) NOT NULL,
          phone VARCHAR(20) NOT NULL,
          position VARCHAR(100) NOT NULL,
          department VARCHAR(100),
          monthly_salary DECIMAL(10, 2),
          joining_date DATE,
          reporting_manager VARCHAR(100),
          offer_status VARCHAR(20) DEFAULT 'Draft',
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )";
      $this->conn->exec($sql);
    }
    // Insert User Into Database 
    public function insert($doj, $dob, $ename, $eemail, $enumber, $epass, $esalary, $etable, $emid, $ecode, $amountO, $amountT, $amountTh, $amountF, $amountFf, $amountS, $project_name, $D_project, $user_type = '', $assign_user = '', $is_active = 1) {
      $sql = 'INSERT INTO accounts (doj, dob, username, useremail, phonenumber, epassword, salary, tablename, employee_id, one_amt, two_amt, thrid_amt, forth_amt, fifth_amt, sixth_amt, project_name, project_type, user_type, assign_user, is_active)
       VALUES (:doj, :dob, :ename, :eemail, :enumber, :epass, :esalary, :etable, :emid, :amountO, :amountT, :amountTh, :amountF, :amountFf, :amountS, :project_name, :D_project, :user_type, :assign_user, :is_active)';
      $stmt = $this->conn->prepare($sql);
      $stmt->execute([
        'doj' => $doj,
        'dob' => $dob,
        'ename' => $ename,
        'eemail' => $eemail,
        'enumber' => $enumber,
        'epass' => $epass,
        'esalary' => $esalary,
        'etable' => $etable,
        'emid' => $emid,
        'amountO' => $amountO,
        'amountT' => $amountT,
        'amountTh' => $amountTh,
        'amountF' => $amountF,
        'amountFf' => $amountFf,
        'amountS' => $amountS,
        'project_name' => $project_name,
        'D_project' => $D_project,
        'user_type' => $user_type,
        'assign_user' => $assign_user,
        'is_active' => $is_active
      ]);
      return true;
    }
    // Fetch All accounts From Database
    public function read() {
      $sql = 'SELECT * FROM accounts ORDER BY id DESC';
      $stmt = $this->conn->prepare($sql);
      $stmt->execute();
      $result = $stmt->fetchAll();
      return $result;
    }
    // Fetch Single User From Database
    public function readOne($id) {
      $sql = 'SELECT * FROM accounts WHERE id = :id';
      $stmt = $this->conn->prepare($sql);
      $stmt->execute(['id' => $id]);
      $result = $stmt->fetch();
      return $result;
    }
    // Update Single User 
    public function update($id, 
      $doj, $dob, 
      $ename, $eemail, 
      $enumber, $epass, 
      $esalary, $etable, 
      $emid, $amountO, 
      $amountT, $amountTh, 
      $amountF, $amountFf, 
      $amountS, $project_name, 
      $D_project, $user_type,
      $assign_user, $is_active
    ) {
    
    // Get the current salary from the account table
    $sql = 'SELECT is_active, salary, assign_user FROM accounts WHERE id = :id';
    $stmt = $this->conn->prepare($sql);
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $oldIsActive = $row['is_active'];
    $oldSalary = $row['salary'];
    $oldAssignUser = $row['assign_user'];
    // Check if the assign_user has changed
    if ($assign_user !== $oldAssignUser) {
      // Step 1: Update the end date for the old manager's record in assign_user_history
      $sqlUpdateOldManager = 'UPDATE assign_user_history 
                              SET end_date = NOW() 
                              WHERE user_id = :user_id 
                              AND assign_user = :old_assign_user 
                              AND end_date IS NULL';  // Only update if end_date is NULL (still active)
      $stmtUpdateOldManager = $this->conn->prepare($sqlUpdateOldManager);
      $stmtUpdateOldManager->execute([
          'user_id' => $id,
          'old_assign_user' => $oldAssignUser
      ]);
      // Step 2: Insert a new record for the new manager in assign_user_history
      $sqlInsertNewManager = 'INSERT INTO assign_user_history (user_id, assign_user, effective_date) 
                              VALUES (:user_id, :assign_user, NOW())';
      $stmtInsertNewManager = $this->conn->prepare($sqlInsertNewManager);
      $stmtInsertNewManager->execute([
          'user_id' => $id,
          'assign_user' => $assign_user
      ]);
  }
    
    // Update the account table with the new values
    $sql = 'UPDATE accounts SET
        doj = :doj, 
        dob = :dob, 
        username = :ename, 
        useremail = :eemail, 
        phonenumber = :enumber, 
        epassword = :epass, 
        salary = :esalary, 
        tablename = :etable, 
        employee_id = :emid, 
        user_type = :user_type,
        old_salary = :oldsalary,
        one_amt = :amountO,
        two_amt = :amountT,
        thrid_amt = :amountTh,
        forth_amt = :amountF,
        fifth_amt = :amountFf,
        sixth_amt = :amountS,
        project_name = :project_name,
        project_type = :D_project,
        assign_user = :assign_user,
        is_active = :is_active,
        flag_user_login = CURRENT_TIMESTAMP,
        deactivated_at = CASE 
            WHEN :is_active = 0 AND :oldIsActive = 1 THEN NOW()  -- Set deactivated_at when deactivating
            WHEN :is_active = 1 THEN NULL  -- Reset deactivated_at when reactivating
            ELSE deactivated_at  -- Keep the current value if no status change
        END
        WHERE id = :id';
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        'doj' => $doj,
        'dob' => $dob,
        'ename' => $ename,
        'eemail' => $eemail,
        'enumber' => $enumber,
        'epass' => $epass,
        'esalary' => $esalary,
        'etable' => $etable,
        'emid' => $emid,
        'user_type' => $user_type,
        'oldsalary' => $oldSalary,
        'amountO' => $amountO,
        'amountT' => $amountT,
        'amountTh' => $amountTh,
        'amountF' => $amountF,
        'amountFf' => $amountFf,
        'amountS' => $amountS,
        'project_name' => $project_name,
        'D_project' => $D_project,
        'assign_user' => $assign_user,
        'is_active' => $is_active,
        'oldIsActive' => $oldIsActive,
        'id' => $id
    ]);
    // Send a WebSocket message to notify clients of the update
    $this->sendWebSocketMessage(json_encode(['action' => 'update', 'userId' => $id]));
    return true;
  }
  // this is to call the websocket
  private function sendWebSocketMessage($message) {
    if (file_exists('../vendor/autoload.php')) {
        require_once '../vendor/autoload.php';
    } else {
        return;
    }
    $client = new WebSocket\Client("ws://searchhomesindia.in:65003/");
    try {
        $client->send($message);
        $client->close();
    } catch (Exception $e) {
        error_log("Failed to send WebSocket message: " . $e->getMessage());
    }
  }
    // Delete User From Database
    public function delete($id) {
      // Fetch the 'tablename' from accounts
      $sqlFetchTableName = 'SELECT tablename FROM accounts WHERE id = :id';
      $stmtFetchTableName = $this->conn->prepare($sqlFetchTableName);
      $stmtFetchTableName->execute(['id' => $id]);
      $result = $stmtFetchTableName->fetch(PDO::FETCH_ASSOC);
      
      if ($result) {
          $tablename = $result['tablename'];
  
          // Delete records from user_alerts where tablename matches
          $sqlAlerts = 'DELETE FROM user_alerts WHERE user_id = :tablename';
          $stmtAlerts = $this->conn->prepare($sqlAlerts);
          $stmtAlerts->execute(['tablename' => $tablename]);
  
          // Now delete the user from the accounts table
          $sqlAccount = 'DELETE FROM accounts WHERE id = :id';
          $stmtAccount = $this->conn->prepare($sqlAccount);
          $stmtAccount->execute(['id' => $id]);
  
          return true;
      }
      return false; // Return false if no user is found with that id
    }

    // --- Offer Letter Methods ---
    public function getOfferLetters() {
      $sql = "SELECT * FROM offer_letters ORDER BY id DESC";
      $stmt = $this->conn->prepare($sql);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOfferLetter($id) {
      $sql = "SELECT * FROM offer_letters WHERE id = :id";
      $stmt = $this->conn->prepare($sql);
      $stmt->execute(['id' => $id]);
      return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function upsertOfferLetter($data) {
      if (isset($data['id']) && $data['id'] > 0) {
        $sql = "UPDATE offer_letters SET 
                candidate_name = :candidate_name, 
                email = :email, 
                phone = :phone, 
                position = :position, 
                department = :department, 
                monthly_salary = :monthly_salary, 
                joining_date = :joining_date, 
                reporting_manager = :reporting_manager,
                offer_status = :offer_status
                WHERE id = :id";
      } else {
        $sql = "INSERT INTO offer_letters (candidate_name, email, phone, position, department, monthly_salary, joining_date, reporting_manager, offer_status) 
                VALUES (:candidate_name, :email, :phone, :position, :department, :monthly_salary, :joining_date, :reporting_manager, :offer_status)";
      }
      $stmt = $this->conn->prepare($sql);
      return $stmt->execute($data);
    }

    public function deleteOfferLetter($id) {
      $sql = "DELETE FROM offer_letters WHERE id = :id";
      $stmt = $this->conn->prepare($sql);
      return $stmt->execute(['id' => $id]);
    }
    public function updateUserStatus($id, $status) {
      $sql = 'UPDATE accounts SET is_active = :status, deactivated_at = CASE WHEN :status = 0 THEN NOW() ELSE NULL END WHERE id = :id';
      $stmt = $this->conn->prepare($sql);
      return $stmt->execute(['id' => $id, 'status' => $status]);
    }
    public function active_users() {
      // Query to count Active (1) and Inactive (0) users
      $sql = 'SELECT is_active, COUNT(*) AS user_count 
              FROM accounts 
              GROUP BY is_active';
      
      $stmt = $this->conn->prepare($sql);
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      return $result; // Return the result as an array
    }
    public function updateAdvancePay($id, $newAdvancePay) {
      $sql = 'UPDATE payment_table SET advance_pay = :newAdvancePay WHERE id = :id';
      $stmt = $this->conn->prepare($sql);
      $stmt->bindParam(':newAdvancePay', $newAdvancePay);
      $stmt->bindParam(':id', $id);
      $stmt->execute();
    }
    // --- Live Location Tracking ---
    public function createLocationHistoryTable() {
        $sql = "CREATE TABLE IF NOT EXISTS location_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            latitude DECIMAL(10, 8) NOT NULL,
            longitude DECIMAL(11, 8) NOT NULL,
            accuracy FLOAT NULL,
            session_id VARCHAR(100) NULL,
            captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id, captured_at)
        )";
        $this->conn->exec($sql);
    }
    public function recordLocation($user_id, $lat, $lng, $accuracy = null, $session_id = null) {
        $sql = "INSERT INTO location_history (user_id, latitude, longitude, accuracy, session_id) VALUES (:uid, :lat, :lng, :acc, :sid)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['uid' => $user_id, 'lat' => $lat, 'lng' => $lng, 'acc' => $accuracy, 'sid' => $session_id]);
    }
    // --- Payroll & Payslip Logic ---
    // Create Payroll Table if not exists
    public function createPayrollTable() {
      $sql = "CREATE TABLE IF NOT EXISTS payroll (
          id INT AUTO_INCREMENT PRIMARY KEY,
          employee_id INT NOT NULL,
          employee_name VARCHAR(255) NOT NULL,
          month_year VARCHAR(20) NOT NULL,
          base_salary DECIMAL(10,2) NOT NULL,
          present_days INT NOT NULL,
          total_days INT NOT NULL,
          deductions DECIMAL(10,2) DEFAULT 0.00,
          net_salary DECIMAL(10,2) NOT NULL,
          status VARCHAR(20) DEFAULT 'Processed',
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY unique_payroll (employee_id, month_year)
      )";
      $this->conn->exec($sql);
    }
    // Create Deductions Table
    public function createDeductionsTable() {
      $sql = "CREATE TABLE IF NOT EXISTS deductions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          company_id INT DEFAULT 0,
          deduction_name VARCHAR(255) NOT NULL,
          type ENUM('percentage', 'fixed') NOT NULL,
          value DECIMAL(10,2) NOT NULL,
          status TINYINT(1) DEFAULT 1,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )";
      $this->conn->exec($sql);
    }
    // Fetch Active Deductions
    public function getActiveDeductions($company_id = 0) {
      $sql = "SELECT * FROM deductions WHERE status = 1 AND company_id = :cid";
      $stmt = $this->conn->prepare($sql);
      $stmt->execute(['cid' => $company_id]);
      return $stmt->fetchAll();
    }
    // Fetch All Deductions
    public function getAllDeductions($company_id = 0) {
      $sql = "SELECT * FROM deductions WHERE company_id = :cid ORDER BY created_at DESC";
      $stmt = $this->conn->prepare($sql);
      $stmt->execute(['cid' => $company_id]);
      return $stmt->fetchAll();
    }
    // Add Deduction
    public function addDeduction($name, $type, $value, $cid = 0) {
      $sql = "INSERT INTO deductions (deduction_name, type, value, company_id) VALUES (:name, :type, :value, :cid)";
      $stmt = $this->conn->prepare($sql);
      return $stmt->execute(['name' => $name, 'type' => $type, 'value' => $value, 'cid' => $cid]);
    }
    // Delete Deduction
    public function deleteDeduction($id) {
      $sql = "DELETE FROM deductions WHERE id = :id";
      $stmt = $this->conn->prepare($sql);
      return $stmt->execute(['id' => $id]);
    }
    // Toggle Deduction Status
    public function toggleDeduction($id, $status) {
      $sql = "UPDATE deductions SET status = :status WHERE id = :id";
      $stmt = $this->conn->prepare($sql);
      return $stmt->execute(['id' => $id, 'status' => $status]);
    }
    // Insert or Update Payroll Record
    public function upsertPayroll($data) {
      $sql = "INSERT INTO payroll (employee_id, employee_name, month_year, base_salary, present_days, total_days, deductions, net_salary, status)
              VALUES (:eid, :ename, :month, :base, :present, :total, :deductions, :net, :status)
              ON DUPLICATE KEY UPDATE 
              employee_name = VALUES(employee_name),
              base_salary = VALUES(base_salary),
              present_days = VALUES(present_days),
              total_days = VALUES(total_days),
              deductions = VALUES(deductions),
              net_salary = VALUES(net_salary),
              status = VALUES(status)";
      $stmt = $this->conn->prepare($sql);
      return $stmt->execute($data);
    }
    // Get Payroll List by Month
    public function getPayrollByMonth($month) {
      $sql = "SELECT p.*, a.useremail, a.phonenumber, a.employee_id as emp_code, a.user_type as designation 
              FROM payroll p
              LEFT JOIN accounts a ON p.employee_id = a.id
              WHERE p.month_year = :month 
              ORDER BY p.employee_name ASC";
      $stmt = $this->conn->prepare($sql);
      $stmt->execute(['month' => $month]);
      return $stmt->fetchAll();
    }
    // Get Single Payroll Record for Payslip
    public function getPayrollRecord($id) {
       $sql = "SELECT p.*, a.useremail, a.phonenumber, a.employee_id as emp_code, a.user_type as designation 
               FROM payroll p
               LEFT JOIN accounts a ON p.employee_id = a.id
               WHERE p.id = :id";
       $stmt = $this->conn->prepare($sql);
       $stmt->execute(['id' => $id]);
       return $stmt->fetch();
    }
    // Get count of 'Present' days for an employee in a specific month
    public function getPresentDaysCount($employee_id, $month_year) {
        // Parse month_year (e.g., "Apr 2024")
        $dateObj = DateTime::createFromFormat('M Y', $month_year);
        if (!$dateObj) return 0;
        
        $month = $dateObj->format('m');
        $year = $dateObj->format('Y');
        $sql = "SELECT COUNT(*) as present_count 
                FROM attendance_logs 
                WHERE user_id = :eid 
                AND MONTH(punch_date) = :month 
                AND YEAR(punch_date) = :year 
                AND status IN ('Present', 'Late')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'eid' => $employee_id,
            'month' => $month,
            'year' => $year
        ]);
        $result = $stmt->fetch();
        return (int)($result['present_count'] ?? 0);
    }
    // --- Asset Management Logic (Group D) ---
    // Fetch all assets from registry
    public function getAllAssets() {
        $sql = "SELECT * FROM assets ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    // Fetch active assignments (Who has what)
    public function getActiveAssignments() {
        $sql = "SELECT aa.*, a.asset_name, a.asset_type, a.serial_number, 
                       acc.username as employee_name, acc.employee_id as emp_code
                FROM asset_assignments aa
                JOIN assets a ON aa.asset_id = a.id
                JOIN accounts acc ON aa.employee_id = acc.id
                WHERE aa.returned_date IS NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    // Fetch active assignments for a specific user
    public function getActiveAssignmentsByUser($user_id) {
        $sql = "SELECT aa.*, a.asset_name, a.asset_type, a.serial_number 
                FROM asset_assignments aa
                JOIN assets a ON aa.asset_id = a.id
                WHERE aa.employee_id = :uid AND aa.returned_date IS NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['uid' => $user_id]);
        return $stmt->fetchAll();
    }
    // Add asset to registry
    public function addAsset($data) {
        $sql = "INSERT INTO assets (asset_name, asset_type, serial_number, status) 
                VALUES (:name, :type, :sn, 'Available')";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }
    // Assign an asset
    public function assignAsset($asset_id, $emp_id, $date, $notes) {
        $this->conn->beginTransaction();
        try {
            // 1. Create assignment record
            $sql = "INSERT INTO asset_assignments (asset_id, employee_id, assigned_date, notes) 
                    VALUES (:aid, :eid, :adate, :notes)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'aid' => $asset_id,
                'eid' => $emp_id,
                'adate' => $date,
                'notes' => $notes
            ]);
            // 2. Mark asset as Assigned
            $sqlUpd = "UPDATE assets SET status = 'Assigned' WHERE id = :aid";
            $stmtUpd = $this->conn->prepare($sqlUpd);
            $stmtUpd->execute(['aid' => $asset_id]);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    // Return an asset
    public function returnAsset($assignment_id, $date) {
        $this->conn->beginTransaction();
        try {
            // 1. Get the asset ID first
            $sqlId = "SELECT asset_id FROM asset_assignments WHERE id = :id";
            $stmtId = $this->conn->prepare($sqlId);
            $stmtId->execute(['id' => $assignment_id]);
            $res = $stmtId->fetch();
            $asset_id = $res['asset_id'];
            // 2. Mark assignment as closed
            $sql = "UPDATE asset_assignments SET returned_date = :rdate WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'id' => $assignment_id,
                'rdate' => $date
            ]);
            // 3. Mark asset as Available
            $sqlUpd = "UPDATE assets SET status = 'Available' WHERE id = :aid";
            $stmtUpd = $this->conn->prepare($sqlUpd);
            $stmtUpd->execute(['aid' => $asset_id]);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    // Search Payroll Records with filters
    public function searchPayslips($month = null, $eid = null) {
        $sql = "SELECT p.*, a.useremail, a.phonenumber, a.employee_id as emp_code, a.user_type as designation 
                FROM payroll p
                LEFT JOIN accounts a ON p.employee_id = a.id
                WHERE 1=1";
        $params = [];
        if ($month) { $sql .= " AND p.month_year = :month"; $params['month'] = $month; }
        if ($eid) { $sql .= " AND p.employee_id = :eid"; $params['eid'] = $eid; }
        $sql .= " ORDER BY p.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    // Fetch Salary Summary statistics
    public function getSalarySummary($month = null, $eid = null) {
        $sql = "SELECT SUM(net_salary) as total_payout, AVG(net_salary) as avg_salary, COUNT(*) as total_emps 
                FROM payroll WHERE 1=1";
        $params = [];
        if ($month) { $sql .= " AND month_year = :month"; $params['month'] = $month; }
        if ($eid) { $sql .= " AND employee_id = :eid"; $params['eid'] = $eid; }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    // --- FNF (Full and Final) Settlement Logic ---
    public function createFnfTable() {
        $sql = "CREATE TABLE IF NOT EXISTS fnf_settlements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            last_working_day DATE NOT NULL,
            unpaid_salary DECIMAL(10,2) DEFAULT 0.00,
            leave_encashment DECIMAL(10,2) DEFAULT 0.00,
            bonus_incentives DECIMAL(10,2) DEFAULT 0.00,
            deductions DECIMAL(10,2) DEFAULT 0.00,
            net_settlement DECIMAL(10,2) NOT NULL,
            status ENUM('Pending', 'Settled') DEFAULT 'Pending',
            assets_returned TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_fnf (user_id)
        )";
        $this->conn->exec($sql);
    }

    public function getFnfSettlement($user_id) {
        $sql = "SELECT * FROM fnf_settlements WHERE user_id = :uid";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['uid' => $user_id]);
        return $stmt->fetch();
    }

    public function upsertFnfSettlement($data) {
        $sql = "INSERT INTO fnf_settlements (user_id, last_working_day, unpaid_salary, leave_encashment, bonus_incentives, deductions, net_settlement, status, assets_returned)
                VALUES (:uid, :lwd, :salary, :leaves, :bonus, :deductions, :net, :status, :assets)
                ON DUPLICATE KEY UPDATE 
                last_working_day = VALUES(last_working_day),
                unpaid_salary = VALUES(unpaid_salary),
                leave_encashment = VALUES(leave_encashment),
                bonus_incentives = VALUES(bonus_incentives),
                deductions = VALUES(deductions),
                net_settlement = VALUES(net_settlement),
                status = VALUES(status),
                assets_returned = VALUES(assets_returned)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    public function checkPendingAssets($user_id) {
        $sql = "SELECT COUNT(*) as pending_count FROM asset_assignments WHERE employee_id = :uid AND returned_date IS NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['uid' => $user_id]);
        $res = $stmt->fetch();
        return (int)($res['pending_count'] ?? 0);
    }
  }
?>