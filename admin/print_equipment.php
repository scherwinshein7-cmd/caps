<?php
require('../fpdf/fpdf.php'); // or dompdf if you prefer
include '../includes/db_connection.php';

$serial = $_GET['serial'] ?? '';
if ($serial == '') die("Invalid serial");

$result = $conn->query("SELECT e.*, c.category, 
                        CONCAT(a.firstname,' ',a.middlename,' ',a.lastname) AS custody_name
                        FROM equipment e
                        LEFT JOIN categories c ON e.category_id = c.id
                        LEFT JOIN account a ON e.custody = a.register_id
                        WHERE e.serial = '$serial'");
$data = $result->fetch_assoc();

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,"Equipment Report",0,1,'C');
$pdf->Ln(5);

foreach ($data as $k=>$v) {
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(50,10,ucfirst($k).":",1);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(130,10,$v,1);
    $pdf->Ln();
}

$pdf->Output("D","equipment_$serial.pdf");
