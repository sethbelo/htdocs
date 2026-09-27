{{-- Statut métier normalisé : <x-ui.status map="voucher" :value="$v->status" /> --}}
@props(['map', 'value'])
@php [$label, $tone] = erp_status($map, $value instanceof \BackedEnum ? $value->value : $value); @endphp
<x-ui.badge :tone="$tone" dot {{ $attributes }}>{{ $label }}</x-ui.badge>
