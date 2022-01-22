<?php

namespace Spip\Archiver;

/**
 * {@inheritDoc}
 * Implémentation spécifique au fichier .tgz|tar.gz.
 */
class TgzArchive extends TarArchive implements ArchiveInterface
{
	protected bool $gzCompress = true;
}
