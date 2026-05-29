<?php include 'headerfd.php'; ?>

<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "mydb_store";

$conn = mysqli_connect($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$message_class = "";
$generated_invoice_id = null; // متغير جديد لحفظ المعرّف الأخير وعرض زر الـ PDF له فوراً

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- عملية الإضافة (ADD) ---
    if (isset($_POST['add'])) {
        $quantity     = mysqli_real_escape_string($conn, $_POST['quantity']);
        $total        = mysqli_real_escape_string($conn, $_POST['total']);
        $product      = mysqli_real_escape_string($conn, $_POST['product']);
        $id_client    = mysqli_real_escape_string($conn, $_POST['id_clientfd']);
        $employe      = mysqli_real_escape_string($conn, $_POST['employe']);
        $date_facture = mysqli_real_escape_string($conn, $_POST['date_facture']);
        $type_payment = mysqli_real_escape_string($conn, $_POST['type_payment']);

        if (empty($quantity) || empty($total) || empty($product) || empty($id_client) || empty($employe) || empty($date_facture) || empty($type_payment)) {
            $message = "Please fill in all required fields to add a facture.";
            $message_class = "alert-danger";
        } else {
            $sql = "INSERT INTO facture_details (quantity, prix, product_id, client_id, employe_id, payment_id, date_facture) 
                    VALUES ('$quantity', '$total', '$product', '$id_client', '$employe', '$type_payment', '$date_facture')";
            
            if ($conn->query($sql) === TRUE) {
                $invoice_id = $conn->insert_id;
                $generated_invoice_id = $invoice_id; // حفظ المعرف للطباعة

                $up_stoke = "UPDATE stock SET quantity = quantity - '$quantity' WHERE product_id = '$product'";
                $conn->query($up_stoke);

                // ارسـال الـ Webhook لـ n8n
                $n8n_webhook_url = 'http://localhost:5678/webhook-test/invoice-notify';
                $data = ['invoice_id' => $invoice_id];
                $ch = curl_init($n8n_webhook_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                @curl_exec($ch);
                curl_close($ch);

                $message = "New Facture created successfully with ID: " . $invoice_id;
                $message_class = "alert-success";
            } else {
                $message = "Error: " . $conn->error;
                $message_class = "alert-danger";
            }
        }
    }

    // --- عملية التحديث (UPDATE) ---
    if (isset($_POST['update'])) {
        $id           = mysqli_real_escape_string($conn, $_POST['id']);
        $quantity     = mysqli_real_escape_string($conn, $_POST['quantity']);
        $total        = mysqli_real_escape_string($conn, $_POST['total']);
        $product      = mysqli_real_escape_string($conn, $_POST['product']);
        $id_client    = mysqli_real_escape_string($conn, $_POST['id_clientfd']);
        $employe      = mysqli_real_escape_string($conn, $_POST['employe']);
        $date_facture = mysqli_real_escape_string($conn, $_POST['date_facture']);
        $type_payment = mysqli_real_escape_string($conn, $_POST['type_payment']);

        if (empty($id)) {
            $message = "Please select a Facture ID to update.";
            $message_class = "alert-danger";
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
                        $conn->query("UPDATE stock SET quantity = quantity - '$delta' WHERE product_id = '$product'");
                    }
                } else {
                    $conn->query("UPDATE stock SET quantity = quantity + '$old_quantity' WHERE product_id = '$old_product_id'");
                    $conn->query("UPDATE stock SET quantity = quantity - '$quantity' WHERE product_id = '$product'");
                }

                $update_sql = "UPDATE facture_details SET quantity = '$quantity', prix = '$total', product_id = '$product', client_id = '$id_client', employe_id = '$employe', date_facture = '$date_facture', payment_id = '$type_payment' WHERE id_facture_details = '$id'";
                if ($conn->query($update_sql) === TRUE) {
                    $generated_invoice_id = $id; // حفظ المعرف للطباعة بعد التعديل مباشرة
                    $message = "Facture updated successfully.";
                    $message_class = "alert-success";
                } else {
                    $message = "Error updating facture: " . $conn->error;
                    $message_class = "alert-danger";
                }
            } else {
                $message = "No record found with ID: " . $id;
                $message_class = "alert-danger";
            }
        }
    }

    // --- عملية الحذف (DELETE) ---
    if (isset($_POST['delete'])) {
        $id = mysqli_real_escape_string($conn, $_POST['id']);
        
        if (empty($id)) {
            $message = "Please select a Facture ID to delete.";
            $message_class = "alert-danger";
        } else {
            $fetch_sql = "SELECT quantity, product_id FROM facture_details WHERE id_facture_details = '$id'";
            $result = $conn->query($fetch_sql);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $delete_quantity = $row['quantity'];
                $delete_product_id = $row['product_id'];
                
                $conn->query("UPDATE stock SET quantity = quantity + '$delete_quantity' WHERE product_id = '$delete_product_id'");
                
                if ($conn->query("DELETE FROM facture_details WHERE id_facture_details = '$id'") === TRUE) {
                    $message = "Record deleted successfully and stock updated!";
                    $message_class = "alert-success";
                } else {
                    $message = "Error deleting record: " . $conn->error;
                    $message_class = "alert-danger";
                }
            } else {
                $message = "No record found with ID: " . $id;
                $message_class = "alert-danger";
            }
        }
    }
}

$re_pr       = mysqli_query($conn, "SELECT id_product, type_product FROM product");
$re_fc       = mysqli_query($conn, "SELECT id_client, name FROM client");
$re_idfc     = mysqli_query($conn, "SELECT id_facture_details FROM facture_details");
$re_employe  = mysqli_query($conn, "SELECT id_employe, name FROM employe");
$res_payment = mysqli_query($conn, "SELECT id_payment, type_payment FROM payment");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_class; ?> alert-dismissible fade show d-flex justify-content-between align-items-center" role="alert">
                    <div><?php echo $message; ?></div>
                    <?php if ($generated_invoice_id): ?>
                        <a href="generate_pdf.php?id=<?php echo $generated_invoice_id; ?>" target="_blank" class="btn btn-sm btn-light text-dark fw-bold border ms-3">Print PDF Now</a>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0">Facture Details Management</h4>
                </div>
                <div class="card-body p-4">
                    
                    <form action="" method="post" id="main-facture-form">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Date Facture</label>
                                <input type="date" name="date_facture" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product</label>
                                <select name="product" class="form-select" required>
                                    <option value="">Select Product</option>
                                    <?php mysqli_data_seek($re_pr, 0); while($row = mysqli_fetch_assoc($re_pr)): ?>
                                        <option value="<?php echo $row['id_product']; ?>"><?php echo $row['type_product']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Client</label>
                                <select name="id_clientfd" class="form-select" required>
                                    <option value="">Select Client</option>
                                    <?php mysqli_data_seek($re_fc, 0); while($row = mysqli_fetch_assoc($re_fc)): ?>
                                        <option value="<?php echo $row['id_client']; ?>"><?php echo $row['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employe Name</label>
                                <select name="employe" class="form-select" required>
                                    <option value="">Select Employe</option>
                                    <?php mysqli_data_seek($re_employe, 0); while($row = mysqli_fetch_assoc($re_employe)): ?>
                                        <option value="<?php echo $row['id_employe']; ?>"><?php echo $row['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Type payment</label>
                                <select name="type_payment" class="form-select" required>
                                    <option value="">Choose Type Payment</option>
                                    <?php mysqli_data_seek($res_payment, 0); while($row = mysqli_fetch_assoc($res_payment)): ?>
                                        <option value="<?php echo $row['id_payment']; ?>"><?php echo $row['type_payment']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" name="quantity" class="form-control" placeholder="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Total</label>
                                <input type="number" step="0.01" name="total" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>
                        
                        <hr>
                        <div class="d-flex gap-2">
                            <button type="submit" name="add" class="btn btn-success px-4">Add to Facture</button>
                            <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#editFactureModal">Edit / Delete Facture</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editFactureModal" tabindex="-1" aria-labelledby="editFactureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editFactureModalLabel">Modify or Delete Existing Facture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <div class="mb-4 p-3 bg-light rounded border">
                        <label class="form-label fw-bold text-danger">Select Facture ID to load data:</label>
                        <select name="id" id="modal_facture_id" class="form-select border-danger" onchange="fetchFactureDetails(this.value)">
                            <option value="">-- Choose ID --</option>
                            <?php mysqli_data_seek($re_idfc, 0); while($row = mysqli_fetch_assoc($re_idfc)): ?>
                                <option value="<?php echo $row['id_facture_details']; ?>"><?php echo "Facture N°: " . $row['id_facture_details']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date Facture</label>
                            <input type="date" name="date_facture" id="m_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product</label>
                            <select name="product" id="m_product" class="form-select">
                                <option value="">Select Product</option>
                                <?php mysqli_data_seek($re_pr, 0); while($row = mysqli_fetch_assoc($re_pr)): ?>
                                    <option value="<?php echo $row['id_product']; ?>"><?php echo $row['type_product']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client</label>
                            <select name="id_clientfd" id="m_client" class="form-select">
                                <option value="">Select Client</option>
                                <?php mysqli_data_seek($re_fc, 0); while($row = mysqli_fetch_assoc($re_fc)): ?>
                                    <option value="<?php echo $row['id_client']; ?>"><?php echo $row['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employe</label>
                            <select name="employe" id="m_employe" class="form-select">
                                <option value="">Select Employe</option>
                                <?php mysqli_data_seek($re_employe, 0); while($row = mysqli_fetch_assoc($re_employe)): ?>
                                    <option value="<?php echo $row['id_employe']; ?>"><?php echo $row['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Type payment</label>
                            <select name="type_payment" id="m_payment" class="form-select">
                                <option value="">Choose Type Payment</option>
                                <?php mysqli_data_seek($res_payment, 0); while($row = mysqli_fetch_assoc($res_payment)): ?>
                                    <option value="<?php echo $row['id_payment']; ?>"><?php echo $row['type_payment']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="m_quantity" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Total</label>
                            <input type="number" step="0.01" name="total" id="m_total" class="form-control">
                        </div>
                    </div>

                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="submit" name="delete" class="btn btn-danger px-4" onclick="return confirm('Are you sure you want to delete this invoice?');">Delete Facture</button>
                    <div>
                        <button type="button" id="modal_print_btn" class="btn btn-info text-white me-2" onclick="printModalInvoice()" disabled>Print PDF</button>
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update" class="btn btn-warning px-4">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function fetchFactureDetails(id) {
    const printBtn = document.getElementById('modal_print_btn');
    if (!id) {
        if(printBtn) printBtn.disabled = true;
        return;
    }
    
    // تفعيل زر الطباعة وتغيير الرابط له
    if(printBtn) printBtn.disabled = false;

    fetch(`http://localhost:3001/products/${id}`)
        .then(response => response.json())
        .then(data => {
            if(data) {
                document.getElementById('m_date').value = data.date_facture || '';
                document.getElementById('m_product').value = data.product_id || '';
                document.getElementById('m_client').value = data.client_id || '';
                document.getElementById('m_employe').value = data.employe_id || '';
                document.getElementById('m_payment').value = data.payment_id || '';
                document.getElementById('m_quantity').value = data.quantity || '';
                document.getElementById('m_total').value = data.prix || '';
            }
        })
        .catch(err => console.error("Error loading fields:", err));
}

// دالة تفتح الفاتورة المحددة داخل الـ Modal في لسان تبويب خارجي جديد
function printModalInvoice() {
    const id = document.getElementById('modal_facture_id').value;
    if(id) {
        window.open(`generate_pdf.php?id=${id}`, '_blank');
    }
}
</script>

<?php include 'footerfd.php'; ?>