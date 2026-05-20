<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="indexpage.css">
</head>
<body>
   <?php include 'header.php'; ?>
   <?php
// Database connection assumes $conn exists
$host = "localhost";
$username = "root";
$password = "";
$database = "mydb";

$conn = mysqli_connect($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$query = "SELECT f.id_facture_details, f.client_id, c.name, f.date_facture, f.quantity, f.prix, f.product_id, p.type_product 
          FROM facture_details f 
          JOIN client c ON c.id_client = f.client_id
          JOIN product p ON p.id_product = f.product_id
          ORDER BY f.date_facture DESC";
$result = mysqli_query($conn, $query);
$total_invoices = mysqli_num_rows($result);
?>


<section class="all-invoices-section" style="padding: 20px; font-family: sans-serif;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #333;">All Invoices Management</h2>
        <span style="background: #e1e8ed; padding: 5px 12px; border-radius: 20px; font-size: 14px; font-weight: bold;">
            Total: <?php echo $total_invoices; ?> Invoices
        </span>
    </div>

    <?php if($total_invoices > 0): ?>
        <div style="overflow-x: auto; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background-color: #2c3e50; color: #ffffff;">
                        <th style="padding: 12px 15px;">Invoice ID</th>
                        <th style="padding: 12px 15px;">Client Name</th>
                        <th style="padding: 12px 15px;">Date</th>
                        <th style="padding: 12px 15px;">Product</th>
                        <th style="padding: 12px 15px;">Qty</th>
                        <th style="padding: 12px 15px;">Unit Price</th>
                        <th style="padding: 12px 15px;">Total Amount</th>
                        <th style="padding: 12px 15px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $row_bg = false; // Used to alternate row colors for readability
                    while($row = mysqli_fetch_assoc($result)): 
                        $total_price = $row['quantity'] * $row['prix'];
                        $row_style = $row_bg ? 'background-color: #f8f9fa;' : 'background-color: #ffffff;';
                        $row_bg = !$row_bg;
                    ?>
                        <tr style="<?php echo $row_style; ?> border-bottom: 1px solid #dddddd;">
                            <td style="padding: 12px 15px; font-weight: bold; color: #3498db;">
                                #<?php echo $row['id_facture_details']; ?>
                            </td>
                            <td style="padding: 12px 15px;"> Id Client: <?php echo $row['client_id']; ?> -  <?php echo $row['name']; ?></td>
                            <td style="padding: 12px 15px;"><?php echo date('Y-m-d', strtotime($row['date_facture'])); ?></td>
                            <td style="padding: 12px 15px;">
                                <span style="font-size: 12px; color: #7f8c8d; display: block;">ID: #<?php echo $row['product_id']; ?></span>
                                <strong><?php echo $row['type_product']; ?></strong>
                            </td>
                            <td style="padding: 12px 15px;"><?php echo $row['quantity']; ?></td>
                            <td style="padding: 12px 15px;"><?php echo number_format($row['prix']); ?> Dhs</td>
                            <td style="padding: 12px 15px; font-weight: bold; color: #2ecc71;">
                                <?php echo number_format($total_price); ?> Dhs
                            </td>
                            <td style="padding: 12px 15px;">
                                <span style="background: #2ecc71; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Paid</span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <!-- Safe fallback case if database table is entirely empty -->
        <div style="text-align: center; padding: 40px; background: #f9f9f9; border: 1px dashed #ccc; border-radius: 8px;">
            <p style="color: #7f8c8d; font-size: 16px; margin: 0;">No invoices found in the system database.</p>
        </div>
    <?php endif; ?>
</section>




<!-- <section class="invoice-slider-section">
    <h2 class="section-title">Recent Invoices</h2>
    <div class="slider-container">
        <!-- <?php while($row = mysqli_fetch_assoc($result)): ?>
            <div class="invoice-card">
                <div class="card-header">
                    <span>Id of Facture: <?php echo $row['id_facture_details']; ?></span>
                    <span class="status-badge">Paid</span>
                </div>
                <h3>Name Client: <?php echo $row['name']; ?></h3>
                <p class="date">Date of Facture: <?php echo $row['date_facture']; ?></p>
                
                <!-- Displaying both the ID and the Type side-by-side -->
                <!-- <p class="product">
                    <!-- <strong>Product:</strong> #<?php echo $row['product_id']; ?> (<?php echo $row['type_product']; ?>) -->
                <!-- </p>
                
                <div class="amount">Total: <?php echo number_format($row['quantity'] * $row['prix']); ?> Dhs</div>
            </div>
        <?php endwhile; ?> -->
    </div>
</section> 


   

   <?php include 'footer.php'; ?>


</body>
</html>