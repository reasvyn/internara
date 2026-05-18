@extends('errors::minimal')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message', $exception->getMessage() ?: __('Forbidden'))
