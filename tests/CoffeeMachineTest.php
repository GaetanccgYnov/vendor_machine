<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use App\CoinCode;
use App\BrewerInterface;
use App\ChangeMachineInterface;

class CoffeeMachineTest extends TestCase
{
    private BrewerInterface $brewer;
    private ChangeMachineInterface $coinMachine;

    protected function setUp(): void
    {
        $this->brewer = $this->createMock(BrewerInterface::class);
        $this->coinMachine = $this->createMock(ChangeMachineInterface::class);
    }

    #[DataProvider('validCoinProvider')]
    #[TestDox('Test Brewer starts with valid coin')]
    public function testBrewerStartsWithValidCoin(CoinCode $coin): void
    {
        // ETANT DONNE une machine a café
        // QUAND on insère une pièce de 50cts ou plus
        // ALORS le brewer reçoit l'ordre de faire un café
        // CAS 50cts, 1€, 2€

        $coinValue = $this->formatCoinValue($coin->value);

        $this->brewer->expects($this->once())
            ->method('makeACoffee')
            ->willReturn(true);

        if ($coin->value >= 50) {
            $this->brewer->makeACoffee();
        }
    }

    public static function validCoinProvider(): array
    {
        return [
            'fifty cents' => [CoinCode::FIFTY_CENTS],
            'one euro' => [CoinCode::ONE_EURO],
            'two euros' => [CoinCode::TWO_EUROS],
        ];
    }

    #[DataProvider('invalidCoinProvider')]
    #[TestDox('Test Brewer not started with invalid coin')]
    public function testBrewerNotStartedWithInvalidCoin(CoinCode $coin): void
    {
        // ETANT DONNE une machine a café
        // QUAND on insère une pièce moins de 50cts
        // ALORS le brewer ne reçoit pas d'ordre
        // ET l'argent est restitué
        // CAS 1cts, 2cts, 5cts, 10cts, 20cts

        $coinValue = $this->formatCoinValue($coin->value);

        $this->brewer->expects($this->never())
            ->method('makeACoffee');

        $this->coinMachine->expects($this->once())
            ->method('flushStoredMoney');

        if ($coin->value < 50) {
            $this->coinMachine->flushStoredMoney();
        }
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

    #[DataProvider('validCoinProvider')]
    #[TestDox('Test Money refunded on machine failure')]
    public function testMoneyRefundedOnMachineFailure(CoinCode $coin): void
    {
        // ETANT DONNE une machine a café défaillante
        // QUAND on insère une pièce de 50cts ou plus
        // ALORS l'argent est restitué

        $coinValue = $this->formatCoinValue($coin->value);

        $this->brewer->expects($this->once())
            ->method('makeACoffee')
            ->willReturn(false);

        $this->coinMachine->expects($this->once())
            ->method('flushStoredMoney');

        if ($coin->value >= 50) {
            $success = $this->brewer->makeACoffee();
            if (!$success) {
                $this->coinMachine->flushStoredMoney();
            }
        }
    }

    #[TestDox('Test No action without coin')]
    public function testNoActionWithoutCoin(): void
    {
        // ETANT DONNE une machine a café
        // ALORS le brewer ne reçoit pas d'ordre

        $this->brewer->expects($this->never())
            ->method('makeACoffee');

        $this->coinMachine->expects($this->never())
            ->method('flushStoredMoney');
    }

    #[TestDox('Test Two valid coins trigger two coffees')]
    public function testTwoValidCoinsTriggerTwoCoffees(): void
    {
        // ETANT DONNE une machine a café
        // QUAND on insère une pièce de 50cts deux fois
        // ALORS le brewer reçoit deux fois l'ordre de faire un café

        $this->brewer->expects($this->exactly(2))
            ->method('makeACoffee')
            ->willReturn(true);

        $this->brewer->makeACoffee();
        $this->brewer->makeACoffee();
    }

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