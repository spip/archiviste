<?php

/**
 * Tests unitaires de l'API d'Archives
 * Information sur les fichiers
 */

use Spip\Archives\SpipArchives;

$test = 'informer archives';
$remonte = '../';
while (!is_dir($remonte . 'ecrire')) {
	$remonte = '../' . $remonte;
}
require $remonte . 'tests/test.inc';
$ok = true;

require __DIR__ . '/TestCase.inc';
archiviste_nettoyer_environnement_test();

include_spip('inc/archives');

$fichier = archiviste_fichier_de_test('zip');
archiviste_generer_zip_de_test($fichier);

$destination = archiviste_repertoire_de_test();

$archive = new SpipArchives($fichier);

$infos = $archive->informer();
if (!$infos) {
	archiviste_finir_test("Echec archive->informer()", $destination);
}

if (empty($infos['fichiers'])) {
	archiviste_finir_test("Entree fichiers manquante dans archive->informer()", $destination);
}

if (count($infos['fichiers']) !== 2) {
	archiviste_finir_test("Echec archive->informer()", $destination);
}
if (!archiviste_trouver_fichier('test.txt', $infos['fichiers'])) {
	archiviste_finir_test("Fichier test.txt absent de archive->informer()", $destination);
}
if (!archiviste_trouver_fichier('sousrep/fichier', $infos['fichiers'])) {
	archiviste_finir_test("Fichier sousrep/fichier absent de archive->informer()", $destination);
}

if (empty($infos['proprietes'])) {
	archiviste_finir_test("Entree proprietes manquante dans archive->informer()", $destination);
}

if ($infos['proprietes']['racine'] !== '') {
	archiviste_finir_test("Entree proprietes/racine incorrecte dans archive->informer()", $destination);
}

function archiviste_trouver_fichier($fileName, $files) {
	foreach ($files as $file) {
		if ($file['filename'] === $fileName) {
			return $file;
		}
	}
	return false;
}

archiviste_finir_test(false, $destination);
