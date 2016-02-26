<?php
	session_start();
	include_once("config.php");
	include_once("../php/connect.php");
	include_once("facebook_class.php");

	$gpid = isset($_COOKIE['pid']) ? $_COOKIE['pid'] : null;

	if(isset($_SESSION['access_token']))
	{

		$access_token = $_SESSION['access_token'];
		GetFacebookUserProfile($access_token);

	}
	else
	{
		header('Location: ../facebook/fbcallback.php');
	}

	function GetFacebookUserProfile($access_token)
	{
		global $fb, $conn, $gpid;
		$IsSuccess = true;

		$response = $fb->get('/me?fields=id,name,gender,birthday,location,friends.summary(true)', $access_token);
		$user = $response->getGraphNode();
		//print_r($user);
		$fuser = new Profile();
		$fuser->id 			= $user->getField('id');
		$fuser->name 		= $user->getField('name');
		$fuser->gender  	= $user->getField('gender');
		$fuser->location 	= $user->getField('location')->getField('name');
		$fuser->birthyear	= $user->getField('birthday')->format('Y');
		$fuser->total_friend= $user->getField('friends')->getTotalCount();

		//SaveFacebookUserProfile
		try{
			//$pid = CreateUserProfile($fuser);
			//echo "PID:".$pid;
			//$success = CreateFacebookUserProfile($fuser,$pid);
			$gpid = 93;
			$result = IsCurrentUser('tb_user','fb_id',$fuser->id);
			$hasFacebookId = ($result->num_rows > 0) ? true : false;
			if(!$hasFacebookId)
			{
				if($gpid!=null)//Current user
				{
					//Update tb_user
					$pid =$gpid;
					$row = null;
					$result = IsCurrentUser('tb_user', 'pid', $pid);
					if ($result->num_rows > 0) {
					    // output data of each row
					    $row = $result->fetch_assoc();
					}
					$gender = $row['gender']== null ? $fuser->gender : $row['gender'];
					$birthday = $row['birthday']== null ? $fuser->birthyear : $row['birthday'];
					$country = $row['country']== null ? $fuser->location : $row['country'];
					$fb_id = $fuser->id;
					
					//Update tb_user and Create tb_facebook_profile
					if(UpdateUserProfile($pid, $fb_id, $gender, $birthday, $country))
					{
						echo "Update User Profile";
						if(!CreateFacebookUserProfile($fuser,$pid))
							$IsSuccess = false;
						echo $IsSuccess;
					}
				}
				else // new user
				{
					//Create new user in tb_user
					$pid = CreateUserProfile($fuser);
					echo "PID:".$pid;
					if($pid!=false)
					{
						$gpid=$pid; 
						if(!CreateFacebookUserProfile($fuser,$pid))
							$IsSuccess = false;
					} 
				}
			}
			else // Update tb_facebook_profile
			{
				if(!UpdateFacebookUserProfile($fuser))
					$IsSuccess = false;
			}
			

		}catch(Exception $e){

		}
	}
	
	function GetFacebookActivities()
	{

	}
	function IsCurrentUser($tb_name, $field, $val) //already has record in tb_user or tb_twitter profile
	{
		global $conn;
		$s = "SELECT * FROM ".$tb_name." WHERE ".$field." = '$val'";
		$result = $conn->query($s);

		return $result;
	}
	function CreateUserProfile($fuser)
	{
		global $conn;
		$sql = 	"INSERT INTO tb_user (fb_id, gender, birthday,country) ".
				"VALUES ('$fuser->id','$fuser->gender','$fuser->birthyear','$fuser->location')";
		if (!mysqli_query($conn, $sql)) {
						    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $sql . "<br>" . mysqli_error($conn);
			return false;
		}
		else{
			return mysqli_insert_id($conn);
		}

	}
	function UpdateUserProfile($pid, $fb_id, $gender, $birthday, $country)
	{
		global $conn, $CURRENT_DATE;
		$s = 	"UPDATE tb_user SET fb_id = '$fb_id', gender='$gender', birthday='$birthday', country='$country', updated_date= '$CURRENT_DATE' ".
				"WHERE pid = '$pid'";
		if (!mysqli_query($conn, $s)) {
					    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $s . "<br>" . mysqli_error($conn);
			return false;
		}
		return true;
	}
	function CreateFacebookUserProfile($fuser,$pid)
	{
		global $conn;
		$sql = 	"INSERT INTO tb_facebook_profile (user_id, pid, name, total_friend) ".
				"VALUES ('$fuser->id','$pid','$fuser->name','$fuser->total_friend')";
		if (!mysqli_query($conn, $sql)) {
							    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $sql . "<br>" . mysqli_error($conn);
			return false;
		} 
		return true;
	}
	function UpdateFacebookUserProfile($fuser)
	{
		global $conn, $CURRENT_DATE;
		$sql = 	"UPDATE tb_facebook_profile SET name = '$fuser->name', total_friend='$fuser->total_friend', updated_date= '$CURRENT_DATE' ".
				"WHERE user_id = '$fuser->id'";
		if (!mysqli_query($conn, $sql)) {
					    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $sql . "<br>" . mysqli_error($conn);
			return false;
		} 
		return true;
	}

	
?>