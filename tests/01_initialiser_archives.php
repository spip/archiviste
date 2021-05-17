<?php

/**
 * Tests unitaires de l'API d'Archives
 * Initialisation
 */

use Spip\Archives\SpipArchives;

$test = 'initialiser archives';
$remonte = '../';
while (!is_dir($remonte . 'ecrire')) {
	$remonte = '../' . $remonte;
}
require $remonte . 'tests/test.inc';
$ok = true;

require __DIR__ . '/TestCase.inc';
archiviste_nettoyer_environnement_test();

include_spip('inc/archives');

$repertoire = archiviste_repertoire_de_test();

//extensions inconnues
foreach (array('sans_extension', 'extension_inconnue', 'faux_amis') as $cas) {
	$archive = new SpipArchives(archiviste_fichier_de_test($cas));
	if ($archive->erreur() !== 2) {
		var_dump($archive->erreur(),$archive->message());
		archiviste_finir_test("Echec creation d'une archive avec extensions incorrecte doit produire une erreur", $repertoire);
	}
}

foreach (SpipArchives::compressionsConnues as $format) {

	//presence fichier
	$fichier = archiviste_fichier_de_test($format);

	//fichier absent
	$archive = new SpipArchives($fichier);
	if ($archive->erreur() !== 3) {
		var_dump($archive->erreur(),$archive->message());
		archiviste_finir_test("[$format] Echec creation d'une nouvelle archive : doit produire une erreur fichier absent", $repertoire);
	}
	if ($archive->getLectureSeule()) {
		var_dump($archive->erreur(),$archive->message());
		archiviste_finir_test("[$format] Echec creation d'une nouvelle archive : ne doit pas etre en lecture seule", $repertoire);
	}
	if ($archive->deballer()) {
		var_dump($archive->erreur(),$archive->message());
		archiviste_finir_test("[$format] Echec creation d'une nouvelle archive : on ne peut deballer un fichier qui n'existe pas", $repertoire);
	}

	//fichier present
	touch($fichier);
	$archive = new SpipArchives($fichier);
	if ($archive->erreur() !== false) {
		var_dump($archive->erreur(),$archive->message());
		archiviste_finir_test("[$format] Echec ouverture d'une archive existante :  ne doit pas produire une erreur", $repertoire);
	}
	if ($archive->getLectureSeule()) {
		var_dump($archive->erreur(),$archive->message());
		archiviste_finir_test("[$format] Echec ouverture d'une archive existante : ne doit pas etre en lecture seule", $repertoire);
	}
	if ($archive->deballer($repertoire) or $archive->erreur() !== 5) {
		var_dump($archive->erreur(),$archive->message());
		archiviste_finir_test("[$format] Echec ouverture d'une archive existante : on ne peut pas deballer dans un repertoire qui n'existe pas", $repertoire);
	}

	// destination en lecture seule
	mkdir($repertoire);
	chmod($repertoire, 0500);
	if ($archive->deballer($repertoire) or $archive->erreur() !== 5) {
		var_dump($archive->erreur(),$archive->message());
		archiviste_finir_test("[$format] Echec ouverture d'une archive existante : on ne peut pas deballer dans un repertoire en lecture seule", $repertoire);
	}
	chmod($repertoire, 0700);

	//fichier en lecteure seule
	chmod($fichier, 0400);
	$archive = new SpipArchives($fichier);
	if ($archive->erreur() !== false) {
		var_dump($archive->erreur(),$archive->message());
		archiviste_finir_test("[$format] Echec ouverture d'une archive existante en lecture seule :  ne doit pas produire une erreur", $repertoire);
	}
	if (! $archive->getLectureSeule()){
		var_dump($archive->erreur(), $archive->message());
		archiviste_finir_test("[$format] Echec ouverture d'une archive existante en lecture seule : la lecture seule n'a pas ete detectee", $repertoire);
	}
	chmod($fichier, 0600);

	//forcer le mode de compression
	$fichier = archiviste_fichier_de_test('sans_extension');
	touch($fichier);
	$archive = new SpipArchives($fichier, $format);
	if ($archive->erreur() !== false) {
		var_dump($archive->erreur(),$archive->message());
		archiviste_finir_test("[$format] Echec ouverture d'une archive existante dont on force le format :  ne doit pas produire une erreur", $repertoire);
	}
	archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $repertoire);
	archiviste_nettoyer_environnement_test();
}

archiviste_finir_test(false, $repertoire);
