<x-emails.layout eyebrow="REMINDER" :brand="$businessName" :reference="$order->order_no" :preheader="'Still overdue: '.number_format($invoice['balance_due'], 2)">

<tr>
<td style="padding:28px 32px; background-color:#fef2f2; border-bottom:1px solid #fecaca;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="font-size:11px; font-weight:700; letter-spacing:1px; color:#b91c1c; text-transform:uppercase;">Balance Due</td>
<td align="right"><x-emails.status-badge :label="$daysOverdue.' Days Overdue'" text="#b91c1c" border="#fecaca" /></td>
</tr>
</table>
<div style="margin-top:8px; font-size:34px; font-weight:700; color:#b91c1c; line-height:1.2;">
{{ number_format($invoice['balance_due'], 2) }}
</div>
<div style="margin-top:4px; font-size:13px; color:#57534e;">
Was due {{ $invoice['due_date']->format('M j, Y') }}
</div>
</td>
</tr>

<tr>
<td style="padding:24px 32px 8px;">
<p style="margin:0 0 8px; font-size:15px; color:#111827;">Hi {{ $order->customer_name }},</p>
<p style="margin:0; font-size:14px; color:#6b7280; line-height:1.5;">
This is a reminder that your balance for <strong style="color:#111827;">{{ $order->pasabuySchedule->title }}</strong> is still unpaid and continues to accrue interest.
</p>
</td>
</tr>

<tr>
<td style="padding:16px 32px 24px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="padding:4px 0; font-size:13px; color:#6b7280;">Original Total</td>
<td align="right" style="padding:4px 0; font-size:13px; color:#6b7280;">{{ number_format($invoice['total'], 2) }}</td>
</tr>
<tr>
<td style="padding:4px 0; font-size:13px; color:#6b7280;">Interest accrued so far</td>
<td align="right" style="padding:4px 0; font-size:13px; color:#b91c1c;">+{{ number_format($invoice['interest_total'], 2) }}</td>
</tr>
<tr>
<td style="padding:4px 0; font-size:13px; color:#6b7280;">Amount Paid</td>
<td align="right" style="padding:4px 0; font-size:13px; color:#6b7280;">-{{ number_format($invoice['amount_paid'], 2) }}</td>
</tr>
<tr>
<td style="padding:8px 0 0; font-size:14px; font-weight:700; color:#b91c1c; border-top:1px solid #e5e7eb;">Balance Due</td>
<td align="right" style="padding:8px 0 0; font-size:14px; font-weight:700; color:#b91c1c; border-top:1px solid #e5e7eb;">{{ number_format($invoice['balance_due'], 2) }}</td>
</tr>
</table>
<p style="margin:16px 0 0; font-size:12px; color:#9ca3af;">
Please settle this balance as soon as possible to avoid further interest charges.
</p>
</td>
</tr>

</x-emails.layout>
