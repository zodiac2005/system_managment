<?php include 'headerclient.php'; ?>

<?php 

$host="localhost";
$user="root";
$pass="";
$db="mydb_store";
$conn=mysqli_connect($host,$user,$pass,$db);
if(!$conn){
    die("Connection failed: " . mysqli_connect_error());
}

$name="";
$cin="";
if(isset($_POST['submit'])){
    $name=$_POST['name'];
    $cin=$_POST['cin'];

    $sql="INSERT INTO client (name,cin) VALUES ('$name','$cin')";
    if(mysqli_query($conn,$sql)){
        echo "New record created successfully";
    }else{
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

?>

<h4>Add Client</h4>
<form action="" method="post">
    <label for="">Name</label>
    <input type="text" name="name">
    <label for="">Cin</label>
    <input type="text" name="cin">
    <button type="submit" name="submit">Submit</button>
</form>
<?php include 'footerclient.php'; ?>
