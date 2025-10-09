<?php
session_start();
include '../includes/db_connection.php';

// ✅ Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: equipment.php");
    exit();
}

// ✅ Validate required fields
if (empty($_POST['serials']) || empty($_POST['custody_to'])) {
    $_SESSION['error'] = "Please select equipment and faculty to transfer.";
    header("Location: equipment.php");
    exit();
}

// ✅ Logged-in faculty (the one initiating the transfer)
$custody_from = mysqli_real_escape_string($conn, $_POST['custody_from']);
$custody_to   = mysqli_real_escape_string($conn, $_POST['custody_to']);
$room_to      = mysqli_real_escape_string($conn, $_POST['room_to'] ?? '');
$stayRoom     = isset($_POST['stayRoom']); // checkbox true/false

$successCount = 0;
$failCount = 0;

// ✅ Loop over all selected equipment
foreach ($_POST['serials'] as $serial) {
    $serial = mysqli_real_escape_string($conn, $serial);

    // Get original equipment info
    $eqRes = mysqli_query($conn, "SELECT description, category_id, room AS room_from FROM equipment WHERE serial='$serial'");
    if (!$eqRes || mysqli_num_rows($eqRes) === 0) {
        $failCount++;
        continue;
    }

    $eq = mysqli_fetch_assoc($eqRes);
    $description = mysqli_real_escape_string($conn, $eq['description']);
    $category_id = mysqli_real_escape_string($conn, $eq['category_id']);
    $room_from   = mysqli_real_escape_string($conn, $eq['room_from']);

    // If user stays in the same room, use original room
    if ($stayRoom || $room_to === '' || $room_to === null) {
        $room_to = $room_from;
    }

    // ✅ Insert into transfer table (removed approved_by_admin)
    $sql = "INSERT INTO equipment_transfer 
            (serial, description, category_id, room_from, room_to, custody_from, custody_to, transfer_date, approved_by_custody)
            VALUES ('$serial', '$description', '$category_id', '$room_from', '$room_to', '$custody_from', '$custody_to', NOW(), 0)";
    
    if (mysqli_query($conn, $sql)) {
        $successCount++;
    } else {
        $failCount++;
    }
}

// ✅ Set feedback message
if ($successCount > 0) {
    $_SESSION['success'] = "$successCount transfer request(s) submitted successfully.";
}
if ($failCount > 0) {
    $_SESSION['error'] = "$failCount transfer(s) failed to process.";
}

// ✅ Redirect back
header("Location: equipment.php");
exit();
?>
