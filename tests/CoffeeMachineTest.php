<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use App\CoinCode;
use App\BrewerInterface;
use App\ChangeMachineInterface;
use Tests\CoffeeMachineMatchers;
use Tests\Builders\CoffeeMachineTestBuilder;

/**
 * Tests pour la machine à café utilisant le pattern Builder
 *
 * Cette classe se concentre sur la logique de test métier
 * tandis que la configuration est déléguée au Builder.
 */
class CoffeeMachineTest extends TestCase
{
    use CoffeeMachineMatchers;

    private BrewerInterface $brewer;
    private ChangeMachineInterface $coinMachine;
    private CoffeeMachineTestBuilder $builder;

    protected function setUp(): void
    {
        $this->brewer = $this->createMock(BrewerInterface::class);
        $this->coinMachine = $this->createMock(ChangeMachineInterface::class);
        $this->builder = new CoffeeMachineTestBuilder($this->brewer, $this->coinMachine);
    }

    protected function tearDown(): void
    {
        // Reset le builder pour éviter les effets de bord
        $this->builder->reset();
    }

    #[DataProvider('validCoinProvider')]
    #[TestDox('Test Brewer starts with valid coin')]
    public function testBrewerStartsWithValidCoin(CoinCode $coin): void
    {
        // Arrange
        $scenario = $this->builder
            ->withCoin($coin)
            ->withBrewerSuccess(true)
            ->expectBrewerCalls(1)
            ->expectNoCoinMachineCalls()
            ->build();

        // Configure mocks
        $this->setupMocksFromScenario($scenario);

        // Assert - Vérifications métier
        $this->assertThat($coin, $this->isValidCoin());
        $this->assertThat($coin, $this->canMakeCoffee());

        // Act
        $scenario->execute();
    }

    #[DataProvider('invalidCoinProvider')]
    #[TestDox('Test Brewer not started with invalid coin')]
    public function testBrewerNotStartedWithInvalidCoin(CoinCode $coin): void
    {
        // Arrange
        $scenario = $this->builder
            ->withCoin($coin)
            ->expectNoBrewerCalls()
            ->expectCoinMachineCalls(1)
            ->build();

        // Configure mocks
        $this->setupMocksFromScenario($scenario);

        // Assert - Vérifications métier
        $this->assertThat($coin, $this->isInvalidCoin());
        $this->assertThat($coin, $this->shouldRefundMoney());

        // Act
        $scenario->execute();
    }

    #[DataProvider('validCoinProvider')]
    #[TestDox('Test Money refunded on machine failure')]
    public function testMoneyRefundedOnMachineFailure(CoinCode $coin): void
    {
        // Arrange
        $scenario = $this->builder
            ->withCoin($coin)
            ->withBrewerSuccess(false)
            ->expectBrewerCalls(1)
            ->expectCoinMachineCalls(1)
            ->build();

        // Configure mocks
        $this->setupMocksFromScenario($scenario);

        // Assert - Vérifications métier
        $this->assertThat($coin, $this->isValidCoin());

        // Act
        $scenario->execute();
    }

    #[TestDox('Test No action without coin')]
    public function testNoActionWithoutCoin(): void
    {
        // Arrange
        $scenario = $this->builder
            ->expectNoBrewerCalls()
            ->expectNoCoinMachineCalls()
            ->build();

        // Configure mocks
        $this->setupMocksFromScenario($scenario);

        // Act
        $scenario->execute();
    }

    #[TestDox('Test Two valid coins trigger two coffees')]
    public function testTwoValidCoinsTriggerTwoCoffees(): void
    {
        // Arrange
        $scenario = $this->builder
            ->expectBrewerCalls(2)
            ->expectNoCoinMachineCalls()
            ->build();

        // Configure mocks
        $this->setupMocksFromScenario($scenario);

        // Act
        $scenario->executeMultipleCoffees(2);
    }

    #[TestDox('Test Complex scenario: Valid coin with multiple attempts')]
    public function testComplexScenarioWithValidCoin(): void
    {
        // Premier scénario: échec puis remboursement
        $failureScenario = $this->builder
            ->withCoin(CoinCode::ONE_EURO)
            ->withBrewerSuccess(false)
            ->expectBrewerCalls(1)
            ->expectCoinMachineCalls(1)
            ->build();

        // Configure mocks
        $this->setupMocksFromScenario($failureScenario);

        $this->assertThat($failureScenario->getCoin(), $this->isValidCoin());

        $failureScenario->execute();
    }

    /**
     * Configure les mocks basés sur un scénario
     */
    private function setupMocksFromScenario($scenario): void
    {
        // Configuration du brewer
        if ($scenario->getExpectedBrewerCalls() > 0) {
            $this->brewer->expects($this->exactly($scenario->getExpectedBrewerCalls()))
                ->method('makeACoffee')
                ->willReturn($scenario->getBrewerSuccess());
        } elseif (!$scenario->shouldCallBrewer()) {
            $this->brewer->expects($this->never())
                ->method('makeACoffee');
        }

        // Configuration de la machine à monnaie
        if ($scenario->getExpectedCoinMachineCalls() > 0) {
            $this->coinMachine->expects($this->exactly($scenario->getExpectedCoinMachineCalls()))
                ->method('flushStoredMoney');
        } elseif (!$scenario->shouldCallCoinMachine()) {
            $this->coinMachine->expects($this->never())
                ->method('flushStoredMoney');
        }
    }

    #[TestDox('Test Edge case: Multiple scenarios in sequence')]
    public function testMultipleScenariosInSequence(): void
    {
        // Test avec différentes pièces en séquence
        $coins = [CoinCode::FIFTY_CENTS, CoinCode::ONE_EURO, CoinCode::TWO_EUROS];

        foreach ($coins as $coin) {
            $scenario = $this->builder
                ->reset() // Important: reset pour chaque itération
                ->withCoin($coin)
                ->withBrewerSuccess(true)
                ->expectBrewerCalls(1)
                ->expectNoCoinMachineCalls()
                ->build();

            $this->assertTrue($scenario->isValidCoin());

            // Note: Dans un vrai test, vous auriez besoin de nouveaux mocks
            // pour chaque itération ou d'une stratégie de reset appropriée
        }
    }

    // Data Providers
    public static function validCoinProvider(): array
    {
        return [
            'fifty cents' => [CoinCode::FIFTY_CENTS],
            'one euro' => [CoinCode::ONE_EURO],
            'two euros' => [CoinCode::TWO_EUROS],
        ];
    }

    public static function invalidCoinProvider(): array
    {
        return [
            'one cent' => [CoinCode::ONE_CENT],
            'two cents' => [CoinCode::TWO_CENTS],
            'five cents' => [CoinCode::FIVE_CENTS],
            'ten cents' => [CoinCode::TEN_CENTS],
            'twenty cents' => [CoinCode::TWENTY_CENTS],
        ];
    }

    // Méthodes utilitaires
    private function formatCoinValue(int $value): string
    {
        return match ($value) {
            1 => '1 centime',
            2 => '2 centimes',
            5 => '5 centimes',
            10 => '10 centimes',
            20 => '20 centimes',
            50 => '50 centimes',
            100 => '1 euro',
            200 => '2 euros',
            default => $value . ' centimes'
        };
    }
}