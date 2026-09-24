<?php
// views/ticket_pdf.php
session_start();
if (!isset($_SESSION['id_usuario'])) { exit('Access denied'); }

require_once '../config/database.php';
require_once '../fpdf/fpdf.php'; // Make sure this path points to where you saved FPDF

$id_pedido = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_pedido === 0) {
    die("Error: No order was specified.");
}

// 1. Get the order's general data
$stmt = $pdo->prepare("
    SELECT p.id_pedido, p.fecha_pedido, p.total, u.nombre_usuario, u.rol 
    FROM pedidos p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    WHERE p.id_pedido = ?
");
$stmt->execute([$id_pedido]);
$pedido = $stmt->fetch();

if (!$pedido) {
    die("The order does not exist.");
}

// 2. Get the detail of the products sold (FIXED TO PLURAL: detalles_pedidos)
$stmt_detalle = $pdo->prepare("
    SELECT d.cantidad, d.precio_unitario, d.subtotal, prod.nombre_producto 
    FROM detalles_pedidos d
    JOIN productos prod ON d.id_producto = prod.id_producto
    WHERE d.id_pedido = ?
");
$stmt_detalle->execute([$id_pedido]);
$detalles = $stmt_detalle->fetchAll();

// 3. Start the PDF (80mm wide x 200mm tall ticket format)
$pdf = new FPDF('P', 'mm', array(80, 200));
$pdf->AddPage();
$pdf->SetMargins(5, 5, 5); // Small margins for the ticket

// --- HEADER ---
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(70, 8, utf8_decode('PUPUSERIA DONA MARY'), 0, 1, 'C');

$pdf->SetFont('Arial', '', 9);
$pdf->Cell(70, 5, utf8_decode('El Salvador'), 0, 1, 'C');
$pdf->Cell(70, 5, 'Ticket #: ' . $pedido['id_pedido'], 0, 1, 'C');
$pdf->Cell(70, 5, 'Date: ' . date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])), 0, 1, 'C');
$pdf->Cell(70, 5, 'Served by: ' . utf8_decode($pedido['nombre_usuario']) . ' (' . utf8_decode($pedido['rol']) . ')', 0, 1, 'C');
$pdf->Ln(3);

// --- DIVIDER LINE ---
$pdf->Cell(70, 2, '---------------------------------------------------------', 0, 1, 'C');

// --- PRODUCTS HEADER ---
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(10, 5, 'Qty', 0, 0, 'L');
$pdf->Cell(40, 5, 'Product', 0, 0, 'L');
$pdf->Cell(20, 5, 'Total', 0, 1, 'R');
$pdf->SetFont('Arial', '', 9);

// --- PRODUCT LIST ---
foreach ($detalles as $item) {
    $pdf->Cell(10, 5, $item['cantidad'], 0, 0, 'L');
    
    // Trim the name if it's too long so it doesn't distort the ticket
    $nombre = substr(utf8_decode($item['nombre_producto']), 0, 18);
    $pdf->Cell(40, 5, $nombre, 0, 0, 'L');
    
    $pdf->Cell(20, 5, '$' . number_format($item['subtotal'], 2), 0, 1, 'R');
}

// --- DIVIDER LINE ---
$pdf->Cell(70, 2, '---------------------------------------------------------', 0, 1, 'C');

// --- TOTAL ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 8, 'TOTAL DUE:', 0, 0, 'R');
$pdf->Cell(20, 8, '$' . number_format($pedido['total'], 2), 0, 1, 'R');

$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(70, 5, 'Thank you for your purchase!', 0, 1, 'C');

// 4. Generate and display the PDF in the browser
// 'I' = Displays in the browser. Change to 'D' if you want it to download automatically.
$pdf->Output('I', 'Ticket_' . $pedido['id_pedido'] . '.pdf');
?>
