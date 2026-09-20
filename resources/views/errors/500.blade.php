@extends('errors::minimal')

@section('title', __('errors.server_error'))
@section('code', '500')
@section('message', __('errors.server_error'))
@if (__('errors.server_error', [], 'id') !== __('errors.server_error'))
    <span class="sr-only">{{ __('errors.server_error', [], 'id') }}</span>
@endif
