@extends('layout')
@section('content')
<div>
    <form method="POST" action="{{ route('readImage') }}" enctype="multipart/form-data">
        {{ csrf_field() }}
        <input type="file" name="image" placeholder="Select image">
        <button type="submit">Parse Text</button>
    </form>
</div>
@endsection