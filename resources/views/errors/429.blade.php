@extends('errors.layout')

@section('code', '429')
@section('title', __('Trop de requêtes'))
@section('message', __('Vous avez effectué trop de tentatives en peu de temps. Patientez un moment avant de réessayer.'))
