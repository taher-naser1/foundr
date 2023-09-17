<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\State;
use App\Models\Doctor;
use App\Models\Country;
use App\Models\Patient;
use Laracasts\Flash\Flash;
use Illuminate\Http\Request;
use App\Models\Specialization;
use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use App\Repositories\PatientRepository;
use App\Http\Controllers\UserController;

class RegisteredUserController extends Controller
{
    
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */


     private $patientRepository;
     public $userRepo;
    public $states;
    public function __construct(PatientRepository $patientRepo,UserRepository $userRepository)
    {
        $this->patientRepository = $patientRepo;
        $this->userRepo = $userRepository;

    }


   
    public function create()
    {


       // $data = $this->patientRepository->getData();
        
        return view('auth.register');
    }
    public function createDoctor()
    {
        $specializations = Specialization::pluck('name', 'id')->toArray();
        $country = $this->userRepo->getCountries(); 
       /* $state = $this->userRepo->getStates(); 
        $city = $this->userRepo->getCities(); */
        $bloodGroup = Doctor::BLOOD_GROUP_ARRAY;

        return view('auth.registerDoctor', compact('specializations', 'country', 'bloodGroup'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  Request  $request
     * @return string
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix|unique:users,email',
            'password' => ['required', 'confirmed', 'min:6'],
            'countryid'=>'max:255',
            'stateid'=>'max:255',
            'toc' => 'required',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'countryid' => $request->countryid,
            'stateid' => $request->stateid,
            'password' => Hash::make($request->password),
            'type' => User::PATIENT,
        ]);

        $user->patient()->create([
            'patient_unique_id' => mb_strtoupper(Patient::generatePatientUniqueId()),
        ]);

        $user->assignRole('patient');

       // $user->sendEmailVerificationNotification();

        Flash::success(__('messages.flash.your_reg_success'));

        return redirect('login');
    }
}
