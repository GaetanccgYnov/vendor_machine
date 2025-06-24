<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\CoinCode;
use App\BrewerInterface;
use App\ChangeMachineInterface;
use Tests\Builders\CoffeeMachineTestBuilder;
use Tests\Scenarios\CoffeeMachineTestScenario;

/**
 * Factory pour créer des scénarios de test prédéfinis
 *
 * Fournit des méthodes statiques pour créer rapidement
 * des scénarios de test courants.
 */
class CoffeeMachineTestFactory
{
    /**
     * Crée un scénario de succès avec une pièce valide
     */
    public static function createSuccessfulCoffeeScenario(
        BrewerInterface $brewer,
        ChangeMachineInterface $coinMachine,
        CoinCode $coin = CoinCode::ONE_EURO
    ): CoffeeMachineTestScenario {
        return (new CoffeeMachineTestBuilder($brewer, $coinMachine))
            ->withCoin($coin)
            ->withBrewerSuccess(true)
            ->expectBrewerCalls(1)
            ->expectNoCoinMachineCalls()
            ->build();
    }

    /**
     * Crée un scénario d'échec avec remboursement
     */
    public static function createFailureWithRefundScenario(
        BrewerInterface $brewer,
        ChangeMachineInterface $coinMachine,
        CoinCode $coin = CoinCode::ONE_EURO
    ): CoffeeMachineTestScenario {
        return (new CoffeeMachineTestBuilder($brewer, $coinMachine))
            ->withCoin($coin)
            ->withBrewerSuccess(false)
            ->expectBrewerCalls(1)
            ->expectCoinMachineCalls(1)
            ->build();
    }

    /**
     * Crée un scénario avec pièce invalide
     */
    public static function createInvalidCoinScenario(
        BrewerInterface $brewer,
        ChangeMachineInterface $coinMachine,
        CoinCode $coin = CoinCode::TEN_CENTS
    ): CoffeeMachineTestScenario {
        return (new CoffeeMachineTestBuilder($brewer, $coinMachine))
            ->withCoin($coin)
            ->expectNoBrewerCalls()
            ->expectCoinMachineCalls(1)
            ->build();
    }

    /**
     * Crée un scénario sans pièce
     */
    public static function createNoCoinScenario(
        BrewerInterface $brewer,
        ChangeMachineInterface $coinMachine
    ): CoffeeMachineTestScenario {
        return (new CoffeeMachineTestBuilder($brewer, $coinMachine))
            ->expectNoBrewerCalls()
            ->expectNoCoinMachineCalls()
            ->build();
    }

    /**
     * Crée un scénario pour cafés multiples
     */
    public static function createMultipleCoffeesScenario(
        BrewerInterface $brewer,
        ChangeMachineInterface $coinMachine,
        int $coffeeCount = 2
    ): CoffeeMachineTestScenario {
        return (new CoffeeMachineTestBuilder($brewer, $coinMachine))
            ->expectBrewerCalls($coffeeCount)
            ->expectNoCoinMachineCalls()
            ->build();
    }

    /**
     * Retourne toutes les pièces valides
     */
    public static function getValidCoins(): array
    {
        return [
            CoinCode::FIFTY_CENTS,
            CoinCode::ONE_EURO,
            CoinCode::TWO_EUROS,
        ];
    }

    /**
     * Retourne toutes les pièces invalides
     */
    public static function getInvalidCoins(): array
    {
        return [
            CoinCode::ONE_CENT,
            CoinCode::TWO_CENTS,
            CoinCode::FIVE_CENTS,
            CoinCode::TEN_CENTS,
            CoinCode::TWENTY_CENTS,
        ];
    }
}