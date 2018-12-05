<?php
session_start();
date_default_timezone_set ("America/Chicago");
// function for alert box and redirecting
//https://stackoverflow.com/questions/19825283/redirect-to-a-page-url-after-alert-button-is-pressed
function alertbox($msg, $page){
    echo "<script>";
    echo "alert('".htmlentities($msg)."');";
    echo "window.location.href ='".htmlentities($page)."';";
    echo "</script>";
    exit;
}

// Not log in, redirect to login page
if(!isset($_SESSION['username'])){
    alertbox("Please log in.","login.php");
}

// path config
$dir = "/srv/uploads/";
$userfolder = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang='en'>
    <head>
        <title>Welcome!</title>
	    <link rel="stylesheet" type="text/css" href="stylesheet.css">
    </head>
    <body>
        <h1>Welcome, <?php echo  htmlentities($_SESSION['username'])?>!</h1>
        <br>
        <hr>
        <h3>File List:</h3>
        <form action="delete.php" method="GET">
        <table>
            <tr>
                <th>&nbsp;&nbsp;&nbsp;Del&nbsp;&nbsp;&nbsp;</th>
                <th class="name">Name</th>
                <th class="wid">Upload Time</th>
                <th>Size</th>
            </tr>
            <tr>
                <td colspan="4">
                    <hr>
                </td>
            </tr>
            <?php
                // show list of uploaded file
                $fulldir = $dir.$userfolder;
                $filelist = scandir($fulldir);
                
                for($i = 2;$i < count($filelist); $i++){
                    $filedir = $fulldir."/".$filelist[$i];
                    $uptime = date("m/d/y H:i",filemtime($filedir));
                    $size = floor(filesize($filedir)/1024);
                    echo "<tr>";
                    echo sprintf("<td><input type='radio' name='file' value='%s'></td>",htmlentities($filelist[$i]));
                    echo sprintf("<td class='left'><a href='view.php?file=%s'>%s</a></td>",htmlentities($filelist[$i]),htmlentities($filelist[$i]));
                    echo sprintf("<td>%s</td>",htmlentities($uptime));
                    echo sprintf("<td>%s KB</td>",htmlentities($size));
                    echo "</tr>";
                }
            ?>
            <tr>
                <td colspan="4">
                    <hr>
                </td>
            </tr>
        </table>
        <p class="right">
            <input type="submit" value="Delete File" />  
        </p>
        </form>
        <br><br>
        <hr>
        <div class="topright"><a href='deluser.php'>delete user</a>&nbsp;&nbsp;&nbsp;<a href='logout.php'>log out</a></div>
        <!-- upload file -->
        <form enctype="multipart/form-data" action="uploader.php" method="POST">
            <p>
                <input type="hidden" name="MAX_FILE_SIZE" value="20000000" />
                <label for="uploadfile_input">Choose a file to upload:</label> <input name="uploadedfile" type="file" id="uploadfile_input" />
            </p>
            <p>
                <input type="submit" value="Upload File" />
            </p>
        </form>
            
    </body>
</html>