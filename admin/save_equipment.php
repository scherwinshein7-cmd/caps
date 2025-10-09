<?php
session_start();
include '../includes/db_connection.php';
require '../phpqrcode/qrlib.php'; // QR code library

// ✅ Validation
if (empty($_POST['serials']) || empty($_POST['description'])) {
    $_SESSION['modal_error'] = "Please fill in all required fields!";
    $_SESSION['modal_open'] = true;
    $_SESSION['old'] = $_POST;
    header("Location: equipment.php");
    exit();
}

// ✅ Assign posted data
$serials     = $_POST['serials'];
$description = $_POST['description'];
$unit_value  = $_POST['unit_value'];
$category_id = $_POST['category_id'];
$room        = $_POST['room'];
$custody     = $_POST['custody'];
$remarks     = $_POST['remarks'] ?? "functional";

// ✅ Split into array
$serialArray = array_map('trim', explode(',', $serials));
$existing    = [];

// ✅ Check duplicates
foreach ($serialArray as $s) {
    $s_clean = mysqli_real_escape_string($conn, $s);
    $check = mysqli_query($conn, "SELECT serial FROM equipment WHERE serial = '$s_clean'");
    if (mysqli_num_rows($check) > 0) {
        $existing[] = $s;
    }
}

// ✅ If any serial exists, stop and show error
if (!empty($existing)) {
    $_SESSION['modal_error'] = "The following serials already exist: " . implode(", ", $existing);
    $_SESSION['old'] = $_POST;       // Keep user input
    $_SESSION['modal_open'] = true;  // Reopen modal
    header("Location: equipment.php");
    exit();
}

// ✅ Directory for QR codes
$qrDir = "../admin/qrcode/";
if (!file_exists($qrDir)) mkdir($qrDir, 0777, true);

// ✅ Insert all serials
foreach ($serialArray as $s) {
    $s_clean       = mysqli_real_escape_string($conn, $s);
    $desc_clean    = mysqli_real_escape_string($conn, $description);
    $unit_clean    = mysqli_real_escape_string($conn, $unit_value);
    $category_clean= mysqli_real_escape_string($conn, $category_id);
    $room_clean    = mysqli_real_escape_string($conn, $room);
    $custody_clean = mysqli_real_escape_string($conn, $custody);
    $remarks_clean = mysqli_real_escape_string($conn, $remarks);

    // ✅ Generate QR code only (temporary file)
    // ✅ Generate QR code only (temporary file)
    $qrTemp = $qrDir . "tmp_" . $s_clean . ".png";
    QRcode::png($s_clean, $qrTemp, QR_ECLEVEL_L, 6);

    // ✅ Load QR into GD
    $qrImg = imagecreatefrompng($qrTemp);
    $qrWidth = imagesx($qrImg);
    $qrHeight = imagesy($qrImg);

    // ✅ Create new image (extra space for serial text below QR)
    $fontHeight = 20; // adjust spacing
    $newHeight = $qrHeight + $fontHeight + 10;
    $newImg = imagecreatetruecolor($qrWidth, $newHeight);

    // ✅ Colors
    $white = imagecolorallocate($newImg, 255, 255, 255);
    $black = imagecolorallocate($newImg, 0, 0, 0);

    // ✅ Fill background white
    imagefill($newImg, 0, 0, $white);

    // ✅ Copy QR into new image
    imagecopy($newImg, $qrImg, 0, 0, 0, 0, $qrWidth, $qrHeight);

    // ✅ Add serial number text centered below QR
    $font = __DIR__ . "/arial.ttf"; // path to TTF font in your project
    if (file_exists($font)) {
        // Use TTF font (better quality)
        $bbox = imagettfbbox(12, 0, $font, $s_clean);
        $textWidth = $bbox[2] - $bbox[0];
        $x = ($qrWidth - $textWidth) / 2;
        $y = $qrHeight + 18; 
        imagettftext($newImg, 12, 0, $x, $y, $black, $font, $s_clean);
    } else {
        // Fallback: built-in GD font
        $textWidth = imagefontwidth(5) * strlen($s_clean);
        $x = ($qrWidth - $textWidth) / 2;
        $y = $qrHeight + 5;
        imagestring($newImg, 5, $x, $y, $s_clean, $black);
    }

    // ✅ Save final QR with serial number
    $qrFile = $qrDir . $s_clean . ".png";
    imagepng($newImg, $qrFile);

    $qrPath = $s_clean . ".png";

    // ✅ Clean up
    imagedestroy($qrImg);
    imagedestroy($newImg);
    unlink($qrTemp);




    // ✅ Insert into DB
    $insert = mysqli_query($conn, "
        INSERT INTO equipment (serial, description, unit_value, category_id, room, custody, remarks, qrcode, date_of_issuance)
        VALUES ('$s_clean', '$desc_clean', '$unit_clean', '$category_clean', '$room_clean', '$custody_clean', '$remarks_clean', '$qrPath', CURDATE())
    ");

    if (!$insert) {
        $_SESSION['modal_error'] = "Error saving serial '$s': " . mysqli_error($conn);
        $_SESSION['modal_open'] = true;
        $_SESSION['old'] = $_POST;
        header("Location: equipment.php");
        exit();
    }
}

// ✅ Success message
// ✅ Success message
$_SESSION['success'] = "Equipment successfully added with QR codes.";
$_SESSION['last_serials'] = $serialArray; // keep last saved serials

// Redirect back to equipment.php
header("Location: equipment.php?open_qr=1");
exit();

?>
