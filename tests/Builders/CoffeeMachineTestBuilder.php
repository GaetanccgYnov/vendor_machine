<?php

declare(strict_types=1);

namespace Tests\Builders;

use App\CoinCode;
use App\BrewerInterface;
use App\ChangeMachineInterface;
use App\CardHandleInterface;
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
    private CardHandleInterface $cardHandler;
    private ?CoinCode $coin = null;
    private bool $brewerSuccess = true;
    private int $expectedBrewerCalls = 0;
    private int $expectedCoinMachineCalls = 0;
    private int $expectedCardChargeCalls = 0;
    private int $expectedCardRefundCalls = 0;
    private bool $shouldCallBrewer = false;
    private bool $shouldCallCoinMachine = false;
    private bool $shouldCallCardCharge = false;
    private bool $shouldCallCardRefund = false;
    private ?PaymentMethod $paymentMethod = null;
    private bool $cardChargeSuccess = false;
    private int $toManyCoins = 0;
    private array $multipleCoins = [];
    private array $initialCoinStock = [];
    private array $expectedChange = [];
    private array $expectedFinalStock = [];

    public function __construct(
        BrewerInterface $brewer,
        ChangeMachineInterface $coinMachine,
        CardHandleInterface $cardHandler
    ) {
        $this->brewer = $brewer;
        $this->coinMachine = $coinMachine;
        $this->cardHandler = $cardHandler;
    }

    /**
     * Définit le mode de paiement par carte
     */
    public function withCardPayment(bool $chargeSuccess): self
    {
        $this->paymentMethod = PaymentMethod::CARD;
        $this->cardChargeSuccess = $chargeSuccess;
        $this->coin = null; // Reset coin si on utilise la carte
        return $this;
    }

    /**
     * Définit la pièce à utiliser dans le test
     */
    public function withCoin(CoinCode $coin): self
    {
        $this->coin = $coin;
        $this->paymentMethod = null; // Reset payment method si on utilise des pièces
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
     * Définit le nombre d'appels attendus pour le prélèvement carte
     */
    public function expectCardChargeCalls(int $times): self
    {
        $this->expectedCardChargeCalls = $times;
        $this->shouldCallCardCharge = $times > 0;
        return $this;
    }

    /**
     * Définit le nombre d'appels attendus pour le remboursement carte
     */
    public function expectCardRefundCalls(int $times): self
    {
        $this->expectedCardRefundCalls = $times;
        $this->shouldCallCardRefund = $times > 0;
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
     * Indique qu'aucun appel de prélèvement carte n'est attendu
     */
    public function expectNoCardChargeCalls(): self
    {
        $this->expectedCardChargeCalls = 0;
        $this->shouldCallCardCharge = false;
        return $this;
    }

    /**
     * Indique qu'aucun appel de remboursement carte n'est attendu
     */
    public function expectNoCardRefundCalls(): self
    {
        $this->expectedCardRefundCalls = 0;
        $this->shouldCallCardRefund = false;
        return $this;
    }

    /**
     * Indique qu'aucun appel lié à la carte n'est attendu
     */
    public function expectNoCardCalls(): self
    {
        return $this->expectNoCardChargeCalls()->expectNoCardRefundCalls();
    }

    /**
     *  Définit le nombre de pièces à insérer
     */
    public function withCoinInsertion(int $times): self
    {
        $this->toManyCoins = $times;
        return $this;
    }

    /**
     * Définit plusieurs pièces à insérer
     */
    public function withMultipleCoins(array $coins): self
    {
        $this->multipleCoins = $coins;
        $this->coin = null;
        $this->paymentMethod = null;
        return $this;
    }

    /**
     * Définit le stock initial de pièces dans la machine
     */
    public function withInitialCoinStock(array $stock): self
    {
        $this->initialCoinStock = $stock;
        return $this;
    }

    /**
     * Définit la monnaie attendue en retour
     */
    public function expectChangeReturn(array $change): self
    {
        $this->expectedChange = $change;
        return $this;
    }

    /**
     * Indique qu'aucune monnaie ne doit être rendue
     */
    public function expectNoChangeReturn(): self
    {
        $this->expectedChange = [];
        return $this;
    }

    /**
     * Définit l'état final attendu du stock de pièces
     */
    public function expectFinalCoinStock(array $finalStock): self
    {
        $this->expectedFinalStock = $finalStock;
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
            $this->cardHandler,
            $this->coin,
            $this->brewerSuccess,
            $this->expectedBrewerCalls,
            $this->expectedCoinMachineCalls,
            $this->expectedCardChargeCalls,
            $this->expectedCardRefundCalls,
            $this->shouldCallBrewer,
            $this->shouldCallCoinMachine,
            $this->shouldCallCardCharge,
            $this->shouldCallCardRefund,
            $this->paymentMethod,
            $this->cardChargeSuccess,
            $this->toManyCoins,
            $this->multipleCoins,
            $this->initialCoinStock,
            $this->expectedChange,
            $this->expectedFinalStock
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
        $this->expectedCardChargeCalls = 0;
        $this->expectedCardRefundCalls = 0;
        $this->shouldCallBrewer = false;
        $this->shouldCallCoinMachine = false;
        $this->shouldCallCardCharge = false;
        $this->shouldCallCardRefund = false;
        $this->paymentMethod = null;
        $this->cardChargeSuccess = false;
        $this->multipleCoins = [];
        $this->initialCoinStock = [];
        $this->expectedChange = [];
        $this->expectedFinalStock = [];

        return $this;
    }
}