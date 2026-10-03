@extends('layouts.app')

@section('title', 'فروشگاه محصولات - ' . site_setting('site_name', config('app.name')))

@section('content')
    <livewire:catalog.product-catalog
        :category="request('category')"
        :search="request('search', '')"
        :color-id="request()->integer('color_id') ?: null"
        :min-price="request()->integer('min_price') ?: null"
        :max-price="request()->integer('max_price') ?: null"
        :sort="request('sort', 'newest')"
    />
@endsection