<?php

namespace Tests\Unit;

use App\Support\Percentile;
use PHPUnit\Framework\TestCase;

class PercentileTest extends TestCase
{
    public function test_matches_excel_percentile_inc_at_80th(): void
    {
        // Excel: =PERCENTILE.INC({1..10}, 0.8) = 8.2
        $values = range(1, 10);

        $this->assertEqualsWithDelta(8.2, Percentile::inc($values, 80), 0.0001);
    }

    public function test_matches_excel_percentile_inc_at_90th(): void
    {
        // Excel: =PERCENTILE.INC({1..10}, 0.9) = 9.1
        $values = range(1, 10);

        $this->assertEqualsWithDelta(9.1, Percentile::inc($values, 90), 0.0001);
    }

    public function test_unsorted_input_does_not_affect_result(): void
    {
        $values = [10, 2, 7, 1, 9, 4, 6, 3, 8, 5];

        $this->assertEqualsWithDelta(8.2, Percentile::inc($values, 80), 0.0001);
    }

    public function test_single_value_returns_that_value_regardless_of_percentile(): void
    {
        $this->assertEqualsWithDelta(42.0, Percentile::inc([42], 80), 0.0001);
        $this->assertEqualsWithDelta(42.0, Percentile::inc([42], 0), 0.0001);
        $this->assertEqualsWithDelta(42.0, Percentile::inc([42], 100), 0.0001);
    }

    public function test_throws_on_empty_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Percentile::inc([], 80);
    }

    public function test_throws_on_out_of_range_percentile(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Percentile::inc([1, 2, 3], 101);
    }
}
