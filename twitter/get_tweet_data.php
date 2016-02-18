<?php
	session_start();
	include_once("config.php");
	include_once("../php/connect.php");
	include_once("twitter_class.php");
	require 'autoload.php';
	use Abraham\TwitterOAuth\TwitterOAuth;

	//Initial Variable
	const MAX_PAGE = 16;
	

	$tuser = new Profile();
	//$tact = array();

	$cookie_expired = time() + (60 * 20);//60sec*20 = 20mins 86400 = 1 day

	$since_date = new DateTime();
	$since_date->setDate(2015,1,1);
	$since_date = date_format($since_date,'Y-m-d H:i:s');

	$CURRENT_DATE = date_format(new DateTime("now", new DateTimeZone('Asia/Tokyo')),'Y-m-d H:i:s');
	//Prepare twitter connection
	$access_token = $_SESSION['access_token'];
	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'] , $access_token['oauth_token_secret']);

	//echo "PID:".$_COOKIE['pid'];
	GetTwitterProfile($connection);
	echo "TID:".$tuser->id;
	GetTwitterActivities($connection,$tuser->id);
	//print_r($tact);
	//SaveTwitterProfile($tuser);


	function GetTwitterProfile($tconn)
	{
		global $tuser;
		$user = $tconn->get("account/verify_credentials");

		$tuser->id 			= $user->id;
		$tuser->screen_name = $user->screen_name;
		$tuser->follower	= $user->followers_count;
		$tuser->friend		= $user->friends_count;
		$tuser->favorite	= $user->favourites_count;
		$tuser->statuse		= $user->statuses_count;

		$tuser->joined_date	= date( 'Y-m-d H:i:s', strtotime($user->created_at));
		$tuser->location 	= $user->location != null ? $user->location : $user->time_zone;

	}

	function GetTwitterActivities($tconn, $userid)
	{	
		global $tact, $since_date;
		$tact = array();
		$k=1;
		for($i=1; $i<=MAX_PAGE; $i++)//MAX_PAGE
		{
				$tweet = $tconn->get("statuses/user_timeline",array("count"=>200,"screen_name"=>"tangjaidee","page"=>$i));//oyoeoyo polycomxix tangjaidee
				if(empty($tweet))
					break;
				else
				{
					$break = false;
					$j = 1;
					foreach($tweet as $t)
					{
						//echo "j:".$j;
						$created_at = date( 'Y-m-d H:i:s', strtotime($t->created_at));
						if(empty($t) || $created_at<$since_date)
						{
							$break = true;
							break;
						}

						$tweetact = new Activities();
						$tweetact->user_id 		= $userid;
						$tweetact->tweet_id 	= $t->id;

						//action tweet|retweet|reply-user|reply-tweet|reply-screenname
						$action = "tweet";
						if($t->retweeted){
							$action="retweeted";}
						else if($t->in_reply_to_user_id!=null){
							$action="reply-user";}
						else if($t->in_reply_to_status_id!=null){
							$action="reply-tweet";}
						else if($t->in_reply_to_screen_name!=null){
							$action="reply-screenname";}

						$tweetact->action 		= $action;

						$tweettype = "text";
						if(isset($t->entities->media))
						{
							if(isset($t->entities->media->type))
								$tweettype = $t->entities->media->type;
						}

						$tweetact->media 		= $tweettype;
						$tweetact->source 		= GetTextFromTag($t->source);
						$tweetact->created_at 	= date( 'Y-m-d H:i:s', strtotime($t->created_at));

						$tact[] = $tweetact;
						if($j%50==0)
						{
							//echo "<br/> J:".$j;
							SaveTweetActToDb($tact);
							unset($tact);
							$tact = array();
						}
						//echo $k."-".$tweetact->tweet_id."-".$tweetact->created_at."<br/>"; 
						$j++; $k++;
						//print_r($tact);
					}
					//echo "COUNT:".count($tact);
					if(count($tact)>0)
					{
						//echo "a <br/>";
						SaveTweetActToDb($tact);
						unset($tact);
						$tact = array();
					}
						

					if($break) //no data, then stop looping
						break;
					
				}

				//print_r($tact);
				
		}

	}
	function SaveTweetActToDb($tacts)
	{
		global $conn, $CURRENT_DATE, $tuser;
		//$_COOKIE['pid']=77;
		$pid = 77;

		mysqli_query($conn,"START TRANSACTION");
		try{
			$sql = "INSERT INTO tb_tweet (user_id, pid, tweet_id, action, media, source, created_at) VALUES ";
			$i=1;
			foreach($tacts as $ts)
			{
				if($i!=1)
					$sql .=", ";

				$sql .= "('$ts->user_id','$pid','$ts->tweet_id','$ts->action','$ts->media','$ts->source','$ts->created_at')";
				$i++;
			}
			//echo $sql;
			if (!mysqli_query($conn, $sql)) {
							    //echo "Insert ID:".$pid."\r\n";
				echo "Error: " . $sql . "<br>" . mysqli_error($conn);
			} 
			
			mysqli_query($conn,"COMMIT");
		}catch (Exception $e){
			mysqli_query($conn,"ROLLBACK");
		}
		//$conn->close();
	}

	function GetTextFromTag($data)
	{
		$regex = '#<\s*?a\b[^>]*>(.*?)</a\b[^>]*>#s';
		preg_match_all($regex, $data, $source);
		return $source[1][0];
	}

	function IsCurrentUser($tb_name, $field, $val) //already has record in tb_user or tb_twitter profile
	{
		global $conn;
		$s = "SELECT * FROM ".$tb_name." WHERE ".$field." = '$val'";
		$result = $conn->query($s);

		return $result;
	}
	function CreateUserProfile($twitter_id, $country)
	{
		global $conn;
		$s = 	"INSERT INTO tb_user (twitter_id, country, agreement) ".
				"VALUES ('$twitter_id','$country','1')";
		if (!mysqli_query($conn, $s)) {
						    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $s . "<br>" . mysqli_error($conn);
			return false;
		}
		else{
			return mysqli_insert_id($conn);
		} 
	}
	function UpdateUserProfile($pid, $twitter_id, $country)
	{
		global $conn, $CURRENT_DATE;
		$s = 	"UPDATE tb_user SET twitter_id = '$twitter_id', country='$country', updated_date= '$CURRENT_DATE' ".
				"WHERE pid = '$pid'";
		if (!mysqli_query($conn, $s)) {
					    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $s . "<br>" . mysqli_error($conn);
			return false;
		}
		return true;
	}
	function CreateTwitterProfile($user,$pid)
	{
		global $conn;
		$s = 	"INSERT INTO tb_twitter_profile (user_id, pid, screen_name, followers_count, friends_count, favourites_count, statuses_count, joined_date) ".
				"VALUES ('$user->id','$pid','$user->screen_name','$user->follower','$user->friend','$user->favorite','$user->statuse','$user->joined_date')";
		if (!mysqli_query($conn, $s)) {
							    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $s . "<br>" . mysqli_error($conn);
			return false;
		} 
		return true;
	}
	function UpdateTwitterProfile($user)
	{
		global $conn, $CURRENT_DATE;
		$s = 	"UPDATE tb_twitter_profile SET screen_name = '$user->screen_name', followers_count='$user->follower', friends_count='$user->friend',".
				"favourites_count='$user->favorite', statuses_count='$user->statuse', joined_date='$user->joined_date', updated_date= '$CURRENT_DATE' ".
				"WHERE user_id = '$user->id'";
		if (!mysqli_query($conn, $s)) {
					    //echo "Insert ID:".$pid."\r\n";
			echo "Error: " . $s . "<br>" . mysqli_error($conn);
			return false;
		} 
		return true;
	}

	function SaveTwitterProfile($user)
	{
		global $conn, $cookie_expired, $CURRENT_DATE;
		$IsSuccess = true;

		mysqli_query($conn,"START TRANSACTION");
		try{
			//Check Twitter ID
			//$_COOKIE['pid']=77;
			$result 		= IsCurrentUser('tb_user','twitter_id',$user->id);
			$hasTwitterId	= ($result->num_rows > 0) ? true : false;

			if(!$hasTwitterId)
			{
				if(isset($_COOKIE['pid']))//Current User
				{
					//Update tb_user
					$pid = $_COOKIE['pid'];
					$country = null;
					$twitter_id = $user->id;

					$result = IsCurrentUser('tb_user', 'pid', $pid);
					if ($result->num_rows > 0) {
					    // output data of each row
					    $row = $result->fetch_assoc();
					    echo "<br/>PID:".$row['pid'];
					    $country 	= $row['country'];
					}
					$country = $country== null ? $user->location : $country;
					
					//Update tb_user and Create tb_twitter_profile
					if(UpdateUserProfile($pid, $twitter_id, $country))
					{
						if(!CreateTwitterProfile($user,$pid))
							$IsSuccess = false;
					}
				}
				else //new user
				{
					//Create new user in tb_user
					$pid = CreateUserProfile($user->id, $user->location);
					echo "PID:".$pid;
					//Create new twitter profile in tb_twitter_profile
					if($pid!=false)
					{
						setcookie('pid', $pid, $cookie_expired, "/"); 
						if(!CreateTwitterProfile($user,$pid))
							$IsSuccess = false;
					}
				}
			}
			else //Update tb_twitter_profile
			{
				if(!UpdateTwitterProfile($user))
					$IsSuccess = false;
			}

			mysqli_query($conn,"COMMIT");
		}catch (Exception $e){
			mysqli_query($conn,"ROLLBACK");
		}
		$conn->close();
	}

?>