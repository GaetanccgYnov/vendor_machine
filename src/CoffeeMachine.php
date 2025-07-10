<?php

declare(strict_types=1);

namespace App;

/**
 * Machine à café - Logique métier séparée du hardware
 *
 * Cette classe orchestre les interactions entre les différents composants hardware
 * pour réaliser les fonctionnalités de la machine à café.
 */
class CoffeeMachine
{
    private const COFFEE_PRICE_CENTS = 50;

    public function __construct(
        private BrewerInterface $brewer,
        private ChangeMachineInterface $changeMachine,
        private CardHandleInterface $cardHandler
    ) {
        // Enregistrer le callback pour les pièces insérées
        $this->changeMachine->registerMoneyInsertedCallback(
            fn(int $coinValue) => $this->handleCoinInsertion($coinValue)
        );
    }

    /**
     * Traite l'insertion d'une pièce
     */
    private function handleCoinInsertion(int $coinValue): void
    {
        if ($this->isCoinSufficient($coinValue)) {
            $this->processSufficientCoinPayment($coinValue);
        } else {
            $this->processInsufficientCoinPayment();
        }
    }

    /**
     * Traite un paiement par carte bancaire
     */
    public function processCardPayment(): void
    {
        $chargeSuccessful = $this->cardHandler->tryChargeAmount(self::COFFEE_PRICE_CENTS);

        if (!$chargeSuccessful) {
            return; // Paiement refusé, rien à faire
        }

        $brewingSuccessful = $this->brewer->makeACoffee();

        if (!$brewingSuccessful) {
            // Café raté, rembourser la carte
            $this->cardHandler->refund(self::COFFEE_PRICE_CENTS);
        }
    }

    /**
     * Vérifie si une pièce est suffisante pour acheter un café
     */
    private function isCoinSufficient(int $coinValue): bool
    {
        return $coinValue >= self::COFFEE_PRICE_CENTS;
    }

    /**
     * Traite un paiement avec une pièce suffisante
     */
    private function processSufficientCoinPayment(int $coinValue): void
    {
        $brewingSuccessful = $this->brewer->makeACoffee();

        if ($brewingSuccessful) {
            $this->handleSuccessfulBrewing($coinValue);
        } else {
            $this->handleFailedBrewing();
        }
    }

    /**
     * Traite un paiement avec une pièce insuffisante
     */
    private function processInsufficientCoinPayment(): void
    {
        // Pièce insuffisante, la rendre
        $this->changeMachine->flushStoredMoney();
    }

    /**
     * Gère le succès de la préparation du café
     */
    private function handleSuccessfulBrewing(int $coinValue): void
    {
        // Encaisser la pièce
        $this->changeMachine->collectStoredMoney();

        // Calculer et rendre la monnaie si nécessaire
        $changeAmount = $coinValue - self::COFFEE_PRICE_CENTS;
        if ($changeAmount > 0) {
            $this->returnChange($changeAmount);
        }
    }

    /**
     * Gère l'échec de la préparation du café
     */
    private function handleFailedBrewing(): void
    {
        // Café raté, rendre la pièce
        $this->changeMachine->flushStoredMoney();
    }

    /**
     * Rend la monnaie en utilisant les plus grosses pièces en premier
     */
    private function returnChange(int $changeAmount): void
    {
        $coinsToReturn = $this->calculateOptimalChange($changeAmount);

        foreach ($coinsToReturn as $coin) {
            $dropped = $this->changeMachine->dropCashback($coin);
            if (!$dropped) {
                // Plus assez de stock pour cette pièce, essayer avec d'autres
                break;
            }
        }
    }

    /**
     * Calcule la monnaie optimale à rendre (plus grosses pièces en premier)
     */
    private function calculateOptimalChange(int $amount): array
    {
        $availableCoins = [
            CoinCode::TWO_EUROS,
            CoinCode::ONE_EURO,
            CoinCode::FIFTY_CENTS,
            CoinCode::TWENTY_CENTS,
            CoinCode::TEN_CENTS,
            CoinCode::FIVE_CENTS,
            CoinCode::TWO_CENTS,
            CoinCode::ONE_CENT
        ];

        $change = [];
        $remainingAmount = $amount;

        foreach ($availableCoins as $coin) {
            while ($remainingAmount >= $coin->value) {
                $change[] = $coin;
                $remainingAmount -= $coin->value;
            }
        }

        return $change;
    }
}