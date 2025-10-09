<?php
session_start();
include '../includes/db_connection.php';

// ✅ Only MIS access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'MIS(Main)') {
    header('Location: login.php');
    exit();
}

// ✅ Handle remarks update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['serial'], $_POST['remarks'])) {
    $serial = trim($_POST['serial']);
    $remarks = trim($_POST['remarks']);

    $stmt = $conn->prepare("UPDATE equipment SET remarks = ? WHERE serial = ?");
    $stmt->bind_param("ss", $remarks, $serial);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Remarks updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update remarks.";
    }
    $stmt->close();
    header("Location: mis_dashboard.php?serial=" . urlencode($serial));
    exit();
}

// ✅ Handle search if serial is provided
$equipmentDetails = '';
if (isset($_GET['serial'])) {
    $serial = trim($_GET['serial']);
    $stmt = $conn->prepare("
        SELECT e.*, 
            CONCAT_WS(' ', a.firstname, a.middlename, a.lastname) AS full_name,
            c.category AS category_name
        FROM equipment e
        LEFT JOIN account a ON e.custody = a.employee_id
        LEFT JOIN categories c ON e.category_id = c.id
        WHERE e.serial = ?
    ");
    $stmt->bind_param("s", $serial);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $equipmentDetails = "
        <form method='POST' class='equipment-card'>
            <h3 class='equipment-title'>Equipment Details</h3>
            <div class='detail-row'><span class='label'>Serial:</span> <span class='value'>" . htmlspecialchars($row['serial']) . "</span></div>
            <div class='detail-row'><span class='label'>Description:</span> <span class='value'>" . htmlspecialchars($row['description']) . "</span></div>
            <div class='detail-row'><span class='label'>Unit Value:</span> <span class='value'>" . htmlspecialchars($row['unit_value']) . "</span></div>
            <div class='detail-row'><span class='label'>Category:</span> <span class='value'>" . htmlspecialchars($row['category_name'] ?? 'N/A') . "</span></div>
            <div class='detail-row'><span class='label'>Room:</span> <span class='value'>" . htmlspecialchars($row['room']) . "</span></div>
            <div class='detail-row'><span class='label'>Custody:</span> <span class='value'>" . htmlspecialchars($row['full_name'] ?? $row['custody']) . "</span></div>
            <div class='detail-row'><span class='label'>Date of Issuance:</span> <span class='value'>" . htmlspecialchars($row['date_of_issuance']) . "</span></div>
            <div class='detail-row'><span class='label'>Current Remarks:</span> <span class='value'>" . htmlspecialchars($row['remarks'] ?: 'None') . "</span></div>

            <div class='detail-row'>
                <label class='label'>Update Remarks:</label>
                <select name='remarks' class='form-select' required>
                    <option value=''>-- Select --</option>
                    <option value='Functional' " . ($row['remarks'] == 'Functional' ? 'selected' : '') . ">Functional</option>
                    <option value='Rejected' " . ($row['remarks'] == 'Rejected' ? 'selected' : '') . ">Rejected</option>
                    <option value='Nonfunctional' " . ($row['remarks'] == 'Nonfunctional' ? 'selected' : '') . ">Nonfunctional</option>
                </select>
            </div>

            <input type='hidden' name='serial' value='" . htmlspecialchars($row['serial']) . "'>

            <div class='actions'>
                <button type='submit' class='btn btn-primary'><i class='fas fa-save'></i> Update Remarks</button>
            </div>

            " . (!empty($row['qrcode']) ? "<div class='qr-container'><img src='" . htmlspecialchars($row['qrcode']) . "' alt='QR Code' class='qr-image'></div>" : "") . "
        </form>";
    } else {
        $equipmentDetails = "<div class='error-message'>No equipment found for serial: " . htmlspecialchars($serial) . "</div>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MIS Dashboard - QR Scanner</title>
    <link rel="icon" type="image/png" href="logo.png">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            line-height: 1.6;
            color: #333;
            padding: 16px;
        }

        /* Container */
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #2196F3, #21CBF3);
            padding: 24px;
            text-align: center;
            color: white;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        /* Main Content */
        .content {
            padding: 24px;
        }

        .section {
            margin-bottom: 32px;
        }

        .section h2 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section h2 i {
            color: #2196F3;
        }

        /* QR Scanner Styles */
        #reader {
            max-width: 100%;
            margin: 0 auto;
            border: 3px solid #2196F3;
            border-radius: 16px;
            overflow: hidden;
            background: #f8f9fa;
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.15);
            transition: all 0.3s ease;
        }

        #reader:hover {
            box-shadow: 0 12px 35px rgba(33, 150, 243, 0.25);
            transform: translateY(-2px);
        }

        /* Search Box */
        .search-container {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 14px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
        }

        .search-input:focus {
            outline: none;
            border-color: #2196F3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        /* Buttons */
        .btn {
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 48px;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2196F3, #21CBF3);
            color: white;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Results Area */
        #result {
            margin-top: 24px;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            font-size: 16px;
            color: #6c757d;
        }

        /* Equipment Card */
        .equipment-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            text-align: left;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .equipment-title {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e9ecef;
        }

        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f1f3f4;
            align-items: flex-start;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: 600;
            color: #495057;
            min-width: 120px;
            flex-shrink: 0;
        }

        .value {
            color: #2c3e50;
            word-break: break-word;
        }

        .qr-container {
            text-align: center;
            margin-top: 20px;
        }

        .qr-image {
            max-width: 200px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Error Message */
        .error-message {
            background: #fff5f5;
            color: #c53030;
            padding: 16px;
            border-radius: 12px;
            border-left: 4px solid #c53030;
            text-align: center;
            font-weight: 500;
        }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e9ecef;
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #2196F3;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 8px;
            }

            .container {
                border-radius: 16px;
            }

            .header {
                padding: 20px 16px;
            }

            .header h1 {
                font-size: 24px;
            }

            .content {
                padding: 16px;
            }

            .section {
                margin-bottom: 24px;
            }

            .section h2 {
                font-size: 18px;
            }

            #reader {
                border-width: 2px;
            }

            .search-container {
                flex-direction: column;
            }

            .search-input {
                min-width: 100%;
            }

            .btn {
                width: 100%;
                padding: 16px;
                font-size: 16px;
            }

            .actions {
                flex-direction: column;
            }

            .detail-row {
                flex-direction: column;
                gap: 4px;
            }

            .label {
                min-width: auto;
                font-size: 14px;
                color: #6c757d;
            }

            .equipment-card {
                padding: 16px;
            }

            .equipment-title {
                font-size: 20px;
            }
        }

        /* Tablet Responsive Design */
        @media (min-width: 769px) and (max-width: 1024px) {
            .container {
                margin: 20px auto;
            }

            .search-container {
                max-width: 500px;
                margin: 0 auto;
            }

            #reader {
                max-width: 400px;
            }
        }

        /* Large Screen Optimizations */
        @media (min-width: 1025px) {
            .container {
                margin: 40px auto;
            }

            .header h1 {
                font-size: 32px;
            }

            .content {
                padding: 32px;
            }

            .detail-row {
                padding: 16px 0;
            }

            .label {
                min-width: 140px;
            }
        }

        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .btn {
                min-height: 56px;
                font-size: 18px;
            }

            .search-input {
                min-height: 56px;
                font-size: 18px;
            }
        }

        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            }

            .container {
                background: rgba(30, 30, 30, 0.95);
                color: #e0e0e0;
            }

            .section h2 {
                color: #f0f0f0;
            }

            .equipment-card {
                background: #2a2a2a;
                color: #e0e0e0;
            }

            .equipment-title {
                color: #f0f0f0;
                border-bottom-color: #404040;
            }

            .label {
                color: #b0b0b0;
            }

            .value {
                color: #e0e0e0;
            }

            .detail-row {
                border-bottom-color: #404040;
            }

            #result {
                background: #2a2a2a;
                color: #b0b0b0;
            }

            .search-input {
                background: #2a2a2a;
                border-color: #404040;
                color: #e0e0e0;
            }

            .search-input:focus {
                border-color: #2196F3;
            }
        }
    </style>>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-qrcode"></i> MIS Dashboard</h1>
            <p>Scan or search equipment QR to update status</p>
        </div>

        <div class="content">
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- QR Scanner Section -->
            <div class="section">
                <h2><i class="fas fa-camera"></i> Scan QR Code</h2>
                <div id="reader"></div>
            </div>

            <!-- Manual Search Section -->
            <div class="section">
                <h2><i class="fas fa-search"></i> Search by Serial</h2>
                <div class="search-container">
                    <input type="text" id="serialInput" class="search-input" placeholder="Enter Serial Number">
                    <button class="btn btn-primary" onclick="searchSerial()"><i class="fas fa-search"></i> Search</button>
                </div>
            </div>

            <!-- Equipment Result -->
            <div id="result">
                <?= !empty($equipmentDetails) ? $equipmentDetails : '<div class="text-center text-muted"><i class="fas fa-info-circle"></i> Scan or enter serial to view details.</div>'; ?>
            </div>

            <!-- Action Buttons -->
            <div class="actions">
                <a href="mis_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                <a href="equipment_checking.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Refresh</a>
            </div>
        </div>
    </div>

    <script>
        // QR SCAN SUCCESS
        function onScanSuccess(decodedText) {
            document.getElementById('result').innerHTML = '<div style="text-align:center;"><div class="loading"></div> Processing scan...</div>';
            setTimeout(() => {
                window.location.href = "?serial=" + encodeURIComponent(decodedText);
            }, 500);
        }

        let html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: { width: 250, height: 250 } }, false);
        html5QrcodeScanner.render(onScanSuccess);

        // Manual search
        function searchSerial() {
            let serial = document.getElementById('serialInput').value.trim();
            if (serial === "") return alert("Please enter a serial number");
            document.getElementById('result').innerHTML = '<div style="text-align:center;"><div class="loading"></div> Searching...</div>';
            setTimeout(() => {
                window.location.href = "?serial=" + encodeURIComponent(serial);
            }, 500);
        }
    </script>
</body>
</html>
