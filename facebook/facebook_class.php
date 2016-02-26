<?php
	class profile
	{
		var $id;
		function set_id($new_id) {$this->id = $new_id;}
		function get_id() {return $this->id;}

		var $name;
		function set_name($new_name) {$this->name = $new_name;}
		function get_name() {return $this->name;}

		var $gender;
		function set_gender($new_gender) {$this->gender = $new_gender;}
		function get_gender() {return $this->gender;}

		var $location;
		function set_location($new_location) {$this->location = $new_location;}
		function get_location() {return $this->location;}

		var $birthyear;
		function set_birthyear($new_birthyear) {$this->birthyear = $new_birthyear;}
		function get_birthyear() {return $this->birthyear;}

		var $total_friend;
		function set_total_friend($new_total_friend) {$this->total_friend = $new_total_friend;}
		function get_total_friend() {return $this->total_friend;}
	}
?>