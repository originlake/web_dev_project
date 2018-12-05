<?php
require 'toolbox.php';
$isVisitor=checkUser();
if($isVisitor){
    header("Location: login.php");
    exit();
}
if(isset($_POST['cancel'])){
    header('Location: home.php');
    exit();
}
// CSRF
if(isset($_POST['token'])){
    if($_POST['token'] != $_SESSION['token']){
        printf("CSRP failed");
        exit;
    }
    
    if(isset($_POST['post']) && isset($_POST['title']) && isset($_POST['content'])){
        do{
            //check input
            if(trim($_POST['title'], ' \t') == false || trim($_POST['content'], ' \t') == false){
                $errmsg = 'empty input';
                break;
            }
            $title = trim($_POST['title'], ' \t');
            $content = $_POST['content'];   
            // check if title exists
            $stmt = $mysqli->prepare("SELECT COUNT(*) FROM stories WHERE title=?");
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('s', $title);
            $stmt->execute();
            $stmt->bind_result($cnt);
            $stmt->fetch();
            if($cnt==1){
                $errmsg = "title exists";
                break;
            }
            $stmt->close();
            // insert story
            $stmt = $mysqli->prepare("INSERT INTO stories (uid, title, content, up_date) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('sss', $_SESSION['uid'], $title, $content);
            $stmt->execute();
            $stmt->close();
            // create link
            $stmt = $mysqli->prepare("SELECT story_id FROM stories WHERE title=?");
            $stmt->bind_param('s', $title);
            $stmt->execute();
            $stmt->bind_result($story_id);
            $stmt->fetch();            
            $link = 'story.php?story_id='.$story_id;
            $story_id = $story_id;
            $stmt->close();
            
            $stmt = $mysqli->prepare("UPDATE stories set link=? WHERE story_id=?");
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('ss', $link, $story_id);
            $stmt->execute();
            $stmt->close();
            header("Location: ".$link);
            exit();
        }while(0);
    }
    if(isset($_POST['edit']) && isset($_POST['title']) && isset($_POST['content']) && isset($_POST['story_id'])){
        do{
            //check if current user is author of this story
            $stmt = $mysqli->prepare('select uid, link from stories where story_id=?;');
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('s', $_POST['story_id']);
            $stmt->execute();
            $stmt->bind_result($uid, $link);
            $stmt->fetch();
            if($uid != $_SESSION['uid']){
                header("Location: ".$link);
                exit();
            }
            $stmt->close();
            //check input
            if(trim($_POST['title'], ' \t') == false || trim($_POST['content'], ' \t') == false){
                $errmsg = 'empty input';
                break;
            }
            $title = trim($_POST['title'], ' \t');
            $content = $_POST['content'];
            // update story
            $stmt=$mysqli->prepare("UPDATE stories set title=?, content=?, up_date=CURRENT_TIMESTAMP where story_id=?;");
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('sss',$title,$content,$_POST['story_id']);
            $stmt->execute();
            $stmt->close();
            header("Location: ".$link);
            exit();
        }while(0);
    }
}
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <title>post story</title>
    <?php css();?>
</head>
<body>
    <div class='container'>
        <?php  showNav($isVisitor);?>
        <div class='main'>
            <div class='editor'>
                <form action='<?php echo htmlentities($_SERVER["PHP_SELF"]);?>' method='post'>
                    <p><strong>Post Story</strong></p>
                    <label for='title'>Title:</label>
                    <input type='text' name='title'
                    <?php
                    if(isset($_POST['edit'])){
                        $stmt = $mysqli->prepare('select title, content, uid from stories where story_id=?;');
                        if(!$stmt){
                            printf("Query Prep Failed: %s\n", $mysqli->error);
                            exit;
                        }
                        $stmt->bind_param('s', $_POST['story_id']);
                        $stmt->execute();
                        $stmt->bind_result($title, $content, $uid);
                        $stmt->fetch();
                        if($uid != $_SESSION['uid']){
                            header("Location: story.php?story_id=".$_POST['story_id']);
                            exit();
                        }
                    }
                    if(isset($_POST['title'])){
                        echo 'value="'.htmlentities($_POST['title']).'"';
                    }else if(isset($title)){
                        echo 'value="'.htmlentities($title).'"';
                    }
                    ?>
                    />
                    <label for='content'>Content:</label>
                    <?php
                        echo sprintf('<input type="hidden" name="token" value="%s"/>', $_SESSION['token']);
                        echo '';
                        if(isset($_POST['story_id'])){
                            echo '<input type="hidden" name="story_id" value="'.htmlentities($_POST['story_id']).'"/>';
                        }
                        echo '<textarea name="content" rows="14">';
                        if(isset($_POST['content'])){
                            echo htmlentities($_POST['content']);
                        }else if(isset($content)){
                            echo htmlentities($content);
                        }
                        echo '</textarea>';
                        if(isset($errmsg)){
                            echo "<p class='err'>".$errmsg."</p>";
                        }
                    ?>
                    <div class='submit'>
                        <input type='submit'
                        <?php
                        if(isset($_POST['edit'])){
                            echo "name='edit' value='Edit'";
                        }else{
                            echo "name='post' value='Post'";
                        }
                        ?>
                        >
                        <input type='submit' name='cancel' value='Cancel'>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>