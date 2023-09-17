<?php

namespace App\Http\Controllers;

use App\Models\newpres;
use App\Models\Appointment;

use Illuminate\Http\Request;

class NewPresController extends Controller
{

   

    public function create()
    {
        // Show the prescription creation form
        return view('newpres.create');
    }


    public function store(Request $request)
{
    $prescriptionData = $request->all();

    $rules = [
        'appointment_id' => 'required|integer',
        'drug_name' => 'required|string',
        'period' => 'required|string',
        'times' => 'required|integer',
        'notes' => 'nullable|string',
    ];

    $validator = validator($prescriptionData, $rules);

    if ($validator->fails()) {
        // Handle validation errors as needed
        return response()->json(['error' => 'Validation failed'], 400);
    }

    $validatedData = $validator->validated();

    // Now, $validatedData contains the validated prescription data

    NewPres::create($validatedData);

    return response()->json(['message' => 'Prescription created successfully']);
}

    

    public function show(Request $request, $appointmentId)
    {
        $prescription = newpres::where('appointment_id', $appointmentId)->get();


        if ($request->expectsJson()) {

            return response()->json([
                'success' => true,
                'message' => 'prescription retrieved successfully',
                'data' => $prescription
            ]);
        }


        return view('newpres.show', compact('prescription'));
    }

    public function edit(Request $request, $id)
    {
        // Show the prescription edit form
        $prescription = newpres::findOrFail($id);

        
        if ($request->expectsJson()) {

            return response()->json([
                'success' => true,
                'message' => 'drug would be editted retrieve successfully',
                'data' => $prescription
            ]);
        }
        return view('newpres.edit', compact('prescription'));
    }

    

    public function update(Request $request, $id)
    {
        // Validate and update the prescription
        $validatedData = $request->validate([
            'appointment_id' => 'required|integer',
            'drug_name' => 'required|string',
            'period' => 'required|string',
            'times' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

    

        $prescription = newpres::findOrFail($id);
        $prescription->update($validatedData);

        $appointment_id=$request->appointment_id;
        if ($request->expectsJson()) {

            return response()->json([
                'success' => true,
                'message' => 'drug updated successfully',
                'data' => $prescription
            ]);
        }

        return redirect()->route('newpres.show', ['appointmentId' => $appointment_id])->with('success', 'Prescription updated successfully');
    }


    public function destroy(Request $request, $id)
    {
        // Delete a prescription
        $prescription = newpres::findOrFail($id);
        $prescription->delete();
        $appointment_id = $prescription->appointment_id;
        if ($request->expectsJson()) {

            return response()->json([
                'success' => true,
                'message' => 'drug deleted successfully',
                'data' => $prescription
            ]);
        }

        return redirect()->route('newpres.show', ['appointmentId' => $appointment_id])->with('success', 'Prescription updated successfully');
    }



    public function getDrugsForAppointment(Request $request, $appointmentId)
{
    $appointment = Appointment::find($appointmentId);

    $prescriptions = $appointment->prescriptions;

    if ($request->expectsJson()) {

        return response()->json([
            'success' => true,
            'message' => 'prescription retrieved successfully',
            'data' => $prescriptions
        ]);
    }

    return view('newpres.index', compact('prescriptions'));
}
}