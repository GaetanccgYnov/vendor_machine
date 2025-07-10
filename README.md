# Coffee Machine Project

## Prérequis

- PHP (>= 8.1 sinon BOOM)
- [Composer](https://getcomposer.org/)

## Installation

Installe les dépendances du projet avec Composer :

```bash
composer install
```

## Lancer les tests

Exécute les tests PHPUnit avec la commande suivante :

* Pour la version de cours, avant retours du professeur :
```bash
./vendor/bin/phpunit --testdox tests/CoffeeMachineTest.php
```

* Pour la version après retours du professeur :
```bash
./vendor/bin/phpunit --testdox .\tests\Acceptance\
```

## Structure du projet

- `src/` : Code source PHP
- `tests/` : Tests unitaires PHPUnit
- `composer.json` : Dépendances du projet
