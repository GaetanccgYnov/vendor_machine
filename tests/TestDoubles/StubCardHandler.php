<?php

namespace Tests\TestDoubles;

use App\CardHandleInterface;

/**
 * Stub pour le gestionnaire de carte - comportement prédéfini
 */
class StubCardHandler implements CardHandleInterface
{
    private bool $chargeWillSucceed = true;
    private array $charges = [];
    private array $refunds = [];

    public function tryChargeAmount(int $amountInCents): bool
    {
        $this->charges[] = $amountInCents;
        return $this->chargeWillSucceed;
    }

    public function refund(int $amountInCents): void
    {
        $this->refunds[] = $amountInCents;
    }

    // Méthodes de configuration pour les tests
    public function simulateChargeFailure(): void
    {
        $this->chargeWillSucceed = false;
    }

    public function wasCharged(): bool
    {
        return !empty($this->charges);
    }

    public function wasRefunded(): bool
    {
        return !empty($this->refunds);
    }

    public function getChargeAmount(): ?int
    {
        return $this->charges[0] ?? null;
    }

    public function getRefundAmount(): ?int
    {
        return $this->refunds[0] ?? null;
    }

    public function reset(): void
    {
        $this->charges = [];
        $this->refunds = [];
        $this->chargeWillSucceed = true;
    }
}
