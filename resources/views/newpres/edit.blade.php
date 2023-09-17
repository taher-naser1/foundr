@extends('layouts.app') <!-- Use your layout file here if you have one -->

@section('content')
<div class="container">
    <h2>Edit Prescription</h2>
    <form method="POST" action="{{ route('newpres.update', $prescription->id) }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="appointment_id">Appointment ID:</label>
            <input type="text" name="appointment_id" class="form-control" value="{{ $prescription->appointment_id }}" required>
        </div>

        <div class="form-group">
            <label for="drug_name">Drug Name:</label>
            <input type="text" name="drug_name" class="form-control" value="{{ $prescription->drug_name }}" required>
        </div>

        <div class="form-group">
            <label for="period">Period:</label>
            <input type="text" name="period" class="form-control" value="{{ $prescription->period }}" required>
        </div>

        <div class="form-group">
            <label for="times">Number of Times:</label>
            <input type="text" name="times" class="form-control" value="{{ $prescription->times }}" required>
        </div>

        <div class="form-group">
            <label for="notes">Notes (optional):</label>
            <textarea name="notes" class="form-control">{{ $prescription->notes }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Prescription</button>
    </form>
</div>
@endsection