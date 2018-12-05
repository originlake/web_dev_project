<?php
require 'toolbox.php';
$isVisitor = checkUser();
if($isVisitor){
    header("Location: login.php");
    exit();
}
if(isset($_POST['token'])){
    if($_POST['token'] != $_SESSION['token']){
        printf("CSRP failed");
        exit;
    }
    if(isset($_POST['reset'])){
        // set reset password token for validation
        $stmt = $mysqli->prepare('select COUNT(*) from validation where uid=?');
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('s',$_SESSION['uid']);
        $stmt->execute();
        $stmt->bind_result($cnt);
        $stmt->fetch();
        $stmt->close();
        if($cnt==1){
            $stmt = $mysqli->prepare('update validation set token=? where uid=?');
        }else{
            $stmt = $mysqli->prepare('insert into validation (token, uid) values (?,?)');
        }
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $token = bin2hex(openssl_random_pseudo_bytes(32));
        $stmt->bind_param('ss',$token, $_SESSION['uid']);
        $stmt->execute();
        $stmt->close();
        // get val_id
        $stmt = $mysqli->prepare('select val_id from validation where uid=?');
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('s',$_SESSION['uid']);
        $stmt->execute();
        $stmt->bind_result($val_id);
        $stmt->fetch();
        $stmt->close();
        // send email to user
        $stmt = $mysqli->prepare('select email from users where uid=?');
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('s',$_SESSION['uid']);
        $stmt->execute();
        $stmt->bind_result($email);
        $stmt->fetch();
        $stmt->close();
        $to='<'.$email.'>';
        $subject = 'reset password';
        $body = 'reset password:http://ec2-18-222-223-208.us-east-2.compute.amazonaws.com/~shuo/module3/group/reset.php?token='.$token.'&val_id='.$val_id;
        mailer($to, $subject, $body);
        $msg = 'A mail has been sent to your email.';
    }
}
?>
<!DOCTYPE html>
<html lang='en'>
    <head>
        <title>Home</title>
        <?php css();?>
    </head>
    <body>
        <div class='container'>
            <?php showNav($isVisitor);?>
            <div class='main'>
                <form action='profile.php' method='post'>
                    <input type='hidden' name='token' value='<?php echo htmlentities($_SESSION["token"]);?>'>
                    <input type='submit' name='reset' value='Reset Password'>
                </form>
                <?php
                if(isset($msg)){
                    echo sprintf('<p>%s</p>',$msg);
                    header('refresh: 5; url=profile.php');
                    exit();
                }
                ?>
            </div>
        </div>
    </body>
</html>