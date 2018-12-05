<?php
session_start();

// function for alert box and redirecting
//https://stackoverflow.com/questions/19825283/redirect-to-a-page-url-after-alert-button-is-pressed
function alertbox($msg, $page){
    echo "<script>";
    echo "alert('".htmlentities($msg)."');";
    echo "window.location.href ='".htmlentities($page)."';";
    echo "</script>";
    exit;
}

// not log in
if(!isset($_SESSION['username'])){
    alertbox("Please log in.","login.php");
}

// Get the filename and make sure it is valid
$filename = basename($_FILES['uploadedfile']['name']);
if( !preg_match('/^[\w_\.\-]+$/', $filename) ){
    alertbox("Invalid filename", "main.php");
}

// Get the username and make sure it is valid
$username = $_SESSION['username'];
if( !preg_match('/^[\w_\-]+$/', $username) ){
	alertbox("Invalid username", "main.php");
}

$full_path = sprintf("/srv/uploads/%s/%s", $username, $filename);

if( move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $full_path) ){
	alertbox("Succeed.", "main.php");
}else{
	alertbox("Failed.", "main.php");
}
?>