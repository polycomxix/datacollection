<?php
	session_start();
	include_once("config.php");

	if(isset($_SESSION['access_token']))
	{
		$access_token = $_SESSION['access_token'];
		$response = $fb->get('/me?fields=id,name', $access_token);
	  	// Get the base class GraphNode from the response
$graphNode = $response->getGraphNode();

// Get the response typed as a GraphUser
$user = $response->getGraphUser();

// Get the response typed as a GraphPage
$page = $response->getGraphPage();

// User example
echo "<br/> GraphNode".$graphNode->getField('name'); // From GraphNode
echo "<br/> GraphUser".$user->getName(); // From GraphUser

// Location example
echo "<br/> GraphNode".$graphNode->getField('country'); // From GraphNode
echo "<br/> GraphUser".$location->getCountry(); // From GraphLocation
	}
	else
	{
		header('Location: ../facebook/fbcallback.php');
	}
?>