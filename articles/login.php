<?php
    require 'toolbox.php';
    $isVisitor = checkUser();
    // Already login, redirect
    if(!$isVisitor){
        header('Location: home.php');
        exit();
    }
    
    // login
    if(isset($_POST['username']) && isset($_POST['password'])){
        //check if username is email address
        //https://stackoverflow.com/questions/13719821/email-validation-using-regular-expression-in-php
        if(filter_var($_POST['username'], FILTER_VALIDATE_EMAIL)){
            $stmt = $mysqli->prepare('SELECT COUNT(*), uid, password FROM users WHERE email=?');
        }else{
            $stmt = $mysqli->prepare('SELECT COUNT(*), uid, password FROM users WHERE username=?');
        }
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('s',$user);
        $user = $_POST['username'];
        $stmt->execute();
        
        $stmt->bind_result($cnt, $uid, $pwd);
        $stmt->fetch();
        if($cnt == 1 && password_verify($_POST['password'], $pwd)){
            $_SESSION['uid'] = $uid;
            $_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
            header('Location: home.php');
            exit();
        }else{
            $errmsg = 'The username or password is incorrect';
        }
    }
?>
<!DOCTYPE html>
<html lang='en'>
    <head>
        <title>Log in</title>
        <?php css();?>
    </head>
    <body>
        <div class='container'>
            <?php showNav($isVisitor);?>
            <div class='main'>
                <div class='logbox'>
                    <p class='log'><strong>Log in</strong></p>
                    <form action='<?php echo htmlentities($_SERVER["PHP_SELF"]); ?>' method='post'>
                        <label for='username'>Username or email:</label>
                        <input type='text' name='username' id='username'/>
                        <label for='password'>Password:</label>
                        <input type='password' name='password' id='password'/>
                        <?php
                        if(isset($errmsg)){
                            echo "<p class='err'>".$errmsg."</p>";
                        }
                        ?>
                        <input type='submit' value='Log in'>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>