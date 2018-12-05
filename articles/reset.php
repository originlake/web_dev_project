<?php
    require 'toolbox.php';
    if(!isset($_GET['token']) && !isset($_POST['token'])){
        header('Location: home.php');
        exit();
    }
    if(isset($_GET['token'])){
        $token = $_GET['token'];
        $stmt=$mysqli->prepare('select COUNT(*),uid from validation where val_id=?');
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('s',$_GET['val_id']);
        $stmt->execute();
        $stmt->bind_result($cnt, $uid);
        $stmt->fetch();
        $stmt->close();
        if($cnt == 0){
            echo 'Token not correct.';
            header('refresh: 5; url=profile.php');
            exit();
        }
    }
    else if(isset($_POST['token']) && isset($_POST['uid']) && isset($_POST['pwd1']) && isset($_POST['pwd2'])){
        do{
            $token = $_POST['token'];
            $uid = $_POST['uid'];
            $pwd1 = $_POST['pwd1'];
            $pwd2 = $_POST['pwd2'];
            $stmt = $mysqli->prepare('select COUNT(*), token from validation where uid=?');
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('s', $uid);
            $stmt->execute();
            $stmt->bind_result($cnt, $server_token);
            $stmt->fetch();
            $stmt->close();
            if($cnt == 0 || strcmp($token,$server_token)!=0){
                echo 'Token not correct.';
                header('refresh: 5; url=home.php');
                exit();
            }
            if($pwd1 != $pwd2){
                $errmsg = 'Passwords not same';
                break;
            }
            if(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[#$^+=!*_@%&]).{6,}$/',$pwd1)){
                $errmsg = 'Password not valid,';
                break;
            }
            $pw_hash = password_hash($pwd1, PASSWORD_BCRYPT);
            $stmt = $mysqli->prepare('update users set password=? where uid=?');
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('ss', $pw_hash, $uid);
            $stmt->execute();
            $stmt->close();
            //delete token
            $stmt = $mysqli->prepare('delete from validation where uid=?');
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('s', $uid);
            $stmt->execute();
            $stmt->close();
            echo 'Reset password successful.';
            header('refresh: 5; url=login.php');
            exit();
        }while(0);
    }
    
?>
<!DOCTYPE html>
<html lang='en'>
    <head>
        <title>Sign up</title>
        <?php css();?>
    </head>
    <body>
        <div class='container'>
            <div class='main'>
                <div class='logbox'>
                    <p class='log'><strong>Reset password</strong></p>
                    <form action='<?php echo htmlentities($_SERVER["PHP_SELF"]); ?>' method='post'>
                        <label for='pwd1'>New password:</label>
                        <input type='password' id='pwd1' name='pwd1'/>
                        <label for='pwd2'>Repeat:</label>
                        <input type='password' id='pwd2' name='pwd2'/>
                        <input type='hidden' name=token value='<?php echo htmlentities($token);?>'>
                        <input type='hidden' name=uid value='<?php echo htmlentities($uid);?>'>
                        <?php
                        if(isset($errmsg)){
                            echo "<p class='err'>".$errmsg."</p>";
                        }
                        ?>
                        <input type='submit' value='Reset'>
                    </form>
                </div>
            </div>
        </div>