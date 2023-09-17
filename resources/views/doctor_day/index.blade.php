@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Doctor-Day Relationships</h1>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Doctor</th>
                <th>Day</th>
                <th>Duration</th>
                <th>Start Time (AM)</th>
                <th>End Time (AM)</th>
                <th>Start Time (PM)</th>
                <th>End Time (PM)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($doctorDays as $doctorDay)
            <tr>
                <td>{{ $doctorDay->id }}</td>
                <td>{{ $doctorDay->doctor->name }}</td>
                <td>{{ $doctorDay->day->name }}</td>
                <td>{{ $doctorDay->duration }}</td>
                <td>{{ $doctorDay->start_time_am }}</td>
                <td>{{ $doctorDay->end_time_am }}</td>
                <td>{{ $doctorDay->start_time_pm }}</td>
                <td>{{ $doctorDay->end_time_pm }}</td>
                <td>
                    <a href="{{ route('doctor-day.show', $doctorDay->id) }}" class="btn btn-primary btn-sm">View</a>
                    <a href="{{ route('doctor-day.edit', $doctorDay->id) }}" class="btn btn-success btn-sm">Edit</a>
                    <form action="{{ route('doctor-day.destroy', $doctorDay->id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this relationship?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <a href="{{ route('doctor-day.create') }}" class="btn btn-primary">Create New Relationship</a>
</div>
@endsection