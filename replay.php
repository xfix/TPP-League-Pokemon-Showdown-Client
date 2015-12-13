<?php
$db = pg_connect('dbname=showdown');

function to_id($name) {
	return preg_replace('/[^a-z0-9]/', '', strtolower($name));
}

if (!isset($_GET['id'])) {
	die('No replay ID specified.');
}

if (strpos($_GET['id'], '-') === FALSE) {
	$query = '
	SELECT replayid, rp2.userid, to_char(time, \'YYYY-MM-DD HH:MI\')
	FROM replays
	LEFT JOIN replay_players AS rp1 USING (replayid)
	LEFT JOIN replay_players AS rp2 USING (replayid)
	WHERE rp1.userid = $1 AND rp2.userid <> rp1.userid
	ORDER BY time DESC, replayid DESC;';

	$id = to_id($_GET['id']);
	$result = pg_query_params($query, array($id));
	$array_result = array();

	while (list ($replayid, $otherplayer, $time) = pg_fetch_row($result)) {
		$array_result[] = '<a href="/replay/' . htmlspecialchars(rawurlencode($replayid)) . '">' . htmlspecialchars($replayid) . '</a> - ' . htmlspecialchars($id) . ' vs ' . htmlspecialchars($otherplayer) . ' (' . htmlspecialchars($time) . ')';
	}

	if (!$array_result) {
		die('No results.');
	}
	echo '<!doctype html><title>', htmlspecialchars($id), ' replays</title><ul><li>', implode('<li>', $array_result), '</ul>';
	die;
}

$id = $_GET['id'];

$result = pg_query_params('SELECT format, log FROM replays
WHERE replayid = $1', array($id));

if (!list ($format, $log) = pg_fetch_row($result)) {
	die("Replay not found!");
}

$result = pg_query_params('SELECT userid FROM replay_players
WHERE replayid = $1 ORDER BY userid', array($id));

$users = array();

while (list ($user) = pg_fetch_row($result)) {
	$users[] = $user;
}

$joined_users = implode(" vs ", $users);

$title = "$format replay: $joined_users";

function h($text) {
	return htmlspecialchars($text);
}
?>
<!DOCTYPE html>
<title><?=h($title)?></title>
<meta charset=utf-8>
<link rel=stylesheet href="/style/replay.css">

<div class="wrapper replay-wrapper" style="max-width:1180px;margin:0 auto">
<div class="battle"></div><div class="battle-log"></div><div class="replay-controls"></div><div class="replay-controls-2"></div>
<h1 style="font-weight:normal;text-align:center"><strong><?=h($format)?></strong><br /><?=h($joined_users)?></h1>
<script type="text/plain" class="battle-log-data"><?=$log?></script>
</div>
<script src="https://play.pokemonshowdown.com/js/replay-embed.js"></script>
