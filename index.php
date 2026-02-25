<?php
session_start();

/* ================= KONEKSI ================= */
$conn=mysqli_connect("localhost","root","","cerdas_cermat");
if(!$conn) die(mysqli_connect_error());

function esc($s){
global $conn;
return mysqli_real_escape_string($conn,$s);
}

$page=$_GET['page'] ?? 'home';

/* ================= REGISTER ================= */
if(isset($_POST['register'])){
$u=esc($_POST['u']);
$p=password_hash($_POST['p'], PASSWORD_DEFAULT);
$k=$_POST['k'];

$role = "siswa";


mysqli_query($conn,"
INSERT INTO users(username,password,kelas,role)
VALUES('$u','$p','$k','$role')
");

header("Location:index.php");
exit;
}


/* ================= LOGIN ================= */
if(isset($_POST['login'])){
    $u = esc($_POST['u']);
    $p = $_POST['p'];

    $q = mysqli_query($conn,"SELECT * FROM users WHERE username='$u'");

    if(mysqli_num_rows($q) > 0){
        $data = mysqli_fetch_assoc($q);

        if(password_verify($p, $data['password'])){
            $_SESSION['user'] = $data;
            header("Location:?page=home");
            exit;
        } else {
            echo "<script>alert('Password salah');</script>";
        }

    } else {
        echo "<script>alert('Username tidak ditemukan');</script>";
    }
}





/* ================= LOGOUT ================= */
if($page=="logout"){
session_destroy();
header("Location:index.php");
exit;
}


/* ================= SIMPAN NILAI ================= */
if(isset($_POST['finish'])){
  $skor = $_POST['skor'];
  $u = $_SESSION['user']['username'];
  $detail = json_decode($_POST['detail'], true);
  $mapel = esc($_POST['mapel'] ?? '');


  mysqli_query($conn,"
INSERT INTO nilai(username,skor,mapel)
VALUES('$u','$skor','$mapel')
");


  $q = mysqli_query($conn,"
  SELECT pertanyaan, jawaban
  FROM soal
  WHERE kelas='".esc($_SESSION['user']['kelas'])."'
  AND mapel='$mapel'
  LIMIT 40
");


  if($q){
    $i = 0;
    while($s = mysqli_fetch_assoc($q)){
      $js = $detail[$i] ?? '';
      mysqli_query($conn,"
        INSERT INTO detail_jawaban(username,soal,jawaban_siswa,jawaban_benar)
        VALUES(
          '$u',
          '".esc($s['pertanyaan'])."',
          '$js',
          '".$s['jawaban']."'
        )
      ");
      $i++;
    }
  }

  $_SESSION['nilai_akhir'] = $skor;
  header("Location:index.php?page=hasil");
  exit;
}





/* ================= TAMBAH SOAL ================= */
if(isset($_POST['add_soal'])){

    // Ambil input
    $kelas = esc($_POST['kelas']);  // SD / SMP / SMA
    $mapel = esc($_POST['mapel']);  // Matematika / IPA / dst
    $p = esc($_POST['pertanyaan']);
    $a = esc($_POST['a']);
    $b = esc($_POST['b']);
    $c = esc($_POST['c']);
    $d = esc($_POST['d']);
    $j = esc($_POST['jawaban']);    // A/B/C/D

    // Upload gambar jika ada
    $img = "";
    if(!empty($_FILES['gambar']['name'])){
        $img = "uploads/".time()."_".basename($_FILES['gambar']['name']);
        move_uploaded_file($_FILES['gambar']['tmp_name'],$img);
    }

    // Insert soal ke database
    $sql = "
    INSERT INTO soal(kelas,mapel,pertanyaan,gambar,a,b,c,d,jawaban,status)
    VALUES('$kelas','$mapel','$p','$img','$a','$b','$c','$d','$j','aktif')
    ";

    $q = mysqli_query($conn, $sql);
    if(!$q){
        die("Gagal tambah soal: ".mysqli_error($conn));
    } else {
        echo "<script>alert('Soal berhasil ditambahkan!');window.location='?page=admin';</script>";
    }
}


/* ================= HAPUS SOAL ================= */
if($page=="hapus_soal" && isset($_SESSION['user']) && $_SESSION['user']['role']=="admin"){
$id=$_GET['id'];
mysqli_query($conn,"DELETE FROM soal WHERE id='$id'");
header("Location:?page=admin");
exit;
}

/* ================= UPDATE SOAL ================= */
if(isset($_POST['update_soal'])){
$id=$_POST['id'];
$p=$_POST['pertanyaan'];
$a=$_POST['a'];
$b=$_POST['b'];
$c=$_POST['c'];
$d=$_POST['d'];
$j=$_POST['jawaban'];

mysqli_query($conn,"
UPDATE soal SET 
pertanyaan='$p', a='$a', b='$b', c='$c', d='$d', jawaban='$j'
WHERE id='$id'
");

header("Location:?page=admin");
exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>CBT Sekolah</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
background:linear-gradient(135deg,#4e73df,#1cc88a);
min-height:100vh;
color:white;
font-family:Segoe UI;
display:flex;
flex-direction:column;
position: relative;
}
.container{flex:1}
.glass{
background:rgba(255,255,255,.12);
backdrop-filter:blur(12px);
padding:25px;
border-radius:20px;
box-shadow:0 10px 25px rgba(0,0,0,.3);
}
.slide{display:none}
.slide.active{display:block}
.timer{color:yellow;font-weight:bold;font-size:20px}
footer{text-align:center;padding:10px;opacity:.8}

.title-header{
    font-weight:bold;
    color:#ffc107;
    text-shadow:0 0 15px rgba(255,193,7,0.8);
}

#jamDigital{
  font-size:14px;
  line-height:1.3;
}

.clock-box{
    position: fixed;
    top: 20px;
    right: 25px;
    z-index: 9999;

    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(10px);
    padding: 12px 18px;
    border-radius: 15px;
    font-size: 14px;
    text-align: right;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}


.clock-box .date{
    font-weight:bold;
    font-size:13px;
}
.clock-box .time{
    font-size:16px;
    color:#ffc107;
}

.user-info{
    background: rgba(0,0,0,0.3);
    padding: 12px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.clock-box .btn{
    font-size:12px;
    padding:4px 10px;
}



#jamDigital{
    font-size: 14px;
    margin-bottom: 5px;
}

.btn-group-top{
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 5px;
}


.btn-top{
    padding: 5px 12px;
    font-size: 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
}

.btn-admin{
    background: orange;
    color: black;
}

.btn-logout{
    background: crimson;
    color: white;
}


</style>
</head>

<body>
  <div class="clock-box">
   
  <div id="jamDigital"></div>

    <?php if(isset($_SESSION['user'])){ ?>
        <div class="btn-group-top">

            <?php if($_SESSION['user']['role']=="admin"){ ?>
                <a href="?page=admin" class="btn-top btn-admin">Admin</a>
            <?php } ?>

            <a href="?page=logout" class="btn-top btn-logout">Logout</a>

        
    <?php } ?>
</div>
</div>
<div class="container py-5">



  <div class="text-center mb-4">
    <h2 class="title-header">
         CBT Cerdas Cermat
    </h2>
</div>



    



<script>
function updateJam(){
  const now = new Date();

  const hari = now.toLocaleDateString('id-ID', { weekday: 'long' });
  const tanggal = now.getDate();
  const bulan = now.toLocaleDateString('id-ID', { month: 'long' });
  const tahun = now.getFullYear();
  const jam = now.toLocaleTimeString('id-ID');

  document.getElementById("jamDigital").innerHTML =
    `<div class="date">🗓️ ${hari}, ${tanggal} ${bulan} ${tahun}</div>
     <div class="time">🕒 ${jam}</div>`;
}

setInterval(updateJam,1000);
updateJam();
</script>





<?php
/* ================= LOGIN PAGE ================= */
if(!isset($_SESSION['user']) && $page!="register"){ ?>
<div class="row justify-content-center align-items-center" style="min-height:80vh;">
<div class="col-md-4">
<div class="glass text-center">
<h3 class="fw-bold text-warning">CBT Cerdas Cermat</h3>


<form method="post">
<input name="u" class="form-control mb-2" placeholder="Username" required>

<div class="input-group mb-3">
<input type="password" name="p" class="form-control" placeholder="Password" required>
<button type="button" class="btn btn-light" onclick="togglePassword(this)">👁️</button>
</div>


<button name="login" class="btn btn-warning w-100">Login</button>
</form>

<p class="mt-3">
Belum punya akun?
<a href="index.php?page=register" class="text-warning fw-bold">
Buat akun
</a>
</p>
</div>
</div>
</div>
<?php } ?>

<?php
/* ================= REGISTER PAGE ================= */
if($page=="register" && !isset($_SESSION['user'])){ ?>
<div class="row justify-content-center align-items-center" style="min-height:80vh;">
<div class="col-md-4">
<div class="glass text-center">
<h4>📝 Daftar Akun Siswa</h4>

<form method="post">
<input name="u" class="form-control mb-2" placeholder="Username" required>

<div class="input-group mb-2">
<input type="password" name="p" class="form-control" placeholder="Password" required>
<button type="button" class="btn btn-light" onclick="togglePassword(this)">👁️</button>
</div>


<select name="k" class="form-control mb-3">
<option>SD</option><option>SMP</option><option>SMA</option>
</select>

<button name="register" class="btn btn-success w-100">Daftar</button>
</form>

<p class="mt-3">
Sudah punya akun?
<a href="index.php" class="text-warning fw-bold">Login</a>
</p>
</div>
</div>
</div>
<?php } ?>


<?php
/* ================= DASHBOARD ================= */
if (isset($_SESSION['user'])) {
?>

<?php if($page=="home"){ ?>
<div class="glass mb-4">

<?php if($_SESSION['user']['role']=="admin"){ ?>

  <div class="user-info mb-3">
    <h5 class="fw-bold text-warning">
       Panel Admin
    </h5>
    <small>Login sebagai: <?= $_SESSION['user']['username']; ?></small>
  </div>

  <hr>

  <a href="?page=admin" class="btn btn-warning">
    Masuk ke Dashboard Admin
  </a>

<?php } else { ?>

  <div class="user-info mb-3">
    <h5 class="fw-bold">
       Nama Siswa:
      <span class="text-warning">
        <?= $_SESSION['user']['username']; ?>
      </span>
    </h5>
    <small>Kelas: <?= $_SESSION['user']['kelas']; ?></small>
  </div>

  <hr>

  <h4> Pilih Mata Pelajaran</h4>

  <form method="get">
  <input type="hidden" name="page" value="quiz">
    
 <?php
 $kelas = $_SESSION['user']['kelas'];
 $username = $_SESSION['user']['username'];
/*
echo "<div class='alert alert-info'>";
echo "DEBUG → Kelas login: <b>$kelas</b><br>";
echo "DEBUG → Username login: <b>$username</b>";
echo "</div>";
*/
?>
    <select name="mapel" class="form-control mb-3" required>
  <option value="">-- Pilih Mapel --</option>

<?php

$qMapel = mysqli_query($conn,"
  SELECT DISTINCT mapel
  FROM soal
  WHERE kelas='$kelas'
  AND TRIM(LOWER(status))='aktif'
");


$qNilai = mysqli_query($conn,"
  SELECT DISTINCT mapel
  FROM nilai
  WHERE username='$username'
");

$mapelSudah = [];
while($n = mysqli_fetch_assoc($qNilai)){
    $mapelSudah[] = $n['mapel'];
}

if(mysqli_num_rows($qMapel) > 0){
    while($m = mysqli_fetch_assoc($qMapel)){
        $namaMapel = $m['mapel'];

        // CEK: sudah dikerjakan atau belum
        if(in_array($namaMapel, $mapelSudah)){
            echo "<option disabled>
                    $namaMapel (Selesai)
                  </option>";
        } else {
            echo "<option value='$namaMapel'>
                    $namaMapel
                  </option>";
        }
    }
}else{
    echo "<option disabled>Belum ada soal tersedia</option>";
}

?>
</select>

    <button class="btn btn-success">Mulai Ujian</button>
  </form>

<?php } ?>

</div>
<?php } ?>




<?php
/* ================= HASIL ================= */
if ($page == "hasil") {
?>
<div class="glass text-center">
  <h3>🎉 Pekerjaan Terkirim</h3>
  <h1 class="text-warning">
    Nilai Kamu: <?= $_SESSION['nilai_akhir'] ?>
  </h1>
  <a href="index.php" class="btn btn-success mt-3">
    Kembali ke Dashboard
  </a>
</div>
<?php
}
?>




<nav class="mb-4 d-flex justify-content-between">
<span>
<?php if(isset($_GET['mapel'])){ ?>
📘 <?= htmlspecialchars($_GET['mapel']) ?>
<?php } ?>
</span>


<div>


</div>
</nav>

<?php
/* ================= QUIZ ================= */
if($page=="quiz"){

// 🔐 PROTEKSI QUIZ
  if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != "siswa"){
      header("Location:index.php");
      exit;
  }
  if(!isset($_GET['mapel'])){
    header("Location:index.php");
    exit;
  }


$kelas=$_SESSION['user']['kelas'];
$user=$_SESSION['user']['username'];
$mapel = esc($_GET['mapel'] ?? '');

$soal = mysqli_query($conn,"
  SELECT * FROM soal
  WHERE kelas='$kelas'
  AND mapel='$mapel'
  AND status='aktif'
  ORDER BY id DESC
  LIMIT 40
");

if(mysqli_num_rows($soal) == 0){
    echo "<div class='alert alert-warning text-center'>
    ⚠️ Belum ada soal untuk mapel ini.
    </div>";
    return;
}


?>

<button onclick="fullscreen()"
 class="btn btn-dark btn-sm mb-2">
🖥️ Mode Ujian (Fullscreen)
</button>
<div class="glass text-center">
    <h4 class="mb-3">
⏱️ Sisa Waktu Ujian:
<span id="timerUjian" class="text-warning fw-bold">120:00</span>
</h4>
<form method="post" id="quizForm">
  <input type="hidden" name="mapel" value="<?= htmlspecialchars($mapel) ?>">

<?php $i=0; while($s=mysqli_fetch_assoc($soal)){ ?>
<div class="slide <?= $i==0?'active':'' ?>">

 <button type="button"
 class="btn btn-warning btn-sm mt-2"
 onclick="statusSoal[i]=2; renderStatus()">
Ragu-ragu
</button>

<p><?= $s['pertanyaan']?></p>
<?php foreach(['a','b','c','d'] as $o){ ?>
<label class="d-block">
<input type="radio" name="jawab<?= $i ?>" value="<?= strtoupper($o) ?>">
<?= $s[$o] ?>
</label>
<?php } ?>
<input type="hidden" class="kunci" value="<?= $s['jawaban']?>">
<div class="mt-3 text-center">

  <button type="button"
          class="btn btn-secondary btn-sm"
          onclick="prev()">
    ⬅️ Sebelumnya
  </button>

  <button type="button"
          class="btn btn-primary btn-sm"
          onclick="simpanJawaban()">
     Simpan Jawaban
  </button>

  <button type="button"
          class="btn btn-warning btn-sm"
          onclick="raguSoal()">
     Ragu-ragu
  </button>

  <button type="button"
          class="btn btn-success btn-sm"
          onclick="next()">
    ➡️ Selanjutnya
  </button>

</div>

</div>
<?php $i++; } ?>
<input type="hidden" name="skor" id="skorInput">
<input type="hidden" name="detail" id="detail">
<button name="finish" id="finishBtn" class="d-none"></button>
<div class="mt-4 text-center" id="statusSoal"></div>
<button type="button"
        id="kirimBtn"
        class="btn btn-danger mt-3 d-none"
        onclick="kirimPekerjaan()">
 Kirim Pekerjaan ke Guru
</button>


</form>
</div>
<script>

history.pushState(null,null,location.href);
window.onpopstate = function(){
  history.go(1);
};

document.addEventListener("keydown",function(e){
  if(e.key=="F5" || (e.ctrlKey && e.key=="r")){
    e.preventDefault();
    alert("🚫 Refresh diblokir saat ujian!");
  }
});

window.onbeforeunload = function(){
  return "Ujian sedang berlangsung!";
};

// ================= TIMER UJIAN TOTAL =================
let totalDetik = 120 * 60; // 120 menit

function mulaiTimer(){
  let t = setInterval(()=>{
    let menit = Math.floor(totalDetik / 60);
    let detik = totalDetik % 60;

    document.getElementById("timerUjian").innerText =
      menit + ":" + (detik < 10 ? "0"+detik : detik);

    if(totalDetik <= 0){
      clearInterval(t);
      alert("!! Waktu habis! Pekerjaan dikirim otomatis.");
      kirimPekerjaan();
    }

    totalDetik--;
  },1000);
}

mulaiTimer();

let slides = document.querySelectorAll(".slide");
let i = 0;
let skor = 0;
let statusSoal = Array(slides.length).fill(0);
// 0 = belum, 1 = dijawab, 2 = ragu
let jawaban = [];


function renderStatus(){
  let html="";
  statusSoal.forEach((s,n)=>{
    let c = s==1?"success":s==2?"warning":"secondary";
    html += `<button type="button"
     class="btn btn-${c} btn-sm m-1"
     onclick="gotoSoal(${n})">${n+1}</button>`;
  });
  document.getElementById("statusSoal").innerHTML = html;
}

function gotoSoal(n){
  slides.forEach(s=>s.classList.remove("active"));
  slides[n].classList.add("active");
  i=n;
}

document.querySelectorAll("input[type=radio]").forEach(r=>{
  r.onclick = ()=>{
    statusSoal[i]=1;
    jawaban[i] = r.value;
    renderStatus();
  }
});

function next(){
  let k = slides[i].querySelector(".kunci").value;
  let p = slides[i].querySelector("input:checked");
  if(p && p.value === k) skor += 2.5;

  i++;
  if(i >= slides.length){
    document.getElementById("kirimBtn").classList.remove("d-none");
    return;
  }

  slides.forEach(s=>s.classList.remove("active"));
  slides[i].classList.add("active");

  if(i === slides.length-1){
    document.getElementById("kirimBtn").classList.remove("d-none");
  }
}

function kirimPekerjaan(){
  if(confirm("Yakin kirim pekerjaan?")){
    document.getElementById("skorInput").value = skor;
    document.getElementById("detail").value = JSON.stringify(jawaban);
    document.getElementById("finishBtn").click();
  }
}


renderStatus();


function prev(){
  if(i <= 0) return;

  slides.forEach(s=>s.classList.remove("active"));
  i--;
  slides[i].classList.add("active");
}

function simpanJawaban(){
  let p = slides[i].querySelector("input:checked");

  if(p){
    statusSoal[i] = 1; // dijawab
    jawaban[i] = p.value;
    alert("Jawaban disimpan ");
  } else {
    alert("Pilih jawaban dulu!");
  }

  renderStatus();
}

function raguSoal(){
  statusSoal[i] = 2; // ragu
  alert("Soal ditandai ragu ");
  renderStatus();
}

</script>


<?php } ?>

<?php
/* ================= ADMIN ================= */
if($page=="admin"){

    if(!isset($_SESSION['user']) || $_SESSION['user']['role']!="admin"){
        echo "<div class='alert alert-danger text-center mt-4'>
        ⛔ Akses ditolak! Kamu bukan admin.
        </div>";
    } else {
?>

<h4 class="mt-4">📊 Hasil Pekerjaan Siswa</h4>

<table class="table table-dark table-bordered">
<tr>
  <th>No</th>
  <th>Username</th>
  <th>Skor</th>
</tr>

<?php
$no = 1;
$q = mysqli_query($conn,"SELECT * FROM nilai ORDER BY id DESC");

if($q && mysqli_num_rows($q)>0){
  while($n = mysqli_fetch_assoc($q)){
?>
<tr>
  <td><?= $no++ ?></td>
  <td><?= $n['username'] ?></td>
  <td><?= $n['skor'] ?></td>
</tr>
<?php
  }
}else{
  echo "<tr><td colspan='3' class='text-center'>Belum ada nilai</td></tr>";
}
?>
</table>

<h4 class="mt-4">📋 Detail Jawaban Siswa</h4>
<table class="table table-dark table-bordered">
<tr>
<th>User</th><th>Soal</th><th>Jawaban</th><th>Kunci</th>
</tr>

<?php
$q = mysqli_query($conn,"SELECT * FROM detail_jawaban ORDER BY id DESC");

if($q && mysqli_num_rows($q)>0){
  while($d = mysqli_fetch_assoc($q)){
?>
<tr>
<td><?= $d['username']?></td>
<td><?= substr($d['soal'],0,40) ?>...</td>
<td><?= $d['jawaban_siswa']?></td>
<td><?= $d['jawaban_benar']?></td>
</tr>
<?php
  }
}else{
  echo "<tr><td colspan='4' class='text-center'>Belum ada detail jawaban</td></tr>";
}
?>
</table>


<div class="glass">
<h4>🛠️ Kelola Soal</h4>

<form method="post" enctype="multipart/form-data" class="mb-3">

<select name="kelas" class="form-control mb-2">
<option>SD</option>
<option>SMP</option>
<option>SMA</option>
</select>

<select name="mapel" class="form-control mb-2">
<option>Matematika</option>
<option>Bahasa Indonesia</option>
<option>IPA</option>
<option>IPS</option>
</select>

<input name="pertanyaan" class="form-control mb-2" placeholder="Pertanyaan">
<input name="a" class="form-control mb-2" placeholder="Jawaban A">
<input name="b" class="form-control mb-2" placeholder="Jawaban B">
<input name="c" class="form-control mb-2" placeholder="Jawaban C">
<input name="d" class="form-control mb-2" placeholder="Jawaban D">

<select name="jawaban" class="form-control mb-2">
<option>A</option>
<option>B</option>
<option>C</option>
<option>D</option>
</select>

<button name="add_soal" class="btn btn-success w-100">Tambah Soal</button>

</form>


<table class="table table-dark table-bordered">
<tr><th>No</th><th>Kelas</th><th>Pertanyaan</th><th>Aksi</th></tr>
<?php $no=1; $q=mysqli_query($conn,"SELECT * FROM soal"); while($s=mysqli_fetch_assoc($q)){ ?>
<tr>
<td><?= $no++ ?></td>
<td><?= $s['kelas']?></td>
<td><?= substr($s['pertanyaan'],0,40) ?>...</td>
<td>
<a href="?page=edit_soal&id=<?= $s['id']?>" class="btn btn-info btn-sm">✏️</a>
<a href="?page=hapus_soal&id=<?= $s['id']?>" class="btn btn-danger btn-sm"
onclick="return confirm('Hapus soal?')">🗑️</a>
</td>
</tr>
<?php } ?>
</table>
<?php
    }  
}      
?>

</div>

<?php } ?>


<footer>© <?= date("Y") ?> CBT by Imron Zachariaz Rufa Oyi</footer>

<script>

function togglePassword(btn){
  let input = btn.previousElementSibling;

  if(input.type === "password"){
    input.type = "text";
    btn.innerHTML = "🙈";
  } else {
    input.type = "password";
    btn.innerHTML = "👁️";
  }
}


</script>

<script>
function fullscreen(){
  let el = document.documentElement;
  if(el.requestFullscreen) el.requestFullscreen();
  else if(el.webkitRequestFullscreen) el.webkitRequestFullscreen();
}
</script>


</body>
</html>
