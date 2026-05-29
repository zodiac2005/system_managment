<?php
// منع أي مخرجات HTML عشوائية قبل توليد الـ PDF
ob_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "mydb_store";

$conn = mysqli_connect($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// جلب المعرف الخاص بالفاتورة من الرابط
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    die("Invalid Invoice ID.");
}

// استعلام شامل يجلب أسماء العناصر بدلاً من معرفاتها الرقمية فقط
$query = "SELECT f.*, p.type_product, c.name AS client_name, e.name AS employe_name, py.type_payment 
          FROM facture_details f
          LEFT JOIN product p ON f.product_id = p.id_product
          LEFT JOIN client c ON f.client_id = c.id_client
          LEFT JOIN employe e ON f.employe_id = e.id_employe
          LEFT JOIN payment py ON f.payment_id = py.id_payment
          WHERE f.id_facture_details = '$id'";

$result = $conn->query($query);
if (!$result || $result->num_rows === 0) {
    die("Invoice not found.");
}

$invoice = $result->fetch_assoc();

// استخدام مكتبة FPDF الشهيرة (تأكدي من تحميل ملف fpdf.php وضعه بجانب ملفاتك)
// يمكنك تحميلها مجاناً بملف واحد من موقع fpdf.org الرسمي
require('fpdf.php');

class InvoicePDF extends FPDF {
    function Header() {
        // عنوان الشركة أو المتجر
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(33, 37, 41); // Dark Gray
        $this->Cell(100, 10, 'INVENTORY PRO STORE', 0, 0, 'L');
        
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(90, 10, 'INVOICE', 0, 1, 'R');
        
        // خط تفريقي متناسق
        $this->Line(10, 25, 200, 25);
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-25);
        $this->Line(10, 270, 200, 270);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Thank you for your business!', 0, 1, 'C');
        $this->Cell(0, 5, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
}

$pdf = new InvoicePDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);

// قسم معلومات الفاتورة والعميل (موزعة على عمودين)
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(100, 6, 'Bill To:', 0, 0, 'L');
$pdf->Cell(90, 6, 'Invoice Details:', 0, 1, 'R');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(100, 6, 'Client Name: ' . $invoice['client_name'], 0, 0, 'L');
$pdf->Cell(90, 6, 'Invoice No: #' . $invoice['id_facture_details'], 0, 1, 'R');

$pdf->Cell(100, 6, 'Served By: ' . $invoice['employe_name'], 0, 0, 'L');
$pdf->Cell(90, 6, 'Date: ' . $invoice['date_facture'], 0, 1, 'R');

$pdf->Cell(100, 6, '', 0, 0, 'L');
$pdf->Cell(90, 6, 'Payment Method: ' . $invoice['type_payment'], 0, 1, 'R');

$pdf->Ln(15);

// تصميم جدول العناصر الاحترافي
$pdf->SetFillColor(52, 58, 64); // لون الهيدر غامق متناسق مع الداشبورد
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 11);

$pdf->Cell(80, 8, 'Product Description', 1, 0, 'L', true);
$pdf->Cell(35, 8, 'Qty', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Unit Price', 1, 0, 'R', true);
$pdf->Cell(40, 8, 'Total Price', 1, 1, 'R', true);

// تعبئة بيانات الجدول الرئيسي
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 11);

$unit_price = ($invoice['quantity'] > 0) ? ($invoice['prix'] / $invoice['quantity']) : 0;

$pdf->Cell(80, 10, ' ' . $invoice['type_product'], 1, 0, 'L');
$pdf->Cell(35, 10, $invoice['quantity'], 1, 0, 'C');
$pdf->Cell(35, 10, number_format($unit_price, 2) . ' DH', 1, 0, 'R');
$pdf->Cell(40, 10, number_format($invoice['prix'], 2) . ' DH', 1, 1, 'R');

$pdf->Ln(10);

// المجموع الكلي النهائي
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(115, 8, '', 0, 0);
$pdf->SetFillColor(248, 249, 250);
$pdf->Cell(35, 8, 'Total Amount:', 1, 0, 'L', true);
$pdf->SetTextColor(220, 53, 69); // باللون الأحمر الفاخر للحسابات
$pdf->Cell(40, 8, number_format($invoice['prix'], 2) . ' DH ', 1, 1, 'R', true);

// إخراج الملف للمتصفح مباشرة للقراءة أو التحميل
ob_end_clean();
$pdf->Output('I', 'Invoice_' . $invoice['id_facture_details'] . '.pdf');
?>