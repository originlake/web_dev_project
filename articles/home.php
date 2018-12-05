<?php
    require 'toolbox.php';
    $isVisitor=checkUser();
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
                $stmt = $mysqli->prepare("select username, title, content, up_date, link, users.uid from stories join users on (stories.uid=users.uid) ORDER BY stories.story_id  DESC");
                if(!$stmt){
                    printf("Query Prep Failed: %s\n", $mysqli->error);
                    exit;
                }
                $stmt->execute();
                $result = $stmt ->get_result();
                while ($row = $result ->fetch_assoc()){
                    showstory($row["link"], $row["title"], $row["username"] ,$row["up_date"], substr($row["content"],0,200).'...');
                }
                ?>
            </div>
        </div>
        
    </body>
</html>