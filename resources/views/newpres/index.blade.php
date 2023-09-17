@extends('layouts.app') <!-- Use your layout file here if you have one -->

@section('content')
<div class="container">

    @if ($prescriptions->isEmpty())
        <p>No prescriptions found for this appointment.</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Drug Name</th>
                    <th>Period</th>
                    <th>Number of Times</th>
                    <th>Notes</th>
                    <!-- Add any other columns you want to display -->
                </tr>
            </thead>
            <tbody>
                @foreach ($prescriptions as $prescription)
                    <tr>
                        <td>{{ $prescription->id }}</td>
                        <td>{{ $prescription->drug_name }}</td>
                        <td>{{ $prescription->period }}</td>
                        <td>{{ $prescription->times }}</td>
                        <td>{{ $prescription->notes }}</td>
                        <!-- Add any other columns you want to display -->
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <a href="{{ route('newpres.index') }}" class="btn btn-primary">Back to All Prescriptions</a>
</div>
@endsection