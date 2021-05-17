<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

namespace Spip\Archives;

/**
 * Point d'entrée de la gestion des archives compressées de SPIP
 */
class SpipArchives
{
	/** @const array Mode de compression connus */
	public const compressionsConnues = ['zip', 'tar', 'tgz'];

	/** @var integer Dernier code d'erreur */
	private $codeErreur;

	/** @var string Dernier message d'erreur */
	private $messageErreur;

	/** @var string Mode de compression si l'extension du fichier n'est pas explicite */
	private $modeCompression;

	/** @var string Chemin vers le fichier d'archives */
	private $fichierArchive;

	/** @var boolean true si l'archive est en lecture seule */
	private $lectureSeule = true;

	/** @var array Liste des erreurs possibles */
	private $erreurs = [
		0 => 'OK',
		1 => 'erreur_inconnue',
		2 => 'extension_inconnue',
		3 => 'fichier_absent',
		4 => 'fichier_lecture_seule',
		5 => 'destination_inaccessible',
		6 => 'fichier_deja_existant',
	];

	/**
	 * Renvoyer le dernier code d'erreur.
	 *
	 * @return integer Dernier code d'erreur
	 */
	public function erreur() {
		if (!$this->codeErreur) {
			return false;
		}

		$code = in_array($this->codeErreur, array_keys($this->erreurs)) ? $this->codeErreur : 1;

		$this->codeErreur = $code;
		if ($this->codeErreur!==1 or !$this->messageErreur) {
			$this->messageErreur = 'archives:'.$this->erreurs[$code];
		}

		return $code;
	}

	/**
	 * Renvoyer le dernier message d'erreur.
	 *
	 * @return string Dernier message d'erreur
	 */
	public function message() {
		return $this->messageErreur;
	}

	/**
	 * Indiquer le détail du contenu de l'archive.
	 *
	 * @return array détail du contenu de l'archive
	 */
	public function informer() {
		if ($this->codeErreur !== 0) {
			return false;
		}

		$res = [
			'proprietes' => [],
			'fichiers' => [
				/*
				 * filename
				 * checksum
				 * size
				 * mtime
				 * status
				 * raw
				 */
			]
		];

		switch ($this->modeCompression) {
			case 'zip':
				include_spip('inc/pclzip');
				$zip = new \PclZip($this->fichierArchive);
				$files = $zip->listContent();
				foreach ($files as $file) {
					$res['fichiers'][] = [
						'filename' => $file['stored_filename'],
						'checksum' => $file['crc'],
						'size' => $file['size'],
						'mtime' => $file['mtime'],
						'raw' => $file
					];
				}
				break;
			case 'tar':
			case 'tgz':
				include_spip('inc/pcltar');
				$files = PclTarList($this->fichierArchive, $this->modeCompression);
				foreach ($files as $file) {
					$res['fichiers'][] = [
						'filename' => $file['filename'],
						'checksum' => $file['checksum'],
						'size' => $file['size'],
						'mtime' => $file['mtime'],
						'raw' => $file
					];
				}
		}

		// trouver la racine des fichiers
		if (!empty($res['fichiers'])) {
			$res['proprietes']['racine'] = $this->trouver_racine(array_column($res['fichiers'], 'filename'));
		}
		else {
			$res['proprietes']['racine'] = '';
		}

		return $res;
	}

	/**
	 * Cherche la plus longue racine commune à tous les fichiers
	 *
	 * @param array $list
	 *     Liste de chemin de fichiers
	 * @return string
	 *     Chemin commun entre tous les fichiers
	 **/
	protected function trouver_racine($path_list) {
		// on cherche la plus longue racine commune a tous les fichiers
		// pour l'enlever au deballage
		$max_n = 999999;
		$paths = [];
		foreach ($path_list as $path) {
			$p = [];
			foreach (explode('/', $path) as $n => $x) {
				if ($n > $max_n) {
					continue;
				}
				$sofar = join('/', $p);
				if (!isset($paths[$n])) {
					$paths[$n] = [];
				}
				if (!isset($paths[$n][$sofar])) {
					$paths[$n][$sofar] = 0;
				}
				$paths[$n][$sofar]++;
				$p[] = $x;
			}
			$max_n = min($n, $max_n);
		}

		$total = $paths[0][''];
		$i = 0;
		while (isset($paths[$i])
			and count($paths[$i]) <= 1
			and array_values($paths[$i]) == [$total]) {
			$i++;
		}

		$racine = '';
		if ($i) {
			$racine = array_keys($paths[$i - 1]);
			$racine = array_pop($racine);
			if ($racine) {
				$racine .= '/';
			}
		}

		return $racine;
	}


	/**
	 * Extraire tout ou partie des fichiers de l'archive vers une destination.
	 *
	 * @param  string  $destination Chemin du répertoire d'extraction
	 * @param  array   $fichiers	Liste des fichiers à extraire
	 *
	 * @return boolean			  Succès de l'opération
	 */
	public function deballer($destination = '', array $fichiers = []) {
		if ($this->codeErreur !== 0) {
			return false;
		}

		if (!(is_dir($destination) and is_writable($destination))) {
			$this->codeErreur = 5;
			return false;
		}

		if (!$infos = $this->informer()) {
			return false;
		}

		$errors = [];
		switch ($this->modeCompression) {
			case 'zip':
				include_spip('inc/pclzip');
				$zip = new \PclZip($this->fichierArchive);

				if (!$fichiers) {
					$ok = $zip->extract(
						PCLZIP_OPT_PATH,
						$destination,
						PCLZIP_OPT_SET_CHMOD, _SPIP_CHMOD,
						PCLZIP_OPT_REPLACE_NEWER,
						PCLZIP_OPT_REMOVE_PATH, $infos['proprietes']['racine']
					);
					if (!$ok or $zip->error_code < 0) {
						$errors[] = 'deballer() erreur zip ' . $zip->error_code . ' pour paquet: ' . $this->fichierArchive;
						return false;
					}
				}
				else {
					foreach ($fichiers as $fichier) {
						$ok = $zip->extract(
							PCLZIP_OPT_PATH,
							$destination,
							PCLZIP_OPT_SET_CHMOD, _SPIP_CHMOD,
							PCLZIP_OPT_REPLACE_NEWER,
							PCLZIP_OPT_REMOVE_PATH, $infos['proprietes']['racine'],
							PCLZIP_OPT_BY_NAME, $fichier
						);
						if (!$ok or $zip->error_code < 0) {
							$errors[] = "deballer() Fichier $fichier: erreur zip " . $zip->error_code . ' pour paquet: ' . $this->fichierArchive;
						}
					}
				}


				break;

			case 'tar':
			case 'tgz':
				include_spip('inc/pcltar');
				if (!$fichiers){
					$ok = PclTarExtract($this->fichierArchive, $destination, $infos['proprietes']['racine'], $this->modeCompression);
					if ($ok === 0){
						$errors[] = 'deballer() erreur tar ' . PclErrorString() . ' pour paquet: ' . $this->fichierArchive;
					}
				}
				else {
					$ok = PclTarExtractList($this->fichierArchive, $fichiers, $destination, $infos['proprietes']['racine'], $this->modeCompression);
					if ($ok === 0){
						$errors[] = 'deballer() erreur tar ' . PclErrorString() . ' pour paquet: ' . $this->fichierArchive;
					}
				}

				break;
		}

		if (count($errors)) {
			$this->codeErreur = 1;
			$this->messageErreur = implode("\n", $errors);

			return false;
		}

		$this->codeErreur = 0;
		return true;
	}

	/**
	 * Créer ou modifier des fichiers dans le fichier d'archive.
	 *
	 * @param  array   $fichiers Liste des fichiers à ajouter ou modifier
	 *
	 * @return boolean		   Succès de l'opération
	 */
	public function emballer(array $fichiers = []) {
		if ($this->lectureSeule) {
			$this->codeErreur = 4;
			return false;
		}

		// le fichier ne doit pas deja exister (c'est une creation)
		if ($this->codeErreur !== 3) {
			$this->codeErreur = 6;
			return false;
		}

		$racine = $this->trouver_racine($fichiers);

		switch ($this->modeCompression) {
			case 'zip':
				include_spip('inc/pclzip');
				$zip = new \PclZip($this->fichierArchive);

				$v_list = $zip->create(
					$fichiers,
					PCLZIP_OPT_REMOVE_PATH,
					$racine,
					PCLZIP_OPT_ADD_PATH,
					''
				);
				if (!$v_list or $zip->error_code < 0) {
					$this->codeErreur = 1;
					$this->messageErreur = "emballer() : Echec creation du zip " . $zip->error_code . ' pour paquet: ' . $this->fichierArchive;
					return false;
				}

				break;

			case 'tar':
			case 'tgz':
				include_spip('inc/pcltar');

				$ok = PclTarCreate($this->fichierArchive, $fichiers, $this->modeCompression, "", $racine);
				if ($ok === 0){
					$this->codeErreur = 1;
					$this->messageErreur = "emballer() : Echec creation du ".$this->modeCompression. " " . PclErrorString() . ' pour paquet: ' . $this->fichierArchive;
					return false;
				}

		}

		// verifier que le fichier existe bien
		if (!file_exists($this->fichierArchive)) {
			$this->codeErreur = 3;
			return false;
		}

		$this->codeErreur = 0;
		return true;
	}

	/**
	 * Retirer une liste de fichiers dans le fichier d'archive.
	 *
	 * @param  array   $fichiers Liste des fichiers à retirer
	 *
	 * @return boolean		   Succès de l'opération
	 */
	public function retirer(array $fichiers = []) {
		if ($this->lectureSeule) {
			$this->codeErreur = 4;
			return false;
		}

		if ($this->codeErreur !== 0) {
			return false;
		}

		switch ($this->modeCompression) {
			case 'zip':
				include_spip('inc/pclzip');
				$zip = new \PclZip($this->fichierArchive);
				$ok = $zip->delete(PCLZIP_OPT_BY_NAME, $fichiers);
				if (!$ok or $zip->error_code < 0) {
					$this->codeErreur = 1;
					$this->messageErreur = "retirer() : Echec retirer fichiers ".json_encode($fichiers). " " . $zip->error_code . ' pour paquet: ' . $this->fichierArchive;
					return false;
				}
				break;

			case 'tar':
			case 'tgz':
				include_spip('inc/pcltar');
				$ok = PclTarDelete($this->fichierArchive, $fichiers, $this->modeCompression);
				if ($ok === 0){
					$this->codeErreur = 1;
					$this->messageErreur = "retirer() : Echec retirer fichiers ".json_encode($fichiers). " " . PclErrorString() . ' pour paquet: ' . $this->fichierArchive;
					return false;
				}

				break;
		}


		$this->codeErreur = 0;
		return true;
	}

	/**
	 * Constructeur de base.
	 *
	 * @param string $fichierArchive  Chemin vers le fichier d'archives
	 * @param string $modeCompression Mode de compression si l'extension du fichier n'est pas explicite
	 */
	public function __construct($fichierArchive, $modeCompression = '') {
		$this->codeErreur = 0;

		if ('' === $modeCompression) {
			$modeCompression = preg_replace(',.+\.([^.]+)$,', '$1', $fichierArchive);
		}

		$modeCompression = strtolower($modeCompression);
		if (!in_array($modeCompression, self::compressionsConnues)) {
			$this->codeErreur = 2;
		} elseif (!file_exists($fichierArchive)) {
			$this->codeErreur = 3;

			$repertoireArchive = dirname($fichierArchive);
			$this->lectureSeule = !(is_dir($repertoireArchive) and is_writable($repertoireArchive));
		} else {
			$this->lectureSeule = !is_writable($fichierArchive);
		}

		$this->modeCompression = $modeCompression;
		$this->fichierArchive = $fichierArchive;
	}

	/**
	 * Indique si l'archive est accessible en ecriture ou pas.
	 *
	 * @return boolean true si l'archive est en lecture seule
	 */
	public function getLectureSeule() {
		return $this->lectureSeule;
	}
}
