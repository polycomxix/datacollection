<?php
	$servername = "localhost";
	$username = "root";
	$password = "1q2w3e4r5t";
	$dbname = "db_datacollection";

	// Create connection
	$conn = mysqli_connect($servername, $username, $password, $dbname);

	// Check connection
	if (!$conn) {
	    die("Connection failed: " . mysqli_connect_error());
	}


	$cookie_expired = time() + (60 * 20);//60sec*20 = 20mins 86400 = 1 day

	$CURRENT_DATE = date_format(new DateTime("now", new DateTimeZone('Asia/Tokyo')),'Y-m-d H:i:s');

	$since_date = new DateTime();
	$since_date->setDate(2015,1,1);
	$since_date = date_format($since_date,'Y-m-d H:i:s');
?>