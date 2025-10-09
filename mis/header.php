<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ITDS Equipment Monitoring</title>
<link rel="icon" type="image/png" href="logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* ===== General ===== */
body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f9fafb;
    margin: 0;
    display: flex;
}

/* ===== Sidebar ===== */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    width: 70px; /* collapsed */
    background-color: #1e3a8a;
    color: white;
    transition: width 0.3s ease;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 10px;
    overflow-x: hidden;
}

.sidebar.expanded {
    width: 260px;
    align-items: flex-start;
    padding-left: 10px;
}

/* ===== Toggle Button (Top) ===== */
.toggle-btn {
    background-color: transparent;
    border: none;
    color: #fff;
    font-size: 1.6rem;
    cursor: pointer;
    width: 100%;
    text-align: center;
    margin-bottom: 10px;
}

.sidebar.expanded .toggle-btn {
    text-align: right;
    padding-right: 15px;
}

/* ===== Logo ===== */
.logo {
    width: 100%;
    display: flex;
    justify-content: center;
    padding: 10px 0;
}

.logo img {
    height: 55px;
    border-radius: 8px;
    transition: transform 0.3s ease;
}

.logo img:hover {
    transform: scale(1.05);
}

/* ===== Title ===== */
.brand {
    width: 100%;
    text-align: center;
    margin-bottom: 15px;
    line-height: 1.3;
    display: none;
}

.sidebar.expanded .brand {
    display: block;
}

.brand span {
    font-size: 1rem;
    font-weight: 600;
    color: #fff;
    display: block;
}

/* ===== Links ===== */
.sidebar a {
    display: flex;
    align-items: center;
    color: #cbd5e1;
    text-decoration: none;
    font-weight: 500;
    padding: 10px 15px;
    width: 100%;
    transition: background 0.3s ease;
    border-radius: 8px;
    margin: 3px 0;
}

.sidebar a:hover {
    background-color: #3b82f6;
    color: #fff;
}

.sidebar a i {
    font-size: 1.2rem;
    margin-right: 10px;
}

.sidebar a span {
    display: none;
}

.sidebar.expanded a span {
    display: inline;
}

/* ===== Main Content ===== */
.main-content {
    flex-grow: 1;
    padding: 25px;
    margin-left: 70px;
    transition: margin-left 0.3s ease;
}

@media (max-width: 768px) {
    .sidebar {
        left: -70px;
    }
    .sidebar.expanded {
        left: 0;
        width: 260px;
    }
    .main-content {
        margin-left: 0;
    }
}
</style>
</head>

<body>
<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebarMenu">

    <!-- Collapse Button -->
    <button class="toggle-btn" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Logo -->
    <div class="logo">
        <img src="logo.png" alt="Logo">
    </div>

    <!-- Title -->
    <div class="brand">
        <span>ITDS Equipment Management</span>
        <span>with QR Code</span>
    </div>

    <!-- Navigation Links -->
    <a href="<?php echo ($_SESSION['role'] === 'Admin') ? 'admin_dashboard.php' : 'faculty_dashboard.php'; ?>">
        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
    </a>
    <a href="mis_dashboard.php"><i class="bi bi-hdd-stack"></i> <span>Equipment</span></a>
    <a href="equipment_checking.php"><i class="bi bi-arrow-left-right"></i> <span>Transfer</span></a>
    <a href="condemn.php"><i class="bi bi-recycle"></i> <span>Return</span></a>
    <a href="account.php"><i class="bi bi-person-circle"></i> <span>Account</span></a>
    <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a>


</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">
    

<!-- ===== JS ===== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebarMenu');
const toggleBtn = document.getElementById('sidebarToggle');

// Default collapsed
sidebar.classList.remove('expanded');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('expanded');
});
</script>
</body>
</html>
