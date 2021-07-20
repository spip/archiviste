<?php

/**
 * Tests unitaires de l'API d'Archives
 * Production de fichiers
 */

use Spip\Archives\SpipArchives;

$test = 'emballer archives';
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

$destination = archiviste_repertoire_de_test() . '/';

foreach (SpipArchives::compressionsConnues as $format){

	$fichier = archiviste_fichier_de_test($format);
	$files_list = archiviste_generer_contenu_de_test(archiviste_contenu_de_test());

	$archive = new SpipArchives($fichier);
	if (!$archive->emballer($files_list)){
		var_dump($archive->erreur(), $archive->message());
		archiviste_finir_test("[$format] Echec emballer " . json_encode($files_list), $destination);
	}

	archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $destination);

	$infos = $archive->informer();
	if (!$infos or count($infos['fichiers'])!==count($files_list)){
		var_dump($infos);
		archiviste_finir_test("[$format] Echec emballer : nombre de fichiers incorrects", $destination);
	}

	archiviste_teste_deballer($fichier, $format);
}

archiviste_finir_test(false, $destination);
