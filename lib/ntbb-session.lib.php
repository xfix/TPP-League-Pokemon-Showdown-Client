<?php
/*
Free Pokemon Showdown session manager
Copyright (C) 2014 Konrad Borowski

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require 'pkey.php';
$pkey = openssl_pkey_get_private($pkey);

$db = pg_connect('dbname=showdown');

class Users {
	// Probably exists because the main server is behind Cloudflare.
	function getIp() {
		return $_SERVER['REMOTE_ADDR'];
	}

	function login($name, $password) {
		/* Pretty sure this function writes $curuser. */
		global $curuser;

		$curuser = array(
			'loggedin' => $this->passwordVerify($userid, $password),
			'username' => $name,
			'userid' => $userid,
		);
	}

	function passwordVerify($username, $password) {
		global $db;
		$userid = $this->userid($username);
		$result = pg_query_params(
			$db, 'SELECT password_hash FROM users WHERE userid = $1',
			array($userid)
		);
		$hash = pg_fetch_result($result, 0);
		if ($hash) {
			return password_verify($password, $hash);
		}
	}

	function userid($name) {
		return preg_replace('/[^a-z0-9]/', "", strtolower($name));
	}

	function getAssertion($userid, $hostname, $userdata,
	                      $challenge_key, $challenge_value, $challenge_prefix) {
		global $pkey;
		if ($userid === "" || substr($userid, 0, 5) === 'guest') {
			return ";;";
		}
		if (($a = $this->passwordVerify($userid, "")) !== NULL) {
			return ";";
		}
		$message = "$challenge_value,$userid,2," . time() . ",tokenhost";
		openssl_sign($message, $signature, $pkey, OPENSSL_ALGO_SHA1);
		return $message . ';' . bin2hex($signature);
	}
}

$users = new Users;
