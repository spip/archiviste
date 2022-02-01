<?php

namespace Spip\Archiver;

/**
 * {@inheritDoc}
 * Implémentation des méthodes principales.
 */
class SpipArchiver extends AbstractArchiver implements ArchiverInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function informer(): array {
		$liste = [
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
			],
		];

		$archive = $this->archiveEnLecture();
		if ($archive) {
			if (1 !== $archive->open($this->fichier_archive, 'lecture')) {
				$this->setErreur(1);

				return $liste;
			}

			$liste['fichiers'] = $archive->list();
			$liste['proprietes']['racine'] = $this->trouverRacine(array_column($liste['fichiers'], 'filename'));
			$archive->close();
		}

		return $liste;
	}

	/**
	 * {@inheritDoc}
	 */
	public function deballer(string $destination = '', array $fichiers = []): bool {
		if (!(is_dir($destination) && is_writable($destination))) {
			$this->setErreur(5);

			return false;
		}

		$archive = $this->archiveEnLecture();
		if ($archive) {
			if (1 === $archive->open($this->fichier_archive, 'lecture')) {
				$retour = $archive->extractTo($destination, $fichiers);
				$archive->close();

				return $retour;
			}

			$this->setErreur(1);
		}

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function emballer(array $fichiers = [], ?string $racine = null): bool {
		$source = is_null($racine) ? $this->trouverRacine($fichiers) : $racine;

		if (!(is_dir($source) && is_readable($source))) {
			$this->setErreur(7);

			return false;
		}

		if (!file_exists($this->fichier_archive)) {
			if (is_writable(dirname($this->fichier_archive))) {
				if ('' === $this->mode_compression) {
					$this->mode_compression = (string) preg_replace(',.+\.([^.]+)$,', '$1', $this->fichier_archive);
				}
				$archive = $this->getArchive();
				if ($archive) {
					$retour = false;
					if (1 === $archive->open($this->fichier_archive, 'creation')) {
						$retour = $archive->compress($source, $fichiers);
						$archive->close();
					}
					$this->setErreur(intval(!$retour));

					return $retour;
				}
			}

			$this->setErreur(4);

			return false;
		}

		$this->setErreur(6);

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function retirer(array $fichiers = []): bool {
		if (file_exists($this->fichier_archive)) {
			if (is_writable($this->fichier_archive)) {
				if ('' !== $this->mimeType()) {
					$archive = $this->getArchive();
					if ($archive) {
						if (1 === $archive->open($this->fichier_archive, 'retrait')) {
							// Vérifier qu'on ne cherche pas à vider l'archive
							$reste = count($this->informer());
							if ($reste === count($fichiers)) {
								$this->setErreur(8);

								return false;
							}
							$retour = $archive->remove($fichiers);
							$archive->close();
						}
						$this->setErreur(0);

						return true;
					}
				}
			}

			$this->setErreur(4);

			return false;
		}

		$this->setErreur(3);

		return false;
	}
}
