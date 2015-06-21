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

session_start();

$curuser = $_SESSION['curuser'];

class Users {
	// Probably exists because the main server is behind Cloudflare.
	function getIp() {
		return $_SERVER['REMOTE_ADDR'];
	}

	function login($name, $password) {
		/* Pretty sure this function writes $curuser. */
		global $curuser;
		if ($name === $curuser['username'] && $curuser['loggedin']) {
			return;
		}

		$userid = $this->userid($name);
		$verified = $this->passwordVerify($userid, $password);

		$_SESSION['curuser'] = $curuser = array(
			'loggedin' => $verified,
			'username' => $name,
			'userid' => $userid,
		);
	}

	function logout() {
		session_destroy();
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
		else {
			return NULL;
		}
	}

	function userid($name) {
		return preg_replace('/[^a-z0-9]/', "", strtolower($name));
	}

	function getAssertion($userid, $hostname, $userdata,
	                      $challenge_key, $challenge_value, $challenge_prefix) {
		global $pkey, $curuser;
		$user = $userdata ?: $curuser;
		$args = func_get_args();
		if ($userid === "" || substr($userid, 0, 5) === 'guest') {
			return ";;";
		}
		$status = 2;
		if ($this->passwordVerify($userid, "") === NULL) {
			$status = 1;
		}
		else if (!$user['loggedin'] || $userid !== $user['userid']) {
			return ";";
		}
		$message = "$challenge_value,$userid,$status," . time() . ",tokenhost";
		openssl_sign($message, $signature, $pkey, OPENSSL_ALGO_SHA1);
		return $message . ';' . bin2hex($signature);
	}

	function addUser($user, $password) {
		global $db;
		$user = $user['username'];
		$userid = $this->userid($user);
		$password = password_hash($password, PASSWORD_DEFAULT);
		$query = "
			INSERT INTO users (userid, password_hash)
			SELECT $1, $2
			WHERE NOT EXISTS (SELECT 1 FROM users WHERE userid = $1)
			RETURNING TRUE
		";
		$result = pg_query_params($db, $query, array($userid, $password));
		$success = pg_fetch_result($result, 0);
		if ($success) {
			return array(
				'loggedin' => true,
				'username' => $user,
				'userid' => $userid,
			);
		}
	}

	function modifyUser($user, $data) {
		$password = $data['password'];
		$query = "
			UPDATE users SET password_hash = $1 WHERE userid = $2
			RETURNING TRUE
		";
		$result = pg_query_params($query, array(password_hash($password, PASSWORD_DEFAULT), $user));
		$success = pg_fetch_result($result, 0);
		return $success;
	}

	// This is placeholder.
	function getRecentRegistrationCount() {
		return 0;
	}
}

$users = new Users;
