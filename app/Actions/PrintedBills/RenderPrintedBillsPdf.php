<?php

namespace App\Actions\PrintedBills;

use App\Models\AssociationSetting;
use App\Models\Property;
use App\Support\BillingPeriod;
use App\Support\PropertyBalances;
use Illuminate\Support\Collection;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

class RenderPrintedBillsPdf
{
    public function __construct(
        private BuildPrintedBill $buildPrintedBill,
        private PropertyBalances $propertyBalances,
    ) {}

    public function forProperty(Property $property, BillingPeriod $period): PdfBuilder
    {
        return $this->pdf(
            [$this->buildPrintedBill->handle($property, $period)],
            $this->singleFilename($property, $period),
        );
    }

    public function forUnpaidProperties(BillingPeriod $period): PdfBuilder
    {
        /** @var list<array<string, mixed>> $bills */
        $bills = array_values($this->unpaidProperties()
            ->map(fn (Property $property): array => $this->buildPrintedBill->handle($property, $period))
            ->all());

        return $this->pdf($bills, $this->batchFilename($period));
    }

    /**
     * @param  list<array<string, mixed>>  $bills
     */
    private function pdf(array $bills, string $filename): PdfBuilder
    {
        $settings = AssociationSetting::current();

        return pdf()
            ->view('pdfs.printed-bill', [
                'issuedOn' => now('Asia/Manila')->format('F j, Y'),
                'letterhead' => [
                    'name' => $settings->letterhead_name ?? AssociationSetting::printedBillDefaults()['letterhead_name'],
                    'short_name' => $settings->letterhead_short_name ?? AssociationSetting::printedBillDefaults()['letterhead_short_name'],
                    'address_lines' => $settings->letterhead_address_lines ?? AssociationSetting::defaultLetterheadAddressLines(),
                    'contact' => $settings->letterhead_contact ?? AssociationSetting::printedBillDefaults()['letterhead_contact'],
                    'treasurer' => $settings->letterhead_treasurer ?? AssociationSetting::printedBillDefaults()['letterhead_treasurer'],
                ],
                'paymentChannels' => $settings->payment_channels ?? AssociationSetting::defaultPaymentChannels(),
                'bills' => $bills,
            ])
            ->format('a4')
            ->name($filename)
            ->download();
    }

    /**
     * @return Collection<int, Property>
     */
    private function unpaidProperties(): Collection
    {
        return Property::query()
            ->orderBy('block')
            ->orderBy('lot')
            ->orderBy('id')
            ->get()
            ->filter(function (Property $property): bool {
                $balances = $this->propertyBalances->forProperty($property);

                return (float) $balances['outstanding_balance'] > 0;
            })
            ->values();
    }

    private function singleFilename(Property $property, BillingPeriod $period): string
    {
        return sprintf(
            'printed-bill-b%s-l%s-%04d-%02d.pdf',
            $property->block,
            $property->lot,
            $period->year,
            $period->month,
        );
    }

    private function batchFilename(BillingPeriod $period): string
    {
        return sprintf('printed-bills-unpaid-%04d-%02d.pdf', $period->year, $period->month);
    }
}
