<?php

interface ButtonPanelInterface
{
    /**
     * Enregistre un callback appelé lors de l'appui sur un bouton de la façade avant.
     *
     * @param callable|null $callback Fonction callback prenant en paramètre l'ID du bouton pressé.
     * @return void
     */
    public function registerButtonPressedCallback(?callable $callback): void;

    /**
     * Allume ou éteint la LED informant de l'impossibilité d'avoir un allongé.
     *
     * @param bool $state Le nouvel état de la LED (true = allumé, false = éteint).
     * @return void
     */
    public function setLungoWarningState(bool $state): void;
}
