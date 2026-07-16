@php
    $lastPayment = collect($invoice['payments'])->last();
@endphp
<x-emails.layout eyebrow="RECEIPT" :brand="$businessName" :reference="$order->order_no" :preheader="'Payment received: '.number_format($invoice['amount_paid'], 2)">

<tr>
<td style="padding:28px 32px; background-color:#ecfdf5; border-bottom:1px solid #a7f3d0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="font-size:11px; font-weight:700; letter-spacing:1px; color:#15803d; text-transform:uppercase;">Amount Paid</td>
<td align="right"><x-emails.status-badge label="Paid in Full" text="#15803d" border="#a7f3d0" /></td>
</tr>
</table>
<div style="margin-top:8px; font-size:34px; font-weight:700; color:#15803d; line-height:1.2;">
{{ number_format($invoice['amount_paid'], 2) }}
</div>
@if ($lastPayment && $lastPayment['paid_at'])
<div style="margin-top:4px; font-size:13px; color:#166534;">
Received {{ $lastPayment['paid_at']->format('M j, Y') }}
</div>
@endif
</td>
</tr>

<tr>
<td style="padding:24px 32px 8px;">
<p style="margin:0 0 8px; font-size:15px; color:#111827;">Hi {{ $order->customer_name }},</p>
<p style="margin:0; font-size:14px; color:#6b7280; line-height:1.5;">
Thanks! We've received full payment for <strong style="color:#111827;">{{ $order->pasabuySchedule->title }}</strong>. Here's your receipt.
</p>
</td>
</tr>

<tr>
<td style="padding:16px 32px 24px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="padding:4px 0; font-size:13px; color:#6b7280;">Total</td>
<td align="right" style="padding:4px 0; font-size:13px; color:#6b7280;">{{ number_format($invoice['total'], 2) }}</td>
</tr>
@if ($lastPayment)
<tr>
<td style="padding:4px 0; font-size:13px; color:#6b7280;">Payment method</td>
<td align="right" style="padding:4px 0; font-size:13px; color:#6b7280;">{{ ucfirst(str_replace('_', ' ', $lastPayment['method'])) }}</td>
</tr>
@endif
<tr>
<td style="padding:8px 0 0; font-size:14px; font-weight:700; color:#111827; border-top:1px solid #e5e7eb;">Amount Paid</td>
<td align="right" style="padding:8px 0 0; font-size:14px; font-weight:700; color:#111827; border-top:1px solid #e5e7eb;">{{ number_format($invoice['amount_paid'], 2) }}</td>
</tr>
</table>
</td>
</tr>

</x-emails.layout>
