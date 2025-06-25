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

```bash
./vendor/bin/phpunit --testdox tests/CoffeeMachineTest.php
```

## Structure du projet

- `src/` : Code source PHP
- `tests/` : Tests unitaires PHPUnit
- `composer.json` : Dépendances du projet

## Nouvelles fonctionnalitées
Pour machine équipé (pas toutes) on veut paiement CB, lecteur attends un callback (pouvoir rembourser)
Rendu de monnaie, si 1€ on rends 50ct
Pièce multiple = paiement en pls fois. Max 5 pièces, si on mets +5 on rends tous.

## Cas de test
Paiement CB
// D'abord choisir le café ou passer la carte et choisir le café ? reset après chaque café
// Si pls cafés, on demande le paiement CB pour tous ou un par un ? si un paiement passe pas on annule tous ou juste ceux qui ne passent pas ?
// Si il passe sa carte et mets sa pièce ? on peut pas
// Si le paiement CB passe pas ? on annule la commande
// 
ETANT DONNE une machine a café
QUAND 
ALORS 

Rendu monnaie
// Comment on gére le stock de pièce ? fake, stock de pièces (rembourse dans la limites des stocks)
// On rends la monnaire en priorité avec les pièces de plus grande valeur ? oui
// Si on a pas le stock de pièce on rends pas la monnaie ? du coup non

Pièce multiple
// On rends la monnaie ? (si 60cts ?) (on garde l'argent)
// 