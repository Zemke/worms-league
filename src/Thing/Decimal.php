<?php

namespace App\Thing;

class Decimal implements \Stringable
{
    public const SCALE = 50;

    private string $s;

    private function __construct(mixed $x)
    {
        \bcscale(self::SCALE);
        if ($x instanceof Decimal) {
            $this->s = $x;
        } else {
            if (!$this->valid($x)) {
                throw new \RuntimeException($x . ' is not numeric');
            }
            $this->s = strval($x);
        }
    }

    public function valid(mixed $s): bool
    {
        return \is_numeric($s);
    }

    public function add(mixed $x): Decimal
    {
        return $this->op('\bcadd', $x);
    }

    public function sub(mixed $x): Decimal
    {
        return $this->op('\bcsub', $x);
    }

    public function mul(mixed $x): Decimal
    {
        return $this->op('\bcmul', $x);
    }

    public function div(mixed $x): Decimal
    {
        return $this->op('\bcdiv', $x);
    }

    public function pow(mixed $x): Decimal
    {
        return $this->op('\bcpow', $x);
    }

    public function sqrt(): Decimal
    {
        return self::of(\bcsqrt(strval($this)));
    }

    public function comp(mixed $x): int
    {
        return \bccomp($this->s, strval(self::of($x)));
    }

    private function op(callable $op, mixed $x): Decimal
    {
        return self::of(call_user_func($op, $this->s, self::of($x)));
    }

    public function __toString(): string
    {
        return $this->s;
    }

    public static function of(mixed $x): Decimal
    {
        return new Decimal($x);
    }

    public static function sum(array $xx): Decimal
    {
        self::assertNotEmpty($xx);
        return array_reduce($xx, fn($acc, $x) => $acc->add($x), self::zero());
    }

    public static function min(array $xx): Decimal
    {
        self::assertNotEmpty($xx);
        return self::of(array_reduce(
            array_slice($xx, 1),
            fn($acc, $x) => self::of($x)->comp($acc) > 0 ? $acc : $x,
            array_values($xx)[0]));
    }

    public static function max(array $xx): Decimal
    {
        self::assertNotEmpty($xx);
        return self::of(array_reduce(
            $xx,
            fn($acc, $x) => self::of($x)->comp($acc) > 0 ? $x : $acc,
            self::least()));
    }

    public static function assertNotEmpty(array $xx): void
    {
        if (empty($xx)) {
            throw new \RuntimeException('Array must contain at least one argument');
        }
    }

    public static function abs(mixed $x): Decimal
    {
        $d = self::of($x);
        $s = strval($d);
        return $s[0] === '-' ? self::of(substr($s, 1)) : $d;
    }

    public static function least(): Decimal
    {
        return self::of('0.' . str_repeat('0', self::SCALE - 1) . '1');
    }

    public static function zero(): Decimal
    {
        return self::of(0);
    }

    public static function one(): Decimal
    {
        return self::of(1);
    }
}

