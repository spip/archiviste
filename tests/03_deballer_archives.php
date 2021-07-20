<?php

/**
 * Tests unitaires de l'API d'Archives
 * Extraction de fichiers
 */

use Spip\Archives\SpipArchives;

$test = 'deballer archives';
$remonte = "";
while (!is_file($remonte."test.inc") and !is_dir($remonte.'ecrire/'))
	$remonte = $remonte."../";
foreach ([$remonte."test.inc", $remonte."tests/test.inc", $remonte."tests/tests/legacy/test.inc"] as $f) {
	if (is_file($f)){
		require $f;
		break;
	}
}
if (!defined('_SPIP_TEST_INC')) {
	die('Impossible de trouver test.inc depuis ' .getcwd());
}
$ok = true;

require __DIR__ . '/TestCase.inc';
archiviste_nettoyer_environnement_test();

include_spip('inc/archives');

$destination = archiviste_repertoire_de_test();

foreach (SpipArchives::compressionsConnues as $format){

	$fichier = archiviste_fichier_de_test($format);
	archiviste_generer_archive_de_test($fichier, $format);

	archiviste_teste_deballer($fichier, $format);
}

archiviste_finir_test(false, $destination);
