<?php
	require_once 'lib/RestServer.php';
	require_once 'lib/HSO.php';

	$mode = 'debug'; // 'debug' or 'production'
	$server = new RestServer($mode);
	$server->refreshCache(); // uncomment momentarily to clear the cache if classes change in production mode

	$server->addClass('HSO');
	$server->handle();