<?php

namespace Tests\TestDoubles;

use App\BrewerInterface;

/**
 * Spy pour le Brewer - enregistre les appels et peut simuler des échecs
 */
class SpyBrewer implements BrewerInterface
{
    private array $calls = [];
    private bool $shouldSucceed = true;

    public function makeACoffee(): bool
    {
        $this->calls[] = 'makeACoffee';
        return $this->shouldSucceed;
    }

    public function simulateFailure(): void
    {
        $this->shouldSucceed = false;
    }

    public function getCallCount(): int
    {
        return count($this->calls);
    }

    public function wasCalled(): bool
    {
        return !empty($this->calls);
    }

    public function reset(): void
    {
        $this->calls = [];
        $this->shouldSucceed = true;
    }

   public function tryPullWater(): bool
   {
       return $this->shouldSucceed;
   }

   public function pourMilk(): bool
   {
       return $this->shouldSucceed;
   }

   public function pourWater(): bool
   {
       return $this->shouldSucceed;
   }

   public function pourSugar(): bool
   {
       return $this->shouldSucceed;
   }

   public function pourChocolate(): bool
   {
       return $this->shouldSucceed;
   }
}