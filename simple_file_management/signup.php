<?php
session_start();
if(isset($_SESSION['username'])){
    //Had log in, redirect to main page
    header("Location: main.php");
    exit;
}

// function for alert box and redirecting
//https://stackoverflow.com/questions/19825283/redirect-to-a-page-url-after-alert-button-is-pressed
function alertbox($msg, $page){
    echo "<script>";
    echo "alert('".htmlentities($msg)."');";
    echo "window.location.href ='".htmlentities($page)."';";
    echo "</script>";
    exit;
}

// Address config
$private_addr = "/srv/uploads/";
$user_names = "users.txt";
?>
<!DOCTYPE html>
<html lang='en'>
    <head>
        <title>signup page</title>
        <link rel="stylesheet" type="text/css" href="logpage.css">
    </head>
    <body>
        <?php
        // 1 of 2 inputs is empty
        if(!isset($_GET['newuser1']) xor !isset($_GET['newuser2'])){
            alertbox("Usernames not match.",htmlentities($_SERVER['PHP_SELF']));
        }
        // 2 inputs not empty
        else if(isset($_GET['newuser1']) && isset($_GET['newuser2'])){
            $name1 = $_GET['newuser1'];
            $name2 = $_GET['newuser2'];
            $name = $name1;
            $fhand = fopen($private_addr.$user_names, "r");
            // validation
            if( !preg_match('/^[\w_\-]+$/', $name1) ){
                alertbox("Invalid username.",htmlentities($_SERVER['PHP_SELF']));
            }
            // match two inputs
            else if( $name1 != $name2){
                alertbox("Usernames not match.",htmlentities($_SERVER['PHP_SELF']));
            }
            
            // check if new username in users.txt
            while(!feof($fhand)){
                //Success, redirect
                if($name == rtrim(fgets($fhand))){
                    alertbox("User ".$name." exists.",htmlentities($_SERVER['PHP_SELF']));
                }
            }
            fclose($fhand);
            // Add new username to users.txt, open as append mode
            $fhand = fopen($private_addr.$user_names,"a");
            fwrite($fhand, $name.PHP_EOL);
            mkdir($private_addr.$name, 0777);
            fclose($fhand);
            alertbox("Success, please log in.",htmlentities("login.php"));
        }
        ?>
        <div class="signpage">
            <h3 class="center">Sign up</h3>
            <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="get">
                <p class="center"><input type="text" name="newuser1" placeholder="Username"></p>
                <p class="center"><input type="text" name="newuser2" placeholder="Repeat  "></p>
                <p class="center"><input type="submit" value="Sign up"></p>
                <p class="center"><a href="login.php">log in</a></p>
            </form>
        </div>
    </body>
</html>