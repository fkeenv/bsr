<?php

namespace App\Actions\Properties;

use App\Models\Property;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ImportPropertiesFromCsv
{
    public function __construct(
        private CreateProperty $createProperty,
        private UpdateProperty $updateProperty,
    ) {}

    /**
     * @return array{created: int, updated: int}
     */
    public function handle(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to read the CSV file.');
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                throw new InvalidArgumentException('CSV file is empty.');
            }

            $columns = $this->mapHeader($header);
            $created = 0;
            $updated = 0;

            DB::transaction(function () use ($handle, $columns, &$created, &$updated): void {
                while (($row = fgetcsv($handle)) !== false) {
                    if ($this->rowIsBlank($row)) {
                        continue;
                    }

                    $payload = $this->rowToPayload($row, $columns);
                    $existing = Property::query()
                        ->where('block', $payload['block'])
                        ->where('lot', $payload['lot'])
                        ->first();

                    if ($existing === null) {
                        $this->createProperty->handle($payload);
                        $created++;

                        continue;
                    }

                    $this->updateProperty->handle($existing, [
                        'street_address' => $payload['street_address'],
                        'recorded_owner_name' => $payload['recorded_owner_name'],
                    ]);
                    $updated++;
                }
            });

            return compact('created', 'updated');
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string|null>  $header
     * @return array{block: int, lot: int, street_address: int|null, recorded_owner_name: int|null, opening_balance: int|null}
     */
    private function mapHeader(array $header): array
    {
        $normalized = array_map(
            fn (?string $column): string => strtolower(trim((string) $column)),
            $header,
        );

        $required = ['block', 'lot'];

        foreach ($required as $column) {
            if (! in_array($column, $normalized, true)) {
                throw new InvalidArgumentException("CSV is missing required column: {$column}.");
            }
        }

        return [
            'block' => (int) array_search('block', $normalized, true),
            'lot' => (int) array_search('lot', $normalized, true),
            'street_address' => $this->optionalColumnIndex($normalized, 'street_address'),
            'recorded_owner_name' => $this->optionalColumnIndex($normalized, 'recorded_owner_name'),
            'opening_balance' => $this->optionalColumnIndex($normalized, 'opening_balance'),
        ];
    }

    /**
     * @param  list<string>  $normalized
     */
    private function optionalColumnIndex(array $normalized, string $column): ?int
    {
        $index = array_search($column, $normalized, true);

        return $index === false ? null : $index;
    }

    /**
     * @param  list<string|null>  $row
     * @param  array{block: int, lot: int, street_address: int|null, recorded_owner_name: int|null, opening_balance: int|null}  $columns
     * @return array{block: string, lot: string, street_address: string|null, recorded_owner_name: string|null, opening_balance?: string}
     */
    private function rowToPayload(array $row, array $columns): array
    {
        $block = trim((string) ($row[$columns['block']] ?? ''));
        $lot = trim((string) ($row[$columns['lot']] ?? ''));

        if ($block === '' || $lot === '') {
            throw new InvalidArgumentException('Each CSV row requires block and lot.');
        }

        $payload = [
            'block' => $block,
            'lot' => $lot,
            'street_address' => $this->nullableCell($row, $columns['street_address']),
            'recorded_owner_name' => $this->nullableCell($row, $columns['recorded_owner_name']),
        ];

        if ($columns['opening_balance'] !== null && array_key_exists($columns['opening_balance'], $row)) {
            $payload['opening_balance'] = trim((string) $row[$columns['opening_balance']]) ?: '0';
        }

        return $payload;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function nullableCell(array $row, int|false|null $index): ?string
    {
        if ($index === null || $index === false) {
            return null;
        }

        $value = trim((string) ($row[$index] ?? ''));

        return $value === '' ? null : $value;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function rowIsBlank(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
