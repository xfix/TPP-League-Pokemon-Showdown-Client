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
<link rel=stylesheet href="/style/font-awesome.css" />
<link rel=stylesheet href="/style/battle.css">
<link rel=stylesheet href="/style/replay.css">
<a href="https://www.reddit.com/r/tppleague"><div class="pfx-topbar"></div></a>

	<div class="pfx-panel"><div class="pfx-body" style="max-width:1180px">
		<div class="wrapper replay-wrapper">

			<div class="battle"><div class="playbutton"><button data-action="start">Play</button></div></div>
			<div class="battle-log"></div>
			<div class="replay-controls">
				<button data-action="start"><i class="icon-play"></i> Start</button>
			</div>
			<div class="replay-controls-2">
				<div class="chooser leftchooser speedchooser">
					<em>Speed:</em>
					<div><button class="sel" value="fast">Fast</button><button value="normal">Normal</button><button value="slow">Slow</button><button value="reallyslow">Really Slow</button></div>
				</div>
				<div class="chooser soundchooser" style="display:none">
					<em>Music:</em>
					<div><button class="sel" value="on">On</button><button value="off">Off</button></div>
				</div>
			</div>
			<!--[if lte IE 8]>
				<div class="error"><p>&#3232;_&#3232; <strong>You're using an old version of Internet Explorer.</strong></p>
				<p>We use some transparent backgrounds, rounded corners, and other effects that your old version of IE doesn't support.</p>
				<p>Please install <em>one</em> of these: <a href="http://www.google.com/chrome">Chrome</a> | <a href="http://www.mozilla.org/en-US/firefox/">Firefox</a> | <a href="http://windows.microsoft.com/en-US/internet-explorer/products/ie/home">Internet Explorer 9</a></p></div>
			<![endif]-->

			<!--p class="linkbar">[<a href="./">Other replays</a>] [<a href="http://www.smogon.com/forums/showthread.php?t=3453192">Report bugs to the Smogon thread</a>]</p-->
			<div id="loopcount"></div>
		</div>
<script type="text/plain" class=log><?=str_replace('/', '\/', $log)?></script>

<script src="/js/lib/jquery-1.11.0.min.js"></script>
<script src="/js/lib/lodash.compat.js"></script>
<script src="/js/lib/backbone.js"></script>
<script src="https://pokemonshowdown.com/forum/js/panels.js"></script>

<script src="/js/lib/jquery-cookie.js"></script>
<script src="/js/lib/html-sanitizer-minified.js"></script>
<script src="/js/lib/soundmanager2-nodebug-jsmin.js?"></script>
<script>
	soundManager.setup({url: '/swf/'});
</script>
<script src="/js/battledata.js"></script>
<script src="/data/emoteregex.js"></script>
<script src="/data/pokedex-mini.js"></script>
<script src="/data/graphics.js"></script>
<script src="/js/battle.js"></script>
<script src="https://pokemonshowdown.com/js/replay.js"></script>
