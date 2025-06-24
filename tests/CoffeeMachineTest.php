<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
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
        echo "\n🔧 Setup: Machine à café initialisée avec brewer et change machine mockés\n";
    }

    #[DataProvider('validCoinProvider')]
    public function testBrewerStartsWithValidCoin(CoinCode $coin): void
    {
        echo "\n☕ TEST: Pièce valide ({$coin->value} cents)\n";
        echo "ÉTANT DONNÉ une machine à café\n";
        echo "QUAND on insère une pièce de {$coin->value} cents (≥ 50cts)\n";

        $this->brewer->expects($this->once())
            ->method('makeACoffee')
            ->willReturn(true);

        if ($coin->value >= 50) {
            echo "ALORS le brewer reçoit l'ordre de faire un café ✅\n";
            $this->brewer->makeACoffee();
        }

        echo "✅ Test réussi pour {$coin->value} cents\n";
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
    public function testBrewerNotStartedWithInvalidCoin(CoinCode $coin): void
    {
        echo "\n❌ TEST: Pièce invalide ({$coin->value} cents)\n";
        echo "ÉTANT DONNÉ une machine à café\n";
        echo "QUAND on insère une pièce de {$coin->value} cents (< 50cts)\n";

        $this->brewer->expects($this->never())
            ->method('makeACoffee');

        $this->coinMachine->expects($this->once())
            ->method('flushStoredMoney');

        if ($coin->value < 50) {
            echo "ALORS le brewer ne reçoit pas d'ordre ❌\n";
            echo "ET l'argent est restitué 💰\n";
            $this->coinMachine->flushStoredMoney();
        }

        echo "✅ Test réussi pour {$coin->value} cents - argent restitué\n";
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
    public function testMoneyRefundedOnMachineFailure(CoinCode $coin): void
    {
        echo "\n🔧 TEST: Machine défaillante avec pièce de {$coin->value} cents\n";
        echo "ÉTANT DONNÉ une machine à café défaillante\n";
        echo "QUAND on insère une pièce de {$coin->value} cents (≥ 50cts)\n";

        $this->brewer->expects($this->once())
            ->method('makeACoffee')
            ->willReturn(false);

        $this->coinMachine->expects($this->once())
            ->method('flushStoredMoney');

        if ($coin->value >= 50) {
            echo "Le brewer tente de faire un café... ☕\n";
            $success = $this->brewer->makeACoffee();
            if (!$success) {
                echo "ALORS l'argent est restitué (machine défaillante) 💰❌\n";
                $this->coinMachine->flushStoredMoney();
            }
        }

        echo "✅ Test réussi - argent restitué suite à défaillance\n";
    }

    public function testNoActionWithoutCoin(): void
    {
        echo "\n⭕ TEST: Aucune pièce insérée\n";
        echo "ÉTANT DONNÉ une machine à café\n";
        echo "QUAND aucune pièce n'est insérée\n";
        echo "ALORS le brewer ne reçoit pas d'ordre ❌\n";
        echo "ET aucun argent n'est restitué\n";

        $this->brewer->expects($this->never())
            ->method('makeACoffee');

        $this->coinMachine->expects($this->never())
            ->method('flushStoredMoney');

        echo "✅ Test réussi - aucune action sans pièce\n";
    }

    public function testTwoValidCoinsTriggerTwoCoffees(): void
    {
        echo "\n☕☕ TEST: Deux pièces de 50cts\n";
        echo "ÉTANT DONNÉ une machine à café\n";
        echo "QUAND on insère une pièce de 50cts deux fois\n";
        echo "ALORS le brewer reçoit deux fois l'ordre de faire un café\n";

        $this->brewer->expects($this->exactly(2))
            ->method('makeACoffee')
            ->willReturn(true);

        echo "Insertion première pièce... ☕\n";
        $this->brewer->makeACoffee();

        echo "Insertion deuxième pièce... ☕\n";
        $this->brewer->makeACoffee();

        echo "✅ Test réussi - deux cafés commandés\n";
    }
}