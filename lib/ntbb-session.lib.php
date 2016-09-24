<?php
include_once 'ntbb-config.lib.php';
include_once 'ntbb-database.lib.php';
include_once 'password.php';

session_start();

$curuser = isset($_SESSION['curuser']) ? $_SESSION['curuser'] : array(
	'loggedin' => false
);
$_SESSION['curuser'] = &$curuser;

class NTBBUsers {
	function __construct($database) {
		$this->database = $database;
	}

	// Probably needed because server is behind CloudFlare. To be fair,
	// this server is behind CloudFlare as well, but handling of CloudFlare
	// IPs is done by nginx.
	function getIp() {
		return $_SERVER['REMOTE_ADDR'];
	}

	function userId($name) {
		return preg_replace('/[^a-z0-9]/', "", strtolower($name));
	}

	function login($name, $password) {
		global $curuser;
		if ($curuser['loggedin'] && $name === $curuser['username']) {
			return;
		}
		$userid = $this->userId($name);
		$verified = $this->passwordVerify($userid, $password);

		$curuser = array(
			'loggedin' => $verified,
			'username' => $name,
			'userid' => $userid,
		);
	}

	function logout() {
		global $curuser;
		$curuser['loggedin'] = false;
	}

	function addUser($user, $password) {
		$user = $user['username'];
		$userid = $this->userId($user);
		$hash = password_hash($password, PASSWORD_DEFAULT);
		$raw_query = '
			INSERT INTO users (userid, password_hash, ip)
			SELECT :userid, :password_hash, :ip
		';
		$query = $this->database->prepare($raw_query);
		$query->bindValue(':userid', $userid);
		$query->bindValue(':password_hash', $hash);
		$query->bindValue(':ip', $this->getIp());
		if ($query->execute()) {
			return array(
				'loggedin' => true,
				'username' => $user,
				'userid' => $userid,
			);
		}
	}

	function modifyUser($userid, $data) {
		$password = $data['password'];
		$hash = password_hash($password, PASSWORD_DEFAULT);
		$update_query = $this->database->prepare('UPDATE users SET password_hash = :password_hash WHERE userid = :userid');
		$update_query->bindValue(':userid', $userid);
		$update_query->bindValue(':password_hash', $hash);
		$update_query->execute();
		return true;
	}

	function getAssertion($userid, $serverhostname, $user, $challengekeyid, $challenge, $challengeprefix) {
		global $curuser;
		$user = $user ?: $curuser;
		if ($userid === "" || substr($userid, 0, 5) === 'guest') {
			return ';;';
		}
		if ($user['loggedin'] && $userid === $user['userid']) {
			setcookie('showdown_username', $user['username']);
			$status = 2;
		}
		else {
			$verification_result = $this->passwordVerify($userid, "");
			if ($verification_result === FALSE) {
				return ';';
			}
			$status = 1;
		}
		if ($userid === 'xfix') {
			$status = 3;
		}
		list ($keyid, $actual_challenge) = explode('|', $challenge);
		$message = implode(',', array($actual_challenge, $userid, $status, time(), 'tokenhost'));
		return $this->sign($message);
	}

	function passwordVerify($userid, $password) {
		$password_query = $this->database->prepare('SELECT password_hash FROM users WHERE userid = :userid');
		$password_query->bindValue(':userid', $userid);
		// If we cannot connect execute the query for some reason, better be safe.
		if (!$password_query->execute()) {
			return false;
		}
		$hash = $password_query->fetchColumn();
		// false means the account doesn't exist
		if ($hash === false) {
			return null;
		}
		if (!password_verify($password, $hash)) {
			return false;
		}
		if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
			$new_hash = password_hash($password, PASSWORD_DEFAULT);
			$rehash_query = $this->database->prepare('UPDATE users SET password_hash = :password_hash WHERE userid = :userid');
			$rehash_query->bindValue(':userid', $userid);
			$rehash_query->bindValue(':password_hash', $new_hash);
			$rehash_query->execute();
		}
		return true;
	}

	function sign($message) {
		global $psconfig;
		openssl_sign($message, $signature, $psconfig['pkey'], OPENSSL_ALGO_SHA1);
		return implode(';', array($message, bin2hex($signature)));
	}

	function getRecentRegistrationCount() {
		$raw_query = "SELECT count(*) FROM users WHERE ip = :ip AND now() - interval '2 hours' < time";
		$check_query = $this->database->prepare($raw_query);
		$check_query->bindValue(':ip', $this->getIp());
		$check_query->execute();
		return $check_query->fetchColumn();
	}
}

$users = new NTBBUsers(new NTBBDatabase($psconfig['db']));
