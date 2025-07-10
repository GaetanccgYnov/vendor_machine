<?php
namespace Tests\TestDoubles;

use App\ChangeMachineInterface;
use App\CoinCode;

/**
 * Fake pour la machine à monnaie - simule le comportement réel
 */
class FakeChangeMachine implements ChangeMachineInterface
{
//    private ?callable $moneyInsertedCallback = null;
    private array $storedCoins = [];
    private array $stockCoins = [];
    private array $flushedCoins = [];
    private array $collectedCoins = [];
    private array $droppedCoins = [];
    private int $maxStoredCoins = 5;

    public function registerMoneyInsertedCallback(?callable $callback): void
    {
        $this->moneyInsertedCallback = $callback;
    }

    public function insertCoin(CoinCode $coin): void
    {
        if (count($this->storedCoins) >= $this->maxStoredCoins) {
            // Machine pleine, callback non appelé
            return;
        }

        $this->storedCoins[] = $coin;

        if ($this->moneyInsertedCallback !== null) {
            ($this->moneyInsertedCallback)($coin->value);
        }
    }

    public function flushStoredMoney(): void
    {
        $this->flushedCoins = array_merge($this->flushedCoins, $this->storedCoins);
        $this->storedCoins = [];
    }

    public function collectStoredMoney(): void
    {
        $this->collectedCoins = array_merge($this->collectedCoins, $this->storedCoins);
        $this->storedCoins = [];
    }

    public function dropCashback(CoinCode $coinCode): bool
    {
        if (!isset($this->stockCoins[$coinCode->value]) || $this->stockCoins[$coinCode->value] <= 0) {
            return false;
        }

        $this->stockCoins[$coinCode->value]--;
        $this->droppedCoins[] = $coinCode;
        return true;
    }

    // Méthodes de test
    public function setStock(CoinCode $coin, int $quantity): void
    {
        $this->stockCoins[$coin->value] = $quantity;
    }

    public function getStoredCoinsCount(): int
    {
        return count($this->storedCoins);
    }

    public function wasFlushed(): bool
    {
        return !empty($this->flushedCoins);
    }

    public function wasCollected(): bool
    {
        return !empty($this->collectedCoins);
    }

    public function getDroppedCoins(): array
    {
        return $this->droppedCoins;
    }

    public function reset(): void
    {
        $this->storedCoins = [];
        $this->flushedCoins = [];
        $this->collectedCoins = [];
        $this->droppedCoins = [];
        $this->stockCoins = [];
        $this->moneyInsertedCallback = null;
    }

    public function getCurrentStock(): array
    {
        // Retourne un stock vide
        return [];
    }

    public function canMakeChange(int $changeAmount, array $currentStock): bool
    {
        // Toujours faux (pas de monnaie possible)
        return false;
    }

    public function returnChange(array $changeCoins): void
    {
        // Ne fait rien
    }

    public function updateStock(array $newStock): void
    {
        // Ne fait rien
    }

    public function calculateOptimalChange(int $changeAmount, array $availableStock): array
    {
        // Impossible de rendre la monnaie
        return [];
    }
}