<?php
function to_id($name) {
	return preg_replace('/[^a-z0-9]/', '', strtolower($name));
}

class NTBBReplay {
	function prepUpload($reqData) {
		$id = $reqData['id'];
		$format = $reqData['format'];
		$log = $reqData['log'];
		$p1 = $reqData['p1'];
		$p2 = $reqData['p2'];

		pg_query('BEGIN');

		pg_query_params('DELETE FROM replay_players WHERE replayid = $1', array($id));

		pg_query_params('DELETE FROM replays WHERE replayid = $1', array($id));

		pg_query_params('INSERT INTO replays (replayid, format, log)
				VALUES ($1, $2, $3)', array($id, $format, $log));

		pg_query_params('INSERT INTO replay_players (replayid, userid)
				VALUES ($1, $2), ($1, $3);', array($id, to_id($p1), to_id($p2)));

		pg_query('COMMIT');
	}
}

$GLOBALS['Replays'] = new NTBBReplay;
