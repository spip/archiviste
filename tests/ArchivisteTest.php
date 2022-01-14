<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
 * \***************************************************************************/

namespace Spip\Core\Tests;

use PHPUnit\Framework\TestCase;
use Spip\Archives\SpipArchives;

/**
 * ArchivisteTest test
 *
 */
class ArchivisteTest extends TestCase {

	protected $repertoire;

	protected function tearDown(): void{
		if ($this->repertoire){
			archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $this->repertoire);
		}
		archiviste_nettoyer_environnement_test();
		$this->repertoire = '';
	}

	protected function afficheArchiveErreur($archive, $message){
		return ($message ?: "Echec erreur innatendue")
			. "\nErreur : " . $archive->erreur()
			. " | Message: " . $archive->message();
	}

	protected function assertArchiveErreur($expectedError, $archive, $message = ''){
		$this->assertEquals($expectedError, $archive->erreur(), $this->afficheArchiveErreur($archive, $message));
	}

	protected function verifierDeballerArchive($fichier, $format): void{
		$this->repertoire = archiviste_repertoire_de_test();

		$archive = new SpipArchives($fichier);

		$this->assertTrue($archive->deballer($this->repertoire, array('test.txt')), $this->afficheArchiveErreur($archive, "[$format] Echec deballer [test.txt]"));
		$this->assertFileExists($f = $this->repertoire . '/test.txt', $this->afficheArchiveErreur($archive, "[$format] Fichier $f absent"));
		$this->assertFileDoesNotExist($f = $this->repertoire . '/sousrep/fichier', $this->afficheArchiveErreur($archive, "[$format] Fichier $f present mais pas demande"));

		archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $this->repertoire);

		$this->assertTrue($archive->deballer($this->repertoire), $this->afficheArchiveErreur($archive, "[$format] Echec deballer"));
		$this->assertFileExists($f = $this->repertoire . '/test.txt', $this->afficheArchiveErreur($archive, "[$format] Fichier $f absent"));
		$this->assertFileExists($f = $this->repertoire . '/sousrep/fichier', $this->afficheArchiveErreur($archive, "[$format] Fichier $f absent"));

		archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $this->repertoire);
	}

	public function testInitialiserArchives(): void{
		require_once __DIR__ . '/TestCase.inc';
		archiviste_nettoyer_environnement_test();

		include_spip('inc/archives');

		$this->repertoire = archiviste_repertoire_de_test();

		//extensions inconnues
		foreach (array('sans_extension', 'extension_inconnue', 'faux_amis') as $cas){
			$archive = new SpipArchives(archiviste_fichier_de_test($cas));
			$this->assertArchiveErreur(2, $archive, "Echec creation d'une archive avec extensions incorrecte doit produire une erreur");
		}

		foreach (SpipArchives::compressionsConnues as $format){

			//presence fichier
			$fichier = archiviste_fichier_de_test($format);

			//fichier absent
			$archive = new SpipArchives($fichier);
			$this->assertArchiveErreur(3, $archive, "[$format] Echec creation d'une nouvelle archive : doit produire une erreur fichier absent");
			$this->assertFalse($archive->getLectureSeule(), $this->afficheArchiveErreur($archive, "[$format] Echec creation d'une nouvelle archive : ne doit pas etre en lecture seule"));
			$this->assertFalse($archive->deballer(), $this->afficheArchiveErreur($archive, "[$format] Echec creation d'une nouvelle archive : on ne peut deballer un fichier qui n'existe pas"));

			//fichier present
			touch($fichier);
			$archive = new SpipArchives($fichier);
			$this->assertFalse($archive->erreur(), $this->afficheArchiveErreur($archive, "[$format] Echec ouverture d'une archive existante :  ne doit pas produire une erreur"));
			$this->assertFalse($archive->getLectureSeule(), $this->afficheArchiveErreur($archive, "[$format] Echec ouverture d'une archive existante : ne doit pas etre en lecture seule"));
			$this->assertFalse($archive->deballer($this->repertoire), $this->afficheArchiveErreur($archive, "[$format] Echec ouverture d'une archive existante : on ne peut pas deballer dans un repertoire qui n'existe pas"));
			$this->assertArchiveErreur(5, $archive, "[$format] Echec ouverture d'une archive existante : on ne peut pas deballer dans un repertoire qui n'existe pas");

			// destination en lecture seule
			mkdir($this->repertoire);
			chmod($this->repertoire, 0500);
			$this->assertFalse($archive->deballer($this->repertoire), $this->afficheArchiveErreur($archive, "[$format] Echec ouverture d'une archive existante : on ne peut pas deballer dans un repertoire en lecture seule"));
			$this->assertArchiveErreur(5, $archive, "[$format] Echec ouverture d'une archive existante : on ne peut pas deballer dans un repertoire en lecture seule");
			chmod($this->repertoire, 0700);

			//fichier en lecteure seule
			chmod($fichier, 0400);
			$archive = new SpipArchives($fichier);
			$this->assertFalse($archive->erreur(), $this->afficheArchiveErreur($archive, "[$format] Echec ouverture d'une archive existante en lecture seule :  ne doit pas produire une erreur"));
			$this->assertTrue($archive->getLectureSeule(), $this->afficheArchiveErreur($archive, "[$format] Echec ouverture d'une archive existante en lecture seule : la lecture seule n'a pas ete detectee"));
			chmod($fichier, 0600);

			//forcer le mode de compression
			$fichier = archiviste_fichier_de_test('sans_extension');
			touch($fichier);
			$archive = new SpipArchives($fichier, $format);
			$this->assertFalse($archive->erreur(), $this->afficheArchiveErreur($archive, "[$format] Echec ouverture d'une archive existante dont on force le format :  ne doit pas produire une erreur"));

			archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $this->repertoire);
			archiviste_nettoyer_environnement_test();
		}
	}

	/**
	 * @depends testInitialiserArchives
	 */
	public function testInformerArchives(): void{
		require_once __DIR__ . '/TestCase.inc';
		archiviste_nettoyer_environnement_test();

		include_spip('inc/archives');

		$this->repertoire = archiviste_repertoire_de_test();

		foreach (SpipArchives::compressionsConnues as $format){

			$fichier = archiviste_fichier_de_test($format);
			archiviste_generer_archive_de_test($fichier, $format);


			$archive = new SpipArchives($fichier);

			$infos = $archive->informer();
			$this->assertNotEmpty($infos, $this->afficheArchiveErreur($archive, "[$format] Echec archive->informer()"));
			$this->assertArrayHasKey('fichiers', $infos, $this->afficheArchiveErreur($archive, "[$format] Entree fichiers manquante dans archive->informer()"));
			$this->assertNotEmpty($infos['fichiers'], $this->afficheArchiveErreur($archive, "[$format] Entree fichiers vide dans archive->informer()"));
			$this->assertCount(2, $infos['fichiers'], $this->afficheArchiveErreur($archive, "[$format] Echec archive->informer()"));


			$this->assertNotEmpty(archiviste_trouver_fichier('test.txt', $infos['fichiers']), $this->afficheArchiveErreur($archive, "[$format] Fichier test.txt absent de archive->informer()"));
			$this->assertNotEmpty(archiviste_trouver_fichier('sousrep/fichier', $infos['fichiers']), $this->afficheArchiveErreur($archive, "[$format] Fichier sousrep/fichier absent de archive->informer()"));

			$this->assertNotEmpty($infos['proprietes'], $this->afficheArchiveErreur($archive, "[$format] Entree proprietes manquante dans archive->informer()"));

			$this->assertEquals('', $infos['proprietes']['racine'], $this->afficheArchiveErreur($archive, "[$format] Entree proprietes/racine incorrecte dans archive->informer()"));

			archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $this->repertoire);
			archiviste_nettoyer_environnement_test();
		}

	}

	/**
	 * @depends testInformerArchives
	 */
	public function testDeballerArchives(): void{
		require_once __DIR__ . '/TestCase.inc';
		archiviste_nettoyer_environnement_test();

		include_spip('inc/archives');

		foreach (SpipArchives::compressionsConnues as $format){

			$fichier = archiviste_fichier_de_test($format);
			archiviste_generer_archive_de_test($fichier, $format);

			$this->verifierDeballerArchive($fichier, $format);
		}
	}

	/**
	 * @depends testDeballerArchives
	 */
	public function testEmballerArchives(): void{
		require_once __DIR__ . '/TestCase.inc';
		archiviste_nettoyer_environnement_test();

		include_spip('inc/archives');

		$this->repertoire = archiviste_repertoire_de_test();

		foreach (SpipArchives::compressionsConnues as $format){

			$fichier = archiviste_fichier_de_test($format);
			$files_list = archiviste_generer_contenu_de_test(archiviste_contenu_de_test());

			$archive = new SpipArchives($fichier);
			$this->assertTrue($archive->emballer($files_list), $this->afficheArchiveErreur($archive, "[$format] Echec emballer " . json_encode($files_list, JSON_THROW_ON_ERROR)));

			archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $this->repertoire);

			$infos = $archive->informer();
			$this->assertNotEmpty($infos, $this->afficheArchiveErreur($archive, "[$format] Echec emballer : nombre de fichiers incorrects"));
			$this->assertCount(is_countable($files_list) ? count($files_list) : 0, $infos['fichiers'], $this->afficheArchiveErreur($archive, "[$format] Echec emballer : nombre de fichiers incorrects"));

			$this->verifierDeballerArchive($fichier, $format);
		}
	}

	/**
	 * @depends testEmballerArchives
	 */
	public function testRetirerArchives(): void{
		require_once __DIR__ . '/TestCase.inc';
		archiviste_nettoyer_environnement_test();

		include_spip('inc/archives');

		$this->repertoire = archiviste_repertoire_de_test();


		foreach (SpipArchives::compressionsConnues as $format){

			$fichier = archiviste_fichier_de_test($format);
			archiviste_generer_archive_de_test($fichier, $format);

			$archive = new SpipArchives($fichier);

			$infos = $archive->informer();
			$nb_files = is_countable($infos['fichiers']) ? count($infos['fichiers']) : 0;

			$this->assertTrue($archive->retirer(array('test.txt')), $this->afficheArchiveErreur($archive, "[$format] Echec retirer [test.txt]"));

			$infos = $archive->informer();
			$this->assertCount($nb_files-1, $infos['fichiers'],  "[$format] retirer [test.txt] : nombre de fichiers innatendus apres\n" . json_encode($infos, JSON_THROW_ON_ERROR));

			@unlink($fichier);
			archiviste_generer_archive_de_test($fichier, $format);
			$this->assertTrue($archive->retirer(array('sousrep/fichier')), $this->afficheArchiveErreur($archive, "[$format] Echec retirer [sousrep/fichier]"));

			$infos = $archive->informer();
			$this->assertCount($nb_files-1, $infos['fichiers'],  "[$format] retirer [sousrep/fichier] : nombre de fichiers innatendus apres\n" . json_encode($infos, JSON_THROW_ON_ERROR));


			$this->assertTrue($archive->retirer(array('dir/fichierinexistant')), $this->afficheArchiveErreur($archive, "[$format] Echec retirer [dir/fichierinexistant] n'aurait pas du produire une erreur"));
			$this->assertFalse($archive->erreur(), $this->afficheArchiveErreur($archive, "[$format] Echec retirer [dir/fichierinexistant] n'aurait pas du produire une erreur"));
		}
	}

}