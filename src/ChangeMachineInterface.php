<?php

declare(strict_types=1);

namespace App;

interface ChangeMachineInterface
{
    /**
     * Vide l'argent stocké (remboursement)
     */
    public function flushStoredMoney(): void;

    /**
     * Retourne le stock actuel de pièces
     * @return array<CoinCode, int> Stock par type de pièce
     */
    public function getCurrentStock(): array;

    /**
     * Vérifie si on peut rendre la monnaie demandée avec le stock disponible
     */
    public function canMakeChange(int $changeAmount, array $currentStock): bool;

    /**
     * Rend la monnaie avec les pièces spécifiées
     * @param CoinCode[] $changeCoins Pièces à rendre
     */
    public function returnChange(array $changeCoins): void;

    /**
     * Met à jour le stock de pièces
     * @param array<CoinCode, int> $newStock Nouveau stock par type de pièce
     */
    public function updateStock(array $newStock): void;

    /**
     * Calcule la meilleure combinaison de pièces pour rendre la monnaie
     * Priorité aux pièces de plus grande valeur
     * @return CoinCode[] Pièces à rendre, ou tableau vide si impossible
     */
    public function calculateOptimalChange(int $changeAmount, array $availableStock): array;
}