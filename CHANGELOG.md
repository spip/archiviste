# Changelog

## [Unreleased]

### Added

- Fichier CHANGELOG.md


## [2.1.3] - 2022-03-31

### Changed

- Coding standards, tests unitaires, corrections phpstan
- Pas de baseline phpstan dans le zip généré par git archive. composer.json valide.

### Fixed

- Permettre le fonctionnement sur certains serveurs qui utilisent une vieille version de libzip-dev (infomaniak par exemple)


## [2.1.2] - 2022-03-25

### Changed

- Nécessite SPIP 4.1.0 minimum


## [2.1.1] - 2022-02-17

### Added

- #4420 Permettre d’archiver un répertoire directement
- #4419 Permettre des commentaires sur les archives

### Changed

- Nécessite SPIP 4.1.0-beta minimum

### Fixed

- #4423 Détecter la compression zip lorsque le mime-type n’est pas application/zip, mais que le mode RAW de finfo_file l’indique


## [2.1.0] - 2022-02-02

### Changed

- Compatible SPIP 4.2-dev

### Fixed

- #4417 On ne peut pas vider un fichier d'archive. Le plugin râle et émet une nouvelle erreur en cas de tentative.
- #4417 Correction du test unitaire sur l'empty tar
- #4417 Verifier que le fichier existe bien quand on instancie la class PharData
- #4418 Permetre aux tests de fonctionner depuis le plugin ou depuis la suite de tests https://git.spip.net/spip/tests


## [2.0.0-dev] - 2022-01-28

### Changed

- #4416 Refonte des tests unitaires,
- #4416 Requiert les extensions zip, zlib et Phar
- Nécessite PHP 7.4 minimum
- Nécessite SPIP 4.1-dev minimum

### Fixed

- #4415 (#4416) Refonte. Suppression des lib Pcl* obsolètes
