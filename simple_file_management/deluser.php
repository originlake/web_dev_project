<?php
session_start();

//https://stackoverflow.com/questions/19825283/redirect-to-a-page-url-after-alert-button-is-pressed
function alertbox($msg, $page){
    echo "<script>";
    echo "alert('".htmlentities($msg)."');";
    echo "window.location.href ='".htmlentities($page)."';";
    echo "</script>";
    exit;
}

// Not log in, redirect to login page
if(!isset($_SESSION['username'])){
    alertbox("Please log in.","login.php");
}

$private_addr = "/srv/uploads/";
$user_names = "users.txt";
$userfolder = $_SESSION['username'];

if(isset($_GET['namecheck'])){
    if($_GET['namecheck'] == $userfolder){
        exec("rm -r ".$private_addr.$userfolder);
        //if(isdir)
        $fhand = fopen($private_addr.$user_names, "r");
        $userlist = [];
        while(!feof($fhand)){
            //Success, redirect
            $cname = rtrim(fgets($fhand));
            if($userfolder != $cname){
                array_push($userlist, $cname);
            }
        }
        
        fclose($fhand);
        $fhand = fopen($private_addr.$user_names,"w");
        
        foreach($userlist as &$value){
            fwrite($fhand, $value.PHP_EOL);
        }
        
        fclose($fhand);
        alertbox("Deletion succeed!", "logout.php");
    }else{
        alertbox("Deletion failed, username not correct", "main.php");
    }
}
?>
<!DOCTYPE html>
<html lang='en'>
    <head>
        <title>Deletion confirmation</title>
        <link rel="stylesheet" type="text/css" href="logpage.css">
    </head>
    <body>
        <div class="signpage">
            <h3 class="center">Please input username to continue</h3>
            <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="get">
                <p class="center"><input type="text" name="namecheck" placeholder="Username"></p>
                <p class="center"><input type="submit" value="Confirm"></p>
            </form>
        </div>
    </body>



