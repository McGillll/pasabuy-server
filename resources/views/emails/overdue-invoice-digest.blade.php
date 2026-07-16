@php
    $totalBalance = $rows->sum('balance_due');
    $count = $rows->count();
@endphp
<x-emails.layout eyebrow="OVERDUE DIGEST" :brand="$businessName" :preheader="$count.' overdue order(s), '.number_format($totalBalance, 2).' total balance due'">

<tr>
<td style="padding:28px 32px; background-color:#fef2f2; border-bottom:1px solid #fecaca;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="font-size:11px; font-weight:700; letter-spacing:1px; color:#b91c1c; text-transform:uppercase;">Total Overdue Balance</td>
<td align="right"><x-emails.status-badge :label="$count.' '.($count === 1 ? 'Order' : 'Orders')" text="#b91c1c" border="#fecaca" /></td>
</tr>
</table>
<div style="margin-top:8px; font-size:34px; font-weight:700; color:#b91c1c; line-height:1.2;">
{{ number_format($totalBalance, 2) }}
</div>
<div style="margin-top:4px; font-size:13px; color:#7f1d1d;">
Across {{ $count }} {{ $count === 1 ? 'order' : 'orders' }}
</div>
</td>
</tr>

<tr>
<td style="padding:24px 32px 8px;">
<p style="margin:0 0 8px; font-size:15px; color:#111827;">Hi {{ $seller->first_name }},</p>
<p style="margin:0; font-size:14px; color:#6b7280; line-height:1.5;">
The orders below are overdue and still have a balance due. Please follow up with these customers.
</p>
</td>
</tr>

<tr>
<td style="padding:16px 32px 24px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="padding:8px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; border-bottom:1px solid #e5e7eb;">Customer</td>
<td style="padding:8px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; border-bottom:1px solid #e5e7eb;">Order Ref</td>
<td align="right" style="padding:8px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; border-bottom:1px solid #e5e7eb;">Total</td>
<td align="right" style="padding:8px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; border-bottom:1px solid #e5e7eb;">Interest</td>
<td align="right" style="padding:8px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; border-bottom:1px solid #e5e7eb;">Balance Due</td>
<td align="right" style="padding:8px 0; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; border-bottom:1px solid #e5e7eb;">Due Date</td>
</tr>
@foreach ($rows as $row)
<tr>
<td style="padding:10px 0; font-size:13px; color:#111827; border-bottom:1px solid #f3f4f6;">{{ $row['customer_name'] }}</td>
<td style="padding:10px 0; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6; font-family:monospace;">{{ substr($row['order_uuid'], 0, 8) }}</td>
<td align="right" style="padding:10px 0; font-size:13px; color:#374151; border-bottom:1px solid #f3f4f6;">{{ number_format($row['total'], 2) }}</td>
<td align="right" style="padding:10px 0; font-size:13px; color:#b45309; border-bottom:1px solid #f3f4f6;">{{ number_format($row['interest_total'], 2) }}</td>
<td align="right" style="padding:10px 0; font-size:13px; font-weight:700; color:#b91c1c; border-bottom:1px solid #f3f4f6;">{{ number_format($row['balance_due'], 2) }}</td>
<td align="right" style="padding:10px 0; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6;">{{ optional($row['due_date'])->format('M j, Y') }}</td>
</tr>
@endforeach
</table>
</td>
</tr>

</x-emails.layout>
