@extends('errors.layout')

@section('code', '404')
@section('title', __('Page introuvable'))
@section('message', __('La page que vous cherchez n’existe pas ou a été déplacée.'))

@section('secondary')
    <x-ui.button :href="route('catalog.index')" variant="secondary" size="lg">{{ __('Voir les formations') }}</x-ui.button>
@endsection
