<?php
$address = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
$PokemonServers = array(
	'showdown' => array(
		'server' => $address,
		'ipcache' => $address,
	),
);
