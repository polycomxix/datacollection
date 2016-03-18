<?php
	session_start();
	include_once("config.php");
	include_once("../php/connect.php");
	include_once("twitter_class.php");
	require 'autoload.php';
	use Abraham\TwitterOAuth\TwitterOAuth;

	/*------------Initial Variable-------------*/
	const MAX_PAGE = 16;


	$tuser = new Profile();

	$gpid = isset($_COOKIE['pid']) ? $_COOKIE['pid'] : null;

	/*------------/Initial Variable-------------*/

	//Prepare twitter connection
	$access_token = $_SESSION['access_token'];
	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'] , $access_token['oauth_token_secret']);

	/*--STEP 1--*/
	GetTwitterProfile($connection);
	if(isset($tuser->id))
	{
		if(SaveTwitterProfile($tuser))
		{
			//echo "<br/> GET Tweet";
			//setcookie('pid', $gpid, $cookie_expired, "/");
			/*--STEP 2--*/
			//GetTwitterActivities($connection,$tuser->id);
			/*--STEP 3--*/
			//GetTwitterFavorite($connection,$tuser->id);
			/*--STEP 4--*/ //Return result
			$qresult = GetTwitterQuizResult($tuser->id);
			$result =  array(
							"avg"	=> $qresult
						);
			echo json_encode($result);
		}
		/*else{
			$_COOKIE['pid'] = null;
		}	*/
	}
	$conn->close();
	//print_r($tact);

	function GetTwitterQuizResult($userid)
	{
		global $conn;
		$sql = "SELECT COUNT(*) as total, DATEDIFF(MAX(created_at),MIN(created_at)) as diffdate FROM tb_tweet WHERE user_id = $userid AND action ='tweet'";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			// output data of each row
			$row = $result->fetch_assoc();
			//Calculate tweet activity per day
			return number_format($row['total']/$row['diffdate'],2);
		}
		return false;
		//SELECT `created_at` as period, COUNT(user_id) as frequency FROM `tb_tweet` GROUP BY floor(HOUR(`created_at`)/3)
		//GET tweet period usage
	}
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
		global $tact, $since_date, $conn;
		$tact = array();
		$k=1;
		//$userid="282918691";
		//Define Parameter
		$param = array("count"=>200,"user_id"=>$userid,"page"=>null);//oyoeoyo polycomxix tangjaidee 282918691

		//Prevent incase update
		$since_id=GetLatestUserTweet($conn, $userid);
		if($since_id!=null) //update more tweet data
		{
			$param['since_id']=$since_id;
		}
		//print_r($param);
		mysqli_query($conn,"START TRANSACTION");
		try{
			for($i=1; $i<=MAX_PAGE; $i++)//MAX_PAGE
			{
				$param['page'] = $i;
				$tweet = $tconn->get("statuses/user_timeline",$param);
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
				mysqli_query($conn,"COMMIT");
			}catch (Exception $e){
				mysqli_query($conn,"ROLLBACK");
			}
		//$conn->close();
	}

	function GetTwitterFavorite($tconn, $userid)
	{
		global $since_date, $conn;
		$favlist = array();
		$param = array("count"=>200,"user_id"=>$userid,"page"=>null);

		//Prevent incase update
		$since_id=GetLatestUserFav($conn, $userid);
		if($since_id!=null) //update more tweet data
		{
			$param['since_id']=$since_id;
		}

		mysqli_query($conn,"START TRANSACTION");
		try{
			for($i=1; $i<=MAX_PAGE; $i++)//MAX_PAGE
			{
					$param['page'] = $i;

					$favorite = $tconn->get("favorites/list",$param);
					if(empty($favorite))
						break;
					else
					{
						$break = false;
						$j = 1;
						foreach($favorite as $fav)
						{
							$created_at = date( 'Y-m-d H:i:s', strtotime($fav->created_at));
							if(empty($fav) || $created_at<$since_date)
							{
								$break = true;
								break;
							}
							$favorite_list = new favorites();
							$favorite_list->user_id 	= $userid;
							$favorite_list->fav_id 		= $fav->id;
							$favorite_list->parent_id	= $fav->id_str;
							$favorite_list->created_at	= $created_at;

							$favlist[] = $favorite_list;
							if($j%50==0)
							{
										//echo "<br/> J:".$j;
								SaveFavToDb($favlist);
								unset($favlist);
								$favlist = array();
							}
							
							//echo $i."-".$j.":".$fav->created_at."<br/>";
							$j++;
						}
						if(count($favlist)>0)
						{

							SaveFavToDb($favlist);
							unset($favlist);
							$favlist = array();
						}

						if($break) //no data, then stop looping
						break;
					}
					
			}
			mysqli_query($conn,"COMMIT");
		}catch(Exception $e){
			mysqli_query($conn,"ROLLBACK");
		}
	}

	function GetLatestUserTweet($conn, $userid)
	{
		$sql = "SELECT * FROM tb_tweet WHERE user_id= $userid AND created_at = (SELECT MAX(`created_at`) FROM tb_tweet)";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			// output data of each row
			$row = $result->fetch_assoc();
			//print_r($row);
			return $row['tweet_id'];
		}
		return null;
	}
	function GetLatestUserFav($conn, $userid)
	{
		$sql = "SELECT * FROM tb_tweet_fav WHERE user_id= $userid AND created_at = (SELECT MAX(`created_at`) FROM tb_tweet_fav)";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			// output data of each row
			$row = $result->fetch_assoc();
			//print_r($row);
			return $row['fav_id'];
		}
		return null;
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
			//echo "Error: " . $s . "<br>" . mysqli_error($conn);
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
			//echo "Error: " . $s . "<br>" . mysqli_error($conn);
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
			//echo "Error: " . $s . "<br>" . mysqli_error($conn);
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
			//echo "Error: " . $s . "<br>" . mysqli_error($conn);
			return false;
		}
		else{
			$s = "SELECT pid FROM tb_twitter_profile WHERE user_id = '$user->id';";
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

	function SaveTwitterProfile($user)
	{
		global $conn, $cookie_expired, $CURRENT_DATE, $gpid;
		$IsSuccess = true;

		mysqli_query($conn,"START TRANSACTION");
		try{
			//Check Twitter ID
			//$_COOKIE['pid']=77;
			$result 		= IsCurrentUser('tb_user','twitter_id',$user->id);
			$hasTwitterId	= ($result->num_rows > 0) ? true : false;

			if(!$hasTwitterId)
			{
				if($gpid!=null)//Current User
				{
					//Update tb_user
					//echo "<br/> Update user";
					$pid = $gpid;
					$country = null;
					$twitter_id = $user->id;

					$result = IsCurrentUser('tb_user', 'pid', $pid);
					if ($result->num_rows > 0) {
					    // output data of each row
						$row = $result->fetch_assoc();
						//echo "<br/>PID:".$row['pid'];
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
					//echo "<br/> New user";
					$pid = CreateUserProfile($user->id, $user->location);
					//echo "PID:".$pid;
					//Create new twitter profile in tb_twitter_profile
					if($pid!=false)
					{
						$gpid=$pid; 
						if(!CreateTwitterProfile($user,$pid))
							$IsSuccess = false;
					}
				}
			}
			else //Update tb_twitter_profile
			{
				$val = UpdateTwitterProfile($user);
				if(!$val)
				{
					$IsSuccess = false;
					
				}
				else{
					$gpid =$val;
					//echo "gpid:".$gpid;
				}
			}

			mysqli_query($conn,"COMMIT");
		}catch (Exception $e){
			mysqli_query($conn,"ROLLBACK");
		}
		//$conn->close();
		return $IsSuccess;
	}

	function SaveTweetActToDb($tacts)
	{
		global $conn, $CURRENT_DATE, $tuser, $gpid;
		//$_COOKIE['pid']=77;
		$pid = $gpid;
		//echo "<br/>".$pid;

		
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
			//echo "Error: " . $sql . "<br>" . mysqli_error($conn);
		} 


	}
	function SaveFavToDb($favs)
	{
		global $conn, $CURRENT_DATE, $tuser, $gpid;
		$pid = $gpid;

		$sql = "INSERT INTO tb_tweet_fav (user_id, fav_id, pid, parent_tweet_id, created_at) VALUES ";
		$i=1;
		foreach($favs as $fs)
		{
			if($i!=1)
				$sql .=", ";
			$sql .= "('$fs->user_id','$fs->fav_id','$pid','$fs->parent_id','$fs->created_at')";
			$i++;
		}
		if (!mysqli_query($conn, $sql)) {
							    //echo "Insert ID:".$pid."\r\n";
			//echo "Error: " . $sql . "<br>" . mysqli_error($conn);
		} 
	}

	?>