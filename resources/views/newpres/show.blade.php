@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Prescription Details for Appointment ID: {{ $prescription->first()->appointment_id }}</h2>

        @if($prescription->isEmpty())
            <p>No prescriptions found for this appointment ID.</p>
        @else
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Drug Name</th>
                            <th>Period</th>
                            <th>Times</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($prescription as $pres)
                            <tr>
                                <td>{{ $pres->id }}</td>
                                <td>{{ $pres->drug_name }}</td>
                                <td>{{ $pres->period }}</td>
                                <td>{{ $pres->times }}</td>
                                <td>{{ $pres->notes ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection