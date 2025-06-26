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
    private const COFFEE_PRICE_CENTS = 50;

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

        // Assert
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

        // Assert
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

        // Assert
        $this->assertThat($failureScenario->getCoin(), $this->isValidCoin());

        // Act
        $failureScenario->execute();
    }

    #[TestDox('Test Multiple payment methods in sequence')]
    public function testMultiplePaymentMethodsInSequence(): void
    {
        // Arrange
        $coinScenario = $this->builder
            ->withCoin(CoinCode::ONE_EURO)
            ->withBrewerSuccess(true)
            ->expectBrewerCalls(1)
            ->expectNoCoinMachineCalls()
            ->expectNoCardCalls()
            ->build();

        // Configure mocks
        $this->setupMocksFromScenario($coinScenario);

        // Act
        $coinScenario->execute();

        // Reset builder for next scenario
        $this->builder->reset();
        $this->setUp();

        // Card payment scenario
        $cardScenario = $this->builder
            ->withCardPayment(true)
            ->withBrewerSuccess(true)
            ->expectBrewerCalls(1)
            ->expectNoCoinMachineCalls()
            ->expectCardChargeCalls(1)
            ->expectNoCardRefundCalls()
            ->build();

        // Configure mocks for card scenario
        $this->setupMocksFromScenario($cardScenario);

        // Act
        $cardScenario->execute();
    }

    #[TestDox('Test Edge case: Multiple scenarios in sequence')]
    public function testMultipleScenariosInSequence(): void
    {
        $coins = [CoinCode::FIFTY_CENTS, CoinCode::ONE_EURO, CoinCode::TWO_EUROS];

        // Arrange
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

            // Assert
            $this->assertThat($coin, $this->isValidCoin());

            // Act
            $scenario->execute();
    }

    #[TestDox('Test Coin: More than 5 coins in machine')]
    public function testMoreThanFiveCoins(): void
    {
        // Arrange
        $scenario = $this->builder
            ->withCoin(CoinCode::FIFTY_CENTS)
            ->withCoinInsertion(6)
            ->expectCoinMachineCalls(1)
            ->expectNoBrewerCalls()
            ->expectNoCardCalls()
            ->build();

        // Configure mocks
        $this->setupMocksFromScenario($scenario);

        // Act
        $scenario->execute();
    }

    #[TestDox('Test Exact payment - no change needed')]
    public function testExactPaymentNoChange(): void
    {
        // Arrange
        $scenario = $this->builder
            ->withCoin(CoinCode::FIFTY_CENTS)
            ->withInitialCoinStock([
                CoinCode::TWENTY_CENTS->value => 5,
                CoinCode::TEN_CENTS->value => 10
            ])
            ->withBrewerSuccess(true)
            ->expectBrewerCalls(1)
            ->expectNoChangeReturn()
            ->build();

        // Configure mocks
        $this->setupChangeManagementMocks($scenario,
            [CoinCode::TWENTY_CENTS->value => 5, CoinCode::TEN_CENTS->value => 10],
            [],
            [CoinCode::TWENTY_CENTS->value => 5, CoinCode::TEN_CENTS->value => 10, CoinCode::FIFTY_CENTS->value => 1]
        );

        // Act
        $scenario->execute();
    }

    #[TestDox('Test Overpayment with sufficient stock')]
    public function testOverpaymentWithSufficientStock(): void
    {
        // Arrange
        $scenario = $this->builder
            ->withCoin(CoinCode::ONE_EURO)
            ->withInitialCoinStock([
                CoinCode::TWENTY_CENTS->value => 5,
                CoinCode::TEN_CENTS->value => 10
            ])
            ->withBrewerSuccess(true)
            ->expectBrewerCalls(1)
            ->expectChangeReturn([
                CoinCode::TWENTY_CENTS,
                CoinCode::TWENTY_CENTS,
                CoinCode::TEN_CENTS
            ])
            ->build();

        // Configure mocks
        $expectedFinalStock = [
            CoinCode::TWENTY_CENTS->value => 3,
            CoinCode::TEN_CENTS->value => 9,
            CoinCode::ONE_EURO->value => 1
        ];

        $this->setupChangeManagementMocks($scenario,
            [CoinCode::TWENTY_CENTS->value => 5, CoinCode::TEN_CENTS->value => 10],
            [CoinCode::TWENTY_CENTS, CoinCode::TWENTY_CENTS, CoinCode::TEN_CENTS],
            $expectedFinalStock
        );

        // Act
        $scenario->execute();
    }

    #[TestDox('Test Overpayment with insufficient stock - no change returned')]
    public function testOverpaymentWithInsufficientStock(): void
    {
        // Arrange
        $scenario = $this->builder
            ->withCoin(CoinCode::TWO_EUROS)
            ->withInitialCoinStock([
                CoinCode::TEN_CENTS->value => 5
            ])
            ->withBrewerSuccess(true)
            ->expectBrewerCalls(1)
            ->expectNoChangeReturn()
            ->build();

        // Configure mocks
        $expectedFinalStock = [
            CoinCode::TEN_CENTS->value => 5,
            CoinCode::TWO_EUROS->value => 1
        ];

        $this->setupChangeManagementMocks($scenario,
            [CoinCode::TEN_CENTS->value => 5],
            [],
            $expectedFinalStock
        );

        // Act
        $scenario->execute();
    }

    #[TestDox('Test Change priority - largest coins first')]
    public function testChangePriorityLargestCoinsFirst(): void
    {
        // Arrange
        $scenario = $this->builder
            ->withMultipleCoins([CoinCode::ONE_EURO, CoinCode::TWENTY_CENTS])
            ->withInitialCoinStock([
                CoinCode::FIFTY_CENTS->value => 2,
                CoinCode::TWENTY_CENTS->value => 10,
                CoinCode::TEN_CENTS->value => 10
            ])
            ->withBrewerSuccess(true)
            ->expectBrewerCalls(1)
            ->expectChangeReturn([
                CoinCode::FIFTY_CENTS,
                CoinCode::TWENTY_CENTS
            ])
            ->build();

        // Configure mocks
        $expectedFinalStock = [
            CoinCode::FIFTY_CENTS->value => 1,
            CoinCode::TWENTY_CENTS->value => 10,
            CoinCode::TEN_CENTS->value => 10,
            CoinCode::ONE_EURO->value => 1
        ];

        $this->setupChangeManagementMocks($scenario,
            [CoinCode::FIFTY_CENTS->value => 2, CoinCode::TWENTY_CENTS->value => 10, CoinCode::TEN_CENTS->value => 10],
            [CoinCode::FIFTY_CENTS, CoinCode::TWENTY_CENTS],
            $expectedFinalStock
        );

        // Act
        $scenario->execute();
    }

    private function setupChangeManagementMocks(
        $scenario,
        array $initialStock,
        array $expectedChange,
        array $expectedFinalStock
    ): void {
        // Mock brewer
        if ($scenario->getExpectedBrewerCalls() > 0) {
            $this->brewer->expects($this->exactly($scenario->getExpectedBrewerCalls()))
                ->method('makeACoffee')
                ->willReturn($scenario->getBrewerSuccess());
        }

        // Mock coin machine - stock management
        $this->coinMachine->expects($this->once())
            ->method('getCurrentStock')
            ->willReturn($initialStock);

        if (!empty($expectedChange)) {
            $this->coinMachine->expects($this->once())
                ->method('returnChange')
                ->with($expectedChange);
        } else {
            $this->coinMachine->expects($this->never())
                ->method('returnChange');
        }

        $this->coinMachine->expects($this->once())
            ->method('updateStock')
            ->with($expectedFinalStock);

        // Mock to check if change can be made
        $changeAmount = 0;
        if ($scenario->getCoin()) {
            $changeAmount = $scenario->getCoin()->value - self::COFFEE_PRICE_CENTS;
        } elseif ($scenario->getMultipleCoins()) {
            $total = array_sum(array_map(fn($coin) => $coin->value, $scenario->getMultipleCoins()));
            $changeAmount = $total - self::COFFEE_PRICE_CENTS;
        }

        if ($changeAmount > 0) {
            $this->coinMachine->expects($this->once())
                ->method('canMakeChange')
                ->with($changeAmount, $initialStock)
                ->willReturn(!empty($expectedChange));
        } else {
            $this->coinMachine->expects($this->never())
                ->method('canMakeChange');
        }
    }

    public static function changeScenarioProvider(): array
    {
        return [
            'exact payment' => [
                'insertedCoins' => [CoinCode::FIFTY_CENTS],
                'initialStock' => [CoinCode::TWENTY_CENTS->value => 5],
                'expectedChange' => [],
                'expectedFinalStock' => [CoinCode::TWENTY_CENTS->value => 5, CoinCode::FIFTY_CENTS->value => 1],
                'shouldBrew' => true
            ],
            'overpayment with change' => [
                'insertedCoins' => [CoinCode::ONE_EURO],
                'initialStock' => [CoinCode::TWENTY_CENTS->value => 5, CoinCode::TEN_CENTS->value => 5],
                'expectedChange' => [CoinCode::TWENTY_CENTS, CoinCode::TWENTY_CENTS, CoinCode::TEN_CENTS],
                'expectedFinalStock' => [CoinCode::TWENTY_CENTS->value => 3, CoinCode::TEN_CENTS->value => 4, CoinCode::ONE_EURO->value => 1],
                'shouldBrew' => true
            ],
            'insufficient stock for change' => [
                'insertedCoins' => [CoinCode::TWO_EUROS],
                'initialStock' => [CoinCode::TEN_CENTS->value => 2],
                'expectedChange' => [],
                'expectedFinalStock' => [CoinCode::TEN_CENTS->value => 2, CoinCode::TWO_EUROS->value => 1],
                'shouldBrew' => true
            ]
        ];
    }

    private function setupMocksFromScenario($scenario): void
    {
        // If no coin or card payment, do nothing
        if ($scenario->getToManyCoins() > 5) {
            $this->brewer
                ->expects($this->never())
                ->method('makeACoffee');

            $this->coinMachine
                ->expects($this->once())
                ->method('flushStoredMoney');
            return;
        }

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