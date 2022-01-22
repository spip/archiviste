<?php

namespace Spip\Archiver;

/**
 * Représenter un fichier d'archive.
 */
interface ArchiveInterface
{
	/**
	 * Ouvrir le fichier d'archive.
	 *
	 * @param string $filename Chemin vers le fichier d'archive
	 * @param string $mode Mode d'accès au fichier
	 *
	 * @return integer Code d'erreur
	 */
	public function open(string $filename, string $mode): int;

	/**
	 * Indiquer les fichiers d'une archive.
	 *
	 * @return array<int, mixed>
	 */
	public function list(): array;

	/**
	 * Créer un fichier d'archive à partir d'une liste de fichiers.
	 *
	 * @param array<int, mixed> $files Liste des fichiers
	 */
	public function compress(string $source = '', array $files = []): bool;

	/**
	 * Extraire tout ou partie des fichiers d'une archive.
	 *
	 * @param string        $target Répertoire cible
	 * @param array<string> $files  Tout les fichier si le tableau est vide
	 */
	public function extractTo(string $target = '', array $files = []): bool;

	/**
	 * Supprimer des fichiers de l'archive.
	 *
	 * @param array<string> $files
	 */
	public function remove(array $files = []): bool;

	/**
	 * Fermer la resource du fichier d'archive.
	 */
	public function close(): bool;
}
