<?php

interface CupProviderInterface
{
    /**
     * Relâche une touillette, sans possibilité de savoir si l'action a été efficace.
     *
     * @return void
     */
    public function provideStirrer(): void;

    /**
     * Renvoie l'état du capteur de présence d'une tasse.
     *
     * @return bool True si une tasse est présente, False sinon.
     */
    public function isCupPresent(): bool;

    /**
     * Relâche un gobelet, s'il en reste.
     * Il est conseillé de vérifier isCupPresent() ensuite.
     *
     * @return void
     */
    public function provideCup(): void;
}
