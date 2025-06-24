<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Constraint\Constraint;
use App\CoinCode;

trait CoffeeMachineMatchers
{
    public static function isValidCoin(): ValidCoinConstraint
    {
        return new ValidCoinConstraint();
    }

    public static function isInvalidCoin(): InvalidCoinConstraint
    {
        return new InvalidCoinConstraint();
    }

    public static function canMakeCoffee(): CanMakeCoffeeConstraint
    {
        return new CanMakeCoffeeConstraint();
    }

    public static function shouldRefundMoney(): ShouldRefundMoneyConstraint
    {
        return new ShouldRefundMoneyConstraint();
    }
}

class ValidCoinConstraint extends Constraint
{
    public function matches($other): bool
    {
        return $other instanceof CoinCode && $other->value >= 50;
    }

    public function toString(): string
    {
        return 'is a valid coin (≥ 50 centimes)';
    }

    protected function failureDescription($other): string
    {
        if ($other instanceof CoinCode) {
            $value = match ($other->value) {
                1 => '1 centime',
                2 => '2 centimes',
                5 => '5 centimes',
                10 => '10 centimes',
                20 => '20 centimes',
                50 => '50 centimes',
                100 => '1 euro',
                200 => '2 euros',
                default => $other->value . ' centimes'
            };
            return "coin of $value " . $this->toString();
        }
        return parent::failureDescription($other);
    }
}

class InvalidCoinConstraint extends Constraint
{
    public function matches($other): bool
    {
        return $other instanceof CoinCode && $other->value < 50;
    }

    public function toString(): string
    {
        return 'is an invalid coin (< 50 centimes)';
    }

    protected function failureDescription($other): string
    {
        if ($other instanceof CoinCode) {
            $value = match ($other->value) {
                1 => '1 centime',
                2 => '2 centimes',
                5 => '5 centimes',
                10 => '10 centimes',
                20 => '20 centimes',
                50 => '50 centimes',
                100 => '1 euro',
                200 => '2 euros',
                default => $other->value . ' centimes'
            };
            return "coin of $value " . $this->toString();
        }
        return parent::failureDescription($other);
    }
}

class CanMakeCoffeeConstraint extends Constraint
{
    public function matches($other): bool
    {
        return $other instanceof CoinCode && $other->value >= 50;
    }

    public function toString(): string
    {
        return 'can make coffee';
    }

    protected function failureDescription($other): string
    {
        return "coin " . $this->toString();
    }
}

class ShouldRefundMoneyConstraint extends Constraint
{
    public function matches($other): bool
    {
        return $other instanceof CoinCode && $other->value < 50;
    }

    public function toString(): string
    {
        return 'should refund money';
    }

    protected function failureDescription($other): string
    {
        return "coin " . $this->toString();
    }
}