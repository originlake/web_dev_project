<?php
require 'toolbox.php';
$isVisitor=checkUser();
if($isVisitor){
    header("Location: login.php");
    exit();
}
if(isset($_POST['token'])){
    if($_POST['token'] != $_SESSION['token']){
        printf("CSRP failed");
        exit;
    }
    if(!isset($_POST['edit']) && isset($_POST['to']) && isset($_POST['content']) && isset($_POST['story_id'])){
        do{
            if(trim($_POST['content'], ' \t') == false){
                $errmsg = 'empty input';
                break;
            }
            $content = $_POST['content'];
            $stmt = $mysqli->prepare("INSERT INTO comments (story_id, uid, reply_id, content, com_date) VALUES (?, ?, ?, ?,CURRENT_TIMESTAMP)");
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('ssss', $_POST['story_id'], $_SESSION['uid'], $_POST['to'], $_POST['content']);
            $stmt->execute();
            $stmt->close();
        }while(0);
        header("Location: story.php?story_id=".$_POST['story_id']);
        exit();
    }
    if(isset($_POST['edit']) && isset($_POST['content']) && isset($_POST['story_id']) && isset($_POST['comment_id'])){
        do{
            if(trim($_POST['content'], ' \t') == false){
                $errmsg = 'empty input';
                break;
            }
            $content = $_POST['content'];
            $stmt = $mysqli->prepare("update comments set content=?, com_date=CURRENT_TIMESTAMP where comment_id=?;");
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('ss',$content, $_POST['comment_id']);
            $stmt->execute();
            $stmt->close();
        }while(0);
        header("Location: story.php?story_id=".$_POST['story_id']);
        exit();
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
                <div id='reply' class='reply'>
                    <div class='editor'>
                        <form action='cmt_editor.php' method='post'>
                        <?php
                        $stmt = $mysqli->prepare('select content, uid, story_id from comments where comment_id=?;');
                        if(!$stmt){
                            printf("Query Prep Failed: %s\n", $mysqli->error);
                            exit;
                        }
                        $stmt->bind_param('s', $_POST['comment_id']);
                        $stmt->execute();
                        $stmt->bind_result($content, $uid, $story_id);
                        $stmt->fetch();
                        if($uid != $_SESSION['uid']){
                            //header("Location: story.php?story_id=".$story_id);
                            exit();
                        }
                        $stmt->close();
                        ?>
                        <input type='hidden' name='token' value='<?php echo htmlentities($_SESSION["token"]);?>'>
                        <input type='hidden' name='comment_id' value='<?php echo htmlentities($_POST["comment_id"]);?>'>
                        <input type='hidden' name='story_id' value='<?php echo htmlentities($story_id);?>'>
                        <textarea name='content' rows='5'><?php echo htmlentities($content);?></textarea>
                        <div class='submit'>
                            <input type='submit' name='edit' value='edit'>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>