<?php
$conn=mysqli_connect("localhost","root","","cerdas_cermat");

if(!$conn){
    die("Koneksi gagal");
}

$passwordBaru = "Imron3910"; // GANTI kalau mau password lain
$hash = password_hash($passwordBaru, PASSWORD_DEFAULT);

mysqli_query($conn,"UPDATE users SET password='$hash' WHERE username='Imron'");

echo "Password user imron berhasil direset menjadi: $passwordBaru";
?>
