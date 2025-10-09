<?php
session_start();
include 'includes/db_connection.php';

$error_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = trim($_POST['employee_id']);
    $password = trim($_POST['password']);
    
    if (empty($employee_id) || empty($password)) {
        $error_message = 'Please enter both Register ID and Password.';
    } else {
        $stmt = $conn->prepare("SELECT employee_id, password, role, firstname, lastname FROM account WHERE employee_id = ?");
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['employee_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['lastname'] = $user['lastname'];
                
                if ($user['role'] === 'Admin') {
                    header('Location: admin/admin_dashboard.php');
                } elseif ($user['role'] === 'MIS') {
                    header('Location: mis/mis_dashboard.php');
                }elseif ($user['role'] === 'MIS(Main)') {
                    header('Location: MISmain\mis_dashboard.php');
                }else{
                    header('Location: faculty/faculty_dashboard.php');
                }
                
                exit();
            } else {
                $error_message = 'Invalid Register ID or Password.';
            }
        } else {
            $error_message = 'Invalid Register ID or Password.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ITDS Equipment Management</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
        }

        .main-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Left Section */
        .login-left {
            flex: 1;
            background-color: #082753ff;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
        }
        .login-left img {
            max-width: 220px;
            margin-bottom: 15px;
            filter: drop-shadow(0px 3px 8px rgba(0,0,0,0.5));
        }
        .login-left h2, 
        .login-left h3, 
        .login-left p {
            margin: 5px 0;
            text-shadow: 1px 1px 6px rgba(0,0,0,0.5);
        }

        /* Right Section */
        .login-right {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #082753ff;
            padding: 20px;
        }
        .login-form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        /* General Button Style */
        .btn-animated {
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            transition: all 0.3s ease, transform 0.2s ease;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }

        /* Hover animation */
        .btn-animated:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0px 6px 15px rgba(0,0,0,0.3);
        }

        /* Click (press) effect */
        .btn-animated:active {
            transform: scale(0.95);
        }

        /* Shine animation */
        .btn-animated::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(25deg);
            transition: opacity 0.3s ease;
            opacity: 0;
        }
        .btn-animated:hover::after {
            opacity: 1;
            animation: shine 0.8s forwards;
        }

        /* Shine Keyframes */
        @keyframes shine {
            from { transform: translateX(-100%) rotate(25deg); }
            to { transform: translateX(100%) rotate(25deg); }
        }

        /* Specific Colors */
        .btn-blue {
            background-color: #0d6efd;
            color: #fff;
        }
        .btn-blue:hover {
            background-color: #0b5ed7;
        }

        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }
        .btn-danger:hover {
            background-color: #bb2d3b;
        }



        /* 📱 Mobile: stack layout (scroll to see right section) */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                overflow-y: auto;
            }
            .login-left, .login-right {
                height: 100vh; /* each section full screen */
            }
        }
    </style>
</head>
<body>
<div class="main-container">
    <!-- LEFT SIDE -->
    <div class="login-left">
        <img src="logo.png" alt="Logo">
        <h2>BULACAN STATE UNIVERSITY</h2>
        <h3>SARMIENTO CAMPUS</h3>
        <p>ITDS Department Equipment Management System</p>
    </div>
    
    <!-- RIGHT SIDE -->
    <div class="login-right">
        <div class="login-form-container">
            <div class="text-center mb-4">
                <h4>LOGIN</h4>
                <p class="text-muted">Access your account</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="employee_id" class="form-label">Employee ID</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <span class="input-group-text" style="cursor:pointer;" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn btn-danger btn-animated w-100 mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i> LOGIN
                </button> 
                <div class="text-center">
                    <button type="button" class="btn btn-blue btn-animated" onclick="window.location.href='qr_code.php'">
                        <i class="fas fa-qrcode me-2"></i> QR CODE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById("togglePassword").addEventListener("click", function() {
    const passwordInput = document.getElementById("password");
    const icon = this.querySelector("i");
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        passwordInput.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
});
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>
