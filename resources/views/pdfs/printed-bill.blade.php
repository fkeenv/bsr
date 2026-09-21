<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Printed Bill</title>
    <style>
        @page {
            size: A4;
            margin: 18mm 16mm;
        }

        body {
            color: #18181b;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
        }

        .bill-sheet {
            page-break-after: always;
        }

        .bill-sheet:last-child {
            page-break-after: auto;
        }

        .bill-inner {
            min-height: 250mm;
        }

        .letterhead {
            border-bottom: 2px solid #18181b;
            padding-bottom: 12px;
        }

        .eyebrow {
            font-size: 10px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin: 0;
        }

        .association-name {
            font-family: DejaVu Serif, serif;
            font-size: 22px;
            font-weight: 700;
            margin: 4px 0 6px;
        }

        .muted {
            color: #52525b;
            margin: 0;
        }

        .meta {
            margin-top: 10px;
            width: 100%;
        }

        .meta td {
            vertical-align: bottom;
        }

        .meta td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .section {
            margin-top: 22px;
        }

        .section-label {
            color: #71717a;
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin: 0 0 4px;
        }

        .bill-to {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 2px;
        }

        h2 {
            border-bottom: 1px solid #d4d4d8;
            font-size: 12px;
            letter-spacing: 0.08em;
            margin: 0 0 8px;
            padding-bottom: 4px;
            text-transform: uppercase;
        }

        table.lines {
            border-collapse: collapse;
            width: 100%;
        }

        table.lines th,
        table.lines td {
            padding: 6px 0;
            text-align: left;
        }

        table.lines th {
            color: #71717a;
            font-weight: 600;
        }

        table.lines td.amount,
        table.lines th.amount {
            text-align: right;
        }

        table.lines tbody tr {
            border-top: 1px solid #f4f4f5;
        }

        table.lines tfoot tr {
            border-top: 1px solid #a1a1aa;
            font-weight: 600;
        }

        .forward {
            border-top: 1px dashed #a1a1aa;
            margin-top: 18px;
            padding-top: 8px;
        }

        .forward-row {
            width: 100%;
        }

        .forward-row td:last-child {
            text-align: right;
        }

        .due {
            border: 2px solid #18181b;
            margin-top: 18px;
            padding: 10px 14px;
            width: 100%;
        }

        .due td:last-child {
            font-family: DejaVu Serif, serif;
            font-size: 28px;
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }

        .channels {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .channels li {
            margin-bottom: 8px;
        }

        .channel-method {
            display: inline-block;
            font-weight: 600;
            width: 110px;
        }

        .footer {
            border-top: 1px solid #e4e4e7;
            color: #71717a;
            font-size: 10px;
            margin-top: 28px;
            padding-top: 8px;
        }
    </style>
</head>
<body>
@foreach ($bills as $bill)
    <article class="bill-sheet">
        <div class="bill-inner">
            <header class="letterhead">
                <p class="eyebrow">{{ $letterhead['short_name'] }}</p>
                <h1 class="association-name">{{ $letterhead['name'] }}</h1>
                @foreach ($letterhead['address_lines'] as $line)
                    <p class="muted">{{ $line }}</p>
                @endforeach
                <table class="meta">
                    <tr>
                        <td><span class="muted">Issued</span> {{ $issuedOn }}</td>
                        <td>Billing Period: {{ $bill['period_label'] }}</td>
                    </tr>
                </table>
            </header>

            <section class="section">
                <p class="section-label">Bill to</p>
                <p class="bill-to">{{ $bill['bill_to'] }}</p>
                <p>{{ $bill['property_label'] }}</p>
                @if (filled($bill['address']))
                    <p class="muted">{{ $bill['address'] }}</p>
                @endif
            </section>

            <section class="section">
                <h2>Charges for {{ $bill['period_label'] }}</h2>
                <table class="lines">
                    <thead>
                        <tr>
                            <th>Fee Type</th>
                            <th class="amount">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bill['lines'] as $line)
                            <tr>
                                <td>{{ $line['name'] }}</td>
                                <td class="amount">{{ number_format((float) $line['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="muted">No Charge levied for this Billing Period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>This period</td>
                            <td class="amount">{{ number_format((float) $bill['period_total'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </section>

            <section class="forward">
                <table class="forward-row">
                    <tr>
                        <td>Balance forward (prior arrears)</td>
                        <td>{{ number_format((float) $bill['balance_forward'], 2) }}</td>
                    </tr>
                </table>
                @if ((float) $bill['opening_balance_remaining'] > 0 && count($bill['older_unpaid_labels']) > 0)
                    <p class="muted">Includes Opening Balance {{ number_format((float) $bill['opening_balance_remaining'], 2) }} and unpaid {{ implode(', ', $bill['older_unpaid_labels']) }}</p>
                @elseif ((float) $bill['opening_balance_remaining'] > 0)
                    <p class="muted">Includes Opening Balance {{ number_format((float) $bill['opening_balance_remaining'], 2) }}</p>
                @elseif (count($bill['older_unpaid_labels']) > 0)
                    <p class="muted">Unpaid {{ implode(', ', $bill['older_unpaid_labels']) }}</p>
                @endif
            </section>

            <table class="due">
                <tr>
                    <td>
                        <div class="section-label">Amount due</div>
                        <div class="muted">Outstanding Balance for this Property</div>
                    </td>
                    <td>{{ number_format((float) $bill['outstanding_balance'], 2) }}</td>
                </tr>
            </table>

            <section class="section">
                <h2>How to pay</h2>
                <ul class="channels">
                    @foreach ($paymentChannels as $channel)
                        <li>
                            <span class="channel-method">{{ $channel['method'] }}</span>
                            <span class="muted">{{ $channel['detail'] }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="muted" style="margin-top: 14px;">{{ $letterhead['treasurer'] }} · {{ $letterhead['contact'] }}</p>
            </section>

            <footer class="footer">
                This notice is not a receipt. Confirmed Payments appear on your Statement of Account in the member platform. Please name {{ $bill['property_label'] }} on every transfer.
            </footer>
        </div>
    </article>
@endforeach
</body>
</html>
