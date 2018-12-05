<?php
    require 'toolbox.php';
    $isVisitor = checkUser();
    // Already login, redirect
    if(!$isVisitor){
        header('Location: home.php');
        exit();
    }
    
    if(isset($_POST['username']) && isset($_POST['email']) && isset($_POST['pw1']) && isset($_POST['pw2'])){
        $username = $_POST['username'];
        $email = $_POST['email'];
        $pw1 = $_POST['pw1'];
        $pw2 = $_POST['pw2'];
        // email address validation
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $errmsg = 'Email address not valid';
        }
        // username validation a, a_b, a.b
        // https://stackoverflow.com/questions/12018245/regular-expression-to-validate-username
        else if(!preg_match('/^[a-zA-Z0-9]+([._]?[a-zA-Z0-9]+)*$/', $username)){
            $errmsg = 'User name not valid';
        }
        else if($pw1 != $pw2){
            $errmsg = 'Passwords not same';
        }
        // password validation, at least one uppercase, one lower case, one number and one special character with length at least 6
        // https://stackoverflow.com/questions/19605150/regex-for-password-must-contain-at-least-eight-characters-at-least-one-number-a
        else if(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[#$^+=!*_@%&]).{6,}$/',$pw1)){
            $errmsg = 'Password not valid,';
        }
        else{
            // Get uid for new user
            $stmt = $mysqli->prepare('SELECT MAX(uid) FROM users');
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->execute();
            $stmt->bind_result($maxuid);
            if($stmt->fetch()){
                $uid = $maxuid + 1;
            }
            else{
                // No user exists
                $uid = 1;
            }
            $stmt->close();
            // insert new user
            $pw_hash = password_hash($pw1, PASSWORD_BCRYPT);
            $stmt = $mysqli->prepare("INSERT INTO users(uid, username, password, reg_date, email)
                                     VALUES(?,?,?,CURDATE(),?)");
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('ssss', $uid, $username, $pw_hash, $email);
            $stmt->execute();
            $stmt->close();
            header('Location: login.php');
            exit();
        }
    }
?>
<!DOCTYPE html>
<html lang='en'>
    <head>
        <title>Sign up</title>
        <?php css();?>
    </head>
    <body>
        <?php showNav($isVisitor);?>
        <div class='main'>
            <div class='logbox'>
                <p class='log'><strong>Sign up</strong></p>
                <form action='<?php echo htmlentities($_SERVER["PHP_SELF"]); ?>' method='post'>
                    <label for='username'>Your name</label>
                    <input type='text' id='username' name='username' placeholder= '8 - 20 characters'/>
                    <p class='err'>You could choose from letters, underscore and dot. Underscore and dot can't be at the end or the start, or next to each other, or used multiple times in a row.</p>
                    <label for='email'>Email</label>
                    <input type='email' id='email' name='email'/>
                    <label for='pw1'>Password</label>
                    <input type='password' name='pw1' id='pw1' placeholder= 'At least 6 characters'/>
                    <p class='err'>At least one uppercase letter, one lower case letter, one number and one special character such as #$^+=!*_@%&</p>
                    <label for=pw2>Re-enter password</label>
                    <input type='password' name='pw2' id='pw2'/>
                    <?php
                    if(isset($errmsg)){
                        echo "<p class='err'>".$errmsg."</p>";
                    }
                    ?>
                    <input type='submit' value='Register'>
                </form>
            </div>
        </div>
    </body>
</html>