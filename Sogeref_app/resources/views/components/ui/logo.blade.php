{{-- Logo SOGEREF : variant = horizontal | vertical ; white pour fonds bleus --}}
@props(['variant' => 'horizontal', 'white' => false])
<img src="{{ asset('brand/logo-'.$variant.($white ? '-white' : '').'.png') }}" alt="SOGEREF Sarl — Sculptons vos rêves, bâtissons la réalité" {{ $attributes }}>
