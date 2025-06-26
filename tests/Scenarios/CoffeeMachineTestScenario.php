<?php

declare(strict_types=1);

namespace Tests\Scenarios;

use App\CoinCode;
use App\BrewerInterface;
use App\ChangeMachineInterface;
use App\CardHandleInterface;
use App\PaymentMethod;

/**
 * Scénario de test configuré par le Builder
 *
 * Encapsule la logique d'exécution d'un test de machine à café.
 * Les mocks sont configurés dans la classe de test principale.
 */
class CoffeeMachineTestScenario
{
    private const COFFEE_PRICE_CENTS = 50;

    public function __construct(
        private BrewerInterface $brewer,
        private ChangeMachineInterface $coinMachine,
        private CardHandleInterface $cardHandler,
        private ?CoinCode $coin,
        private bool $brewerSuccess,
        private int $expectedBrewerCalls,
        private int $expectedCoinMachineCalls,
        private int $expectedCardChargeCalls,
        private int $expectedCardRefundCalls,
        private bool $shouldCallBrewer,
        private bool $shouldCallCoinMachine,
        private bool $shouldCallCardCharge,
        private bool $shouldCallCardRefund,
        private ?PaymentMethod $paymentMethod = null,
        private bool $cardChargeSuccess = false,
        private int $toManyCoins = 0
    ) {
        $this->toManyCoins = $toManyCoins;
    }

    /**
     * Exécute le scénario principal de test
     */
    public function execute(): void
    {
        if ($this->paymentMethod === PaymentMethod::CARD) {
            $this->handleCardPayment();
            return;
        }

        if ($this->toManyCoins > 5) {
            echo "Too many coins detected\n";
            $this->handleTooManyCoins();
            return;
        }

        if ($this->coin === null) {
            return;
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
     * Retourne le nombre de pièces insérées
     */
    public function getToManyCoins(): int
    {
        return $this->toManyCoins;
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
     * Retourne le nombre d'appels attendus pour le prélèvement carte
     */
    public function getExpectedCardChargeCalls(): int
    {
        return $this->expectedCardChargeCalls;
    }

    /**
     * Retourne le nombre d'appels attendus pour le remboursement carte
     */
    public function getExpectedCardRefundCalls(): int
    {
        return $this->expectedCardRefundCalls;
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
     * Indique si le prélèvement carte doit être appelé
     */
    public function shouldCallCardCharge(): bool
    {
        return $this->shouldCallCardCharge;
    }

    /**
     * Indique si le remboursement carte doit être appelé
     */
    public function shouldCallCardRefund(): bool
    {
        return $this->shouldCallCardRefund;
    }

    /**
     * Retourne le résultat attendu du brewer
     */
    public function getBrewerSuccess(): bool
    {
        return $this->brewerSuccess;
    }

    /**
     * Retourne le résultat attendu du prélèvement carte
     */
    public function getCardChargeSuccess(): bool
    {
        return $this->cardChargeSuccess;
    }

    /**
     * Gère le paiement par carte
     */
    private function handleCardPayment(): void
    {
        // Tentative de prélèvement
        $chargeSuccess = $this->cardHandler->tryChargeAmount(self::COFFEE_PRICE_CENTS);

        if (!$chargeSuccess) {
            // Prélèvement échoué, pas de café
            return;
        }

        // Prélèvement réussi, tentative de café
        $coffeeSuccess = $this->brewer->makeACoffee();

        if (!$coffeeSuccess) {
            // Café échoué, remboursement
            $this->cardHandler->refund(self::COFFEE_PRICE_CENTS);
        }
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

    /**
     *  Gère le cas quand il y a trop de pièces insérées (> 5 pièces)
     */
    private function handleTooManyCoins(): void
    {
        if ($this->shouldCallCoinMachine) {
            $this->coinMachine->flushStoredMoney();
        }
    }
}