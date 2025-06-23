<?php

interface ChangeMachineInterface
{
    /**
     * Enregistre un callback, appelé lors de l'insertion d'une pièce reconnue comme valide.
     * Attention : si le monnayeur est physiquement plein (plus de 5 pièces), cette méthode n'est plus invoquée.
     * Il est de la responsabilité du logiciel de surveiller cela.
     *
     * @param callable|null $callback Callback prenant en paramètre la valeur de la pièce détectée.
     * @return void
     */
    public function registerMoneyInsertedCallback(?callable $callback): void;

    /**
     * Vide le monnayeur et rend l'argent.
     *
     * @return void
     */
    public function flushStoredMoney(): void;

    /**
     * Vide le monnayeur et encaisse l'argent.
     *
     * @return void
     */
    public function collectStoredMoney(): void;

    /**
     * Fait tomber une pièce depuis le stock vers la trappe à monnaie.
     *
     * @param CoinCode $coinCode La pièce à restituer.
     * @return bool True si la pièce était disponible, False sinon.
     */
    public function dropCashback(CoinCode $coinCode): bool;
}
