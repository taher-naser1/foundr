@extends('layouts.app')
@section('title')
    {{__('messages.doctors')}}
@endsection
@section('content')
    
<!-- edit-services.blade.php -->


<form method="POST" action="{{ route('doctor.update-services', $doctor->id) }}">
    @csrf
    @method('PUT')

    @foreach($services as $service)
    <div class="form-group">
        <label for="service-{{ $service->id }}">{{ $service->name }}</label>
        <input type="checkbox" name="services[{{ $service->id }}][activated]" id="service-{{ $service->id }}"
               value="1" {{ $service->pivot->activated ? 'checked' : '' }}>
        <input type="text" name="services[{{ $service->id }}][price]" id="price-{{ $service->id }}"
               value="{{ $service->pivot->price }}">
    </div>
@endforeach

    <button type="submit">Save</button>
</form>

@endsection