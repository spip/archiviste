<?php

/**
 * Cas de tests pour SpipArchives
 */

function archiviste_inconnu()
{
    static $inconnu = '';

    if ('' === $inconnu) {
        $inconnu = md5(rand());
    }

    return $inconnu;
}

function archiviste_repertoire_de_test()
{
    return _NOM_TEMPORAIRES_INACCESSIBLES . 'archive_dir_test' . archiviste_inconnu();
}

function archiviste_definir_fichiers_de_test()
{
    $fichiers_de_test = array(
        'sans_extension' => 'archives_test',
        'extension_inconnue' => 'archives_test.' . archiviste_inconnu(),
        'zip' => 'archives_test' . archiviste_inconnu() . '.zip',
        'tar' => 'archives_test' . archiviste_inconnu() . '.tar',
        'tgz' => 'archives_test' . archiviste_inconnu() . '.tgz',
        'faux_amis' => 'fichier_test_zip',
    );

    return $fichiers_de_test;
}

function archiviste_fichier_de_test($case)
{
    $fichiers = archiviste_definir_fichiers_de_test();

    return _NOM_TEMPORAIRES_INACCESSIBLES . $fichiers[$case];
}

function archiviste_contenu_de_test()
{
    return array(
        'test.txt' => 'contenu de test',
        'sousrep' => array(
            'fichier' => '',
        ),
    );
}

function archiviste_nettoyer_environnement_test()
{
    foreach (archiviste_definir_fichiers_de_test() as $case => $nom_fichier) {
        if (is_file(archiviste_fichier_de_test($case))) {
            unlink(archiviste_fichier_de_test($case));
        }
    }

    archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test());
    @rmdir(archiviste_repertoire_de_test());
}

function archiviste_generer_archive_de_test($archivefile, $format)
{
	$files_list = archiviste_generer_contenu_de_test(archiviste_contenu_de_test());
	$dir_base = archiviste_repertoire_de_test() . '/';
	foreach ($files_list as $k=>$f) {
		$files_list[$k] = substr($f, strlen($dir_base));
	}

	$archivefile = realpath(dirname($archivefile)) . '/' . basename($archivefile);
	switch ($format) {
		case 'tar':
			passthru($cmd = "cd $dir_base && tar -cf $archivefile ".implode(" ", $files_list));
			break;
		case 'tgz':
			passthru($cmd = "cd $dir_base && tar -czf $archivefile ".implode(" ", $files_list));
			break;
		case 'zip':
		default:
			passthru($cmd = "cd $dir_base && zip -q $archivefile ".implode(" ", $files_list));
			break;
	}

	archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test());
}

function archiviste_generer_contenu_de_test(array $fichiers = array(), $arborescence = '')
{
	$files_list = [];
    if ($arborescence == '') {
        $arborescence = archiviste_repertoire_de_test();
    }

    if (!is_dir($arborescence)) {
        mkdir($arborescence);
    }

    foreach ($fichiers as $fichier => $contenu) {
        if (is_array($contenu)) {
	        $files_list = array_merge($files_list, archiviste_generer_contenu_de_test($contenu, $arborescence . '/' . $fichier));
        } elseif (!file_exists($arborescence . '/' . $fichier)) {
            file_put_contents($arborescence . '/' . $fichier, $contenu);
	        $files_list[] = $arborescence . '/' . $fichier;
        }
    }
    return $files_list;
}

function archiviste_nettoyer_contenu_de_test(array $fichiers = array(), $arborescence = '')
{
    if ($arborescence == '') {
        $arborescence = archiviste_repertoire_de_test();
    }

    foreach ($fichiers as $fichier => $contenu) {
        if (is_array($contenu)) {
            archiviste_nettoyer_contenu_de_test($contenu, $arborescence . '/' . $fichier);
            if (is_dir($arborescence . '/' . $fichier)) {
                rmdir($arborescence . '/' . $fichier);
            }
        } elseif (file_exists($arborescence . '/' . $fichier)) {
            unlink($arborescence . '/' . $fichier);
        }
    }
}

function archiviste_finir_test($erreur, $destination) {
	archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $destination);
	archiviste_nettoyer_environnement_test();

	if (!$erreur) {
		echo 'OK';
	}
	else {
		die($erreur);
	}
}

function archiviste_teste_deballer($fichier, $format) {
	$destination = archiviste_repertoire_de_test();


	$archive = new Spip\Archives\SpipArchives($fichier);
	if (!$archive->deballer($destination, array('test.txt'))) {
		archiviste_finir_test("[$format] Echec deballer [test.txt]", $destination);
	}

	if (!file_exists($f = $destination . '/test.txt')) {
		archiviste_finir_test("[$format] Fichier $f absent", $destination);
	}
	if (file_exists($f = $destination . '/sousrep/fichier')) {
		archiviste_finir_test("[$format] Fichier $f present mais pas demande", $destination);
	}

	archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $destination);
	if (!$archive->deballer($destination)){
		archiviste_finir_test("[$format] Echec deballer", $destination);
	}
	if (!file_exists($f = $destination . '/test.txt')) {
		archiviste_finir_test("[$format] Fichier $f absent", $destination);
	}
	if (!file_exists($f = $destination . '/sousrep/fichier')) {
		archiviste_finir_test("[$format] Fichier $f absent", $destination);
	}

	archiviste_nettoyer_contenu_de_test(archiviste_contenu_de_test(), $destination);

}

function archiviste_trouver_fichier($fileName, $files) {
	foreach ($files as $file) {
		if ($file['filename'] === $fileName) {
			return $file;
		}
	}
	return false;
}
