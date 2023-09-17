@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Create New Prescription</h2>
        
        <form method="POST" action="{{ route('newpres.store') }}">
            @csrf

            <div class="form-group">
                <label for="appointment_id">Appointment ID</label>
                <input type="number" name="appointment_id" class="form-control" id="appointment_id" required>
            </div>
            <div class="form-group">
                <label for="drug_name">Drug Name</label>
                <input type="text" name="drug_name" class="form-control" id="drug_name" required>
            </div>
            <div class="form-group">
                <label for="period">Period</label>
                <input type="text" name="period" class="form-control" id="period" required>
            </div>
            <div class="form-group">
                <label for="times">Times</label>
                <input type="number" name="times" class="form-control" id="times" required>
            </div>
            <div class="form-group">
                <label for="notes">Notes (optional)</label>
                <textarea name="notes" class="form-control" id="notes"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
@endsection