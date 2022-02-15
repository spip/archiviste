<?php

namespace Spip\Archiver;

use FilesystemIterator;
use CallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * {@inheritDoc}
 * Gestion des erreurs.
 */
abstract class AbstractArchiver implements ArchiverInterface
{
	/** @var array<string, string> Mode de compression connus, d’après mime-type */
	protected const COMPRESSIONS_CONNUES = ['zip' => 'zip', 'x-tar' => 'tar', 'gzip' => 'tgz'];

	/** @var array<string, string> Mode de compression connus, sur raw mime-type */
	protected const COMPRESSIONS_CONNUES_RAW = ['Zip archive data' => 'zip'];

	/** @var int Dernier code d'erreur */
	protected int $code_erreur = 0;

	/** @var string Dernier message d'erreur */
	protected string $message_erreur = 'OK';

	/** @var string Mode de compression si l'extension du fichier n'est pas explicite */
	protected string $mode_compression;

	/** @var string Chemin vers le fichier d'archives */
	protected string $fichier_archive;

	/** @var bool true si l'archive est en lecture seule */
	protected bool $lecture_seule = true;

	/** @var string[] Liste des erreurs possibles */
	protected array $erreurs = [
		0 => 'OK',
		1 => 'erreur_inconnue',
		2 => 'extension_inconnue',
		3 => 'fichier_absent',
		4 => 'fichier_lecture_seule',
		5 => 'destination_inaccessible',
		6 => 'fichier_deja_existant',
		7 => 'fichier_inaccessible_en_lecture',
		8 => 'tentative_de_vidage_du_fichier',
	];

	/** @var array liste des fichiers à exclure de l'archive */
	protected array $fichiers_ignores = ['.ok'];

	/**
	 * Constructeur de base.
	 *
	 * Le fichier d'archive doit représenter le chemin vers un fichier du file system local
	 *
	 * Ce chemin peut être relatif au dossier où se trouve spip.php (chemin/vers/fichier.zip)
	 * ou absolu (/tmp/cache/fichier.tgz)
	 *
	 * Le mode de compression n'est utile que lorsqu'on créée une archive avec une extension exotique
	 * En lecture, pour fournir la liste des fichiers contenus ou procéder à l'extraction,
	 * L'archiveur détecte le mode de compression en fonction du mime type, peu importe l'extension du fichier
	 * En écriture, pour supprimer des fichiers, l'archiveur fait de même
	 * Si le paramètres est fourni, il ne sera pas exploité lors des appels aux méthodes
	 * ::retirer(), ::informer() et ::deballer()
	 * S'il n'est pas fourni pour créer une archive à extension exotique (ex: tmp/archive.spip),
	 * la méthode ::emballer produire l'erreur 2 ('archives:extension_inconnue')
	 *
	 * @param string $fichier_archive  Chemin vers le fichier d'archive
	 * @param string $mode_compression Mode de compression si l'extension du fichier n'est pas explicite
	 */
	public function __construct(string $fichier_archive, string $mode_compression = '') {
		$this->fichier_archive = $fichier_archive;
		$this->mode_compression = $mode_compression;
	}

	/**
	 * {@inheritDoc}
	 */
	public function erreur(): int {
		return $this->code_erreur;
	}

	/**
	 * {@inheritDoc}
	 */
	public function message(): string {
		return $this->message_erreur;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLectureSeule(): bool {
		return $this->lecture_seule;
	}

	/**
	 * {@inheritDoc}
	 */
	abstract public function informer(): array;

	/**
	 * {@inheritDoc}
	 */
	abstract public function deballer(string $destination = '', array $fichiers = []): bool;

	/**
	 * {@inheritDoc}
	 */
	abstract public function emballer(array $fichiers = [], ?string $racine = null): bool;

	/**
	 * {@inheritDoc}
	 */
	abstract public function retirer(array $fichiers = []): bool;

	/**
	 * {@inheritDoc}
	 */
	abstract public function commenter(string $texte = ''): bool;

	/**
	 * Définir la dernière erreur produite.
	 *
	 * @param int $code un code d'erreur
	 */
	protected function setErreur(int $code): void {
		$code = in_array($code, array_keys($this->erreurs)) ? $code : 1;

		$this->code_erreur = $code;
		$this->message_erreur = 'archives:' . $this->erreurs[$code];
	}

	/**
	 * Indiquer le type mime.
	 *
	 * @return string mime type de type "application/[MODE]; charset=binary" ou vide
	 */
	protected function mimeType(): string {
		$file_mime_type = '';

		if (file_exists($this->fichier_archive)) {
			$finfo = finfo_open(\FILEINFO_MIME);
			if ($finfo) {
				$file_mime_type = (string) finfo_file($finfo, $this->fichier_archive) ?: '';
				$file_mime_type_raw = (string) finfo_file($finfo, $this->fichier_archive, \FILEINFO_RAW) ?: '';
				finfo_close($finfo);
			}

			if (
				preg_match(',^application/([^;]+); charset=binary$,', $file_mime_type, $matches)
				&& in_array($matches[1], array_keys(self::COMPRESSIONS_CONNUES))
			) {
				$this->mode_compression = self::COMPRESSIONS_CONNUES[$matches[1]];
				return $file_mime_type;
			}

			foreach (self::COMPRESSIONS_CONNUES_RAW as $term => $mode_compression) {
				if (false !== stripos($file_mime_type_raw, $term)) {
					$this->mode_compression = $mode_compression;
					return $file_mime_type;
				}
			}

			$this->setErreur(2);
			$file_mime_type = '';
		}

		return $file_mime_type;
	}

	/**
	 * Vérifier que le fichier d'archive est accessible en lecture.
	 */
	protected function archiveEnLecture(): ?ArchiveInterface {
		$archive = null;

		if (file_exists($this->fichier_archive)) {
			if (!is_readable($this->fichier_archive)) {
				$this->setErreur(7);
			} else {
				if ('' === $this->mode_compression) {
					$this->mimeType();
				}

				$archive = $this->getArchive();
			}
		} else {
			$this->setErreur(3);
		}

		return $archive;
	}


	/**
	 * Vérifier que le fichier d'archive est accessible en écriture.
	 */
	protected function archiveEnEcriture(): ?ArchiveInterface {		
		$archive = null;

		if (file_exists($this->fichier_archive)) {
			if (!is_writable($this->fichier_archive)) {
				$this->setErreur(4);
			} else {
				if ('' === $this->mode_compression) {
					$this->mimeType();
				}

				$archive = $this->getArchive();
			}
		} else {
			$this->setErreur(3);
		}

		return $archive;
	}

	/**
	 * Fournir un objet Archive en fonction du mode de compression.
	 */
	protected function getArchive(): ?ArchiveInterface {
		switch ($this->mode_compression) {
			case 'zip':
				$archive = new \Spip\Archiver\ZipArchive();

				break;

			case 'tar':
				$archive = new TarArchive();

				break;

			case 'tgz':
				$archive = new TgzArchive();

				break;

			default:
				$this->setErreur(2);
				$archive = null;

				break;
		}

		return $archive;
	}

	/**
	 * Cherche la plus longue racine commune à tous les fichiers.
	 *
	 * @param array<mixed> $path_list Liste de chemin de fichiers
	 *
	 * @return string Chemin commun entre tous les fichiers
	 */
	protected function trouverRacine(array $path_list): string {
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
				++$paths[$n][$sofar];
				$p[] = $x;
			}
			$max_n = min($n, $max_n);
		}

		if (empty($paths)) {
			return '';
		}

		$total = $paths[0][''];
		$i = 0;
		while (
			isset($paths[$i])
			and count($paths[$i]) <= 1
			and array_values($paths[$i]) == [$total]
		) {
			++$i;
		}

		$racine = '';
		if ($i) {
			$racine = array_keys($paths[$i - 1]);
			$racine = (string) array_pop($racine);
			if ($racine) {
				$racine .= '/';
			}
		}

		return $racine;
	}

	protected function listerFichiers(array $chemins): array {
		$fichiers = [];

		foreach ($chemins as $chemin) {
			if (is_dir($chemin)) {
				$iterateur_dossier = new CallbackFilterIterator(
					new RecursiveIteratorIterator(
						new RecursiveDirectoryIterator($chemin, FilesystemIterator::SKIP_DOTS),
					),
					function ($current, $key, $iterator) {
						if (in_array($current->getFilename(), $this->fichiers_ignores)) {
							return false;
						}

						return true;
					}
				);
				foreach ($iterateur_dossier as $fichier) {
					$fichiers[] = $fichier->getPathname();
				}
			} else {
				$fichiers[] = $chemin;
			}
		}

		return $fichiers;
	}
}
