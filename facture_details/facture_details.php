<?php include 'headerfd.php'; ?>
<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "mydb";

$conn = mysqli_connect($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$selected_id = '';
$selected_product = '';
$selected_client = '';
$selected_employe = '';
$selected_type_payment = '';
$quantity_val = '';
$total_val = '';
$date_facture_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_id = $_POST['id'] ?? '';
    $selected_product = $_POST['product'] ?? '';
    $selected_client = $_POST['id_clientfd'] ?? '';
    $selected_employe = $_POST['employe'] ?? '';
    $selected_type_payment = $_POST['type_payment'] ?? '';
    $quantity_val = $_POST['quantity'] ?? '';
    $total_val = $_POST['total'] ?? '';
    $date_facture_val = $_POST['date_facture'] ?? '';


    if (isset($_POST['update'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $quantity = mysqli_real_escape_string($conn, $_POST['quantity']);
    $total    = mysqli_real_escape_string($conn, $_POST['total']);
    $product  = mysqli_real_escape_string($conn, $_POST['product']);
    $id_client = mysqli_real_escape_string($conn, $_POST['id_clientfd']);
    //  $client   = mysqli_real_escape_string($conn, $_POST['client']);
     $employe  = mysqli_real_escape_string($conn, $_POST['employe']);
    $date_facture_val = mysqli_real_escape_string($conn, $_POST['date_facture']);
    $type_payment = mysqli_real_escape_string($conn, $_POST['type_payment']);

    if ($id === '' && $quantity === '' && $total === '' && $product === '' && $id_client === '' && $employe === '' && $date_facture_val === '' && $type_payment === '') {
        echo "<p class='message-error'> Please select a Facture ID to update.</p>";
    } else {
        $fetch_sql = "SELECT quantity, product_id FROM facture_details WHERE id_facture_details = '$id'";
        $result = $conn->query($fetch_sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $old_quantity = $row['quantity'];
            $old_product_id = $row['product_id'];
           

            if ($old_product_id === $product) {
                $delta = $quantity - $old_quantity;
                if ($delta !== 0) {
                    $up_stoke = "UPDATE stoke SET quantity = quantity - '$delta' WHERE product_id_product = '$product'";
                    if ($conn->query($up_stoke) !== TRUE) {
                        echo "<p class='message-error'>Stock update error: " . $conn->error . "</p>";
                    }
                }
            } else {
                $restore_stock = "UPDATE stoke SET quantity = quantity + '$old_quantity' WHERE product_id_product = '$old_product_id'";
                $reduce_stock = "UPDATE stoke SET quantity = quantity - '$quantity' WHERE product_id_product = '$product'";
                if ($conn->query($restore_stock) !== TRUE || $conn->query($reduce_stock) !== TRUE) {
                    echo "<p class='message-error'>Stock update error: " . $conn->error . "</p>";
                }
            }

            $update_sql = "UPDATE facture_details SET quantity = '$quantity', prix = '$total', product_id = '$product', client_id = '$id_client', employe_id = '$employe', date_facture = '$date_facture_val', type_payment = '$type_payment' WHERE id_facture_details = '$id'";
            if ($conn->query($update_sql) === TRUE) {
                echo "<p class='message-success'>Facture updated successfully.</p>";
            } else {
                echo "<p class='message-error'>Error updating facture: " . $conn->error . "</p>";
            }
        } else {
            echo "<p class='message-error'>No record found with ID: " . $id . "</p>";
        }
    }
}




elseif (
    isset($_POST['quantity']) &&
    isset($_POST['total']) &&
    isset($_POST['product']) &&
    isset($_POST['id_clientfd']) &&
    isset($_POST['add']) &&
    !isset($_POST['delete'])
) {

    
    $quantity = mysqli_real_escape_string($conn, $_POST['quantity']);
    $total    = mysqli_real_escape_string($conn, $_POST['total']);
    $product  = mysqli_real_escape_string($conn, $_POST['product']);
    $id_client = mysqli_real_escape_string($conn, $_POST['id_clientfd']);
    $employe  = mysqli_real_escape_string($conn, $_POST['employe']);
    $date_facture = mysqli_real_escape_string($conn, $_POST['date_facture']);
    $type_payment = mysqli_real_escape_string($conn, $_POST['type_payment']);
    if(isset($_POST['add']) && (empty($quantity) && empty($total) && empty($product) && empty($id_client) && empty($employe) && empty($date_facture) && empty($type_payment))) {
        echo "<p class='message-error'>Please fill in all required fields to add a facture.</p>";
      

    }

    $sql  = "INSERT INTO facture_details
(quantity, prix, product_id,  employe_id, date_facture, type_payment,client_id)
VALUES
('$quantity', '$total', '$product',  '$employe', '$date_facture', '$type_payment','$id_client')";

 header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    

    $up_stoke = "UPDATE stoke SET quantity = quantity - '$quantity' WHERE product_id_product = '$product'";
    if ($conn->query($up_stoke) === TRUE) {
        echo "<p class='message-success'>Record created and Stock updated successfully!</p>";
    } else {
        echo "<p class='message-error'>Facture added but Stock Error: " . $conn->error . "</p>";
    }
   
  




    
    if ($conn->query($sql) === TRUE) {
        $invoice_id = $conn->insert_id;
        echo "New record created successfully with ID: " . $invoice_id;

        // --- هنا يبدأ كود الـ n8n (يعمل فقط عند النجاح) ---
        $n8n_webhook_url = 'http://localhost:5678/webhook-test/invoice-notify';
        $data = ['invoice_id' => $invoice_id];
        
        $ch = curl_init($n8n_webhook_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
        // ----------------------------------------------

    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// 2. التعامل مع الحذف (Delete)
elseif (isset($_POST['delete'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    echo "Attempting to delete record with ID: " . $id . "<br>";
    
    // First, fetch the quantity and product_id from the record to be deleted
    $fetch_sql = "SELECT quantity, product_id FROM facture_details WHERE id_facture_details = '$id'";
    $result = $conn->query($fetch_sql);
    
        if ($result ) {
            // fetch_assoc  ka tjib row li wast columns quantity w product_id b id_facture_details
         $row= $result->fetch_assoc();
        //  echo "Fetched quantity: " . $row['quantity'] . " and product_id: " . $row['product_id'] . "<br>";
        $delete_quantity = $row['quantity'];
        $delete_product_id = $row['product_id'];
        
        // Update stock by adding back the quantity
        // hna n3awdou n7sbou stoke bch nzidou 3lih quantity li msahna  f facture_details
        $up_stoke = "UPDATE stoke 
                     SET quantity = quantity + '$delete_quantity' 
                     WHERE product_id_product = '$delete_product_id'";
        
        // Delete the record
        $delete_sql = "DELETE FROM facture_details WHERE id_facture_details = '$id'";
        
        if ($conn->query($up_stoke) === TRUE && $conn->query($delete_sql) === TRUE) {
            echo "Record deleted successfully and stock updated!";
        } else {
            echo "Error deleting record or updating stock: " . $conn->error;
        }
     }
     
    if ($result->num_rows == 0) {
        echo "No record found with ID: " . $id . "<br>";
    }
}
}




$re_pr   = mysqli_query($conn, "SELECT id_product, type_product FROM product");
$re_fc   = mysqli_query($conn, "SELECT id_client, name  FROM client");
$re_idfc = mysqli_query($conn, "SELECT id_facture_details FROM facture_details");
$re_employe = mysqli_query($conn, "SELECT id_employe, name FROM employe");
$res_payment = mysqli_query($conn, "SELECT id_payment, type_payment FROM payment");
?>

<form action="" method="post">
    <h4>Facture Details</h4>
    <label for="">Date Facture</label>
    <input type="date" name="date_facture" id="" value="<?php echo htmlspecialchars($date_facture_val); ?>">
    <label for="id">Id</label>
    <select name="id">
        <option value="">Select Facture Id</option>
        <?php while($row = mysqli_fetch_assoc($re_idfc)): ?>
        <option value="<?php echo $row['id_facture_details']; ?>" <?php echo $selected_id == $row['id_facture_details'] ? 'selected' : ''; ?>><?php echo $row['id_facture_details']; ?></option>
        <?php endwhile; ?>
    </select>

    <label for="product">Product</label>
    <select name="product">
        <option value="">Select Product</option>
        <?php while($row = mysqli_fetch_assoc($re_pr)): ?>
        <option value="<?php echo $row['id_product']; ?>" <?php echo $selected_product == $row['id_product'] ? 'selected' : ''; ?>><?php echo $row['type_product']; ?></option>
        <?php endwhile; ?>
    </select>
  
    <label for="client">Client</label>

<select name="id_clientfd">

    <option value="">Select Client</option>

    <?php while($row = mysqli_fetch_assoc($re_fc)): ?>

        <option 
            value="<?php echo $row['id_client']; ?>"

            <?php echo $selected_client == $row['id_client'] ? 'selected' : ''; ?>>

            <?php echo $row['name']; ?>

        </option>

    <?php endwhile; ?>

</select>

      <label for="client">Employe Name</label>
    <select name="employe">
        <option value="">Select Employe</option>
        <?php while($row = mysqli_fetch_assoc($re_employe)): ?>
        <option value="<?php echo $row['id_employe']; ?>" <?php echo $selected_employe == $row['id_employe'] ? 'selected' : ''; ?>><?php echo $row['name']; ?></option>
        <?php endwhile; ?>
    </select>

      <label>Type payment</label>
    <select name="type_payment">
        <option value="">Choose Type Payment</option>
        <?php while($row = mysqli_fetch_assoc($res_payment)): ?>
            <option value="<?php echo $row['id_payment'] ; ?>" <?php echo $selected_type_payment == $row['id_payment'] ? 'selected' : ''; ?>>
                <?php echo $row['type_payment']; ?> </option>
        <?php endwhile; ?>
    </select>
    
    <label for="quantity">Quantity</label>
    <input type="number" name="quantity" value="<?php echo htmlspecialchars($quantity_val); ?>">
    <label for="total">Total</label>
    <input type="number" step="0.01" name="total" value="<?php echo htmlspecialchars($total_val); ?>">
    
 
     <button type="submit" name="add">Add to Facture Details</button>
    <button type="submit" name="update">Update Facture</button>
    <button type="submit" name="delete">Delete</button>
  
</form>

<?php include 'footerfd.php'; ?>