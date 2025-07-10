<?php

namespace Tests\Acceptance;

use App\CoffeeMachine;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Tests\TestDoubles\FakeChangeMachine;
use Tests\TestDoubles\SpyBrewer;
use Tests\TestDoubles\StubCardHandler;

class CardPaymentTest extends TestCase
{
    private const COFFEE_PRICE_CENTS = 50;

    #[TestDox('ÉTANT DONNÉ une machine à café ET une carte bancaire valide, QUAND la carte est détectée, ALORS un café est préparé')]
    public function testValidCardTriggersBrewingProcess(): void
    {
        // ÉTANT DONNÉ une machine à café ET une carte bancaire valide
        $brewer = new SpyBrewer();
        $changeMachine = new FakeChangeMachine();
        $cardHandler = new StubCardHandler(); // Par défaut, accepte le paiement
        $coffeeMachine = new CoffeeMachine($brewer, $changeMachine, $cardHandler);

        // QUAND la carte est détectée par le lecteur
        $coffeeMachine->processCardPayment();

        // ALORS un café est préparé
        $this->assertTrue($cardHandler->wasCharged(), 'La carte aurait dû être débitée');
        $this->assertSame(self::COFFEE_PRICE_CENTS, $cardHandler->getChargeAmount(), 'Le montant débité devrait être le prix du café');
        $this->assertTrue($brewer->wasCalled(), 'Un café aurait dû être préparé');
    }

    #[TestDox('ÉTANT DONNÉ une machine à café ET une carte bancaire refusant le paiement, QUAND cette carte est détectée par le lecteur, ALORS le brewer ne reçoit pas d\'ordre')]
    public function testDeclinedCardDoesNotTriggerBrewing(): void
    {
        // ÉTANT DONNÉ une machine à café ET une carte bancaire refusant le paiement
        $brewer = new SpyBrewer();
        $changeMachine = new FakeChangeMachine();
        $cardHandler = new StubCardHandler();
        $cardHandler->simulateChargeFailure(); // Carte refusée
        $coffeeMachine = new CoffeeMachine($brewer, $changeMachine, $cardHandler);

        // QUAND cette carte est détectée par le lecteur
        $coffeeMachine->processCardPayment();

        // ALORS le brewer ne reçoit pas d'ordre
        $this->assertTrue($cardHandler->wasCharged(), 'Une tentative de débit aurait dû être faite');
        $this->assertFalse($brewer->wasCalled(), 'Aucun café ne devrait être préparé si la carte est refusée');
        $this->assertFalse($cardHandler->wasRefunded(), 'Aucun remboursement ne devrait être fait si le débit a échoué');
    }

    #[TestDox('ÉTANT DONNÉ une machine à café ET une carte bancaire valide, QUAND le brewer échoue après débit, ALORS la carte est remboursée')]
    public function testCardRefundedWhenBrewerFails(): void
    {
        // ÉTANT DONNÉ une machine à café ET un brewer qui va échouer
        $brewer = new SpyBrewer();
        $brewer->simulateFailure();
        $changeMachine = new FakeChangeMachine();
        $cardHandler = new StubCardHandler(); // Carte valide
        $coffeeMachine = new CoffeeMachine($brewer, $changeMachine, $cardHandler);

        // QUAND le brewer échoue après débit
        $coffeeMachine->processCardPayment();

        // ALORS la carte est remboursée
        $this->assertTrue($cardHandler->wasCharged(), 'La carte aurait dû être débitée initialement');
        $this->assertTrue($brewer->wasCalled(), 'Le brewer aurait dû être tenté');
        $this->assertTrue($cardHandler->wasRefunded(), 'La carte aurait dû être remboursée après échec du brewer');
        $this->assertSame(self::COFFEE_PRICE_CENTS, $cardHandler->getRefundAmount(), 'Le montant remboursé devrait égaler le prix du café');
    }
}
