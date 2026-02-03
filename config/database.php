<?php

    $db_server = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "resort_reservation_db";
    $conn = "";


    $conn = mysqli_connect($db_server, 
                            $db_user, 
                            $db_pass, 
                            $db_name);

    if($conn){
        echo "You are connected";
    }
    else{
        echo "Not connected";
    }

?>