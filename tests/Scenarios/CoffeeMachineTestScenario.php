<?php

declare(strict_types=1);

namespace Tests\Scenarios;

use App\CoinCode;
use App\BrewerInterface;
use App\ChangeMachineInterface;
use App\PaymentMethod;

/**
 * Scénario de test configuré par le Builder
 *
 * Encapsule la logique d'exécution d'un test de machine à café.
 * Les mocks sont configurés dans la classe de test principale.
 */
class CoffeeMachineTestScenario
{
    public function __construct(
        private BrewerInterface $brewer,
        private ChangeMachineInterface $coinMachine,
        private ?CoinCode $coin,
        private bool $brewerSuccess,
        private int $expectedBrewerCalls,
        private int $expectedCoinMachineCalls,
        private bool $shouldCallBrewer,
        private bool $shouldCallCoinMachine,
        private ?PaymentMethod $paymentMethod = null,
        private bool $cardAccepted = false
    ) {}

    /**
     * Exécute le scénario principal de test
     */
    public function execute(): void
    {
        if ($this->paymentMethod === PaymentMethod::CARD) {
            if ($this->cardAccepted && $this->shouldCallBrewer) {
                $this->brewer->makeACoffee();
            }
            return;
        }

        if ($this->coin === null) {
            return; // Pas de pièce, pas d'action
        }

        if ($this->isValidCoin()) {
            $this->handleValidCoin();
        } else {
            $this->handleInvalidCoin();
        }
    }

    /**
     * Exécute plusieurs cafés consécutifs
     */
    public function executeMultipleCoffees(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->brewer->makeACoffee();
        }
    }

    /**
     * Retourne la pièce utilisée dans le scénario
     */
    public function getCoin(): ?CoinCode
    {
        return $this->coin;
    }

    /**
     * Vérifie si la pièce est valide (>= 50 centimes)
     */
    public function isValidCoin(): bool
    {
        return $this->coin !== null && $this->coin->value >= 50;
    }

    /**
     * Retourne le nombre d'appels attendus pour le brewer
     */
    public function getExpectedBrewerCalls(): int
    {
        return $this->expectedBrewerCalls;
    }

    /**
     * Retourne le nombre d'appels attendus pour la machine à monnaie
     */
    public function getExpectedCoinMachineCalls(): int
    {
        return $this->expectedCoinMachineCalls;
    }

    /**
     * Indique si le brewer doit être appelé
     */
    public function shouldCallBrewer(): bool
    {
        return $this->shouldCallBrewer;
    }

    /**
     * Indique si la machine à monnaie doit être appelée
     */
    public function shouldCallCoinMachine(): bool
    {
        return $this->shouldCallCoinMachine;
    }

    /**
     * Retourne le résultat attendu du brewer
     */
    public function getBrewerSuccess(): bool
    {
        return $this->brewerSuccess;
    }

    /**
     * Gère le cas d'une pièce valide
     */
    private function handleValidCoin(): void
    {
        $success = $this->brewer->makeACoffee();

        if (!$success && $this->shouldCallCoinMachine) {
            $this->coinMachine->flushStoredMoney();
        }
    }

    /**
     * Gère le cas d'une pièce invalide
     */
    private function handleInvalidCoin(): void
    {
        if ($this->shouldCallCoinMachine) {
            $this->coinMachine->flushStoredMoney();
        }
    }
}