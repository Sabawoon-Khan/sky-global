<?php

namespace App\Console\Commands;

use App\Models\Finance\GeneralExpense;
use App\Models\Finance\GeneralIncome;
use App\Models\Finance\TaxPayment;
use App\Models\User;
use App\Support\AfghanSolarDate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportFinancialLedgerCommand extends Command
{
    protected $signature = 'finance:import-ledger
        {file : Path to CSV ledger file}
        {--user-id= : User ID to set as created_by}
        {--default-date= : Fallback transaction date (YYYY-MM-DD)}
        {--dry-run : Validate and preview without writing}';

    protected $description = 'Import ledger CSV rows into general incomes, expenses, and tax payments';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        $dryRun = (bool) $this->option('dry-run');
        $createdBy = $this->resolveCreatedByUserId();

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $rows = $this->readRows($path);
        if ($rows === []) {
            $this->warn('No rows found in CSV.');

            return self::SUCCESS;
        }

        $importStats = [
            'income_created' => 0,
            'income_skipped' => 0,
            'expense_created' => 0,
            'expense_skipped' => 0,
            'tax_created' => 0,
            'tax_skipped' => 0,
            'invalid_rows' => 0,
        ];

        $runner = function () use ($rows, $createdBy, &$importStats): void {
            foreach ($rows as $index => $row) {
                $entryType = strtolower(trim((string) ($row['entry_type'] ?? '')));
                if (! in_array($entryType, ['income', 'expense', 'tax'], true)) {
                    $importStats['invalid_rows']++;
                    $this->warn('Skipping row '.($index + 2)." due to unsupported entry_type: {$entryType}");

                    continue;
                }

                $transactionId = trim((string) ($row['transaction_id'] ?? ''));
                $date = $this->resolveDate($row['transaction_date'] ?? null);
                if (! $date) {
                    $importStats['invalid_rows']++;
                    $this->warn('Skipping row '.($index + 2).' due to invalid date');

                    continue;
                }

                if ($entryType === 'income') {
                    $amount = $this->toDecimal($row['credit_afn'] ?? null);
                    if ($amount <= 0) {
                        $importStats['invalid_rows']++;
                        $this->warn('Skipping income row '.($index + 2).' due to empty/invalid credit amount');

                        continue;
                    }

                    $exists = GeneralIncome::query()
                        ->where('reference_number', $transactionId)
                        ->whereDate('transaction_date', $date->toDateString())
                        ->where('amount', $amount)
                        ->exists();

                    if ($exists) {
                        $importStats['income_skipped']++;
                        continue;
                    }

                    GeneralIncome::query()->create([
                        'amount' => $amount,
                        'currency' => 'AFN',
                        'description' => $this->cleanValue($row['description'] ?? null),
                        'category' => $this->cleanValue($row['account_head'] ?? 'Cash Receipt'),
                        'transaction_date' => $date->toDateString(),
                        'reference_number' => $transactionId ?: null,
                        'payment_method' => 'cash',
                        'status' => 'recorded',
                        'created_by' => $createdBy,
                    ]);

                    $importStats['income_created']++;

                    continue;
                }

                if ($entryType === 'expense') {
                    $amount = $this->toDecimal($row['debit_afn'] ?? null);
                    if ($amount <= 0) {
                        $importStats['invalid_rows']++;
                        $this->warn('Skipping expense row '.($index + 2).' due to empty/invalid debit amount');

                        continue;
                    }

                    $exists = GeneralExpense::query()
                        ->where('reference_number', $transactionId)
                        ->whereDate('transaction_date', $date->toDateString())
                        ->where('amount', $amount)
                        ->exists();

                    if ($exists) {
                        $importStats['expense_skipped']++;
                        continue;
                    }

                    GeneralExpense::query()->create([
                        'amount' => $amount,
                        'currency' => 'AFN',
                        'description' => $this->cleanValue($row['description'] ?? null),
                        'category' => $this->cleanValue($row['account_head'] ?? 'Operating Expense'),
                        'transaction_date' => $date->toDateString(),
                        'reference_number' => $transactionId ?: null,
                        'payment_method' => 'cash',
                        'status' => 'recorded',
                        'created_by' => $createdBy,
                    ]);

                    $importStats['expense_created']++;

                    continue;
                }

                $taxAmount = $this->toDecimal($row['tax_afn'] ?? null);
                if ($taxAmount <= 0) {
                    $importStats['tax_skipped']++;
                    continue;
                }

                $exists = TaxPayment::query()
                    ->where('reference_number', $transactionId)
                    ->whereDate('payment_date', $date->toDateString())
                    ->where('amount', $taxAmount)
                    ->exists();

                if ($exists) {
                    $importStats['tax_skipped']++;
                    continue;
                }

                TaxPayment::query()->create([
                    'period_type' => 'custom',
                    'year' => (int) $date->format('Y'),
                    'quarter' => (int) ceil(((int) $date->format('n')) / 3),
                    'period_start' => $date->copy()->startOfMonth()->toDateString(),
                    'period_end' => $date->copy()->endOfMonth()->toDateString(),
                    'rate' => 0,
                    'rate_percent' => 0,
                    'tax_due' => $taxAmount,
                    'amount' => $taxAmount,
                    'currency' => 'AFN',
                    'payment_date' => $date->toDateString(),
                    'payment_method' => 'cash',
                    'reference_number' => $transactionId ?: null,
                    'notes' => $this->cleanValue($row['description'] ?? 'Imported from ledger CSV'),
                    'status' => 'paid',
                    'created_by' => $createdBy,
                ]);

                $importStats['tax_created']++;
            }
        };

        if ($dryRun) {
            DB::beginTransaction();
            $runner();
            DB::rollBack();
        } else {
            DB::transaction($runner);
        }

        $this->line('');
        $this->info($dryRun ? 'Dry run complete.' : 'Import complete.');
        $this->table(
            ['Metric', 'Count'],
            collect($importStats)->map(fn ($value, $key) => [$key, $value])->values()->all(),
        );

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readRows(string $path): array
    {
        $file = fopen($path, 'rb');
        if (! $file) {
            return [];
        }

        $header = fgetcsv($file);
        if (! is_array($header)) {
            fclose($file);

            return [];
        }

        $normalizedHeader = array_map(
            static fn ($value) => strtolower(trim((string) $value)),
            $header,
        );

        $rows = [];
        while (($line = fgetcsv($file)) !== false) {
            if (! is_array($line)) {
                continue;
            }

            $combined = @array_combine($normalizedHeader, $line);
            if (! is_array($combined)) {
                continue;
            }

            $rows[] = $combined;
        }

        fclose($file);

        return $rows;
    }

    private function resolveCreatedByUserId(): ?int
    {
        $fromOption = $this->option('user-id');
        if ($fromOption !== null && $fromOption !== '') {
            return (int) $fromOption;
        }

        $fallbackUser = User::query()->orderBy('id')->value('id');

        return $fallbackUser ? (int) $fallbackUser : null;
    }

    private function resolveDate(mixed $value): ?Carbon
    {
        $raw = trim((string) $value);
        if ($raw !== '') {
            $parts = AfghanSolarDate::parseShamsiDateString($raw);
            if ($parts !== null) {
                [$year, , $day] = $parts;

                if ($year >= 1300 && $year <= 1500) {
                    return AfghanSolarDate::toGregorianInSystemMonth($day);
                }
            }

            try {
                return Carbon::parse($normalized);
            } catch (\Throwable) {
                // fallback below
            }
        }

        $fallback = trim((string) $this->option('default-date'));
        if ($fallback === '') {
            return null;
        }

        try {
            return Carbon::parse($fallback);
        } catch (\Throwable) {
            return null;
        }
    }

    private function toDecimal(mixed $value): float
    {
        $raw = trim((string) $value);
        if ($raw === '' || $raw === '-') {
            return 0.0;
        }

        $normalized = str_replace([',', ' '], '', $raw);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function cleanValue(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
