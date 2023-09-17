<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use App\Models\Specialization;
use App\Repositories\UserRepository;

class ViewDoctorController extends Controller
{

    public $userRepo;

    /**
     * UserController constructor.
     *
     * @param  UserRepository  $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepo = $userRepository;
    }

    public function index()
    {
        $doctors = Doctor::with('user', 'specializations')->get();

        $doctors->each(function ($doctor) {
            $specializationNames = $doctor->specializations->pluck('name')->implode(', ');

            $doctor->specialization_names = $specializationNames;
        });


     return view('doctors.doctorslist', compact('doctors'));
    }

    //------------index JSON =---------

    public function jsonindex()
    {
        $doctors = Doctor::with('user', 'specializations')->get();

        $doctors->each(function ($doctor) {
            $specializationNames = $doctor->specializations->pluck('name')->implode(', ');

            $doctor->specialization_names = $specializationNames;
        });

            return response()->json([
          'success'=>true,
          'message'=>'all doctors list retrieved successfully',
          'data'=>$doctors
              ]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Doctor $doctor)
    {
        /*
        if (getLogInUser()->hasRole('patient')) {
            $doctorAppointment = Appointment::whereDoctorId($doctor->id)->wherePatientId(getLogInUser()->patient->id);
            if (!$doctorAppointment->exists()) {
                return redirect()->back();
            }
        }*/
        $doctorDetailData = $this->userRepo->doctorDetailPatient($doctor);

        if ($request->expectsJson()) {

        return response()->json([
            'success'=>true,
            'message'=>'this Doctor details retrieved successfully',
            'data'=>$doctorDetailData
                ]);
           }

       return view('doctors.showdoctorforpatient', compact('doctor', 'doctorDetailData'));
    }

    public function specializationfilter()
    {
        
        $specializationName =request('specialization_name'); // Replace with the specialization name you're interested in

        $doctors = Doctor::with(['user', 'specializations'])
            ->whereHas('specializations', function ($query) use ($specializationName) {
                $query->where('name', $specializationName);
            })
            ->get();

        return view('doctors.doctorslist', compact('doctors'));
    }



    public function jsonspecializationfilter()
    {
        
        $specializationName =request('specialization_name'); // Replace with the specialization name you're interested in

        $doctors = Doctor::with(['user', 'specializations'])
            ->whereHas('specializations', function ($query) use ($specializationName) {
                $query->where('name', $specializationName);
            })
            ->get();


            return response()->json([
                'success'=>true,
                'message'=>'search doctors  retrieved successfully',
                'data'=>$doctors
                    ]);
     }


    
    public function searchfilter(Request $request)
    {
       
        $searchQuery = $request->input('search');

        $doctors = Doctor::with(['user'])
            ->whereHas('user', function ($query) use ($searchQuery) {
                $query->where('first_name', 'like', '%' . $searchQuery . '%');
            })
            ->orWhereHas('user', function ($query) use ($searchQuery) {
                $query->where('title', 'like', '%' . $searchQuery . '%');
            })
            ->orWhereHas('user', function ($query) use ($searchQuery) {
                $query->where('description', 'like', '%' . $searchQuery . '%');
            })
            ->get();

            if ($request->expectsJson()) {
                return response()->json([
                'success'=>true,
                'message'=>'search doctors  retrieved successfully',
                'data'=>$doctors
                    ]);
                }
    
        return view('doctors.doctorslist', compact('doctors'));
    }


}
