<?php
	session_start();
	$root = $_SERVER['DOCUMENT_ROOT']."/datacollection";

	include_once("config.php");
	include_once($root."/php/connect.php");
	include_once("facebook_class.php");

	$gpid = isset($_COOKIE['pid']) ? $_COOKIE['pid'] : null;

	
	if(isset($_SESSION['access_token']))
	{
		$conn = CreateConnection();
		$access_token = $_SESSION['access_token'];
		$fbid = GetFacebookUserProfile($access_token);
		//echo "FBID:".$fbid."<br/>";
		if($fbid != false)
		{
			$result = GetFacebookQuizResult($access_token, $fbid);
			//print_r($result);
			//echo "RESULT: <hr/><br/>";
			echo json_encode($result);
		}
		CloseConnection($conn);
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
		$fuser->location 	= $user->getField('location') != null ? $user->getField('location')->getField('name') : null;
		$fuser->birthyear	= $user->getField('birthday') != null ? $user->getField('birthday')->format('Y') : null;
		$fuser->total_friend= $user->getField('friends')->getTotalCount();
		$fuser->token 		= $access_token;

		//SaveFacebookUserProfile
		mysqli_query($conn,"START TRANSACTION");
		try{
			//$gpid = 93;
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
					if(UpdateUserProfile($pid, $fb_id, $gender, $birthday, $country, $access_token))
					{
						//echo "Update User Profile";
						if(!CreateFacebookUserProfile($fuser,$pid))
							$IsSuccess = false;
						//echo $IsSuccess;
					}
				}
				else // new user
				{
					//Create new user in tb_user
					$pid = CreateUserProfile($fuser);
					//echo "PID:".$pid;
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
			
			mysqli_query($conn,"COMMIT");
		}catch(Exception $e){
			mysqli_query($conn,"ROLLBACK");
		}
		return $IsSuccess==true ? $fuser->id : $IsSuccess;
	}
	
	function GetFacebookQuizResult($access_token, $fbid)
	{
		global $fb;
		$albums_id = "";
		$pic = $count= array();
		$count[0]=$count[1]=$count[2]=$count[3]=$count[4]=0;

		$param = 'me/albums';
		$response = $fb->get($param, $access_token);
		$albums = $response->getGraphEdge();
		//print_r($albums);
		$i=1;
		while($albums)
		{
			foreach($albums as $a)
			{
				//echo $i." name:".$a->getField('name')."<br/>";
				if($a->getField('name')=="Profile Pictures")
				{
					$albums_id =  $a->getField('id');
					break;
				}
				$i++;	
			}
			$albums = $fb->next($albums);
		}
		//echo "albums id:".$albums_id."<br/>";

		$param = $albums_id.'/photos?fields=picture,images,created_time,likes.limit(1000)&limit=200';
		$response = $fb->get($param, $access_token);
		$photo = $response->getGraphEdge();

		foreach($photo as $p)
		{
			$year = date_format($p->getField('created_time'),'Y');
			//echo $year."<br/><img src='".$p->getField('picture')."' alt='' /><br/>";
			
			if($year ==2015){
				//echo "Year:".$year." ID:".$p->getField('id')." Total like:".count($p->getField('likes'))."<br/>";
				//print_r(end($p->getField('images')));
				//end($p->getField('images'));
				//print_r($p->getField('images')->getField('width'));
			}

			switch ($year) {
				case '2015':
					if(count($p->getField('likes'))>$count[0])
					{
						$count[0] 	= count($p->getField('likes'));
						$pic[0] 	= $p->getField('picture');
						/*foreach($p->getField('images') as $img)
						{
							if($img->getField('width')==225 || $img->getField('height')==225)
								$pic[0] = $img->getField('source');
						}*/
					}
					break;
				case '2014':
					if(count($p->getField('likes'))>$count[1])
					{
						$count[1] 	= count($p->getField('likes'));
						$pic[1] 	= $p->getField('picture');
						/*foreach($p->getField('images') as $img)
						{
							if($img->getField('width')==225 || $img->getField('height')==225)
								$pic[1] = $img->getField('source');
						}*/
					}
					break;
				case '2013':
					if(count($p->getField('likes'))>$count[2])
					{
						$count[2] 	= count($p->getField('likes'));
						$pic[2] 	= $p->getField('picture');
						/*foreach($p->getField('images') as $img)
						{
							if($img->getField('width')==225 || $img->getField('height')==225)
								$pic[2] = $img->getField('source');
						}*/
					}
					break;
				case '2012':
					if(count($p->getField('likes'))>$count[3])
					{
						$count[3] 	= count($p->getField('likes'));
						$pic[3] 	= $p->getField('picture');
						/*foreach($p->getField('images') as $img)
						{
							if($img->getField('width')==225 || $img->getField('height')==225)
								$pic[3] = $img->getField('source');
						}*/
					}
					break;
				default:
					# code...
					break;
			}
			
		}
		//echo "famous pic id:".$pic[0]."count: ".$count[0]."<br/>";
		/*echo "<img src='".$pic[3]."' alt='' />";
		echo "<img src='".$pic[2]."' alt='' />";
		echo "<img src='".$pic[1]."' alt='' />";
		echo "<img src='".$pic[0]."' alt='' />";*/
		$result = array(
						"success"	=>	true,
						"content"	=>	array(
								array("2012", isset($pic[3]) ? urlencode($pic[3]) : null, isset($count[3]) ? $count[3] : null),
								array("2013", isset($pic[2]) ? urlencode($pic[2]) : null, isset($count[2]) ? $count[2] : null),
								array("2014", isset($pic[1]) ? urlencode($pic[1]) : null, isset($count[1]) ? $count[1] : null),
								array("2015", isset($pic[0]) ? urlencode($pic[0]) : null, isset($count[0]) ? $count[0] : null)
						)
		);
		return $result;
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
	function UpdateUserProfile($pid, $fb_id, $gender, $birthday, $country, $access_token)
	{
		global $conn, $CURRENT_DATE;
		$s = 	"UPDATE tb_user SET fb_id = '$fb_id', gender='$gender', birthday='$birthday', country='$country', updated_date= '$CURRENT_DATE'".
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
		$sql = 	"INSERT INTO tb_facebook_profile (user_id, pid, name, total_friend, access_token) ".
				"VALUES ('$fuser->id','$pid','$fuser->name','$fuser->total_friend', '$fuser->token')";
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
		$sql = 	"UPDATE tb_facebook_profile SET name = '$fuser->name', total_friend='$fuser->total_friend', updated_date= '$CURRENT_DATE', access_token= '$fuser->token' ".
				"WHERE user_id = '$fuser->id'";
		if (!mysqli_query($conn, $sql)) {
					    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $sql . "<br>" . mysqli_error($conn);
			return false;
		} 
		return true;
	}

	
?>