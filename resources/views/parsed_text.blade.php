@extends('layout')
@section('content')
    <h2>Output</h2>
    @if(!@empty($parsedText))
        @dd($parsedText)
        <p style="text-align: left; color: Green;">{{ $parsedText }}</p>
    @else
        <p style="text-align: left; color: Red;" >No Text Found</p>
    @endif
    <a href="{{ route('home') }}">Parse Another Image</a>
@endsection
