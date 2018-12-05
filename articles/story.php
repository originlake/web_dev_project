<?php
    require 'toolbox.php';
    $isVisitor=checkUser();
    if(!isset($_GET['story_id']) || !is_numeric($_GET['story_id'])){
        header('Location: home.php');
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
                <?php
                $stmt = $mysqli->prepare("select username, title, content, up_date, link, users.uid from stories join users on (stories.uid=users.uid) where story_id=?;");
                if(!$stmt){
                    printf("Query Prep Failed: %s\n", $mysqli->error);
                    exit;
                }
                $stmt->bind_param('s', $_GET['story_id']);
                $stmt->execute();
                $stmt->bind_result($author, $title, $content, $date, $link, $author_id);
                $stmt->fetch();
                showstory($link, $title, $author, $date, $content, $author_id=$author_id, $story_id=$_GET['story_id']);
                $stmt->close();
                ?>
                <br><br>
                <p class='head'>Comments:</p>
                <?php
                $stmt = $mysqli->prepare('SELECT comment_id ,uid as from_id, (SELECT username FROM users WHERE uid=from_id) as from_name, reply_id as to_id, (select username from users where uid=to_id) as to_name, content, com_date from comments where story_id=? order by com_date ASC;');
                if(!$stmt){
                    printf("Query Prep Failed: %s\n", $mysqli->error);
                    exit;
                }
                $stmt->bind_param('s', $_GET['story_id']);
                $stmt->execute();
                $stmt->bind_result($comment_id, $from_id, $from, $to_id, $to, $content, $com_date);
                while($stmt->fetch()){
                    showcomment($_GET['story_id'], $from, $com_date, $to, $content, $from_id=$from_id, $to_id=$to_id, $comment_id=$comment_id);
                }
                ?>
                <div id='reply' class='reply'>
                    <div class='editor'>
                        <form action='cmt_editor.php' method='post'>
                        <?php
                        if(isset($_GET['to'])){
                            $to = $_GET['to'];
                            $to_id = $_GET['to_id'];
                        }
                        else{
                            $to = $author;
                            $to_id = $author_id;
                        }
                        ?>
                        <input type='hidden' name='token' value='<?php echo $_SESSION["token"];?>'>
                        <input type='hidden' name='to' value='<?php echo htmlentities($to_id);?>'>
                        <input type='hidden' name='story_id' value='<?php echo htmlentities($_GET['story_id']);?>'>
                        <textarea name='content' rows='5' placeholder='reply to <?php echo htmlentities($to)?>'></textarea>
                        <div class='submit'>
                            <input type='submit' value='reply'>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>