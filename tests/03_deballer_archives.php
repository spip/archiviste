<?php

/**
 * Tests unitaires de l'API d'Archives
 * Extraction de fichiers
 */

use Spip\Archives\SpipArchives;

$test = 'deballer archives';
$remonte = '../';
while (!is_dir($remonte . 'ecrire')) {
	$remonte = "../$remonte";
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
if (!$archive->deballer($destination, array('test.txt'))) {
	archiviste_finir_test("Echec deballer [test.txt]", $destination);
}

if (!file_exists($f = $destination . '/test.txt')) {
	archiviste_finir_test("Fichier $f absent", $destination);
}
if (file_exists($f = $destination . '/sousrep/fichier')) {
	archiviste_finir_test("Fichier $f present mais pas demande", $destination);
}

archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $destination);
if (!$archive->deballer($destination)){
	archiviste_finir_test("Echec deballer", $destination);
}
if (!file_exists($f = $destination . '/test.txt')) {
	archiviste_finir_test("Fichier $f absent", $destination);
}
if (!file_exists($f = $destination . '/sousrep/fichier')) {
	archiviste_finir_test("Fichier $f absent", $destination);
}

archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $destination);
if ($archive->deballer($destination, ['fichierinexistant.truc'])
  or !$archive->erreur()){
	archiviste_finir_test("Echec deballer fichierinexistant.truc, erreur attendue", $destination);
}

archiviste_finir_test(false, $destination);
