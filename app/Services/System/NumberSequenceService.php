<?php

namespace App\Services\System;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Document numbers: INV-{YYYY}-{6 digits}, SHIFT-…, PUR-…, PAY-… (spec section 4).
 *
 * The counter row is locked FOR UPDATE before being read and incremented, so
 * two concurrent transactions are serialised and can never receive the same
 * number. The caller must already hold a transaction.
 */
class NumberSequenceService
{
    /**
     * Produce the next number for a prefix, e.g. "INV-2026-000001".
     */
    public function next(string $type, ?\DateTimeInterface $on = null): string
    {
        $config = config("erp.sequences.{$type}");

        if (! $config) {
            throw new RuntimeException("Unknown number sequence [{$type}].");
        }

        $on ??= now();
        $year = (int) $on->format('Y');
        $prefix = $config['prefix'];
        $digits = (int) $config['digits'];

        $number = $this->increment($prefix, $year, $digits);

        return sprintf('%s-%d-%0'.$digits.'d', $prefix, $year, $number);
    }

    private function increment(string $prefix, int $year, int $digits): int
    {
        // The whole read-modify-write must be serialised.
        DB::statement(
            'INSERT INTO number_sequences (prefix, year, current_number, digits, created_at, updated_at)
             VALUES (?, ?, 1, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE updated_at = updated_at',
            [$prefix, $year, $digits]
        );

        $row = DB::table('number_sequences')
            ->where('prefix', $prefix)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            throw new RuntimeException("Unable to allocate a number for [{$prefix}-{$year}].");
        }

        // The insert above already produced number 1 for a new sequence.
        if ($row->current_number >= 1) {
            $next = DB::table('number_sequences')
                ->where('id', $row->id)
                ->update([
                    'current_number' => DB::raw('current_number + 1'),
                    'updated_at' => now(),
                ]);

            $this->assertUpdated($next, $prefix);
        }

        return (int) $row->current_number;
    }

    private function assertUpdated(int $affected, string $prefix): void
    {
        if ($affected !== 1) {
            throw new RuntimeException("Lost the number sequence lock for [{$prefix}].");
        }
    }
}
