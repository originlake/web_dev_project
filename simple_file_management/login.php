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
        <title>login page</title>
        <link rel="stylesheet" type="text/css" href="logpage.css">
    </head>
    <body>
        <?php
        if(isset($_GET['username'])){
            $name = $_GET['username'];
            // Check if username invalid
            if( !preg_match('/^[\w_\-]+$/', $name) ){
                alertbox("Invalid username.", $_SERVER['PHP_SELF']);
            }
            
            // Check if username exists in users.txt
            $fhand = fopen($private_addr.$user_names, "r");
            while(!feof($fhand)){
                //Success, redirect
                if($name == rtrim(fgets($fhand))){
                    $_SESSION['username'] = $name;
                    header("Location: main.php");
                    exit;
                }
            }
            
            //alert user not exits
            //https://stackoverflow.com/questions/13837375/how-to-show-an-alert-box-in-php
            alertbox("User ".$name." not exists!", $_SERVER['PHP_SELF']);
        }
        ?>
        <div class="signpage">
            <h3 class="center">Please sign in</h3>
            <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="get">
                <p class="center"><input type="text" name="username" placeholder="Username"></p>
                <p class="center"><input type="submit" value="Sign in"></p>
                <br>
                <p class="center"><a href="signup.php">sign up</a></p>
            </form>
        </div>
    </body>
</html>