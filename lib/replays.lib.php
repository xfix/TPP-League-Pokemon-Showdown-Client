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

function to_id($name) {
	return preg_replace('/[^a-z0-9]/', '', strtolower($name));
}

class Replay {
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

$GLOBALS['Replays'] = new Replay;
