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
    if(isset($_POST['comment_id'])){
        $stmt = $mysqli->prepare('DELETE FROM comments WHERE comment_id=?;');
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('s',$_POST['comment_id']);
        $stmt->execute();
        $stmt->close();
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    if(isset($_POST['story_id'])){
        //delete corresponding comments first
        $stmt = $mysqli->prepare("delete from comments where story_id = ?");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('s',$_POST['story_id']);
        $stmt->execute();
        //delete corresponding story column
        $stmt = $mysqli->prepare("delete from stories where story_id = ?");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('s',$_POST['story_id']);
        $stmt->execute();
        $stmt->close();
        header("Location: home.php");
        exit;
    }
}
header("Location: home.php");
?>