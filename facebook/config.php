<?php 
	require_once __DIR__ . "/src/Facebook/autoload.php";
	
	//Initial app
	$fb = new Facebook\Facebook([
			'app_id' => '1686254331661192', // Replace {app-id} with your app id
		  	'app_secret' => 'e3012079dd3edc5527f0615dd81588c5',
		  	'default_graph_version' => 'v2.4',
		]);

	//Login helper with redirect
	$helper = $fb->getRedirectLoginHelper();
	$permissions = ['email','user_birthday','user_friends','user_location','user_posts', 'user_likes', 'user_photos']; //Optional permissions
?>