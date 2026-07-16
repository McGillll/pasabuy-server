@props(['label', 'text', 'border'])
<span style="display:inline-block; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:700; letter-spacing:0.5px; background-color:#ffffff; color:{{ $text }}; border:1px solid {{ $border }}; white-space:nowrap;">
{{ strtoupper($label) }}
</span>
