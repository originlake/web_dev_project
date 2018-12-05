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

// Not loged in, redirect to login page
if(!isset($_SESSION['username'])){
    alertbox("Please log in.","login.php");
}

$filename = $_GET['file'];

// We need to make sure that the filename is in a valid format; if it's not, display an error and leave the script.
// To perform the check, we will use a regular expression.
if( !preg_match('/^[\w_\.\-]+$/', $filename) ){
	alertbox("Invalid filename", "main.php");
}

// Get the username and make sure that it is alphanumeric with limited other characters.
// You shouldn't allow usernames with unusual characters anyway, but it's always best to perform a sanity check
// since we will be concatenating the string to load files from the filesystem.
$username = $_SESSION['username'];
if( !preg_match('/^[\w_\-]+$/', $username) ){
	alertbox("Invalid username","main.php");
}

$full_path = sprintf("/srv/uploads/%s/%s", $username, $filename);

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($full_path);

header("Content-Type: ".$mime);
readfile($full_path);

?>