<?php
    // Toolbox
    // In each page, require 'toolbox.php' at beginning
    session_start();
    
    require_once 'Mail.php';
    // init mysql
    $mysqli = new mysqli('localhost', 'module3', 'cse330', 'module3');
    if($mysqli->connect_errno) {
        printf("Connection Failed: %s\n", $mysqli->connect_error);
        exit;
    }
    
    // Call this to create meta data and link css file
    function css(){
        echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
        echo "<link rel='stylesheet' type='text/css' href='all.css'>";
    }
    // check visitor, return true if visitor, false if not
    function checkUser(){
        if(isset($_SESSION['uid'])){
            return false;
        }else{
            return true;
        }
    }
    // Show navigation bar
    function showNav($isVisitor){
        echo "<div class = 'navbar'><div class = 'navleft'><a href='home.php'>Home</a><a href='editor.php'>Post</a></div>";
        echo "<div class = 'navright'>";
        if($isVisitor){
            echo "<a href='signup.php'>Sign up</a><a href='login.php'>Log in</a>";
        }else{
            echo "<a href='profile.php'>Profile</a><a href='logout.php'>Log out</a>";
        }
        echo "<form action='search.php' method='post'><input type='text' name='search' placeholder = 'Search'>
            <input type='submit' value='Go'/></form> </div></div>";
    }
    
    // generate story grid
    // $url: story page url
    // $title: story title
    // $author: author
    // $date: story post date
    // $content: story content
    function showstory($url, $title, $author, $date, $content, $author_id=0, $story_id=0){
        // For delete and edit
        $manage = "";
        if(isset($_SESSION['uid']) && $_SESSION['uid'] == $author_id){
            $manage = sprintf("<form action='editor.php' method='post'>
                                <input type='hidden' name='token' value=%s>
                                <input type='hidden' name='story_id' value=%s>
                                <input type='submit' name='edit' value='edit'>
                              </form>
                              <form action='delete.php' method='post'>
                                <input type='hidden' name='token' value=%s>
                                <input type='hidden' name='story_id' value=%s>
                                <input type='submit' value='delete'>
                              </form>
                              ", htmlentities($_SESSION['token']),htmlentities($story_id),htmlentities($_SESSION['token']),htmlentities($story_id));
        }
        echo sprintf("<div class='storygrid'>
                        <div class='title'>
                            <div class='title1'>
                                <a href='%s'>%s</a>
                            </div>
                            <div class='manage'>
                                %s
                            </div>
                    </div>
                    <div class='info'>
                        <p>Posted by %s on %s</p>
                    </div>
                    <div class='brief'>
                        <pre>%s</pre>
                    </div>
                </div>",htmlentities($url),htmlentities($title),$manage,htmlentities($author),htmlentities($date),htmlentities($content));
    }
    // generate comment grid
    // $from: user who leave this comment
    // $time: time when leave this comment
    // $to: user who this comment reply to
    // $content: comment content
    function showcomment($story_id, $from, $time, $to, $content, $from_id = 0,$to_id = 0, $comment_id = 0){
        // For edit and delete
        $manage = '';
        if(isset($_SESSION['uid']) && $from_id == $_SESSION['uid']){
            $manage = sprintf("<form action='cmt_editor.php' method='post'>
                                <input type='hidden' name='token' value=%s>
                                <input type='hidden' name='comment_id' value=%s>
                                <input type='submit' name='edit' value='edit'>
                              </form>
                              <form action='delete.php' method='post'>
                                <input type='hidden' name='token' value=%s>
                                <input type='hidden' name='comment_id' value=%s>
                                <input type='submit' value='delete'>
                              </form>
                              ", htmlentities($_SESSION['token']),htmlentities($comment_id),htmlentities($_SESSION['token']),htmlentities($comment_id));
        }
        echo sprintf("<div class='commentgrid'>
                    <div class='commenter'>
                        <div class='comleft'>
                            <p>%s</p>
                            <p>%s</p>
                        </div>
                        <div class='comright'>
                            <a href='?story_id=%s&to=%s&to_id=%s#reply'>reply</a>
                        </div>
                    </div>
                    <div class='comment'>
                        <pre>@%s %s</pre>
                    </div>
                    <div class='manage'>
                        %s
                    </div>
                </div>",htmlentities($from), htmlentities($time), htmlentities($story_id),htmlentities($from), htmlentities($from_id),htmlentities($to),htmlentities($content), $manage);
    }
    // Use pear mail package to send email via gmail free smtp server.
    // https://stackoverflow.com/a/2748837
    function mailer($to, $subject, $body){
        $headers = array(
            'From' => '<goodgood.study4321@gmail.com>',
            'To' => $to,
            'Subject' => $subject
        );
        $smtp = Mail::factory('smtp', array(
            'host' => 'ssl://smtp.gmail.com',
            'port' => '465',
            'auth' => true,
            'username' => 'goodgood.study4321@gmail.com',
            'password' => 'Dayday.up'
        ));
        $mail = $smtp->send($to, $headers, $body);
        if (PEAR::isError($mail)) {
            printf("pear error!");
            exit();
        }
    }
?>
        
      