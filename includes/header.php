<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<style>
    /* Header Styles */
.admin-header {
    background-color: #1e3a8a;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.admin-header .navbar-brand {
    font-weight: 600;
    font-size: 1.2rem;
}

.admin-header .navbar-brand img {
    border-radius: 8px;
}

.admin-header .nav-link {
    color: #ffffff !important;
    font-weight: 500;
    margin: 0 10px;
    transition: color 0.3s ease;
}

.admin-header .nav-link:hover {
    color: #cbd5e1 !important;
}

.admin-header .btn-light {
    background-color: #60a5fa;
    border-color: #60a5fa;
    color: #ffffff;
    font-weight: 600;
    border-radius: 20px;
    padding: 8px 20px;
}

.admin-header .btn-light:hover {
    background-color: #3b82f6;
    border-color: #3b82f6;
}
</style>
<header class="admin-header">
    <link rel="icon" type="image/png" href="/logo.png">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="logo.png" style="height:40px;">
                <span>ITDS Equipment Monitoring</span>
            </a>
            
            <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
                <a class="nav-link" href="<?php echo ($_SESSION['role'] === 'admin') ? 'admin_dashboard.php' : 'faculty_dashboard.php'; ?>">Dashboard</a>
                <a class="nav-link" href="#">Equipment</a>
                <a class="nav-link" href="#">Transfer</a>
                <a class="nav-link" href="#">Return</a>
                <a class="nav-link" href="#">Account</a>
                <a class="btn btn-light btn-sm ms-3" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
</header>
