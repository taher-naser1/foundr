<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Day;
use App\Models\Doctor;
use App\Models\DoctorDay;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DoctorDayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Retrieve the currently logged-in doctor
        $loggedInDoctor = auth()->user()->doctor;

        // Retrieve the DoctorDays associated with the logged-in doctor
        $doctorDays = $loggedInDoctor->doctorDays;
        $doctorDays->load('day');


        if ($request->expectsJson()) {
            return response()->json([
                'days' => $doctorDays,
            ]);
        }


        return view('doctor_day.index', compact('doctorDays'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $days = Day::all();



        if ($request->expectsJson()) {
            return response()->json([
                'days' => $days,
            ]);
        }



        // You can retrieve the list of doctors and days here if needed
        return view('doctor_day.create', compact('days'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $doctorId = auth()->user()->doctor->id;

        $doctorDay = DoctorDay::create([
            'doctor_id' => $doctorId,
            'day_id' => $request->day_id,
            'duration' => $request->duration,
            'start_time_am' => $request->start_time_am,
            'end_time_am' => $request->end_time_am,
            'start_time_pm' => $request->start_time_pm,
            'end_time_pm' => $request->end_time_pm,
        ]);



        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'day stored successfully',
                //  'data' => $user
            ]);
        }



        return redirect()->route('doctor-day.index')
            ->with('success', 'Doctor-Day relationship created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(DoctorDay $doctorDay)
    {
        // Retrieve the doctor-day relationship from the database
        $doctorDay = DoctorDay::findOrFail($doctorDay->id);

        // Pass the retrieved resource to the show view for user viewing (non-editable)
        return view('doctor_day.show', compact('doctorDay'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, DoctorDay $doctorDay)
    {
        $doctorDay = DoctorDay::findOrFail($doctorDay->id);
        $dayName = Day::find($doctorDay->day_id)->name;
        $days = Day::all();

        if ($request->expectsJson()) {
            return response()->json([
                'doctorDay' => $doctorDay,
                'dayName' => $dayName,
                'days' => $days,
            ]);
        }

        // Pass the retrieved resource to the edit view for user modification
        return view('doctor_day.edit', compact('doctorDay', 'dayName', 'days'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DoctorDay $doctorDay)
    {
        // Validate the request data here
        // ...

        // Update the doctor-day relationship
        $doctorId = auth()->user()->doctor->id;

        $doctorDay->update([
            'doctor_id' => $doctorId,
            'day_id' => $request->day_id,
            'duration' => $request->duration,
            'start_time_am' => $request->start_time_am,
            'end_time_am' => $request->end_time_am,
            'start_time_pm' => $request->start_time_pm,
            'end_time_pm' => $request->end_time_pm,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'day updated successfully',
                //  'data' => $user
            ]);
        }

        return redirect()->route('doctor-day.index')
            ->with('success', 'Doctor-Day relationship updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, DoctorDay $doctorDay)
    {
        $doctorDay->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'day deleted successfully',
                // 'data' => $user
            ]);
        }
        return redirect()->route('doctor-day.index')
            ->with('success', 'Doctor-Day relationship deleted successfully.');
    }



    /*

    public function generateTimeSlots(Request $request, $doctorId,$selectedDate)
    {
        // Assuming $selectedDate is the date selected by the patient in the format '14-9'

        // Step 1: Convert the selected date to 'Y-m-d' format with the current year
        $year = date('Y');
        $fullDate = $year . '-' . $selectedDate;
        // Step 2: Find the day of the week for the selected date
        $dayOfWeek = date('l', strtotime($fullDate));
        $day = Day::where('name', $dayOfWeek)->first();
        $day_id = $day->id;
        $daygo = DoctorDay::where('day_id', $day_id)->with('day')->first();
        $dayName = $daygo->day->name;
        $doctorDay = DB::table('doctor_days')
            ->where('doctor_id', $doctorId)
            ->where('day_id', $day_id)
            ->select('start_time_am', 'end_time_am', 'start_time_pm', 'end_time_pm', 'duration')
            ->first();

        if (!$doctorDay) {
            // Handle the case where no matching record was found
            return response()->json(['error' => 'DoctorDay record not found']);
        }

        $duration = $doctorDay->duration;
        $startAm = $doctorDay->start_time_am;
        $endAm = $doctorDay->end_time_am;
        $timeSlots = [];

        // Generate AM time slots
        $startAm = $doctorDay->start_time_am;
        $endAm = $doctorDay->end_time_am;
    
        $startTime = Carbon::createFromFormat('H:i:s', $startAm);
        $endTime = Carbon::createFromFormat('H:i:s', $endAm);
    
        while ($startTime < $endTime) {
            $slotStart = $startTime->format('h:i A'); // 12-hour time format with AM/PM
            $startTime->addMinutes($duration); // Change this interval as needed
            $slotEnd = $startTime->format('h:i A');
            $timeSlots[] = [ 'start' => $slotStart, 'end' => $slotEnd];
        }
    
        // Generate PM time slots
        $startPm = $doctorDay->start_time_pm;
        $endPm = $doctorDay->end_time_pm;
    
        $startTime = Carbon::createFromFormat('H:i:s', $startPm);
        $endTime = Carbon::createFromFormat('H:i:s', $endPm);
    
        while ($startTime < $endTime) {
            $slotStart = $startTime->format('h:i A'); // 12-hour time format with AM/PM
            $startTime->addMinutes($duration); // Change this interval as needed
            $slotEnd = $startTime->format('h:i A');
            $timeSlots[] = ['start' => $slotStart, 'end' => $slotEnd];
        }


    

   
        return response()->json(['day_name' => $dayName, 'time_slots' => $timeSlots]);
    }
    
    */
   



    // new generated time slots 
    public function generateTimeSlots(Request $request, $doctorId, $selectedDate)
{
      // Initialize current time
      $currentTime = now();

      // Initialize an array to store the available time slots
      $timeSlots = [];
  
      // Find the day of the week for the selected date
      $dayOfWeek = date('l', strtotime($selectedDate));
      $day = Day::where('name', $dayOfWeek)->first();
  
      if (!$day) {
          return response()->json(['error' => 'Invalid day']);
      }
  
      $day_id = $day->id;
      $dayName = $day->name;
  
      $doctorDay = DoctorDay::where('doctor_id', $doctorId)
          ->where('day_id', $day_id)
          ->first();
  
      if (!$doctorDay) {
          return response()->json(['error' => 'DoctorDay record not found']);
      }
  
      $duration = $doctorDay->duration;
  
      // Define AM and PM start and end times
      $startAm = $doctorDay->start_time_am;
      $endAm = $doctorDay->end_time_am;
      $startPm = $doctorDay->start_time_pm;
      $endPm = $doctorDay->end_time_pm;
  
      // Create Carbon instances for start and end times
      $startTimeAM = Carbon::createFromFormat('H:i:s', $startAm);
      $endTimeAM = Carbon::createFromFormat('H:i:s', $endAm);
      $startTimePM = Carbon::createFromFormat('H:i:s', $startPm);
      $endTimePM = Carbon::createFromFormat('H:i:s', $endPm);
  
      // Function to check and add time slots
      function addTimeSlots($start, $end, $duration, $timeSlots, $selectedDate, $doctorId, $slotStartType) {
          $currentTime = now();
          $startTime = $start;
          while ($startTime < $end) {
              $slotStart = $startTime->format('h:i A'); // 12-hour time format with AM/PM
              $startTime->addMinutes($duration);
              $slotEnd = $startTime->format('h:i A');
              $isInPast = $startTime->lt($currentTime);
  
              // Check if the time slot is reserved in the appointments table
              $isReserved = Appointment::where('doctor_id', $doctorId)
                  ->where('date', $selectedDate)
                  ->where(function ($query) use ($slotStart, $slotEnd) {
                      $query->where('from_time', $slotStart)
                          ->orWhere('to_time', $slotEnd);
                  })
                  ->where('from_time_type', $slotStartType)
                  ->exists();
  
              if (!$isReserved && !$isInPast) {
                  $timeSlots[] = ['start' => $slotStart, 'end' => $slotEnd];
              }
          }
          return $timeSlots;
      }
  
      // Generate AM time slots
      $timeSlots = addTimeSlots($startTimeAM, $endTimeAM, $duration, $timeSlots, $selectedDate, $doctorId, 'AM');
  
      // Generate PM time slots
      $timeSlots = addTimeSlots($startTimePM, $endTimePM, $duration, $timeSlots, $selectedDate, $doctorId, 'PM');
  
      return response()->json(['day_name' => $dayName, 'time_slots' => $timeSlots]);
  }

    public function convertday()
    {
        $selectedDate = '9-16';

        // Step 1: Convert the selected date to 'Y-m-d' format with the current year
        $year = date('Y');
        $fullDate = $year . '-' . $selectedDate;

        // Step 2: Find the day of the week for the selected date
        $dayOfWeek = date('l', strtotime($fullDate));
        return response()->json(['day_name' => $dayOfWeek]);
    }


    public function countAppointmentsDoctor()
    {
        $user = Auth::user();
        $doctorId = $user->doctor->id;
        return Appointment::where('doctor_id', $doctorId)->count();
    }


public function sumPayableAmountsDoctor()
{
    $user = Auth::user();
        $doctorId = $user->doctor->id;
    return Appointment::where('doctor_id', $doctorId)->sum('payable_amount');
}


//-------------monthly------------------

public function countAppointmentsDoctorMonthly()
{
    $user = Auth::user();
    $doctorId = $user->doctor->id;

    // Get the current month and year
    $currentMonth = now()->month;
    $currentYear = now()->year;

    return Appointment::where('doctor_id', $doctorId)
        ->whereMonth('created_at', '=', $currentMonth)
        ->whereYear('created_at', '=', $currentYear)
        ->count();
}

public function sumPayableAmountsDoctorMonthly()
{
    $user = Auth::user();
    $doctorId = $user->doctor->id;

    // Get the current month and year
    $currentMonth = now()->month;
    $currentYear = now()->year;

    return Appointment::where('doctor_id', $doctorId)
        ->whereMonth('created_at', '=', $currentMonth)
        ->whereYear('created_at', '=', $currentYear)
        ->sum('payable_amount');
}

}
