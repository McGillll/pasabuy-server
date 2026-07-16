@props(['eyebrow' => 'INVOICE', 'preheader' => null, 'brand' => null, 'reference' => null])
@php($brand = $brand ?: 'Pasabuy')
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $eyebrow }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f5; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
@if ($preheader)
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">{{ $preheader }}</div>
@endif
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f5;">
<tr>
<td align="center" style="padding:24px 16px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:8px;">
<tr>
<td style="padding:24px 32px; border-bottom:1px solid #e5e7eb;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="font-size:14px; font-weight:700; letter-spacing:0.5px; color:#111827;">
{{ $brand }}
</td>
<td align="right" style="font-size:11px; font-weight:700; letter-spacing:1px; color:#9ca3af;">
{{ $eyebrow }}
@if ($reference)
<div style="margin-top:4px; font-size:11px; font-weight:400; letter-spacing:0; color:#9ca3af; font-family:monospace;">{{ $reference }}</div>
@endif
</td>
</tr>
</table>
</td>
</tr>
{{ $slot }}
<tr>
<td style="padding:20px 32px; background-color:#fafafa; border-top:1px solid #e5e7eb; border-radius:0 0 8px 8px;">
<p style="margin:0 0 4px; font-size:13px; color:#6b7280;">Thanks,<br>{{ $brand }}</p>
<p style="margin:0; font-size:11px; color:#9ca3af; text-align:center;">&copy; {{ date('Y') }} {{ $brand }}. All rights reserved.</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
