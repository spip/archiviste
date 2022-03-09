<?php

// si on lance les tests depuis tests/ dans une installation SPIP ...
if (function_exists('include_spip')) {
    include_spip('inc/archives');
}
else {
	require_once __DIR__ . '/../vendor/autoload.php';
}
