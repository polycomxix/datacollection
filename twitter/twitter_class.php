<?php
	class profile
	{
		var $id;
		function set_id($new_id) {$this->id = $new_id;}
		function get_id() {return $this->id;}

		var $screen_name;
		function set_screen_name($new_screen_name) {$this->screen_name = $new_screen_name;}
		function get_screen_name() {return $this->screen_name;}

		var $follower;
		function set_follower($new_follower) {$this->follower = $new_follower;}
		function get_follower() {return $this->id;}

		var $friend;
		function set_friend($new_friend) {$this->friend = $new_friend;}
		function get_friend() {return $this->friend;}

		var $favorite;
		function set_favorite($new_favorite) {$this->favorite = $new_favorite;}
		function get_favorite() {return $this->favorite;}

		var $statuse;
		function set_statuse($new_statuse) {$this->statuse = $new_statuse;}
		function get_statuse() {return $this->statuse;}

		var $location;
		function set_location($new_location) {$this->location = $new_location;}
		function get_location() {return $this->location;}

		var $joined_date;
		function set_joined_date($new_joined_date) {$this->joined_date = $new_joined_date;}
		function get_joined_date() {return $this->joined_date;}

	}

	class activities
	{
		var $user_id;
		function set_user_id($new_user_id) {$this->user_id = $new_user_id;}
		function get_user_id() {return $this->user_id;}

		var $tweet_id;
		function set_tweet_id($new_tweet_id) {$this->tweet_id = $new_tweet_id;}
		function get_tweet_id() {return $this->tweet_id;}

		var $action;
		function set_action($new_action) {$this->action = $new_action;}
		function get_action() {return $this->action;}

		var $media;
		function set_media($new_media) {$this->media = $new_media;}
		function get_media() {return $this->media;}

		var $source;
		function set_source($new_source) {$this->source = $new_source;}
		function get_source() {return $this->source;}

		var $created_at;
		function set_created_at($new_created_at) {$this->created_at = $new_created_at;}
		function get_created_at() {return $this->created_at;}
	}

	class favorites
	{
		var $user_id;
		function set_user_id($new_user_id) {$this->user_id = $new_user_id;}
		function get_user_id() {return $this->user_id;}

		var $fav_id;
		function set_fav_id($new_fav_id) {$this->fav_id = $new_fav_id;}
		function get_fav_id() {return $this->fav_id;}

		var $parent_id;
		function set_parent_id($new_parent_id) {$this->parent_id = $new_parent_id;}
		function get_parent_id() {return $this->parent_id;}

		var $created_at;
		function set_created_at($new_created_at) {$this->created_at = $new_created_at;}
		function get_created_at() {return $this->created_at;}

	}

?>