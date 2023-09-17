@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Doctor Day</h1>
    
    <form method="POST" action="{{ route('doctor-day.update', $doctorDay) }}">
        @csrf
        @method('PUT') <!-- Use 'PATCH' method for updating -->

        <!-- Display and allow users to edit the doctor day properties -->
        <!-- For example, you can use form input fields -->



        <div class="form-group">
            <label for="day_id">Old Day:</label>
            <input type="text" class="form-control" value="{{ $dayName }}" disabled>
        </div>




        <div class="form-group">
            <label for="day_id">Day:</label>
            <select class="form-control" id="day_id" name="day_id" required>
                @foreach($days as $day)
                <option value="{{ $day->id }}">{{ $day->name }}</option>
                @endforeach
            </select>
        </div>




        <div class="form-group">
            <label for="duration">Duration (minutes):</label>
            <input type="text" class="form-control" id="duration" name="duration" value="{{ $doctorDay->duration}}">
        </div>

        <div class="form-group">
            <label for="start_time_am">Start Time (AM):</label>
            <input type="text" class="form-control" id="start_time_am" name="start_time_am" value="{{ $doctorDay->start_time_am }}">
        </div>

        <div class="form-group">
            <label for="end_time_am">End Time (AM):</label>
            <input type="text" class="form-control" id="end_time_am" name="end_time_am" value="{{ $doctorDay->end_time_am }}">
        </div>

        <div class="form-group">
            <label for="start_time_pm">Start Time (PM):</label>
            <input type="text" class="form-control" id="start_time_pm" name="start_time_pm" value="{{ $doctorDay->start_time_pm}}">
        </div>

        <div class="form-group">
            <label for="end_time_pm">End Time (PM):</label>
            <input type="text" class="form-control" id="end_time_pm" name="end_time_pm" value="{{ $doctorDay->end_time_pm }}">
        </div>

        <!-- Add more input fields for other properties -->

        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
