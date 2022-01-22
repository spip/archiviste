<?php

/***************************************************************************
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) avec tendresse depuis 2001                               *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
 ***************************************************************************/

namespace Spip\Archives;

include_spip('src/ArchiverInterface');
include_spip('src/AbstractArchiver');
include_spip('src/ArchiveInterface');
include_spip('src/NoDotFilterIterator');
include_spip('src/TarArchive');
include_spip('src/TgzArchive');
include_spip('src/ZipArchive');
include_spip('src/SpipArchiver');

use Spip\Archiver\SpipArchiver;

/**
 * Point d'entrée de la gestion des archives compressées de SPIP
 */
class SpipArchives extends SpipArchiver
{
}
