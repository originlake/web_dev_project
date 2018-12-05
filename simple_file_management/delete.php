<?php
session_start();
function alertbox($msg, $page){
    echo "<script>";
    echo "alert('".htmlentities($msg)."');";
    echo "window.location.href ='".htmlentities($page)."';";
    echo "</script>";
    exit;
}
if(isset($_GET['file'])){
$file = $_GET['file'];
if( !preg_match('/^[\w_\.\-]+$/', $file) ){
	echo "Invalid filename";
	exit;
}

// Get the username and make sure that it is alphanumeric with limited other characters.
// You shouldn't allow usernames with unusual characters anyway, but it's always best to perform a sanity check
// since we will be concatenating the string to load files from the filesystem.
$username = $_SESSION['username'];
if( !preg_match('/^[\w_\-]+$/', $username) ){
	echo "Invalid username";
	exit;
}
$full_path = sprintf("/srv/uploads/%s/%s", $username, $file);
if( unlink($full_path) ){
	alertbox("Succeed.", "main.php");
}else{
	alertbox("Failed.", "main.php");
}
}
header("Location: main.php");
exit;
?>
