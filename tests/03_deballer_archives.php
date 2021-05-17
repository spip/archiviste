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
archiviste_generer_archive_de_test($fichier, 'zip');

$destination = archiviste_repertoire_de_test();

archiviste_teste_deballer($fichier);

archiviste_finir_test(false, $destination);
