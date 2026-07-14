<x-mail::message>
# Invoice for {{ $order->pasabuySchedule->title }}

Hi {{ $order->customer_name }},

Thanks for your order! This pasabuy run has finished, and here's your invoice summary.

<x-mail::table>
| Product | Qty | Price | Subtotal |
| :------ | --: | ----: | -------: |
@foreach ($invoice['items'] as $item)
| {{ $item['product_name'] }} | {{ $item['quantity'] }} | {{ number_format($item['unit_price'], 2) }} | {{ number_format($item['line_total'], 2) }} |
@endforeach
</x-mail::table>

| | |
| :--- | ---: |
| Subtotal | {{ number_format($invoice['subtotal'], 2) }} |
| Markup | {{ number_format($invoice['markup_amount'], 2) }} |
| **Total** | **{{ number_format($invoice['total'], 2) }}** |

@if (! empty($invoice['interest_lines']))
The total above is overdue and is accruing interest, compounded per {{ $invoice['interest_base'] === 'outstanding_balance' ? 'outstanding balance' : 'full total' }}:

<x-mail::table>
| Period | Interest Added | Running Total |
| :----- | -------------: | ------------: |
@foreach ($invoice['interest_lines'] as $line)
| {{ $line['label'] }} | {{ number_format($line['interest_amount'], 2) }} | {{ number_format($line['running_total'], 2) }} |
@endforeach
</x-mail::table>

Interest so far: {{ number_format($invoice['interest_total'], 2) }}
@endif

@if (! empty($invoice['payments']))
<x-mail::table>
| Date | Amount | Method | Reference |
| :--- | -----: | :----- | :-------- |
@foreach ($invoice['payments'] as $payment)
| {{ optional($payment['paid_at'])->format('M j, Y') }} | {{ number_format($payment['amount'], 2) }} | {{ ucfirst(str_replace('_', ' ', $payment['method'])) }} | {{ $payment['reference_number'] ?? '—' }} |
@endforeach
</x-mail::table>
@endif

| | |
| :--- | ---: |
| **Grand Total** | **{{ number_format($invoice['grand_total'], 2) }}** |
| Amount Paid | {{ number_format($invoice['amount_paid'], 2) }} |
| **Balance Due** | **{{ number_format($invoice['balance_due'], 2) }}** |

Due Date: {{ optional($invoice['due_date'])->format('M j, Y') ?? 'N/A' }}<br>
Payment Status: {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
