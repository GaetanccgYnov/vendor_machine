<?php

namespace Tests\Acceptance;

use App\CoffeeMachine;
use App\CoinCode;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Tests\TestDoubles\FakeChangeMachine;
use Tests\TestDoubles\SpyBrewer;
use Tests\TestDoubles\StubCardHandler;

class CoinPaymentTest extends TestCase
{
    private const COFFEE_PRICE_CENTS = 50;

    #[TestDox('ÉTANT DONNÉ une machine à café, QUAND on insère une pièce de 50 centimes, ALORS un café est préparé')]
    public function testSufficientCoinTriggersBrewingProcess(): void
    {
        // ÉTANT DONNÉ une machine à café
        $brewer = new SpyBrewer();
        $changeMachine = new FakeChangeMachine();
        $cardHandler = new StubCardHandler();
        $coffeeMachine = new CoffeeMachine($brewer, $changeMachine, $cardHandler);

        // QUAND on insère une pièce de 50 centimes
        $changeMachine->insertCoin(CoinCode::FIFTY_CENTS);

        // ALORS un café est préparé
        $this->assertTrue($brewer->wasCalled(), 'Le brewer aurait dû être appelé');
        $this->assertSame(1, $brewer->getCallCount(), 'Le brewer aurait dû être appelé exactement une fois');
    }

    #[TestDox('ÉTANT DONNÉ une machine à café, QUAND on insère une pièce de 20 centimes, ALORS la pièce est rendue')]
    public function testInsufficientCoinIsReturned(): void
    {
        // ÉTANT DONNÉ une machine à café
        $brewer = new SpyBrewer();
        $changeMachine = new FakeChangeMachine();
        $cardHandler = new StubCardHandler();
        $coffeeMachine = new CoffeeMachine($brewer, $changeMachine, $cardHandler);

        // QUAND on insère une pièce de 20 centimes (insuffisante)
        $changeMachine->insertCoin(CoinCode::TWENTY_CENTS);

        // ALORS la pièce est rendue ET aucun café n'est préparé
        $this->assertTrue($changeMachine->wasFlushed(), 'La pièce insuffisante aurait dû être rendue');
        $this->assertFalse($brewer->wasCalled(), 'Aucun café ne devrait être préparé avec une pièce insuffisante');
    }

    #[TestDox('ÉTANT DONNÉ une machine à café ET une pièce de 1 euro, QUAND le brewer échoue, ALORS la pièce est rendue')]
    public function testCoinReturnedWhenBrewerFails(): void
    {
        // ÉTANT DONNÉ une machine à café ET un brewer qui va échouer
        $brewer = new SpyBrewer();
        $brewer->simulateFailure();
        $changeMachine = new FakeChangeMachine();
        $cardHandler = new StubCardHandler();
        $coffeeMachine = new CoffeeMachine($brewer, $changeMachine, $cardHandler);

        // QUAND on insère une pièce de 1 euro
        $changeMachine->insertCoin(CoinCode::ONE_EURO);

        // ALORS la pièce est rendue
        $this->assertTrue($changeMachine->wasFlushed(), 'La pièce aurait dû être rendue après échec du brewer');
        $this->assertTrue($brewer->wasCalled(), 'Le brewer aurait dû être tenté');
    }

    #[TestDox('ÉTANT DONNÉ une machine à café ET une pièce de 1 euro, QUAND le paiement réussit, ALORS 50 centimes de monnaie sont rendus')]
    public function testChangeIsReturnedForOverpayment(): void
    {
        // ÉTANT DONNÉ une machine à café avec du stock de monnaie
        $brewer = new SpyBrewer();
        $changeMachine = new FakeChangeMachine();
        $changeMachine->setStock(CoinCode::TWENTY_CENTS, 3);
        $changeMachine->setStock(CoinCode::TEN_CENTS, 5);
        $cardHandler = new StubCardHandler();
        $coffeeMachine = new CoffeeMachine($brewer, $changeMachine, $cardHandler);

        // QUAND on insère une pièce de 1 euro (surpaiement de 50 centimes)
        $changeMachine->insertCoin(CoinCode::ONE_EURO);

        // ALORS un café est préparé ET la monnaie est rendue
        $this->assertTrue($brewer->wasCalled(), 'Un café aurait dû être préparé');
        $this->assertTrue($changeMachine->wasCollected(), 'La pièce aurait dû être encaissée');

        // Vérifier que la monnaie a été rendue (2x20 + 1x10 = 50 centimes)
        $droppedCoins = $changeMachine->getDroppedCoins();
        $totalChange = array_sum(array_map(fn($coin) => $coin->value, $droppedCoins));
        $this->assertSame(50, $totalChange, 'La monnaie de 50 centimes aurait dû être rendue');
    }

    #[TestDox('ÉTANT DONNÉ une machine à café sans stock de monnaie, QUAND on insère 2 euros, ALORS aucune monnaie n\'est rendue')]
    public function testNoChangeWhenInsufficientStock(): void
    {
        // ÉTANT DONNÉ une machine à café sans stock de monnaie
        $brewer = new SpyBrewer();
        $changeMachine = new FakeChangeMachine();
        // Pas de stock configuré = pas de monnaie disponible
        $cardHandler = new StubCardHandler();
        $coffeeMachine = new CoffeeMachine($brewer, $changeMachine, $cardHandler);

        // QUAND on insère une pièce de 2 euros
        $changeMachine->insertCoin(CoinCode::TWO_EUROS);

        // ALORS un café est préparé MAIS aucune monnaie n'est rendue
        $this->assertTrue($brewer->wasCalled(), 'Un café aurait dû être préparé');
        $this->assertTrue($changeMachine->wasCollected(), 'La pièce aurait dû être encaissée');
        $this->assertEmpty($changeMachine->getDroppedCoins(), 'Aucune monnaie ne devrait être rendue');
    }
}