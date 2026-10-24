<?php
/**
 * Student Management System - Database Configuration & API Handler
 * ឯកសារភ្ជាប់ទៅកាន់ Database និង API សម្រាប់គ្រប់គ្រងទិន្នន័យសិស្ស
 */

// កំណត់ Header សម្រាប់ការឆ្លើយតប (JSON & CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// កំណត់ទិន្នន័យភ្ជាប់ទៅ MySQL (XAMPP Default)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'student_db';
$db_port = 3306;
$charset = 'utf8mb4';

// 1. បង្កើតការភ្ជាប់តាម PDO (PDO Connection)
try {
    $dsn = "mysql:host=$db_host;port=$db_port;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // ភ្ជាប់ទៅ Server ជាមុន
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
    // បង្កើត Database ប្រសិនបើមិនទាន់មាន
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `$db_name`;");

    // បង្កើត Table students ប្រសិនបើមិនទាន់មាន
    $table_sql = "
    CREATE TABLE IF NOT EXISTS `students` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `studentId` VARCHAR(50) NOT NULL UNIQUE,
        `fullname` VARCHAR(100) NOT NULL,
        `gender` VARCHAR(10) NOT NULL,
        `dob` DATE NULL,
        `grade` VARCHAR(50) NOT NULL,
        `room` VARCHAR(50) NOT NULL,
        `pob_village` VARCHAR(100) DEFAULT '',
        `pob_commune` VARCHAR(100) DEFAULT '',
        `pob_district` VARCHAR(100) DEFAULT '',
        `pob_province` VARCHAR(100) DEFAULT '',
        `father_name` VARCHAR(100) DEFAULT '',
        `father_status` VARCHAR(50) DEFAULT '',
        `father_job` VARCHAR(100) DEFAULT '',
        `mother_name` VARCHAR(100) DEFAULT '',
        `mother_status` VARCHAR(50) DEFAULT '',
        `mother_job` VARCHAR(100) DEFAULT '',
        `curr_village` VARCHAR(100) DEFAULT '',
        `curr_commune` VARCHAR(100) DEFAULT '',
        `curr_district` VARCHAR(100) DEFAULT '',
        `curr_province` VARCHAR(100) DEFAULT '',
        `ethnicity_religion` VARCHAR(100) DEFAULT 'ខ្មែរ/ព្រះពុទ្ធ',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($table_sql);

    // បង្កើត Table users ប្រសិនបើមិនទាន់មាន
    $users_table_sql = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `fullname` VARCHAR(100) NOT NULL,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `phone` VARCHAR(20) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` VARCHAR(20) DEFAULT 'user',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($users_table_sql);

    // ធានាថាមាន Column phone និង plain_password ក្នុងតារាង users
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'phone'");
        if ($checkCol->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(20) NULL UNIQUE AFTER `username`");
        }
        $checkPwdCol = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'plain_password'");
        if ($checkPwdCol->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `plain_password` VARCHAR(255) NULL AFTER `password`");
        }
        $pdo->exec("UPDATE `users` SET `plain_password` = 'admin123' WHERE `username` = 'admin' AND (`plain_password` IS NULL OR `plain_password` = '')");
        $pdo->exec("UPDATE `users` SET `plain_password` = 'user123' WHERE `username` = 'usertest1' AND (`plain_password` IS NULL OR `plain_password` = '')");
        $pdo->exec("UPDATE `users` SET `plain_password` = '123456' WHERE `plain_password` IS NULL OR `plain_password` = ''");
    } catch (Exception $e) {}

    // ដោះស្រាយបញ្ហា Unique Index លើ email ចាស់ (ជៀសវាង Error: Duplicate entry '' for key 'email')
    try {
        $emailIdx = $pdo->query("SHOW INDEX FROM `users` WHERE Key_name = 'email'")->fetchAll();
        if (!empty($emailIdx)) {
            $pdo->exec("ALTER TABLE `users` DROP INDEX `email`");
        }
        $checkEmailCol = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'email'");
        if ($checkEmailCol->rowCount() > 0) {
            $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `email` VARCHAR(100) NULL DEFAULT NULL");
            $pdo->exec("UPDATE `users` SET `email` = NULL WHERE `email` = ''");
        }
    } catch (Exception $e) {}

    // កំណត់លេខទូរស័ព្ទ Admin លំនាំដើម ប្រសិនបើទទេ
    try {
        $pdo->exec("UPDATE `users` SET `phone` = '012345678' WHERE `username` = 'admin' AND (`phone` IS NULL OR `phone` = '')");
    } catch (Exception $e) {}

    // បញ្ចូល Admin User គំរូ ប្រសិនបើមិនទាន់មាន User
    $userCountStmt = $pdo->query("SELECT COUNT(*) FROM `users`");
    if ($userCountStmt->fetchColumn() == 0) {
        $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
        $insertAdmin = $pdo->prepare("INSERT INTO `users` (`fullname`, `username`, `phone`, `password`, `role`) VALUES (:fullname, :username, :phone, :password, :role)");
        $insertAdmin->execute([
            ':fullname' => 'អ្នកគ្រប់គ្រងប្រព័ន្ធ',
            ':username' => 'admin',
            ':phone' => '012345678',
            ':password' => $defaultPass,
            ':role' => 'admin'
        ]);
    }

} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

// 2. បង្កើតការភ្ជាប់តាម MySQLi សម្រាប់គាំទ្រកូដចាស់ (MySQLi Connection)
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if (!$conn->connect_error) {
    $conn->set_charset("utf8mb4");
}

// 3. មុខងារជំនួយសម្រាប់ API (Helper API Functions)
function jsonResponse($success, $data = null, $message = '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// 4. ដោះស្រាយសំណើ API (Handle HTTP Requests)
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ប្រសិនបើជាសំណើ API (AJAX / Fetch)
if (!empty($action) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
    if (isset($db_error)) {
        jsonResponse(false, null, "Database Connection Error: " . $db_error);
    }

    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true);
    if (!is_array($inputData)) {
        $inputData = $_POST;
    }
    if (isset($inputData['students']) && is_string($inputData['students'])) {
        $decoded = json_decode($inputData['students'], true);
        if (is_array($decoded)) {
            $inputData['students'] = $decoded;
        }
    }
    if (empty($action) && isset($inputData['action'])) {
        $action = $inputData['action'];
    }

    switch ($action) {
        // ទាញយកទិន្នន័យសិស្សទាំងអស់ (Get all students)
        case 'get_students':
        case 'fetch':
        case 'all':
            try {
                $stmt = $pdo->query("SELECT * FROM students ORDER BY grade ASC, room ASC, fullname ASC");
                $students = $stmt->fetchAll();
                jsonResponse(true, $students, "ទាញយកទិន្នន័យជោគជ័យ");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // រក្សាទុកទិន្នន័យទាំងអស់ចូលក្នុង Database (Save all students / Batch save)
        case 'save_all':
        case 'save':
        case 'sync':
            try {
                $studentList = $inputData['students'] ?? [];
                if (!is_array($studentList)) {
                    jsonResponse(false, null, "ទិន្នន័យមិនត្រឹមត្រូវ");
                }
                $pdo->beginTransaction();
                
                $fields = [
                    'studentId', 'fullname', 'gender', 'dob', 'grade', 'room',
                    'pob_village', 'pob_commune', 'pob_district', 'pob_province',
                    'father_name', 'father_status', 'father_job',
                    'mother_name', 'mother_status', 'mother_job',
                    'curr_village', 'curr_commune', 'curr_district', 'curr_province',
                    'ethnicity_religion'
                ];
                
                $updateCols = [];
                foreach ($fields as $f) {
                    $updateCols[] = "`$f` = VALUES(`$f`)";
                }
                
                $sql = "INSERT INTO students (" . implode(', ', $fields) . ") 
                        VALUES (" . implode(', ', array_map(fn($f) => ":$f", $fields)) . ")
                        ON DUPLICATE KEY UPDATE " . implode(', ', $updateCols);
                        
                $stmt = $pdo->prepare($sql);
                
                foreach ($studentList as $s) {
                    $params = [];
                    foreach ($fields as $f) {
                        $params[":$f"] = $s[$f] ?? '';
                    }
                    if (empty($params[':ethnicity_religion'])) {
                        $params[':ethnicity_religion'] = 'ខ្មែរ/ព្រះពុទ្ធ';
                    }
                    $stmt->execute($params);
                }
                
                $pdo->commit();
                jsonResponse(true, ['count' => count($studentList)], "រក្សាទុកទិន្នន័យទាំងអស់ចូលក្នុង Database ដោយជោគជ័យ!");
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // បញ្ចូលសិស្សថ្មី (Insert student)
        case 'add_student':
        case 'add':
        case 'create':
            try {
                $fields = [
                    'studentId', 'fullname', 'gender', 'dob', 'grade', 'room',
                    'pob_village', 'pob_commune', 'pob_district', 'pob_province',
                    'father_name', 'father_status', 'father_job',
                    'mother_name', 'mother_status', 'mother_job',
                    'curr_village', 'curr_commune', 'curr_district', 'curr_province',
                    'ethnicity_religion'
                ];
                
                $sql = "INSERT INTO students (" . implode(', ', $fields) . ") 
                        VALUES (" . implode(', ', array_map(fn($f) => ":$f", $fields)) . ")";
                $stmt = $pdo->prepare($sql);
                
                $params = [];
                foreach ($fields as $field) {
                    $params[":$field"] = $inputData[$field] ?? '';
                }
                if (empty($params[':ethnicity_religion'])) {
                    $params[':ethnicity_religion'] = 'ខ្មែរ/ព្រះពុទ្ធ';
                }
                
                $stmt->execute($params);
                $newId = $pdo->lastInsertId();
                jsonResponse(true, ['id' => $newId], "បានបញ្ចូលសិស្សដោយជោគជ័យ");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // កែប្រែព័ត៌មានសិស្ស (Update student)
        case 'update_student':
        case 'update':
            try {
                $studentId = $inputData['studentId'] ?? '';
                $id = $inputData['id'] ?? null;
                
                $fields = [
                    'studentId', 'fullname', 'gender', 'dob', 'grade', 'room',
                    'pob_village', 'pob_commune', 'pob_district', 'pob_province',
                    'father_name', 'father_status', 'father_job',
                    'mother_name', 'mother_status', 'mother_job',
                    'curr_village', 'curr_commune', 'curr_district', 'curr_province',
                    'ethnicity_religion'
                ];
                
                $setParts = [];
                $params = [];
                foreach ($fields as $field) {
                    if (isset($inputData[$field])) {
                        $setParts[] = "`$field` = :$field";
                        $params[":$field"] = $inputData[$field];
                    }
                }
                
                if (empty($setParts)) {
                    jsonResponse(false, null, "គ្មានទិន្នន័យសម្រាប់កែប្រែ");
                }
                
                if (!empty($id)) {
                    $params[':target_id'] = $id;
                    $sql = "UPDATE students SET " . implode(', ', $setParts) . " WHERE id = :target_id";
                } else {
                    $params[':target_studentId'] = $studentId;
                    $sql = "UPDATE students SET " . implode(', ', $setParts) . " WHERE studentId = :target_studentId";
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                jsonResponse(true, null, "បានកែប្រែព័ត៌មានជោគជ័យ");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // កែប្រែក្រឡាតារាងផ្ទាល់ (Inline update field)
        case 'update_field':
            try {
                $id = $inputData['id'] ?? '';
                $studentId = $inputData['studentId'] ?? $id;
                $field = $inputData['field'] ?? '';
                $value = $inputData['value'] ?? '';

                $allowedFields = [
                    'pob_village', 'pob_commune', 'pob_district', 'pob_province',
                    'father_name', 'father_status', 'father_job',
                    'mother_name', 'mother_status', 'mother_job',
                    'curr_village', 'curr_commune', 'curr_district', 'curr_province',
                    'ethnicity_religion', 'fullname', 'gender', 'dob', 'grade', 'room', 'studentId'
                ];

                if (!in_array($field, $allowedFields)) {
                    jsonResponse(false, null, "Field មិនត្រឹមត្រូវទេ");
                }

                $stmt = $pdo->prepare("UPDATE students SET `$field` = ? WHERE id = ? OR studentId = ?");
                $stmt->execute([trim($value), $id, $studentId]);
                jsonResponse(true, null, "បានកែប្រែជោគជ័យ");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // លុបសិស្ស (Delete student)
        case 'delete_student':
        case 'delete':
            try {
                $id = $inputData['id'] ?? '';
                $studentId = $inputData['studentId'] ?? $id;
                $stmt = $pdo->prepare("DELETE FROM students WHERE id = ? OR studentId = ?");
                $stmt->execute([$id, $studentId]);
                jsonResponse(true, null, "បានលុបទិន្នន័យសិស្សជោគជ័យ");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // លុបទិន្នន័យសិស្សទាំងអស់ (Clear all data)
        case 'clear_all':
            try {
                $pdo->exec("TRUNCATE TABLE students");
                jsonResponse(true, null, "បានលុបទិន្នន័យទាំងអស់");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // ចុះឈ្មោះគណនីថ្មីដោយប្រើលេខទូរស័ព្ទ (Register new user account with phone)
        case 'register':
        case 'create_account':
            try {
                $fullname = trim($inputData['fullname'] ?? '');
                $username = trim($inputData['username'] ?? '');
                $phone = trim($inputData['phone'] ?? $inputData['email'] ?? '');
                $password = trim($inputData['password'] ?? '');

                if (empty($fullname) || empty($username) || empty($phone) || empty($password)) {
                    jsonResponse(false, null, "សូមបំពេញព័ត៌មានចាំបាច់ (ឈ្មោះពេញ, ឈ្មោះអ្នកប្រើ, លេខទូរស័ព្ទ និងពាក្យសម្ងាត់) ឱ្យបានគ្រប់គ្រាន់!");
                }
                if (strlen($username) < 3) {
                    jsonResponse(false, null, "ឈ្មោះអ្នកប្រើ (Username) ត្រូវមានយ៉ាងតិច ៣ តួអក្សរ!");
                }
                if (strlen($password) < 4) {
                    jsonResponse(false, null, "ពាក្យសម្ងាត់ត្រូវមានយ៉ាងតិច ៤ តួអក្សរ!");
                }

                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                if (strlen($cleanPhone) < 8 || strlen($cleanPhone) > 15) {
                    jsonResponse(false, null, "លេខទូរស័ព្ទមិនត្រឹមត្រូវទេ! សូមបញ្ចូលលេខទូរស័ព្ទពី ៨ ទៅ ១២ ខ្ទង់។");
                }

                // ពិនិត្យឈ្មោះអ្នកប្រើ (Check username exists)
                $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = :username");
                $checkUser->execute([':username' => $username]);
                if ($checkUser->fetch()) {
                    jsonResponse(false, null, "ឈ្មោះអ្នកប្រើ '$username' មានរួចហើយ! សូមជ្រើសឈ្មោះផ្សេង។");
                }

                // ពិនិត្យលេខទូរស័ព្ទ (Check phone exists)
                $checkPhone = $pdo->prepare("SELECT id FROM users WHERE phone = :phone OR REPLACE(REPLACE(phone, ' ', ''), '-', '') = :cleanPhone");
                $checkPhone->execute([':phone' => $phone, ':cleanPhone' => $cleanPhone]);
                if ($checkPhone->fetch()) {
                    jsonResponse(false, null, "លេខទូរស័ព្ទ '$phone' ត្រូវបានប្រើប្រាស់រួចហើយ! ប្រសិនបើអ្នកភ្លេចពាក្យសម្ងាត់ សូមប្រើមុខងារ 'ភ្លេចពាក្យសម្ងាត់'។");
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (fullname, username, phone, password, plain_password, role) VALUES (:fullname, :username, :phone, :password, :plain_password, 'user')");
                $stmt->execute([
                    ':fullname' => $fullname,
                    ':username' => $username,
                    ':phone' => $phone,
                    ':password' => $hashedPassword,
                    ':plain_password' => $password
                ]);

                $newUserId = $pdo->lastInsertId();
                $userData = [
                    'id' => $newUserId,
                    'fullname' => $fullname,
                    'username' => $username,
                    'phone' => $phone,
                    'role' => 'user'
                ];
                $_SESSION['user'] = $userData;

                jsonResponse(true, $userData, "ការបង្កើតគណនីបានជោគជ័យ!");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // ចូលប្រើប្រព័ន្ធដោយប្រើ Username ឬ លេខទូរស័ព្ទ (Log in)
        case 'login':
            try {
                $usernameOrPhone = trim($inputData['username'] ?? $inputData['phone'] ?? '');
                $password = trim($inputData['password'] ?? '');

                if (empty($usernameOrPhone) || empty($password)) {
                    jsonResponse(false, null, "សូមបញ្ចូលឈ្មោះអ្នកប្រើ ឬ លេខទូរស័ព្ទ និងពាក្យសម្ងាត់!");
                }

                $cleanPhone = preg_replace('/[^0-9]/', '', $usernameOrPhone);

                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u OR phone = :p OR REPLACE(REPLACE(phone, ' ', ''), '-', '') = :cp LIMIT 1");
                $stmt->execute([':u' => $usernameOrPhone, ':p' => $usernameOrPhone, ':cp' => $cleanPhone]);
                $user = $stmt->fetch();

                if (!$user) {
                    jsonResponse(false, null, "រកមិនឃើញគណនីនេះទេ!");
                }

                if (!password_verify($password, $user['password'])) {
                    jsonResponse(false, null, "ពាក្យសម្ងាត់មិនត្រឹមត្រូវទេ!");
                }

                $userData = [
                    'id' => $user['id'],
                    'fullname' => $user['fullname'],
                    'username' => $user['username'],
                    'phone' => $user['phone'] ?? '',
                    'role' => $user['role'] ?? 'user'
                ];
                $_SESSION['user'] = $userData;

                jsonResponse(true, $userData, "ចូលប្រើប្រព័ន្ធបានជោគជ័យ! សូមស្វាគមន៍ " . $user['fullname']);
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // ស្វែងរក / កំណត់ពាក្យសម្ងាត់ឡើងវិញតាមលេខទូរស័ព្ទ (Forgot / Reset Password by Phone)
        case 'forgot_password':
        case 'reset_password':
            try {
                $phone = trim($inputData['phone'] ?? '');
                $newPassword = trim($inputData['new_password'] ?? $inputData['password'] ?? '');

                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                if (empty($cleanPhone) || strlen($cleanPhone) < 8) {
                    jsonResponse(false, null, "សូមបញ្ចូលលេខទូរស័ព្ទដែលបានចុះឈ្មោះត្រឹមត្រូវ!");
                }

                // ស្វែងរកគណនីតាមលេខទូរស័ព្ទ
                $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = :p OR REPLACE(REPLACE(phone, ' ', ''), '-', '') = :cp LIMIT 1");
                $stmt->execute([':p' => $phone, ':cp' => $cleanPhone]);
                $user = $stmt->fetch();

                if (!$user) {
                    jsonResponse(false, null, "រកមិនឃើញគណនីដែលបានភ្ជាប់ជាមួយលេខទូរស័ព្ទ '$phone' នេះទេ! សូមពិនិត្យលេខទូរស័ព្ទម្តងទៀត។");
                }

                // ប្រសិនបើគ្រាន់តែផ្ទៀងផ្ទាត់លេខទូរស័ព្ទ
                if (empty($newPassword)) {
                    jsonResponse(true, [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'fullname' => $user['fullname'],
                        'phone' => $user['phone']
                    ], "បានរកឃើញគណនីរបស់អ្នក (" . $user['fullname'] . " - @" . $user['username'] . ")! សូមបញ្ចូលពាក្យសម្ងាត់ថ្មី។");
                }

                // ប្រសិនបើកំណត់ពាក្យសម្ងាត់ថ្មី
                if (strlen($newPassword) < 4) {
                    jsonResponse(false, null, "ពាក្យសម្ងាត់ថ្មីត្រូវមានយ៉ាងតិច ៤ តួអក្សរ!");
                }

                $newHashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = :pwd, plain_password = :plain_pwd WHERE id = :id");
                $updateStmt->execute([':pwd' => $newHashed, ':plain_pwd' => $newPassword, ':id' => $user['id']]);

                jsonResponse(true, [
                    'username' => $user['username'],
                    'fullname' => $user['fullname']
                ], "ពាក្យសម្ងាត់របស់គណនី @" . $user['username'] . " ត្រូវបានកំណត់ឡើងវិញដោយជោគជ័យ! អ្នកអាចចូលប្រើបានឥឡូវនេះ។");

            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // ពិនិត្យ Session អ្នកប្រើ (Check session)
        case 'check_session':
        case 'current_user':
            if (isset($_SESSION['user'])) {
                jsonResponse(true, $_SESSION['user'], "មាន Session សកម្ម");
            } else {
                jsonResponse(false, null, "មិនទាន់ចូលប្រើទេ");
            }
            break;

        // ចាកចេញពីប្រព័ន្ធ (Log out)
        case 'logout':
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            jsonResponse(true, null, "បានចាកចេញដោយជោគជ័យ");
            break;

        // ========================================================
        // មុខងារគ្រប់គ្រងគណនី និងពាក្យសម្ងាត់សម្រាប់ Admin (Admin User Management)
        // ========================================================

        // ទាញយកស្ថិតិទិន្នន័យសម្រាប់ Dashboard (Get Dashboard Stats)
        case 'dashboard_stats':
        case 'get_stats':
            try {
                $totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
                $totalMale = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE gender = 'ប្រុស'")->fetchColumn();
                $totalFemale = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE gender = 'ស្រី'")->fetchColumn();
                
                $gradeCounts = [];
                for ($g = 1; $g <= 6; $g++) {
                    $gradeKh = ['ទី១', 'ទី២', 'ទី៣', 'ទី៤', 'ទី៥', 'ទី៦'][$g - 1];
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE grade = :g");
                    $stmt->execute([':g' => $gradeKh]);
                    $totalInGrade = (int)$stmt->fetchColumn();

                    $stmtFemale = $pdo->prepare("SELECT COUNT(*) FROM students WHERE grade = :g AND gender = 'ស្រី'");
                    $stmtFemale->execute([':g' => $gradeKh]);
                    $femaleInGrade = (int)$stmtFemale->fetchColumn();

                    $gradeCounts[$gradeKh] = [
                        'total' => $totalInGrade,
                        'female' => $femaleInGrade
                    ];
                }

                $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                $totalAdmins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

                jsonResponse(true, [
                    'total_students' => $totalStudents,
                    'total_male' => $totalMale,
                    'total_female' => $totalFemale,
                    'grade_counts' => $gradeCounts,
                    'total_users' => $totalUsers,
                    'total_admins' => $totalAdmins
                ], "ទាញយកស្ថិតិជោគជ័យ");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // ទាញយកបញ្ជីគណនីអ្នកប្រើទាំងអស់ (Get all users for Admin)
        case 'get_users':
        case 'fetch_users':
            try {
                $stmt = $pdo->query("SELECT id, fullname, username, phone, role, plain_password, created_at, updated_at FROM users ORDER BY id ASC");
                $users = $stmt->fetchAll();
                jsonResponse(true, $users, "ទាញយកបញ្ជីគណនីជោគជ័យ");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // Admin កែប្រែពាក្យសម្ងាត់របស់អ្នកប្រើណាម្នាក់ (Admin Update User Password)
        case 'admin_update_password':
        case 'change_user_password':
            try {
                $userId = $inputData['user_id'] ?? $inputData['id'] ?? null;
                $newPassword = trim($inputData['new_password'] ?? $inputData['password'] ?? '');

                if (!$userId) {
                    jsonResponse(false, null, "សូមបញ្ជាក់លេខសម្គាល់គណនី (User ID)!");
                }
                if (strlen($newPassword) < 4) {
                    jsonResponse(false, null, "ពាក្យសម្ងាត់ថ្មីត្រូវមានយ៉ាងតិច ៤ តួអក្សរ!");
                }

                $userCheck = $pdo->prepare("SELECT id, username, fullname FROM users WHERE id = :id");
                $userCheck->execute([':id' => $userId]);
                $targetUser = $userCheck->fetch();

                if (!$targetUser) {
                    jsonResponse(false, null, "រកមិនឃើញគណនីនេះទេ!");
                }

                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = :pwd, plain_password = :plain_pwd WHERE id = :id");
                $stmt->execute([':pwd' => $hashedPassword, ':plain_pwd' => $newPassword, ':id' => $userId]);

                jsonResponse(true, [
                    'id' => $userId,
                    'username' => $targetUser['username'],
                    'fullname' => $targetUser['fullname']
                ], "ពាក្យសម្ងាត់របស់គណនី @" . $targetUser['username'] . " ត្រូវបានផ្លាស់ប្តូរដោយជោគជ័យ!");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // Admin កែប្រែព័ត៌មានអ្នកប្រើ (ឈ្មោះ លេខទូរស័ព្ទ សិទ្ធិ) (Admin Update User Info)
        case 'admin_update_user':
            try {
                $userId = $inputData['id'] ?? null;
                $fullname = trim($inputData['fullname'] ?? '');
                $phone = trim($inputData['phone'] ?? '');
                $role = trim($inputData['role'] ?? 'user');

                if (!$userId) {
                    jsonResponse(false, null, "សូមបញ្ជាក់លេខសម្គាល់គណនី (User ID)!");
                }
                if (empty($fullname) || empty($phone)) {
                    jsonResponse(false, null, "សូមបំពេញឈ្មោះពេញ និងលេខទូរស័ព្ទ!");
                }
                if (!in_array($role, ['admin', 'user'])) {
                    $role = 'user';
                }

                // ពិនិត្យលេខទូរស័ព្ទស្ទួន
                $checkPhone = $pdo->prepare("SELECT id FROM users WHERE phone = :p AND id != :id");
                $checkPhone->execute([':p' => $phone, ':id' => $userId]);
                if ($checkPhone->fetch()) {
                    jsonResponse(false, null, "លេខទូរស័ព្ទ '$phone' មានគណនីផ្សេងប្រើរួចហើយ!");
                }

                $stmt = $pdo->prepare("UPDATE users SET fullname = :f, phone = :p, role = :r WHERE id = :id");
                $stmt->execute([
                    ':f' => $fullname,
                    ':p' => $phone,
                    ':r' => $role,
                    ':id' => $userId
                ]);

                jsonResponse(true, [
                    'id' => $userId,
                    'fullname' => $fullname,
                    'phone' => $phone,
                    'role' => $role
                ], "កែប្រែព័ត៌មានគណនីបានជោគជ័យ!");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // Admin លុបគណនីអ្នកប្រើ (Admin Delete User)
        case 'admin_delete_user':
            try {
                $userId = $inputData['id'] ?? null;
                if (!$userId) {
                    jsonResponse(false, null, "សូមបញ្ជាក់លេខសម្គាល់គណនី (User ID)!");
                }

                $userCheck = $pdo->prepare("SELECT id, username, role FROM users WHERE id = :id");
                $userCheck->execute([':id' => $userId]);
                $targetUser = $userCheck->fetch();

                if (!$targetUser) {
                    jsonResponse(false, null, "រកមិនឃើញគណនីនេះទេ!");
                }

                // ការពារកុំឱ្យលុប Main Admin (admin)
                if ($targetUser['username'] === 'admin') {
                    jsonResponse(false, null, "មិនអាចលុបគណនី Master Admin ('admin') បានទេ!");
                }

                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute([':id' => $userId]);

                jsonResponse(true, ['deleted_id' => $userId], "គណនី @" . $targetUser['username'] . " ត្រូវបានលុបដោយជោគជ័យ!");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // Admin បង្កើតគណនីថ្មីដោយផ្ទាល់ (Admin Create User)
        case 'admin_create_user':
            try {
                $fullname = trim($inputData['fullname'] ?? '');
                $username = trim($inputData['username'] ?? '');
                $phone = trim($inputData['phone'] ?? '');
                $password = trim($inputData['password'] ?? '');
                $role = trim($inputData['role'] ?? 'user');

                if (empty($fullname) || empty($username) || empty($phone) || empty($password)) {
                    jsonResponse(false, null, "សូមបំពេញព័ត៌មានឱ្យបានគ្រប់គ្រាន់!");
                }
                if (strlen($username) < 3) {
                    jsonResponse(false, null, "Username ត្រូវមានយ៉ាងតិច ៣ តួអក្សរ!");
                }
                if (strlen($password) < 4) {
                    jsonResponse(false, null, "Password ត្រូវមានយ៉ាងតិច ៤ តួអក្សរ!");
                }

                // Check username exists
                $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = :u");
                $checkUser->execute([':u' => $username]);
                if ($checkUser->fetch()) {
                    jsonResponse(false, null, "Username '$username' មានរួចហើយ!");
                }

                // Check phone exists
                $checkPhone = $pdo->prepare("SELECT id FROM users WHERE phone = :p");
                $checkPhone->execute([':p' => $phone]);
                if ($checkPhone->fetch()) {
                    jsonResponse(false, null, "លេខទូរស័ព្ទ '$phone' មានរួចហើយ!");
                }

                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (fullname, username, phone, password, plain_password, role) VALUES (:f, :u, :p, :pwd, :plain_pwd, :r)");
                $stmt->execute([
                    ':f' => $fullname,
                    ':u' => $username,
                    ':p' => $phone,
                    ':pwd' => $hashed,
                    ':plain_pwd' => $password,
                    ':r' => in_array($role, ['admin', 'user']) ? $role : 'user'
                ]);

                jsonResponse(true, ['id' => $pdo->lastInsertId()], "បង្កើតគណនីថ្មីដោយជោគជ័យ!");
            } catch (Exception $e) {
                jsonResponse(false, null, $e->getMessage());
            }
            break;

        // ពិនិត្យការតភ្ជាប់ (Health check)
        case 'check':
        case 'test':
            jsonResponse(true, ['database' => $db_name, 'status' => 'connected'], "ភ្ជាប់ Database បានជោគជ័យ!");
            break;

        default:
            jsonResponse(false, null, "Action មិនត្រឹមត្រូវ: $action");
            break;
    }
}

// 5. បង្ហាញព័ត៌មានស្ថានភាពនៅពេលបើកផ្ទាល់តាម Browser (Direct Browser View)
$isDirectAccess = (php_sapi_name() !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__));
if ($isDirectAccess && empty($action)) {
    ?>
    <!DOCTYPE html>
    <html lang="km">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ស្ថានភាព Database - Student Management System</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&display=swap" rel="stylesheet">
        <style>body { font-family: 'Battambang', sans-serif; }</style>
    </head>
    <body class="bg-gray-100 min-h-screen p-6">
        <div class="max-w-xl mx-auto bg-white rounded-xl shadow-md p-6 border border-gray-200 mt-10">
            <h1 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-3 flex items-center gap-2">
                <span class="text-indigo-600">🗄️</span> ស្ថានភាព Database
            </h1>

            <?php if (isset($db_error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                    <p class="font-bold text-red-700">❌ មិនអាចភ្ជាប់ទៅកាន់ MySQL បានទេ</p>
                    <p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($db_error) ?></p>
                    <p class="text-xs text-gray-500 mt-2">សូមប្រាកដថាអ្នកបានបើក MySQL នៅក្នុង XAMPP Control Panel។</p>
                </div>
            <?php else: ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                    <p class="font-bold text-green-700">✅ ភ្ជាប់ទៅកាន់ Database បានជោគជ័យ!</p>
                    <ul class="text-sm text-gray-700 mt-2 space-y-1">
                        <li><strong>Host:</strong> <?= htmlspecialchars($db_host) ?>:<?= htmlspecialchars($db_port) ?></li>
                        <li><strong>Database:</strong> <?= htmlspecialchars($db_name) ?></li>
                        <li><strong>Charset:</strong> <?= htmlspecialchars($charset) ?></li>
                        <li><strong>Status:</strong> តារាង <code>students</code> រួចរាល់សម្រាប់ប្រើប្រាស់</li>
                    </ul>
                </div>

                <?php
                // រាប់ចំនួនសិស្សបច្ចុប្បន្ន
                $count = 0;
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM students");
                    $count = $stmt->fetchColumn();
                } catch (Exception $e) {}
                ?>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-sm text-blue-800">
                    ចំនួនទិន្នន័យសិស្សក្នុង Database៖ <strong><?= $count ?></strong> នាក់
                </div>

                <div class="flex gap-2">
                    <a href="index.html" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 inline-block">
                        ← ទៅកាន់កម្មវិធីគ្រប់គ្រងសិស្ស
                    </a>
                    <a href="?action=test" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-150 inline-block">
                        សាកល្បង API (JSON)
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
