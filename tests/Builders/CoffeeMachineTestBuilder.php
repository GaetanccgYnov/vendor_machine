<?php

declare(strict_types=1);

namespace Tests\Builders;

use App\CoinCode;
use App\BrewerInterface;
use App\ChangeMachineInterface;
use Tests\Scenarios\CoffeeMachineTestScenario;
use App\PaymentMethod;

/**
 * Builder pour configurer les tests de machine à café
 *
 * Utilise le pattern Builder pour créer des scénarios de test
 * de manière fluide et lisible.
 */
class CoffeeMachineTestBuilder
{
    private BrewerInterface $brewer;
    private ChangeMachineInterface $coinMachine;
    private ?CoinCode $coin = null;
    private bool $brewerSuccess = true;
    private int $expectedBrewerCalls = 0;
    private int $expectedCoinMachineCalls = 0;
    private bool $shouldCallBrewer = false;
    private bool $shouldCallCoinMachine = false;
    private ?PaymentMethod $paymentMethod = null;
    private bool $cardAccepted = false;

    public function __construct(BrewerInterface $brewer, ChangeMachineInterface $coinMachine)
    {
        $this->brewer = $brewer;
        $this->coinMachine = $coinMachine;
    }

    /**
     * Définit le mode de paiement à utiliser dans le test
     */
    public function withCardPayment(bool $accepted): self
    {
        $this->paymentMethod = PaymentMethod::CARD;
        $this->cardAccepted = $accepted;
        return $this;
    }

    /**
     * Définit la pièce à utiliser dans le test
     */
    public function withCoin(CoinCode $coin): self
    {
        $this->coin = $coin;
        return $this;
    }

    /**
     * Définit si le brewer doit réussir ou échouer
     */
    public function withBrewerSuccess(bool $success = true): self
    {
        $this->brewerSuccess = $success;
        return $this;
    }

    /**
     * Définit le nombre d'appels attendus sur le brewer
     */
    public function expectBrewerCalls(int $times): self
    {
        $this->expectedBrewerCalls = $times;
        $this->shouldCallBrewer = $times > 0;
        return $this;
    }

    /**
     * Définit le nombre d'appels attendus sur la machine à monnaie
     */
    public function expectCoinMachineCalls(int $times): self
    {
        $this->expectedCoinMachineCalls = $times;
        $this->shouldCallCoinMachine = $times > 0;
        return $this;
    }

    /**
     * Indique qu'aucun appel au brewer n'est attendu
     */
    public function expectNoBrewerCalls(): self
    {
        $this->expectedBrewerCalls = 0;
        $this->shouldCallBrewer = false;
        return $this;
    }

    /**
     * Indique qu'aucun appel à la machine à monnaie n'est attendu
     */
    public function expectNoCoinMachineCalls(): self
    {
        $this->expectedCoinMachineCalls = 0;
        $this->shouldCallCoinMachine = false;
        return $this;
    }

    /**
     * Construit le scénario de test final
     */
    public function build(): CoffeeMachineTestScenario
    {
        return new CoffeeMachineTestScenario(
            $this->brewer,
            $this->coinMachine,
            $this->coin,
            $this->brewerSuccess,
            $this->expectedBrewerCalls,
            $this->expectedCoinMachineCalls,
            $this->shouldCallBrewer,
            $this->shouldCallCoinMachine,
            $this->paymentMethod,
            $this->cardAccepted
        );
    }

    /**
     * Remet le builder à zéro pour réutilisation
     */
    public function reset(): self
    {
        $this->coin = null;
        $this->brewerSuccess = true;
        $this->expectedBrewerCalls = 0;
        $this->expectedCoinMachineCalls = 0;
        $this->shouldCallBrewer = false;
        $this->shouldCallCoinMachine = false;

        return $this;
    }
}