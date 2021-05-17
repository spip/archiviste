<?php

/**
 * Tests unitaires de l'API d'Archives
 * Production, mise à jour de fichiers
 */

use Spip\Archives\SpipArchives;

$test = 'retirer archives';
$remonte = '../';
while (!is_dir($remonte . 'ecrire')) {
	$remonte = "../$remonte";
}
require $remonte . 'tests/test.inc';
$ok = true;

require __DIR__ . '/TestCase.inc';
archiviste_nettoyer_environnement_test();

include_spip('inc/archives');

$destination = archiviste_repertoire_de_test();

foreach (SpipArchives::compressionsConnues as $format){

	$fichier = archiviste_fichier_de_test($format);
	archiviste_generer_archive_de_test($fichier, $format);

	$archive = new SpipArchives($fichier);

	$infos = $archive->informer();
	$nb_files = count($infos['fichiers']);

	if (!$archive->retirer(array('test.txt'))){
		var_dump($archive->erreur(), $archive->message());
		archiviste_finir_test("[$format] Echec retirer [test.txt]", $destination);
	}

	$infos = $archive->informer();
	if (count($infos['fichiers'])!==$nb_files-1){
		var_dump($infos);
		archiviste_finir_test("[$format] retirer [test.txt] : nombre de fichiers innatendus apres", $destination);
	}

	@unlink($fichier);
	archiviste_generer_archive_de_test($fichier, $format);
	if (!$archive->retirer(array('sousrep/fichier'))){
		var_dump($archive->erreur(), $archive->message());
		archiviste_finir_test("[$format] Echec retirer [sousrep/fichier]", $destination);
	}

	$infos = $archive->informer();
	if (count($infos['fichiers'])!==$nb_files-1){
		var_dump($infos);
		archiviste_finir_test("[$format] retirer [sousrep/fichier] : nombre de fichiers innatendus apres", $destination);
	}


	if (!$archive->retirer(array('dir/fichierinexistant'))
		or $archive->erreur()){
		var_dump($archive->erreur(), $archive->message());
		archiviste_finir_test("[$format] Echec retirer [dir/fichierinexistant] n'aurait pas du produire une erreur", $destination);
	}

}

archiviste_finir_test(false, $destination);
