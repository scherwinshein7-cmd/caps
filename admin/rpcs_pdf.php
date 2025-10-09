<?php
require('../fpdf/fpdf.php');
include '../includes/db_connection.php';

// Get params
$serials = $_GET['serials'] ?? '';
$description = $_GET['description'] ?? '';
$unit_value = $_GET['unit_value'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$custody = $_GET['custody'] ?? '';
$date_today = date("m/d/Y"); 

// Lookup category
$category_name = "";
if ($category_id) {
    $cat = mysqli_query($conn, "SELECT category FROM categories WHERE id='$category_id'");
    $category_name = mysqli_fetch_assoc($cat)['category'] ?? '';
}

// Lookup custody
$custody_name = "";
if ($custody) {
    $acc = mysqli_query($conn, "SELECT CONCAT(firstname,' ',middlename,' ',lastname) AS name 
                                FROM account WHERE employee_id='$custody'");
    $custody_name = mysqli_fetch_assoc($acc)['name'] ?? '';
}

// Create PDF
$pdf = new FPDF('L', 'mm', 'Legal');
$pdf->AddPage();

// ================================
// HEADER TITLE
// ================================
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'REPORT ON THE PHYSICAL COUNT OF SEMI-EXPANDABLE PROPERTY (RPCSP)',0,1,'C');
$pdf->Ln(4);

$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,"______________________________________________",0,1,'C');
$pdf->Cell(0,8,"(Type of Semi-expandable Property)",0,1,'C');
$pdf->Ln(2);

$pdf->Cell(0,8,"As at ____________________________",0,1,'C');
$pdf->Ln(4);

$pdf->Cell(0,8,"Fund Cluster: ___________________________",0,1);
$pdf->SetFont('Arial','',12);
$pdf->Cell(20,8,"For which: ",0,0);
$pdf->SetFont('Arial','U',12);
$pdf->Cell(113,8,"$custody_name, Faculty Member, BULSU-Sarmiento Campus",0,0);

$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,"is accountable, having assumed such accountability on _______________.",0,1);
$pdf->Ln(6);

// ================================
// COLUMN WIDTHS
// ================================
$pdf->SetFont('Arial','B',9);
$widths = [30, 90, 25, 30, 25, 25, 25,25, 30, 25]; 
$height = 8;

// ================================
// HELPER FUNCTION (wrapped text)
// ================================
function FixedCell($pdf, $w, $h, $text, $border=1, $align='C') {
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->Rect($x, $y, $w, $h);
    $pdf->SetXY($x, $y);
    $pdf->MultiCell($w, 4, $text, 0, $align);
    $pdf->SetXY($x + $w, $y);
}

// ================================
// HEADER ROW 1 (height ×3)
// ================================
$pdf->SetX(15);
FixedCell($pdf, $widths[0], $height*2, "Article", 1, 'C');
FixedCell($pdf, $widths[1], $height*2, "Description", 1, 'C');
FixedCell($pdf, $widths[2], $height*2, "Date of Acquired", 1, 'C');
FixedCell($pdf, $widths[3], $height*2, "Semi-Expandable Property No.", 1, 'C');
FixedCell($pdf, $widths[4], $height*2, "Unit of Measure", 1, 'C');
FixedCell($pdf, $widths[5], $height*2, "Unit Value", 1, 'C');
FixedCell($pdf, $widths[6], $height*2, "Balance Per Card", 1, 'C');
FixedCell($pdf, $widths[7], $height*2, "On Hand Per Count", 1, 'C');
FixedCell($pdf, $widths[8], $height*2, "Shortage / Overage", 1, 'C');
FixedCell($pdf, $widths[9], $height*2, "Remarks", 1, 'C');
$pdf->Ln();
$pdf->Ln();
$pdf->Ln(); 
$pdf->Ln(); 

$pdf->SetX(15);


FixedCell($pdf, $widths[0] + $widths[1], $height*.5, "Below 50,000 - HIGH VALUE ITEMS", 1, 'S');

FixedCell($pdf, $widths[2], $height*.5, "Date", 1, 'C');
FixedCell($pdf, $widths[3], $height*.5, "Property No.", 1, 'C');
FixedCell($pdf, $widths[4], $height*.5, "Unit", 1, 'C');
FixedCell($pdf, $widths[5], $height*.5, "Value", 1, 'C');
FixedCell($pdf, $widths[6], $height*.5, "Quantity", 1, 'C');
FixedCell($pdf, $widths[7], $height*.5, "Quantity", 1, 'C');
$half_w = $widths[8] / 2;
FixedCell($pdf, $half_w, $height*.5, "Quantit", 1, 'C');
FixedCell($pdf, $half_w, $height*.5, "Quantit", 1, 'C');
FixedCell($pdf, $widths[9], $height*.5, "Quantity", 1, 'C');


$pdf->Ln();






$pdf->SetX(15);
FixedCell($pdf, $widths[0], $height*11, "Semi-Expandable\n$category_name   ", 1, 'C');

// Convert CSV serials into multi-line string
$serial_list = array_filter(array_map('trim', explode(',', $serials)));
$serials_text = implode("\n", $serial_list);
$total_serials = count($serial_list);
FixedCell($pdf, $widths[1], $height*11, "$description\n$serials_text", 1, 'L');

FixedCell($pdf, $widths[2], $height*11, "$date_today", 1, 'C');
FixedCell($pdf, $widths[3], $height*11, "", 1, 'C');
FixedCell($pdf, $widths[4], $height*11, "", 1, 'C');
FixedCell($pdf, $widths[5], $height*11, "PHP $unit_value", 1, 'C');
FixedCell($pdf, $widths[6], $height*11, "$total_serials", 1, 'C');
FixedCell($pdf, $widths[7], $height*11, "$total_serials", 1, 'C');
// ---- SPLIT COLUMN 8 into 2 small cells ----
$half_w = $widths[8] / 2;
FixedCell($pdf, $half_w, $height*11, "", 1, 'C');
FixedCell($pdf, $half_w, $height*11, "", 1, 'C');

FixedCell($pdf, $widths[9], $height*11, "Functional", 1, 'C');
$pdf->Ln();

$pdf->Output("I", $custody_name . "(" . $category_name . "_" . $date_today .") RPSCP".".pdf");
