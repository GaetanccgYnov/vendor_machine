<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use App\CoinCode;
use App\BrewerInterface;
use App\ChangeMachineInterface;
use App\CardHandleInterface;
use Tests\CoffeeMachineMatchers;
use Tests\Builders\CoffeeMachineTestBuilder;

class CoffeeMachineTest extends TestCase
{
    use CoffeeMachineMatchers;

    private BrewerInterface $brewer;
    private ChangeMachineInterface $coinMachine;
    private CardHandleInterface $cardHandler;
    private CoffeeMachineTestBuilder $builder;
    private const COFFEE_PRICE_CENTS = 50; // Prix du café en centimes

    protected function setUp(): void
    {
        $this->brewer = $this->createMock(BrewerInterface::class);
        $this->coinMachine = $this->createMock(ChangeMachineInterface::class);
        $this->cardHandler = $this->createMock(CardHandleInterface::class);
        $this->builder = new CoffeeMachineTestBuilder(
            $this->brewer,
            $this->coinMachine,
            $this->cardHandler
        );
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
            ->expectNoCardCalls()
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
            ->expectNoCardCalls()
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
            ->expectNoCardCalls()
            ->build();

        // Configure mocks
        $this->setupMocksFromScenario($scenario);

        // Assert - Vérifications métier
        $this->assertThat($coin, $this->isValidCoin());

        // Act
        $scenario->execute();
    }

    #[TestDox('Test Card accepted, charges amount and starts brewer')]
    public function testCardAcceptedChargesAmountAndStartsBrewer(): void
    {
        $scenario = $this->builder
            ->withCardPayment(true)
            ->withBrewerSuccess(true)
            ->expectBrewerCalls(1)
            ->expectNoCoinMachineCalls()
            ->expectCardChargeCalls(1)
            ->expectNoCardRefundCalls()
            ->build();

        $this->setupMocksFromScenario($scenario);

        $scenario->execute();
    }

    #[TestDox('Test Card refused, does not charge or brew')]
    public function testCardRefusedDoesNotChargeOrBrew(): void
    {
        $scenario = $this->builder
            ->withCardPayment(false)
            ->expectNoBrewerCalls()
            ->expectNoCoinMachineCalls()
            ->expectCardChargeCalls(1) // Simule un échec de prélèvement
            ->expectNoCardRefundCalls()
            ->build();

        $this->setupMocksFromScenario($scenario);

        $scenario->execute();
    }

    #[TestDox('Test Card accepted but coffee unavailable, refunds')]
    public function testCardAcceptedButCoffeeUnavailableRefunds(): void
    {
        $scenario = $this->builder
            ->withCardPayment(true)
            ->withBrewerSuccess(false)
            ->expectBrewerCalls(1)
            ->expectNoCoinMachineCalls()
            ->expectCardChargeCalls(1)
            ->expectCardRefundCalls(1)
            ->build();

        $this->setupMocksFromScenario($scenario);

        $scenario->execute();
    }

    #[TestDox('Test No action without payment')]
    public function testNoActionWithoutPayment(): void
    {
        // Arrange
        $scenario = $this->builder
            ->expectNoBrewerCalls()
            ->expectNoCoinMachineCalls()
            ->expectNoCardCalls()
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
            ->expectNoCardCalls()
            ->build();

        // Configure mocks
        $this->setupMocksFromScenario($scenario);

        // Act
        $scenario->executeMultipleCoffees(2);
    }

    #[TestDox('Test Complex scenario: Valid coin with multiple attempts')]
    public function testComplexScenarioWithValidCoin(): void
    {
        // Arrange
        $failureScenario = $this->builder
            ->withCoin(CoinCode::ONE_EURO)
            ->withBrewerSuccess(false)
            ->expectBrewerCalls(1)
            ->expectCoinMachineCalls(1)
            ->expectNoCardCalls()
            ->build();

        // Configure mocks
        $this->setupMocksFromScenario($failureScenario);

        $this->assertThat($failureScenario->getCoin(), $this->isValidCoin());

        // Act
        $failureScenario->execute();
    }

    #[TestDox('Test Multiple payment methods in sequence')]
    public function testMultiplePaymentMethodsInSequence(): void
    {
        $coinScenario = $this->builder
            ->withCoin(CoinCode::ONE_EURO)
            ->withBrewerSuccess(true)
            ->expectBrewerCalls(1)
            ->expectNoCoinMachineCalls()
            ->expectNoCardCalls()
            ->build();

        $this->setupMocksFromScenario($coinScenario);
        $coinScenario->execute();

        $this->builder->reset();
        $this->setUp();

        $cardScenario = $this->builder
            ->withCardPayment(true)
            ->withBrewerSuccess(true)
            ->expectBrewerCalls(1)
            ->expectNoCoinMachineCalls()
            ->expectCardChargeCalls(1)
            ->expectNoCardRefundCalls()
            ->build();

        $this->setupMocksFromScenario($cardScenario);
        $cardScenario->execute();
    }

    #[TestDox('Test Edge case: Multiple scenarios in sequence')]
    public function testMultipleScenariosInSequence(): void
    {
        // Test with valid coins
        $coins = [CoinCode::FIFTY_CENTS, CoinCode::ONE_EURO, CoinCode::TWO_EUROS];

        foreach ($coins as $coin) {
            $scenario = $this->builder
                ->reset()
                ->withCoin($coin)
                ->withBrewerSuccess(true)
                ->expectBrewerCalls(1)
                ->expectNoCoinMachineCalls()
                ->build();
        }

            // Configure mocks
            $this->setupMocksFromScenario($scenario);

            $this->assertThat($coin, $this->isValidCoin());

            // Act
            $scenario->execute();
    }

    private function setupMocksFromScenario($scenario): void
    {
        // Brewer configuration
        if ($scenario->getExpectedBrewerCalls() > 0) {
            $this->brewer->expects($this->exactly($scenario->getExpectedBrewerCalls()))
                ->method('makeACoffee')
                ->willReturn($scenario->getBrewerSuccess());
        } elseif (!$scenario->shouldCallBrewer()) {
            $this->brewer->expects($this->never())
                ->method('makeACoffee');
        }

        // Coin machine configuration - refund
        if ($scenario->getExpectedCoinMachineCalls() > 0) {
            $this->coinMachine->expects($this->exactly($scenario->getExpectedCoinMachineCalls()))
                ->method('flushStoredMoney');
        } elseif (!$scenario->shouldCallCoinMachine()) {
            $this->coinMachine->expects($this->never())
                ->method('flushStoredMoney');
        }

        // Card handler configuration - charge
        if ($scenario->getExpectedCardChargeCalls() > 0) {
            $this->cardHandler->expects($this->exactly($scenario->getExpectedCardChargeCalls()))
                ->method('tryChargeAmount')
                ->with(self::COFFEE_PRICE_CENTS)
                ->willReturn($scenario->getCardChargeSuccess());
        } elseif (!$scenario->shouldCallCardCharge()) {
            $this->cardHandler->expects($this->never())
                ->method('tryChargeAmount');
        }

        // Card handler configuration - refund
        if ($scenario->getExpectedCardRefundCalls() > 0) {
            $this->cardHandler->expects($this->exactly($scenario->getExpectedCardRefundCalls()))
                ->method('refund')
                ->with(self::COFFEE_PRICE_CENTS);
        } elseif (!$scenario->shouldCallCardRefund()) {
            $this->cardHandler->expects($this->never())
                ->method('refund');
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

    // Utilities
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