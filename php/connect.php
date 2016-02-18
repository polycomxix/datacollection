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
	//echo "Connected successfully";

	/*$sql = "SELECT * FROM tb_user";
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
	    // output data of each row
	    while($row = $result->fetch_assoc()) {
	        //echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
	        print_r($row);
	    }
	} else {
	    echo "0 results";
	}
	$conn->close();*/

?>