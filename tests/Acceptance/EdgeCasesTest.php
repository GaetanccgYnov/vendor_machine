<?php

namespace Tests\Acceptance;

use App\CoffeeMachine;
use App\CoinCode;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Tests\TestDoubles\FakeChangeMachine;
use Tests\TestDoubles\SpyBrewer;
use Tests\TestDoubles\StubCardHandler;

class EdgeCasesTest extends TestCase
{
    #[TestDox('ÉTANT DONNÉ une machine à café pleine (5 pièces), QUAND on insère une 6ème pièce, ALORS elle est rejetée')]
    public function testCoinRejectedWhenMachineIsFull(): void
    {
        // ÉTANT DONNÉ une machine à café
        $brewer = new SpyBrewer();
        $changeMachine = new FakeChangeMachine();
        $cardHandler = new StubCardHandler();
        $coffeeMachine = new CoffeeMachine($brewer, $changeMachine, $cardHandler);

        // Remplir la machine avec 5 pièces (limite)
        for ($i = 0; $i < 5; $i++) {
            $changeMachine->insertCoin(CoinCode::FIFTY_CENTS);
        }

        // QUAND on insère une 6ème pièce
        $changeMachine->insertCoin(CoinCode::FIFTY_CENTS);

        // ALORS elle est rejetée (pas de callback appelé, donc pas de café)
        $this->assertSame(5, $changeMachine->getStoredCoinsCount(), 'La machine ne devrait contenir que 5 pièces maximum');
        $this->assertSame(5, $brewer->getCallCount(), 'Seules 5 tentatives de café devraient avoir été faites');
    }

    #[TestDox('ÉTANT DONNÉ une machine à café vide, QUAND aucun paiement n\'est effectué, ALORS aucune action n\'est déclenchée')]
    public function testNoActionWithoutPayment(): void
    {
        // ÉTANT DONNÉ une machine à café vide
        $brewer = new SpyBrewer();
        $changeMachine = new FakeChangeMachine();
        $cardHandler = new StubCardHandler();
        $coffeeMachine = new CoffeeMachine($brewer, $changeMachine, $cardHandler);

        // QUAND aucun paiement n'est effectué (pas d'appel)
        // ... (rien n'est fait)

        // ALORS aucune action n'est déclenchée
        $this->assertFalse($brewer->wasCalled(), 'Aucun café ne devrait être tenté');
        $this->assertFalse($changeMachine->wasFlushed(), 'Aucune pièce ne devrait être rendue');
        $this->assertFalse($cardHandler->wasCharged(), 'Aucune carte ne devrait être débitée');
    }

    #[TestDox('ÉTANT DONNÉ une machine à café, QUAND on insère successivement 2 pièces de 50 centimes, ALORS 2 cafés sont préparés')]
    public function testMultipleValidCoinsProduceMultipleCoffees(): void
    {
        // ÉTANT DONNÉ une machine à café
        $brewer = new SpyBrewer();
        $changeMachine = new FakeChangeMachine();
        $cardHandler = new StubCardHandler();
        $coffeeMachine = new CoffeeMachine($brewer, $changeMachine, $cardHandler);

        // QUAND on insère successivement 2 pièces de 50 centimes
        $changeMachine->insertCoin(CoinCode::FIFTY_CENTS);
        $changeMachine->insertCoin(CoinCode::FIFTY_CENTS);

        // ALORS 2 cafés sont préparés
        $this->assertSame(2, $brewer->getCallCount(), 'Exactement 2 cafés devraient être préparés');
    }
}