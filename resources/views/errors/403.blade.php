@extends('errors::minimal')

@section('title', __('errors.403'))
@section('code', '403')
@section('message', app(\App\Exceptions\LocalizedErrorRenderer::class)->hasAppMessage($exception) ? $exception->getMessage() : __('messages.forbidden'))
