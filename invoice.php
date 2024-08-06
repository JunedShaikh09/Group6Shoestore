<?php
session_start();
require('./fpdf/fpdf.php');
include_once 'Database.php';

class PDF extends FPDF
{
    function Header()
    {
        $this->SetLeftMargin(10);
        $this->SetRightMargin(10);

        // Add logo
        $this->Image('./images/logo.png', 10, 6, 30);
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(80);
        // Title
        $this->Cell(30, 10, 'Invoice', 0, 0, 'C');
        // Add ShoeShack
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'ShoeShack', 0, 1, 'R');
        // Line break
        $this->Ln(20);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 12);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function TableHeader()
    {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(90, 10, 'Shoe Name', 1, 0, 'C');
        $this->Cell(20, 10, 'Size', 1, 0, 'C');
        $this->Cell(30, 10, 'Price', 1, 0, 'C');
        $this->Cell(20, 10, 'Qty', 1, 0, 'C');
        $this->Cell(30, 10, 'Subtotal', 1, 0, 'C');
        $this->Ln();
    }
    function TableRow($shoe_name, $size, $srp, $qty, $subtotal)
    {
        $this->SetFont('Arial', '', 10);

        $row_height = 10; // Set a fixed row height
        $max_lines = 3;   // Maximum number of lines for the shoe name

        // Calculate how many lines the shoe name will take
        $lines = $this->NbLines(90, $shoe_name);
        $multi_lines = min($lines, $max_lines);

        // If more than one line, adjust row height
        if ($multi_lines > 1) {
            $row_height = $row_height * $multi_lines;
        }

        $starting_y = $this->GetY();
        $current_x = $this->GetX();

        // Shoe Name
        $this->MultiCell(90, $row_height / $multi_lines, $shoe_name, 1, 'L');
        $this->SetXY($current_x + 90, $starting_y);

        // Other columns
        $this->Cell(20, $row_height, $size, 1, 0, 'C');
        $this->Cell(30, $row_height, '$' . number_format($srp, 2), 1, 0, 'C');
        $this->Cell(20, $row_height, $qty, 1, 0, 'C');
        $this->Cell(30, $row_height, '$' . number_format($subtotal, 2), 1, 1, 'C');

        // Ensure we move to the next line properly
        if ($this->GetY() < $starting_y + $row_height) {
            $this->SetY($starting_y + $row_height);
        }
    }

    function NbLines($w, $txt)
    {
        // Computes the number of lines a MultiCell of width w will take
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }

    function GetMultiCellHeight($w, $h, $txt)
    {
        // Calculate the height needed for a MultiCell with the given width and text
        $cw = $this->GetStringWidth($txt);
        $text_height = $h * ceil($cw / $w);
        return $text_height;
    }
}

// Check user details and order_id
if (!isset($_SESSION['user_details']) || !isset($_GET["order_id"])) {
    header('Location: sign-in.php');
    exit();
} else {
    // Retrieve customer_id from session
    $userDetails = json_decode($_SESSION['user_details'], true);
    $customer_id = $userDetails['customer_id'];

    // Get user Details
    $userObj = new User();
    $userDetails = $userObj->getUserDetails($customer_id);

    // Retrieve order details
    $order_id = base64_decode($_GET["order_id"]);

    $invoiceObj = new Checkout();
    $orderDetails = $invoiceObj->getOrderDetails($order_id);

    if (!empty($orderDetails)) {
        // Get Product Details
        $productObj = new ProductDetail();
        foreach ($orderDetails as $key => $value) {
            $productDetails = $productObj->getShoeBySizeId($value['shoe_id'], $value['shoe_size_id']);
            if (!empty($productDetails)) {
                $orderDetails[$key]['shoe'] = $productDetails[0];
            } else {
                $orderDetails[$key]['shoe'] = [];
            }
        }
    } else {
        echo "No order details found.";
        exit();
    }

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // User Details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(100, 10, 'Name: ' . $userDetails['customer_firstname'] . ' ' . $userDetails['customer_lastname'], 0, 1);
    $pdf->Cell(100, 10, 'Email: ' . $userDetails['customer_email'], 0, 1);
    $pdf->Cell(100, 10, 'Phone: ' . $userDetails['customer_mobile'], 0, 1);

    // Additional Details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(120, 40);  // Adjust these coordinates as needed
    $pdf->Cell(0, 10, 'Order Date: ' . date('d F Y', strtotime($orderDetails[0]['order_date'])), 0, 1, 'R');
    $pdf->SetX(120);
    $pdf->Cell(0, 10, 'Payment Type: ' . $orderDetails[0]['payment_type'], 0, 1, 'R');
    $pdf->SetX(120);
    $pdf->Cell(0, 10, 'Invoice : # ' . $orderDetails[0]['invoice_number'], 0, 1, 'R');
    $pdf->Ln(10);

    // Table Header
    $pdf->TableHeader();

    // Add each product to the table
    $totalSubtotal = 0;
    foreach ($orderDetails as $item) {
        $srp = $item['shoe']['shoe_srp'];
        $qty = $item['shoe_qty'];
        $subtotal = floatval($srp) * intval($qty);

        $pdf->TableRow(
            $item['shoe']['shoe_name'],
            $item['shoe']['shoe_size'] . ' (' . $item['shoe']['size_type'] . ')',
            $srp,
            $qty,
            $subtotal
        );

        $totalSubtotal += $subtotal;
    }

    // Summary
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(170, 10, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(20, 10, '$' . number_format($totalSubtotal, 2), 0, 1, 'R');
    $pdf->Cell(170, 10, 'Tax:', 0, 0, 'R');
    $pdf->Cell(20, 10, '$' . number_format($orderDetails[0]['shoe_tax'], 2), 0, 1, 'R');
    $pdf->Cell(170, 10, 'Total Bill:', 0, 0, 'R');
    $pdf->Cell(20, 10, '$' . number_format($orderDetails[0]['final_amt'], 2), 0, 1, 'R');

    // Save and Output PDF
    $fileName = 'invoice_' . $order_id . '.pdf';
    $pdf->Output('I', $fileName);

    header("Location: shop.php");
    exit();
}
