<?php
namespace App;

interface CreditCardInterface
{
    /**
     * Enregistre un callback appelé lors de la détection d'une carte.
     *
     * @param CardHandleInterface|null $cardDetectedCallback Instance de gestion de la carte détectée.
     * @return void
     */
    public function registerCardDetectedCallback(?CardHandleInterface $cardDetectedCallback): void;
}
