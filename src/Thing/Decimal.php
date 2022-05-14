<?php

namespace App\Thing;

class Decimal implements \Stringable
{
    private string $s;

    public function __construct(mixed $x)
    {
        \bcscale(20);
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

    private function op(callable $op, mixed $x): Decimal
    {
        return new Decimal(call_user_func($op, $this->s, new Decimal($x)));
    }

    public function __toString(): string
    {
        return $this->s;
    }
}

