<?php
// 1. เปิดใช้งาน Session สำหรับจดจำการล็อกอิน
session_start();

// 2. ตั้งค่าการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root"; // ใส่ username ของ Laragon/XAMPP
$password = "123456";     // ใส่ password (ปกติจะว่างไว้)
$dbname = "uinjob_db"; // ชื่อฐานข้อมูลของคุณ
$port = 3307;
$conn = new mysqli($servername, $username, $password, $dbname, 3307);
$conn->set_charset("utf8mb4");

// เช็กการเชื่อมต่อ
if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$activeTab = "login";
$alertMessage = ""; // ตัวแปรสำหรับเก็บข้อความแจ้งเตือน

// 3. จัดการเมื่อมีการกดปุ่ม Submit (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ----------------------------------------------------
    // ส่วนที่ 1: ระบบเข้าสู่ระบบ (Login)
    // ----------------------------------------------------
    if (isset($_POST['loginBtn'])) {
        $activeTab = "login";
        $email = $_POST['email'];
        $pass = $_POST['password'];

        // ค้นหาอีเมลในฐานข้อมูล
        $sql = "SELECT user_id, password, role, full_name FROM Users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // ถ้ารหัสผ่านตรงกัน (เช็กด้วย password_verify เพราะเราเข้ารหัสไว้ตอนสมัคร)
            if (password_verify($pass, $row['password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['full_name'] = $row['full_name'];
                
                // แจ้งเตือนและเด้งไปหน้าแรก (เดี๋ยวคุณค่อยไปสร้างไฟล์ index.php/dashboard.php ทีหลัง)
                echo "<script>alert('เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับคุณ ".$row['full_name']."'); window.location.href='index.php';</script>";
                exit();
            } else {
                $alertMessage = "รหัสผ่านไม่ถูกต้อง!";
            }
        } else {
            $alertMessage = "ไม่พบอีเมลนี้ในระบบ กรุณาสมัครสมาชิก";
        }
    }

    // ----------------------------------------------------
    // ส่วนที่ 2: ระบบสมัครสมาชิก (Register)
    // ----------------------------------------------------
    if (isset($_POST['registerBtn'])) {
        $activeTab = "register";
        $email = $_POST['email'];
        // เข้ารหัสผ่านเพื่อความปลอดภัย
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role_type']; // รับค่าจาก hidden input ที่สร้างเพิ่ม

        // ตรวจสอบว่าเป็นนักศึกษาหรือผู้ประกอบการ เพื่อจัดกลุ่มข้อมูลชื่อและเบอร์โทร
        if ($role === 'student') {
            $fullName = $_POST['firstname'] . " " . $_POST['lastname'];
            $phone = $_POST['phone'];
            // (ไฟล์รูปบัตรนักศึกษาอยู่ใน $_FILES['student_card'] สามารถเขียนโค้ดอัปโหลดรูปเพิ่มเติมได้ที่นี่)
        } else {
            $fullName = $_POST['company_name'];
            $phone = $_POST['company_phone'];
        }

        // ตรวจสอบว่าอีเมลนี้มีคนใช้ไปหรือยัง
        $checkEmail = $conn->query("SELECT user_id FROM Users WHERE username = '$email'");
        
        if ($checkEmail->num_rows > 0) {
            $alertMessage = "อีเมลนี้มีผู้ใช้งานแล้ว!";
        } else {
            // บันทึกข้อมูลลงฐานข้อมูล
            $sql = "INSERT INTO Users (username, password, full_name, role, phone_number) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $email, $hashed_password, $fullName, $role, $phone);

            if ($stmt->execute()) {
                $alertMessage = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
                $activeTab = "login"; // สมัครเสร็จให้เด้งกลับมาแท็บล็อกอิน
            } else {
                $alertMessage = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UinJob Connect</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="wrapper"> 
    <div class="container">
        <img src="image/logo.png" class="logo" alt="โลโก้ UinJob">
    <h2>Uinjob Connect</h2>
    <p>แพลตฟอร์มหางานพาร์ทไทม์สำหรับนักศึกษา</p>
    </div>
    <div class="card">
      <div class="tabs">
        <button type="button" class="tab active" onclick="showLogin()">เข้าสู่ระบบ</button>
        <button type="button" class="tab" onclick="showRegister()">ลงทะเบียน</button>
      </div>

        <?php if(!empty($alertMessage)): ?>
            <div class="alert <?php echo (strpos($alertMessage, 'สำเร็จ') !== false) ? 'success' : ''; ?>">
                <?php echo $alertMessage; ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" method="POST">
         อีเมล<input type="email" name="email" placeholder="your@email.com" class="input" required>
         รหัสผ่าน<input type="password" name="password" placeholder="******" class="input" required>
          <button class="btn" name="loginBtn">เข้าสู่ระบบ</button>
        </form>

        <form id="registerForm" method="POST" enctype="multipart/form-data" style="display:none;">
        
        <input type="hidden" name="role_type" id="role_type" value="student">

        ประเภทผู้ใช้<div class="sub-tabs">
        <button type="button" class="sub-tab active" onclick="showStudent()">นักศึกษา</button>
        <button type="button" class="sub-tab" onclick="showCompany()">ผู้ประกอบการ</button>
        </div>

        <div id="studentForm">
            ชื่อ<input type="text" name="firstname" placeholder="ชื่อจริง" class="input">
            นามสกุล<input type="text" name="lastname" placeholder="นามสกุล" class="input">
            มหาวิทยาลัย<input type="text" name="university" placeholder="เช่น มหาวิทยาลัยเทคโนโลยีสุรนารี" class="input">
            สาขาวิชา<input type="text" name="major" placeholder="เช่น ศาสตร์และศิลป์ดิจิทัล" class="input">
            เบอร์โทร<input type="tel" name="phone" placeholder="0xx-xxx-xxxx" class="input">

            <label class="upload-box">
            <input type="file" name="student_card" id="studentCard" hidden>
            <div>คลิกหรือวางไฟล์ที่นี่</div>
            </label>
        </div>
            
        <div id="companyForm">
            ชื่อบริษัท<input type="text" name="company_name" placeholder="ชื่อบริษัท" class="input">
            ประเภทธุรกิจ<input type="text" name="business_type" placeholder="ประเภทธุรกิจ" class="input">
            เบอร์โทร<input type="tel" name="company_phone" placeholder="0xx-xxx-xxxx" class="input">
        </div>
        
          อีเมล<input type="email" name="email" placeholder="your@email.com" class="input" required>
          ตั้งรหัสผ่าน<input type="password" name="password" placeholder="******" class="input" required>

          <div class="consent">
            <label>
                <input type="checkbox" id="agree">ฉันยอมรับ ข้อกำหนดการใช้งาน และ <br> นโยบายความเป็นส่วนตัว
            </label>
          </div>

          <button type="submit" name="registerBtn" class="btn" id="registerBtn">ลงทะเบียน</button>
        </form>
     
    </div>
    </div>

    <script> 
    let currentForm ="student";

        function showLogin() {
            document.getElementById("loginForm").style.display = "block";
            document.getElementById("registerForm").style.display = "none";
            const tabs = document.querySelectorAll(".tab");
            tabs[0].classList.add("active");
            tabs[1].classList.remove("active");
        }

        function showRegister() {
            document.getElementById("loginForm").style.display = "none";
            document.getElementById("registerForm").style.display = "block";
            const mainTabs = document.querySelectorAll(".tab");
            mainTabs[0].classList.remove("active");
            mainTabs[1].classList.add("active");
            showStudent(); // ตั้งค่าเริ่มต้นให้เป็นฟอร์มนักศึกษา
        }

        function showStudent() {
            document.getElementById("studentForm").style.display = "block";
            document.getElementById("companyForm").style.display = "none";
            document.getElementById("role_type").value = "student"; // อัปเดตค่าที่ซ่อนไว้ส่งให้ PHP
            currentForm ="student";

            const tabs = document.querySelectorAll(".sub-tab");
            tabs[0].classList.add("active");
            tabs[1].classList.remove("active");
        }

        function showCompany() {
            document.getElementById("studentForm").style.display = "none";
            document.getElementById("companyForm").style.display = "block";
            document.getElementById("role_type").value = "employer"; // อัปเดตค่าที่ซ่อนไว้ส่งให้ PHP
            currentForm ="company";

            const tabs = document.querySelectorAll(".sub-tab");
            tabs[1].classList.add("active");
            tabs[0].classList.remove("active");
        }
    
        const form = document.getElementById("registerForm");
        const fileInput = document.getElementById("studentCard");
        const checkbox = document.getElementById("agree");
        
        form.addEventListener("submit", function(e) {
            let activeForm = (currentForm === "student") ? document.getElementById("studentForm") : document.getElementById("companyForm");
            const inputs = activeForm.querySelectorAll("input");

            // ถอด required ออกจาก HTML เดิม แล้วมาใช้ JS ดักแทน เพื่อไม่ให้บั๊กเวลาสลับแท็บ
            for (let input of inputs) {
                if (input.type !== "file" && input.value.trim() === "") {
                    e.preventDefault();
                    alert("กรุณากรอกข้อมูลให้ครบ");
                    input.focus();
                    return;
                }
            }

            if (!checkbox.checked) {
                e.preventDefault();
                alert("กรุณายอมรับเงื่อนไขก่อน");
                return;
            }

            if (currentForm === "student" && fileInput.files.length === 0) {
                e.preventDefault();
                alert("กรุณาอัปโหลดบัตรนักศึกษา");
                return;
            }
        });

        fileInput.addEventListener("change", function(){
            if (fileInput.files.length > 0) {
                document.querySelector(".upload-box div").innerText = fileInput.files[0].name;
            }
        });
       
        window.onload = function() {
            const activeTab = "<?php echo $activeTab; ?>";
            if (activeTab === "register") {
                showRegister();
            } else {
                showLogin();
            }
        }
    </script>
</body>
</html>