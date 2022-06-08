<?php

namespace App\Thing;

use App\Thing\Decimal as D;

/**
 * Feature scaling using min-max normalization formula.
 */
class MinMaxNorm
{
    /**
     * @param $mn The original dataset.
     * @param $a  Lower bound of the range to scale to.
     * @param $b  Upper bound of the range to scale to.
     */
    public function __construct(array $xx, mixed $a = 0, mixed $b = 1)
    {
        $this->xx = array_map(fn($x) => D::of($x), $xx);
        $this->mn = D::min($xx);
        $this->mx = D::max($xx);
        $this->a = D::of($a);
        $this->b = D::of($b);
    }

    /**
     * Scale the the whole set.
     *
     * @return D[] Scaled values.
     */
    public function full(): array
    {
        return array_map(fn($x) => $this->step($x), array_values($this->xx));
    }

    private function step(mixed $x): D
    {
        return D::of($this->a)
            ->add(
                D::of($x)->sub($this->mn)
                    ->mul($this->b->sub($this->a))
                    ->div($this->mx->sub($this->mn))
            );
    }
}


