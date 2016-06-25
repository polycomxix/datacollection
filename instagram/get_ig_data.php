<?php
	session_start();
	$root = $_SERVER['DOCUMENT_ROOT']."/datacollection";
	include_once("config.php");
	include_once("ig_class.php");
	include_once($root."/php/connect.php");


	$gpid = isset($_COOKIE['pid']) ? $_COOKIE['pid'] : null;
	echo "gpid:".$gpid."<br/>";

	if(isset($_SESSION['access_token']))
	{
		$access_token = $_SESSION['access_token'];
		echo "access_token:".$access_token;
		$instagram->setAccessToken($access_token);

		GetIGProfile();
		//GetIGUserLikes();
	}
	else
	{
		header('Location: ../instagram/callback.php');
	}
	
	

	

	function GetIGProfile()
	{
		global $instagram, $conn, $gpid;

		$response   = $instagram->getUser();
		$user 		= $response->data;
		//print_r($user);
		//echo "aa:".$user->username;

		$iguser = new profile();
		$iguser->id 			= $user->id;
		$iguser->username 		= $user->username;
		$iguser->post_count 	= $user->counts->media;
		$iguser->follower_count	= $user->counts->followed_by;
		$iguser->following_count= $user->counts->follows;

		//print_r($iguser);
		//Save Instagram Profile
		SaveIGProfile($iguser);
	}

	function GetIGUserLikes()
	{
		global $since_date, $instagram;
		$break=false;
		$iglikes = array();

		$response   = $instagram->getUserLikes(20);
		print_r($response);
	}
	function SaveIGProfile($iguser)
	{
		global $cookie_expired, $CURRENT_DATE, $gpid;
		$IsSuccess = true;
		$conn = CreateConnection();
		mysqli_query($conn, "START TRANSACTION");
		try{

			$result = IsCurrentUser('tb_user','ig_id',$iguser->id, $conn );
			$hasIGId = ($result->num_rows > 0) ? true : false;

			if(!$hasIGId)//new ig user
			{
				if($gpid!=null)//Current user
				{
					$pid = $gpid;
					$row = null;
					$result = IsCurrentUser('tb_user','pid',$pid, $conn);
					if($result->num_rows >0)
					{
						$row = $result->fetch_assoc();
					}

					//Update tb_user and Create tb_ig_profile
					if(UpdateUserProfile($iguser->id, $conn))
					{
						if(!CreateIGUserProfile($iguser, $pid, $conn))
							$IsSuccess = false;
					}
				}
				else// new user
				{
					//Create new user in tb_user
					$pid = CreateUserProfile($iguser->id, $conn);
					echo "Pid:". $pid."<br/>";
					if($pid!=false)
					{
						$gpid=$pid;
						if(!CreateIGUserProfile($iguser, $pid, $conn))
							$IsSuccess = false;
					}

				}
			}
			else //Update tb_ig_profile
			{
				if(!UpdateIGUserProfile($iguser, $conn))
					$IsSuccess = false;
			}
			mysqli_query($conn,"COMMIT");
		}catch(Exception $e){
			mysqli_query($conn,"ROLLBACK");
		}
		CloseConnection($conn);
		return $IsSuccess==true ? $iguser->id : $IsSuccess;
	}
	function IsCurrentUser($tb_name, $field, $val, $conn) //already has record in tb_user or tb_twitter profile
	{
		$s = "SELECT * FROM ".$tb_name." WHERE ".$field." = '$val'";
		$result = $conn->query($s);

		return $result;
	}
	function CreateUserProfile($ig_id, $conn)
	{
		$s = "INSERT INTO tb_user (ig_id, agreement) VALUES ('$ig_id', '1')";
		if (!mysqli_query($conn, $s)) {
						    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $s . "<br>" . mysqli_error($conn);
			return false;
		}
		else{
			return  mysqli_insert_id($conn);
		} 
		
	}
	function UpdateUserProfile($ig_id, $conn)
	{
		global $CURRENT_DATE;

		$s = 	"UPDATE tb_user SET updated_date= '$CURRENT_DATE' WHERE ig_id = '$ig_id'";
		if (!mysqli_query($conn, $s)) {
					    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $s . "<br>" . mysqli_error($conn);
			return false;
		}
		return true;

	}
	function CreateIGUserProfile($iguser, $pid, $conn)
	{
		global $access_token;
		$s = "INSERT INTO tb_ig_profile (user_id, pid, username, post_count, follower_count, following_count, access_token) ".
		"VALUES ('$iguser->id', '$pid', '$iguser->username', '$iguser->post_count', '$iguser->follower_count', '$iguser->following_count', '$access_token')";
		if (!mysqli_query($conn, $s)) {
							    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $s . "<br>" . mysqli_error($conn);
			return false;
		} 
		return true;
	}
	function UpdateIGUserProfile($iguser, $conn)
	{
		global $CURRENT_DATE, $access_token;
		echo $access_token;
		$s = "UPDATE tb_ig_profile SET username= '$iguser->username', post_count = '$iguser->post_count', follower_count='$iguser->follower_count', following_count='$iguser->following_count', access_token='$access_token', updated_date='$CURRENT_DATE' ".
		"WHERE user_id = '$iguser->id'";

		if (!mysqli_query($conn, $s)) {
					    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $s . "<br>" . mysqli_error($conn);
			return false;
		}
		else{
			$s = "SELECT pid FROM tb_ig_profile WHERE user_id = '$iguser->id';";
			$result = $conn->query($s);
			if ($result->num_rows > 0) {
				// output data of each row
				$row = $result->fetch_assoc();
				//print_r($row);
				return $row['pid'];
			}
			return false;
		}
		
	}
?>