<?php

namespace Spip\Archiver;

/**
 * {@inheritDoc}
 * Implémentation spécifique au fichier .zip.
 */
class ZipArchive implements ArchiveInterface
{
	protected \ZipArchive $zip;

	/** @var array<string, int> Paramètre à passer à \ZipArchive pour respecter le mode */
	private array $modes = [
		'lecture' => \ZipArchive::RDONLY,
		'creation' => \ZipArchive::CREATE,
		'retrait' => 0,
	];

	/**
	 * {@inheritDoc}
	 */
	public function open(string $filename, string $mode): int {
		$this->zip = new \ZipArchive();
		$this->zip->open($filename, $this->modes[$mode]);

		return 1;
	}

	/**
	 * {@inheritDoc}
	 */
	public function list(): array {
		$files = [];
		for ($i = 0; $i < $this->zip->numFiles; ++$i) {
			$stat = $this->zip->statIndex($i);
			if ($stat) {
				$files[] = [
					'filename' => $stat['name'],
					'size' => $stat['size'],
				];
			}
		}

		return $files;
	}

	/**
	 * {@inheritDoc}
	 */
	public function compress(string $source = '', array $files = []): bool {
		$ok = true;

		foreach ($files as $file) {
			$ok = $ok && $this->zip->addFile($file, str_replace(realpath($source) . '/', '', (string) realpath($file)));
		}

		return $ok;
	}

	/**
	 * {@inheritDoc}
	 */
	public function extractTo(string $target = '', array $files = []): bool {
		if (empty($files)) {
			$files = null;
		}

		/** @var array<string>|string $files */
		return $this->zip->extractTo($target, $files);
	}

	/**
	 * {@inheritDoc}
	 */
	public function remove(array $files = []): bool {
		$ok = true;

		foreach ($files as $file) {
			$ok = $ok && $this->zip->deleteName($file);
		}

		return $ok;
	}

	/**
	 * {@inheritDoc}
	 */
	public function close(): bool {
		return $this->zip->close();
	}

	/**
	 * {@inheritDoc}
	 */
	public function setComment(string $text): bool {
		return $this->zip->setArchiveComment($text);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getComment() {
		return $this->zip->getArchiveComment();
	}
}
