<html>
<head>
<link rel="stylesheet" type="text/css" href="style.css">
</head> 

<?php
include 'config.php';
include 'utils.php';

//session_start();

if(isset($_GET['logout'])) {        
        session_destroy();    
        echo "Logout erfolgreich";
}
  
showUserLogin();
               
//Abfrage der Nutzer ID vom Login
$userid = $_SESSION['userid'];

showOrderRefreshed();
showOrderStarted();

    echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<title></title>";
    echo "</head>";
    echo "<body>";       
                              

        echo getOrderState();
        switch (getOrderState()) {
            case 0:
                orderNotStarted();
                break;
            case 1:
                orderRunning();
                break;
            case 2:                
                orderFinished();
                break;
        }
        ?>      
    </body>
</html>