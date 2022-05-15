<?php

namespace App\Thing;

class Decimal implements \Stringable
{
    public const SCALE = 20;

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
        return array_reduce($xx, fn($acc, $x) => $acc->add($x), self::zero());
    }

    public static function min(): Decimal
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
