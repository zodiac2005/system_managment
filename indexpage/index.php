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
$query = "SELECT f.id_facture_details, c.name, f.date_facture, f.quantity,f.prix
          FROM facture_details f 
          JOIN client c ON c.name = f.name_client 
          ORDER BY f.date_facture DESC ";
$result = mysqli_query($conn, $query);


?>
<section class="invoice-slider-section">
    <h2 class="section-title">Recent Invoices</h2>
    <div class="slider-container">
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <div class="invoice-card">
                <div class="card-header">
                    <span>Id of Facture: <?php echo $row['id_facture_details']; ?></span>
                    <span class="status-badge">Paid</span>
                </div>
                <h3>Name Client: <?php echo $row['name']; ?></h3>
                <p class="date">Date of Facture: <?php echo $row['date_facture']; ?></p>
                <div class="amount">Total: <?php echo number_format($row['quantity'] * $row['prix']); ?> Dhs</div>
            </div>
        <?php endwhile; ?>
    </div>
</section>


   

   <?php include 'footer.php'; ?>


</body>
</html>