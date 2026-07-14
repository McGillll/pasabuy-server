<x-mail::message>
# Overdue Invoices

Hi {{ $seller->first_name }},

The following orders are overdue and still have a balance due. Please follow up with these customers.

<x-mail::table>
| Customer | Order Ref | Invoice Total | Interest | Balance Due | Due Date |
| :------- | :-------- | -------------: | -------: | -----------: | :------- |
@foreach ($rows as $row)
| {{ $row['customer_name'] }} | {{ substr($row['order_uuid'], 0, 8) }} | {{ number_format($row['invoice_total'], 2) }} | {{ number_format($row['interest_amount'], 2) }} | {{ number_format($row['balance_due'], 2) }} | {{ optional($row['due_date'])->format('M j, Y') }} |
@endforeach
</x-mail::table>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
