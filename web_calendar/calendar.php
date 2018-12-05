<?php

    header("Content-Type: application/json");
    ini_set("session.cookie_httponly", 1);
    session_start();
    
    $previous_ua = @$_SESSION['useragent'];
    $current_ua = $_SERVER['HTTP_USER_AGENT'];
    if(isset($_SESSION['useragent']) && $previous_ua !== $current_ua){
        die("Session hijack detected");
    }else{
        $_SESSION['useragent'] = $current_ua;
    }

    $mysqli = new mysqli('localhost', 'module5', 'cse330', 'module5');
    
    // check visitor, return true if not visitor
    function checkUser(){
        if(isset($_SESSION['uid']) && isset($_SESSION['token']) && isset($_POST['token'])){
            if(hash_equals($_SESSION['token'],$_POST['token'])){
                return true;
            }
        }
        return false;
    }
    //login
    function login(){
        if(isset($_POST['username']) && isset($_POST['password'])){
            global $mysqli;
            $stmt = $mysqli->prepare('SELECT COUNT(*), uid, password FROM users WHERE username=?');
            if(!$stmt){
                echo json_encode(array(
                    "status"=> false,
                    "msg" => "sql prepare error"
                ));
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
                $_SESSION['username'] = $user;
                echo json_encode(array(
                    "status"=> true,
                    "uid" => $uid,
                    "token" => $_SESSION['token']
                ));
                exit();
            }else{
                echo json_encode(array(
                    "status"=> false,
                    "msg" => "User not exists"
                ));
                exit;
            }
        }
    }
    // signup
    function signup(){
        if(isset($_POST['username']) && isset($_POST['password1']) && isset($_POST['password2'])){
            global $mysqli;
            $username = $_POST['username'];
            $pwd1 = $_POST['password1'];
            $pwd2 = $_POST['password2'];
            if(!preg_match('/^[a-zA-Z0-9]+([._]?[a-zA-Z0-9]+)*$/', $username)){
                echo json_encode(array(
                    "status"=> false,
                    "msg" => "username not valid"
                ));
                exit;
            }
            if($pwd1 != $pwd2){
                echo json_encode(array(
                    "status"=> false,
                    "msg" => "password not equal"
                ));
                exit;
            }
            if(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[#$^+=!*_@%&]).{6,}$/',$pwd1)){
                echo json_encode(array(
                    "status"=> false,
                    "msg" => "password not valid"
                ));
                exit;
            }
            $stmt = $mysqli->prepare('SELECT COUNT(*) FROM users WHERE username=?');
            if(!$stmt){
                echo json_encode(array(
                    "status"=> false,
                    "msg"=> "sql prepare error"
                ));
                exit;
            }
            $stmt->bind_param('s',$username);
            $stmt->execute();
            $stmt->bind_result($cnt);
            $stmt->fetch();
            if($cnt > 0){
                echo json_encode(array(
                    "status"=> false,
                    "msg" => "username exits"
                ));
                exit;
            }
            $stmt->close();
            $stmt = $mysqli->prepare('SELECT MAX(uid) FROM users');
            if(!$stmt){
                echo json_encode(array(
                    "status"=> false,
                    "msg"=> "sql prepare error"
                ));
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
            $pw_hash = password_hash($pwd1, PASSWORD_BCRYPT);
            $stmt = $mysqli->prepare("INSERT INTO users(uid, username, password)
                                     VALUES(?,?,?)");
            if(!$stmt){
                echo json_encode(array(
                    "status"=> false,
                    "msg"=> "sql prepare error"
                ));
                exit;
            }
            $stmt->bind_param('sss', $uid, $username, $pw_hash);
            $stmt->execute();
            $stmt->close();
            echo json_encode(array(
                "status"=> true  
            ));
            exit();
        }
    }
    
    //query events for one month
    function event(){
        if(!checkUser()){
            echo json_encode(array(
                "status"=> false,
                "msg" => "login status failed"
            ));
            exit();
        }
        global $mysqli;
        $date1 = $_POST['firstday'];
        $date2 = $_POST['lastday'];
        if(!preg_match('/^\d{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])$/', $date1)){
            echo json_encode(array(
                "status"=> false,
                "msg"=>"date format not valid"
            ));
            exit;
        }
        //  for time /^(0?\d|1\d|2[0123])\:(0?\d|[12345]\d)$/
        if(!preg_match('/^\d{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])$/', $date2)){
            echo json_encode(array(
                "status"=> false,
                "msg"=>"date format not valid"
            ));
            exit;
        }
        $stmt = $mysqli->prepare('SELECT eventid, title, date, time, tag FROM events WHERE uid=? AND date BETWEEN ? AND ?');
        if(!$stmt){
            echo json_encode(array(
                "status"=> false,
                "msg"=> "sql prepare error"
            ));
            exit;
        }
        $stmt->bind_param('sss', $_SESSION['uid'], $date1, $date2);
        $stmt->execute();
        $result = $stmt ->get_result();
        $events = array();
        $i=0;
        while ($row = $result ->fetch_assoc()){
            $i++;
            $events[] = array(
                        "eventid"=>$row['eventid'],
                        "title"=>$row['title'],
                        "date"=>$row['date'],
                        "time"=>$row['time'],
                        "tag"=>$row['tag']
            );
        }
        $stmt->close();
        echo json_encode(array(
            "status"=> true,
            "events"=> $events,
            "n" => $i
        ));
        exit;
    }
    // delete event
    function delete(){
        if(!checkUser()){
            echo json_encode(array(
                "status"=> false,
                "msg" => "login status failed"
            ));
            exit();
        }
        global $mysqli;
        $eventid = $_POST['eventid'];
        $stmt = $mysqli->prepare('DELETE FROM events WHERE eventid=?;');
        if(!$stmt){
            echo json_encode(array(
                "status"=> false,
                "msg"=> "sql prepare error"
            ));
            exit;
        }
        $stmt->bind_param('s',$eventid);
        $stmt->execute();
        $stmt->close();
        echo json_encode(array(
            "status"=> true
        ));
        exit;
    }
    
    //add events
    function addEvent(){
        if(!checkUser()){
            echo json_encode(array(
                "status"=> false,
                "msg" => "login status failed"
            ));
            exit();
        }
        
        global $mysqli;
        
        $stmt = $mysqli->prepare('SELECT MAX(eventid) FROM events');
        if(!$stmt){
            echo json_encode(array(
                "status"=> false    
            ));
            exit;
        }
        $stmt->execute();
        $stmt->bind_result($maxeventid);
        if($stmt->fetch()){
            $eventid = $maxeventid + 1;
        }
        else{
            $eventid = 1;
        }
        $stmt->close();
        
        $stmt = $mysqli->prepare("insert into events (title, uid, date, time, eventid, tag) values (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
          printf("Query Prep Failed: %s\n", $mysqli->error);
          exit;
        }
        $stmt->bind_param('ssssss', $_POST['title'], $_SESSION['uid'], $_POST['date'], $_POST['time'], $eventid, $_POST['tag']);
        
        if($stmt->execute()){
            echo json_encode(array(
                 "status" => true
            ));
            $stmt->close();
            exit();
        }
        else{
            echo json_encode(array(
                 "status" => false,
                 "message" => "Error AddEvent."
            ));
            $stmt->close();
            exit();
        }
    }
    //edit event
    function editEvent(){
        if(!checkUser()){
            echo json_encode(array(
                "status"=> false,
                "msg" => "login status failed"
            ));
            exit();
        }
        
        global $mysqli;
        $stmt = $mysqli->prepare("update events set title=?, date=?, time=?, tag=? where eventid=?");
        if (!$stmt) {
          printf("Query Prep Failed: %s\n", $mysqli->error);
          exit;
        }
        $stmt->bind_param('sssss', $_POST['title'], $_POST['date'], $_POST['time'], $_POST['tag'],$_POST['eventid']);
        
        if($stmt->execute()){
            echo json_encode(array(
                 "status" => true,
            ));
            $stmt->close();
            exit();
        }
        else{
            echo json_encode(array(
                 "status" => false,
                 "message" => "Error AddEvent."
            ));
            $stmt->close();
            exit();
        }
    }
    // share event
    function shareEvent(){
        if(!checkUser()){
            echo json_encode(array(
                "status"=> false,
                "msg" => "login status failed"
            ));
            exit();
        }
        global $mysqli;
        $stmt = $mysqli->prepare('SELECT COUNT(*), uid FROM users WHERE username=?');
        if(!$stmt){
            echo json_encode(array(
                "status"=> false,
                "msg" => "sql prepare error"
            ));
            exit;
        }
        $stmt->bind_param('s',$user);
        $user = $_POST['taruser'];
        $stmt->execute();
        
        $stmt->bind_result($cnt, $uid);
        $stmt->fetch();
        if($cnt < 1 || $uid==$_SESSION['uid']){
            echo json_encode(array(
                "status"=> false,
                "msg" => "User not exists"
            ));
            exit;
        }
        $stmt->close();
        $stmt = $mysqli->prepare('SELECT title, date, time, tag FROM events WHERE eventid=?');
        if(!$stmt){
            echo json_encode(array(
                "status"=> false,
                "msg"=> "sql prepare error"
            ));
            exit;
        }
        $stmt->bind_param('s', $_POST['eventid']);
        $stmt->execute();
        $stmt->bind_result($title, $date, $time, $tag);
        $stmt->fetch();
        $stmt->close();
        $title = $title.'(Shared from '.$_SESSION['username'].')';
        
        $stmt = $mysqli->prepare('SELECT MAX(eventid) FROM events');
        if(!$stmt){
            echo json_encode(array(
                "status"=> false    
            ));
            exit;
        }
        $stmt->execute();
        $stmt->bind_result($maxeventid);
        if($stmt->fetch()){
            $eventid = $maxeventid + 1;
        }
        else{
            $eventid = 1;
        }
        $stmt->close();
        
        $stmt = $mysqli->prepare("insert into events (title, uid, date, time, eventid, tag) values (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
          printf("Query Prep Failed: %s\n", $mysqli->error);
          exit;
        }
        $stmt->bind_param('ssssss', $title, $uid, $date, $time, $eventid, $tag);
        
        if($stmt->execute()){
            echo json_encode(array(
                 "status" => true
            ));
            $stmt->close();
            exit();
        }
        else{
            echo json_encode(array(
                 "status" => false,
                 "message" => "Error share event."
            ));
            $stmt->close();
            exit();
        }
        
    }
    
    if(isset($_POST['action'])){
        switch($_POST['action']){
            case 'login':
                login(); break;
            case 'signup':
                signup(); break;
            case 'add':
                addEvent(); break;
            case 'logout':
                session_destroy();          
                exit();
                break;
            case 'event':
                event();break;
            case 'delete':
                delete();break;
            case 'edit':
                editEvent(); break;
            case 'share':
                shareEvent(); break;
        }
    }
    
    // For any not valid request, return false
    echo json_encode(array(
        "status"=> false,
        "msg" => "other"
    ));
    exit;
    
?>