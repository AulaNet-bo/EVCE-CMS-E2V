@props(['url'])
@php
    $settings = \App\Models\SystemSetting::first();
    $logoUrl = ($settings && $settings->logo_path) ? asset('storage/' . $settings->logo_path) : null;
    $platformName = ($settings && $settings->platform_name) ? $settings->platform_name : 'Electropoint';
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($logoUrl)
<img src="{{ $logoUrl }}" class="logo" alt="{{ $platformName }}" style="max-height: 60px; width: auto; max-width: 200px;">
@else
{{ $platformName }}
@endif
</a>
</td>
</tr>
