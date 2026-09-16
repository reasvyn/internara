@extends('errors::minimal')

@section('title', __('errors.bad_request'))
@section('code', '400')
@section('message', $exception->getMessage())
