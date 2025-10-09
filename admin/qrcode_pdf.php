<?php
session_start();
require('../fpdf/fpdf.php');
include '../includes/db_connection.php';

// ================================
// ✅ Get serials from GET or SESSION
// ================================
$serials = $_GET['serials'] ?? '';
if (empty($serials) && isset($_SESSION['last_serials'])) {
    $serials = implode(",", $_SESSION['last_serials']);
}
if (empty($serials)) {
    die("⚠ No QR codes found. Please add equipment first.");
}

// ✅ Convert CSV to array
$serialArray = array_filter(array_map('trim', explode(',', $serials)));

$qrDir = "../admin/qrcode/";

// ================================
// ✅ Create PDF
// ================================
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,'Equipment QR Codes',0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Arial','',10);

// Layout settings
$cols = 3;          // number of QR codes per row
$colWidth = 65;     // horizontal spacing
$rowHeight = 65;    // vertical spacing
$x = 10;
$y = 30;
$i = 0;

foreach ($serialArray as $s) {
    $qrFile = $qrDir . $s . ".png";

    if (file_exists($qrFile)) {
        // ✅ Add QR Image
        $pdf->Image($qrFile, $x, $y, 40, 40);

        // ✅ Serial number below QR
        $pdf->SetXY($x, $y + 42);
        $pdf->MultiCell(40, 5, $s, 0, 'C');
    }

    // Move position
    $i++;
    $x += $colWidth;
    if ($i % $cols == 0) {
        $x = 10;
        $y += $rowHeight;
    }
}

// ================================
// ✅ Output inline to browser (auto new tab)
// ================================
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="equipment_qrcodes.pdf"');
$pdf->Output("I", "equipment_qrcodes.pdf");
exit;
