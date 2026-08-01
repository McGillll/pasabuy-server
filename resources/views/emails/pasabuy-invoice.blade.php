@php
    $isOverdue = $invoice['is_overdue'] ?? false;
    $statusColors = match (true) {
        $isOverdue => ['label' => 'Overdue', 'bg' => '#fef2f2', 'text' => '#b91c1c', 'border' => '#fecaca'],
        $order->payment_status === 'paid' => ['label' => 'Paid', 'bg' => '#ecfdf5', 'text' => '#15803d', 'border' => '#a7f3d0'],
        $order->payment_status === 'partially_paid' => ['label' => 'Partially Paid', 'bg' => '#fffbeb', 'text' => '#b45309', 'border' => '#fde68a'],
        default => ['label' => 'Unpaid', 'bg' => '#fffbeb', 'text' => '#b45309', 'border' => '#fde68a'],
    };
@endphp
<x-emails.layout eyebrow="INVOICE" :brand="$businessName" :reference="$order->order_no" :preheader="'Balance due: '.number_format($invoice['balance_due'], 2)">

<tr>
<td style="padding:28px 32px; background-color:{{ $statusColors['bg'] }}; border-bottom:1px solid {{ $statusColors['border'] }};">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="font-size:11px; font-weight:700; letter-spacing:1px; color:{{ $statusColors['text'] }}; text-transform:uppercase;">Balance Due</td>
<td align="right"><x-emails.status-badge :label="$statusColors['label']" :text="$statusColors['text']" :border="$statusColors['border']" /></td>
</tr>
</table>
<div style="margin-top:8px; font-size:34px; font-weight:700; color:{{ $statusColors['text'] }}; line-height:1.2;">
{{ number_format($invoice['balance_due'], 2) }}
</div>
@if ($invoice['due_date'])
<div style="margin-top:4px; font-size:13px; color:#57534e;">
Due {{ $invoice['due_date']->format('M j, Y') }}
</div>
@endif
</td>
</tr>

<tr>
<td style="padding:24px 32px 8px;">
<p style="margin:0 0 8px; font-size:15px; color:#111827;">Hi {{ $order->customer_name }},</p>
<p style="margin:0; font-size:14px; color:#6b7280; line-height:1.5;">
Thanks for your order! This pasabuy run has finished for <strong style="color:#111827;">{{ $order->pasabuySchedule->title }}</strong>. Here's your invoice summary.
</p>
</td>
</tr>

<tr>
<td style="padding:16px 32px 8px;">
<p style="margin:0 0 12px; font-size:11px; font-weight:700; letter-spacing:1px; color:#9ca3af; text-transform:uppercase; border-bottom:1px solid #e5e7eb; padding-bottom:8px;">Order Summary</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="padding:8px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Product</td>
<td align="right" style="padding:8px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Qty</td>
<td align="right" style="padding:8px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Price</td>
<td align="right" style="padding:8px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Subtotal</td>
</tr>
@foreach ($invoice['items'] as $item)
<tr>
<td style="padding:10px 0; font-size:14px; color:#111827; border-top:1px solid #f3f4f6;">{{ $item['product_name'] }}</td>
<td align="right" style="padding:10px 0; font-size:14px; color:#374151; border-top:1px solid #f3f4f6;">{{ $item['quantity'] }}</td>
<td align="right" style="padding:10px 0; font-size:14px; color:#374151; border-top:1px solid #f3f4f6;">{{ number_format($item['unit_price'], 2) }}</td>
<td align="right" style="padding:10px 0; font-size:14px; color:#111827; border-top:1px solid #f3f4f6;">{{ number_format($item['line_total'], 2) }}</td>
</tr>
@endforeach
</table>
</td>
</tr>

<tr>
<td style="padding:8px 32px 24px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td></td>
<td width="220">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="padding:4px 0; font-size:13px; color:#6b7280;">Subtotal</td>
<td align="right" style="padding:4px 0; font-size:13px; color:#6b7280;">{{ number_format($invoice['subtotal'], 2) }}</td>
</tr>
<tr>
<td style="padding:4px 0; font-size:13px; color:#6b7280;">Markup</td>
<td align="right" style="padding:4px 0; font-size:13px; color:#6b7280;">{{ number_format($invoice['markup_amount'], 2) }}</td>
</tr>
@if ($invoice['handling_fee'] > 0)
<tr>
<td style="padding:4px 0; font-size:13px; color:#6b7280;">Handling fee</td>
<td align="right" style="padding:4px 0; font-size:13px; color:#6b7280;">{{ number_format($invoice['handling_fee'], 2) }}</td>
</tr>
@endif
<tr>
<td style="padding:8px 0 0; font-size:14px; font-weight:700; color:#111827; border-top:1px solid #e5e7eb;">Total</td>
<td align="right" style="padding:8px 0 0; font-size:14px; font-weight:700; color:#111827; border-top:1px solid #e5e7eb;">{{ number_format($invoice['total'], 2) }}</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>

@if (! empty($invoice['interest_lines']))
<tr>
<td style="padding:0 32px 24px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fef2f2; border-left:3px solid #b91c1c; border-radius:4px;">
<tr>
<td style="padding:14px 16px;">
<p style="margin:0 0 10px; font-size:13px; color:#991b1b; line-height:1.5;">
This balance is overdue and accruing interest, compounded per {{ $invoice['interest_base'] === 'outstanding_balance' ? 'outstanding balance' : 'full total' }}.
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="padding:4px 0; font-size:11px; font-weight:700; color:#991b1b; text-transform:uppercase;">Ledger</td>
<td align="right" style="padding:4px 0; font-size:11px; font-weight:700; color:#991b1b; text-transform:uppercase;">Amount</td>
<td align="right" style="padding:4px 0; font-size:11px; font-weight:700; color:#991b1b; text-transform:uppercase;">Running Total</td>
</tr>
<tr>
<td style="padding:4px 0; font-size:13px; color:#7f1d1d;">Total</td>
<td align="right" style="padding:4px 0; font-size:13px; color:#7f1d1d;"></td>
<td align="right" style="padding:4px 0; font-size:13px; color:#7f1d1d; font-weight:700;">{{ number_format($invoice['total'], 2) }}</td>
</tr>
@foreach ($invoice['interest_lines'] as $line)
<tr>
<td style="padding:4px 0; font-size:13px; color:#7f1d1d;">{{ $line['label'] }}</td>
<td align="right" style="padding:4px 0; font-size:13px; color:{{ $line['type'] === 'payment' ? '#15803d' : '#7f1d1d' }};">{{ $line['amount'] >= 0 ? '+' : '' }}{{ number_format($line['amount'], 2) }}</td>
<td align="right" style="padding:4px 0; font-size:13px; color:#7f1d1d; font-weight:700;">{{ number_format($line['running_total'], 2) }}</td>
</tr>
@endforeach
</table>
</td>
</tr>
</table>
</td>
</tr>
@endif

@if (! empty($invoice['payments']))
<tr>
<td style="padding:0 32px 24px;">
<p style="margin:0 0 12px; font-size:11px; font-weight:700; letter-spacing:1px; color:#9ca3af; text-transform:uppercase; border-bottom:1px solid #e5e7eb; padding-bottom:8px;">Payments Received</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="padding:6px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Date</td>
<td align="right" style="padding:6px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Amount</td>
<td align="right" style="padding:6px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Method</td>
<td align="right" style="padding:6px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Reference</td>
</tr>
@foreach ($invoice['payments'] as $payment)
<tr>
<td style="padding:8px 0; font-size:13px; color:#374151; border-top:1px solid #f3f4f6;">{{ optional($payment['paid_at'])->format('M j, Y') }}</td>
<td align="right" style="padding:8px 0; font-size:13px; color:#111827; border-top:1px solid #f3f4f6;">{{ number_format($payment['amount'], 2) }}</td>
<td align="right" style="padding:8px 0; font-size:13px; color:#374151; border-top:1px solid #f3f4f6;">{{ ucfirst(str_replace('_', ' ', $payment['method'])) }}</td>
<td align="right" style="padding:8px 0; font-size:13px; color:#9ca3af; border-top:1px solid #f3f4f6;">{{ $payment['reference_number'] ?? 'N/A' }}</td>
</tr>
@endforeach
</table>
</td>
</tr>
@endif

<tr>
<td style="padding:20px 32px; background-color:#f9fafb; border-top:2px solid #111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="padding:2px 0; font-size:13px; color:#6b7280;">Grand Total</td>
<td align="right" style="padding:2px 0; font-size:13px; color:#6b7280;">{{ number_format($invoice['grand_total'], 2) }}</td>
</tr>
<tr>
<td style="padding:2px 0; font-size:13px; color:#6b7280;">Amount Paid</td>
<td align="right" style="padding:2px 0; font-size:13px; color:#6b7280;">{{ number_format($invoice['amount_paid'], 2) }}</td>
</tr>
<tr>
<td style="padding:10px 0 0; font-size:16px; font-weight:700; color:{{ $statusColors['text'] }}; border-top:1px solid #e5e7eb;">Balance Due</td>
<td align="right" style="padding:10px 0 0; font-size:16px; font-weight:700; color:{{ $statusColors['text'] }}; border-top:1px solid #e5e7eb;">{{ number_format($invoice['balance_due'], 2) }}</td>
</tr>
</table>
<p style="margin:12px 0 0; font-size:12px; color:#9ca3af;">
Payment status: {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}
</p>
</td>
</tr>

</x-emails.layout>
