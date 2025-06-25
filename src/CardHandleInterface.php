<?php
namespace App;

interface CardHandleInterface
{
    /**
     * Tente de prélever le montant passé en paramètre sur la carte.
     *
     * @param int $amountInCents Le montant en centimes à prélever.
     * @return bool True si la somme a été prélevée, False sinon.
     */
    public function tryChargeAmount(int $amountInCents): bool;

    /**
     * Rembourse une somme sur la carte.
     *
     * @param int $amountInCents Le montant en centimes à rembourser.
     * @return void
     */
    public function refund(int $amountInCents): void;
}
