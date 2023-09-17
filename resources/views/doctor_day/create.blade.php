@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create New Doctor-Day Relationship</h1>
    <form method="POST" action="{{ route('doctor-day.store') }}">
        @csrf
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
            <input type="number" class="form-control" id="duration" name="duration" required>
        </div>

        <div class="form-group">
            <label for="start_time_am">Start Time (AM):</label>
            <input type="time" class="form-control" id="start_time_am" name="start_time_am" required>
        </div>

        <div class="form-group">
            <label for="end_time_am">End Time (AM):</label>
            <input type="time" class="form-control" id="end_time_am" name="end_time_am" required>
        </div>

        <div class="form-group">
            <label for="start_time_pm">Start Time (PM):</label>
            <input type="time" class="form-control" id="start_time_pm" name="start_time_pm" required>
        </div>

        <div class="form-group">
            <label for="end_time_pm">End Time (PM):</label>
            <input type="time" class="form-control" id="end_time_pm" name="end_time_pm" required>
        </div>

        <button type="submit" class="btn btn-primary">Create Relationship</button>
    </form>
</div>
@endsection