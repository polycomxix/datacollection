<?php
	require_once __DIR__ ."/src/Instagram.php";
	use MetzWeb\Instagram\Instagram;
	define('CLIENT_ID','bec029c9d2c44d329f870d2ba0388754');
	define('CLIENT_SECRET','00c1ffe4df984940bd7ca7964810c38b');
	define('CALLBACK','http://localhost:800/datacollection/instagram/callback.php');

	$instagram = new Instagram(array(
		'apiKey' => CLIENT_ID,
		'apiSecret' => CLIENT_SECRET,
		'apiCallback' => CALLBACK, //Callback URL
		'scope' => array('basic','likes','comments', 'relationships', 'public_content')
	));
?>