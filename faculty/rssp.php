<?php
require('../fpdf/fpdf.php');
include '../includes/db_connection.php';

// ✅ Get parameters
$requested_by = $_GET['requested_by'] ?? '';
$date_requested = $_GET['date_requested'] ?? '';
$date_today = date("F d, Y");

// ✅ Fetch Accepted or Condemned Equipment
$query = "
    SELECT e.serial, e.description, e.remarks, CONCAT(a.firstname,' ',a.lastname) AS end_user
    FROM condemn_request cr
    JOIN equipment e ON cr.serial = e.serial
    LEFT JOIN account a ON e.custody = a.employee_id
    WHERE (cr.request_condemn = 0 OR e.remarks = 'Condemned')
      AND cr.requested_by = '$requested_by'
      AND cr.date_requested = '$date_requested'
    ORDER BY e.description ASC
";
$result = mysqli_query($conn, $query);

// ✅ Group by description
$equipment_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $desc = $row['description'];
    if (!isset($equipment_data[$desc])) {
        $equipment_data[$desc] = [
            'quantity' => 0,
            'serials' => [],
            'remarks' => [],
            'end_user' => $row['end_user']
        ];
    }
    $equipment_data[$desc]['quantity']++;
    $equipment_data[$desc]['serials'][] = $row['serial'];
    $equipment_data[$desc]['remarks'][] = $row['remarks'];
}

// ✅ Create PDF (Legal Size)
$pdf = new FPDF('P', 'mm', 'Legal');
$pdf->AddPage();

// ===============================
// FIXED CELL FUNCTION
// ===============================
function FixedCell($pdf, $w, $h, $text, $border=1, $align='C') {
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->Rect($x, $y, $w, $h);
    $pdf->SetXY($x + 1, $y + 2); // padding
    $pdf->MultiCell($w - 2, 5, $text, 0, $align);
    $pdf->SetXY($x + $w, $y);
}

// ===============================
// HEADER BOX (RSSP format)
// ===============================
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'RECEIPT OF RETURNED SEMI-EXPANDABLE PROPERTY (RSSP)',1,1,'C');

$pdf->SetFont('Arial','',11);
$leftWidth = 120;
$rightWidth = 76;
$cellHeight = 8;

$x = $pdf->GetX();
$y = $pdf->GetY();

$pdf->MultiCell($leftWidth, $cellHeight * 2, "Entity Name: BULACAN STATE UNIVERSITY", 1, 'L');
$pdf->SetXY($x + $leftWidth, $y);
$pdf->Cell($rightWidth, $cellHeight, 'Date: ' . $date_today, 1, 2, 'L');
$pdf->Cell($rightWidth, $cellHeight, 'RSSP No.: ____________________', 1, 2, 'L');
$pdf->Ln(0);

$pdf->SetFont('Arial','',11);
$pdf->Cell(0,8,'This is to acknowledge receipt of the returned semi-expandable Property',1,1,'C');
$pdf->Ln(0);

// ===============================
// TABLE HEADER
// ===============================
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(230,230,230);
$widths = [76, 30, 30, 30, 30]; 
$height = 8;

FixedCell($pdf, $widths[0], $height, "Item Description", 1, 'C');
FixedCell($pdf, $widths[1], $height, "ICS No.", 1, 'C');
FixedCell($pdf, $widths[2], $height, "Quantity", 1, 'C');
FixedCell($pdf, $widths[3], $height, "End User", 1, 'C');
FixedCell($pdf, $widths[4], $height, "Remarks", 1, 'C');
$pdf->Ln(8);

// ===============================
// TABLE BODY
// ===============================
$pdf->SetFont('Arial','',10);

if (empty($equipment_data)) {
    $pdf->Cell(0, 10, 'No condemned or accepted equipment found for this request.', 1, 1, 'C');
} else {
    foreach ($equipment_data as $desc => $info) {
        $serials_text = implode("\n", $info['serials']);
        $remarks_text = implode(", ", array_unique($info['remarks']));
        $end_user = $info['end_user'];
        $qty = $info['quantity'];

        // Measure cell heights
        $line_heights = [];
        $xStart = $pdf->GetX();
        $yStart = $pdf->GetY();

        $pdf->SetXY($xStart, $yStart);
        $pdf->MultiCell($widths[0], 5, $desc, 0, 'L');
        $line_heights[] = $pdf->GetY() - $yStart;

        $pdf->SetXY($xStart + $widths[0], $yStart);
        $pdf->MultiCell($widths[1], 5, $serials_text, 0, 'L');
        $line_heights[] = $pdf->GetY() - $yStart;

        $pdf->SetXY($xStart + $widths[0] + $widths[1], $yStart);
        $pdf->MultiCell($widths[2], 5, $qty, 0, 'C');
        $line_heights[] = $pdf->GetY() - $yStart;

        $pdf->SetXY($xStart + $widths[0] + $widths[1] + $widths[2], $yStart);
        $pdf->MultiCell($widths[3], 5, $end_user, 0, 'C');
        $line_heights[] = $pdf->GetY() - $yStart;

        $pdf->SetXY($xStart + $widths[0] + $widths[1] + $widths[2] + $widths[3], $yStart);
        $pdf->MultiCell($widths[4], 5, $remarks_text, 0, 'C');
        $line_heights[] = $pdf->GetY() - $yStart;

        $rowHeight = max($line_heights);
        if ($rowHeight < 8) $rowHeight = 8;

        // Draw empty bordered cells (no visible content)
        $pdf->SetXY($xStart, $yStart);
        FixedCell($pdf, $widths[0], $rowHeight, '', 1, 'L');
        FixedCell($pdf, $widths[1], $rowHeight, '', 1, 'L');
        FixedCell($pdf, $widths[2], $rowHeight, '', 1, 'C');
        FixedCell($pdf, $widths[3], $rowHeight, '', 1, 'C');
        FixedCell($pdf, $widths[4], $rowHeight, '', 1, 'C');


        $pdf->Ln($rowHeight);
    }
}

// ===============================
// SIGNATURE AREA
// ===============================
$pdf->Ln(30);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(95,8,'Returned By:',0,0,'L');
$pdf->Cell(95,8,'Received By:',0,1,'L');
$pdf->Ln(5);

$pdf->SetFont('Arial','',11);
$pdf->Cell(95,6,'__________________________',0,0,'L');
$pdf->Cell(95,6,'__________________________',0,1,'L');

$pdf->SetFont('Arial','I',10);
$pdf->Cell(95,6,'End-User (Signature over printed name)',0,0,'L');
$pdf->Cell(95,6,'Head, Asset Management Unit',0,1,'L');

$pdf->Ln(10);
$pdf->SetFont('Arial','',11);
$pdf->Cell(95,6,'__________________________',0,0,'L');
$pdf->Cell(95,6,'__________________________',0,1,'L');
$pdf->Cell(95,6,'Date',0,0,'L');
$pdf->Cell(95,6,'Date',0,1,'L');

// ✅ Inline Output (Manual Save)
$pdf->Output('I', "RSSP_{$requested_by}_{$date_requested}.pdf");
?>
