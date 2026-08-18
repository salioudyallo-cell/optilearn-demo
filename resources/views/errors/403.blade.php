@extends('errors.layout')

@section('code', '403')
@section('title', __('Accès refusé'))
@section('message', __('Vous n’avez pas l’autorisation d’accéder à cette page. Si vous pensez qu’il s’agit d’une erreur, contactez :brand.', ['brand' => config('brand.short_name')]))
