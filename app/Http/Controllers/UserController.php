<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\City;
use App\Models\User;
use App\Models\State;
use App\Models\Visit;
use App\Models\Doctor;
use App\Models\Country;
use App\Models\Service;
use Laracasts\Flash\Flash;
use App\Models\Appointment;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\DoctorSession;
use App\Models\ServiceDoctor;
use App\Models\Specialization;
use App\DataTable\UserDataTable;
use Yajra\DataTables\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;

use Illuminate\Contracts\View\Factory;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use Illuminate\Contracts\Foundation\Application;
use App\Http\Requests\CreateQualificationRequest;
use App\Http\Requests\UpdateChangePasswordRequest;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class UserController extends AppBaseController
{
    /**
     * @var UserRepository
     */
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

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return Application|Factory|View
     *
     * @throws Exception
     */
    public function index(Request $request)
    {

        if (auth::check()) {
            $years = [];
            $currentYear = Carbon::now()->format('Y');
            for ($year = 1960; $year <= $currentYear; $year++) {
                $years[$year] = $year;
            }

            $status = User::STATUS;

            return view('doctors.index', compact('years', 'status'));
        }
        return view('doctors.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $specializations = Specialization::pluck('name', 'id')->toArray();
        $country = $this->userRepo->getCountries();
        $bloodGroup = Doctor::BLOOD_GROUP_ARRAY;

        return view('doctors.create', compact('specializations', 'country', 'bloodGroup'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  CreateUserRequest  $request
     * @return Application|RedirectResponse|Redirector
     */
    public function store(CreateUserRequest $request)
    {
        $input = $request->all();
        $this->userRepo->store($input);

        Flash::success(__('messages.flash.doctor_create'));

        return redirect(route('doctors.index'));
    }

    public function storeDoctor(CreateUserRequest $request)
    {
        $input = $request->all();
        //   $this->userRepo->store($input);

        $addressInputArray = Arr::only(
            $input,
            ['address1', 'address2', 'country_id', 'city_id', 'state_id', 'postal_code']
        );
        $doctorArray = Arr::only($input, ['experience', 'twitter_url', 'linkedin_url', 'instagram_url']);
        $specialization = $input['specializations'];
        try {
            DB::beginTransaction();
            $input['email'] = setEmailLowerCase($input['email']);
            $input['status'] = (isset($input['status'])) ? 0 : 1;
            $input['password'] = Hash::make($input['password']);
            $input['type'] = User::DOCTOR;
            $doctor = User::create($input);
            $doctor->assignRole('doctor');
            $doctor->address()->create($addressInputArray);
            $createDoctor = $doctor->doctor()->create($doctorArray);
            $services = Service::all();

            $createDoctor->services()->attach($services);
            $createDoctor->specializations()->sync($specialization);
            if (isset($input['profile']) && !empty('profile')) {
                $doctor->addMedia($input['profile'])->toMediaCollection(User::PROFILE, config('app.media_disc'));
            }

            DB::commit();


            if ($request->expectsJson()) {
                $token = $doctor->createToken('abdallah')->accessToken;
                return response()->json(['token' => $token], 200);
            }


            Flash::success(__('messages.flash.doctor_create'));

            if (Auth::check()) {
                return redirect(route('doctors.index'));
            }
            return redirect(route('login'));
        } catch (\Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param  Doctor  $doctor
     * @return Application|Factory|View|RedirectResponse
     *
     * @throws Exception
     */
    public function show(Doctor $doctor)
    {
        if (getLogInUser()->hasRole('patient')) {
            $doctorAppointment = Appointment::whereDoctorId($doctor->id)->wherePatientId(getLogInUser()->patient->id);
            if (!$doctorAppointment->exists()) {
                return redirect()->back();
            }
        }

        $doctorDetailData = $this->userRepo->doctorDetail($doctor);

        return view('doctors.show', compact('doctor', 'doctorDetailData'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Doctor  $doctor
     * @return Application|Factory|View
     */
    public function edit(Doctor $doctor)
    {
        $user = $doctor->user()->first();
        $qualifications = $user->qualifications()->get();
        $data = $this->userRepo->getSpecializationsData($doctor);
        $bloodGroup = Doctor::BLOOD_GROUP_ARRAY;
        $countries = $this->userRepo->getCountries();
        $state = $cities = null;
        $years = [];
        $currentYear = Carbon::now()->format('Y');
        for ($year = 1960; $year <= $currentYear; $year++) {
            $years[$year] = $year;
        }
        if (isset($countryId)) {
            $state = getStates($data['countryId']->toArray());
        }
        if (isset($stateId)) {
            $cities = getCities($data['stateId']->toArray());
        }



        return view(
            'doctors.edit',
            compact('user', 'qualifications', 'data', 'doctor', 'countries', 'state', 'cities', 'years', 'bloodGroup')
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateUserRequest  $request
     * @param  Doctor  $doctor
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, Doctor $doctor)
    {
        $input = $request->all();
        $this->userRepo->update($input, $doctor);

        Flash::success(__('messages.flash.doctor_update'));

        return $this->sendSuccess(__('messages.flash.doctor_update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Doctor  $doctor
     * @return JsonResponse
     */
    public function destroy(Doctor $doctor)
    {
        $existAppointment = Appointment::whereDoctorId($doctor->id)->exists();
        $existVisit = Visit::whereDoctorId($doctor->id)->exists();

        if ($existAppointment || $existVisit) {
            return $this->sendError(__('messages.flash.doctor_use'));
        }

        try {
            DB::beginTransaction();
            $doctor->user->delete();
            $doctor->user->media()->delete();
            $doctor->user->address()->delete();
            $doctor->user->doctor()->delete();
            DB::commit();

            return $this->sendSuccess(__('messages.flash.doctor_delete'));
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @return Application|Factory|View
     */
    public function editProfile(Request $request)
    {
        $user = Auth::user();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'product retrieved successfully',
                'data' => $user
            ]);
        }


        return view('profile.index', compact('user'));
    }

    /**
     * @param  UpdateUserProfileRequest  $request
     * @return Application
     */
    public function updateProfile(UpdateUserProfileRequest $request)
    {

        if ($request->expectsJson()) {

            $user = auth()->user();

            // Update the user's data
            $user->update($request->only([
                'first_name',
                'email',
                'contact',
                'dob',
                'gender',
                'title',
                'description',

            ]));

            return response()->json([
                'success' => true,
                'message' => 'User information updated successfully',
                'data' => $user
            ]);
        }

        /* $x=1;

        if ($request->expectsJson()) 
        {
            $x=2;
        }*/
        $this->userRepo->updateProfile($request->all());

        Flash::success(__('messages.flash.user_profile_update'));

        return redirect(route('profile.setting'));
    }

    /**
     * @param  UpdateChangePasswordRequest  $request
     * @return JsonResponse
     */
    public function changePassword(UpdateChangePasswordRequest $request)
    {
        $input = $request->all();

        try {
            /** @var User $user */
            $user = Auth::user();
            if (!Hash::check($input['current_password'], $user->password)) {
                return $this->sendError(__('messages.flash.current_invalid'));
            }
            $input['password'] = Hash::make($input['new_password']);
            $user->update($input);

            return $this->sendSuccess(__('messages.flash.password_update'));
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getStates(Request $request)
    {
        $countryId = $request->data;
        $states = getStates($countryId);

        return $this->sendResponse($states, __('messages.flash.retrieve'));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getCity(Request $request)
    {
        $state = $request->state;
        $cities = getCities($state);

        return $this->sendResponse($cities, __('messages.flash.retrieve'));
    }

    /**
     * @param  Request  $request
     * @return mixed
     */
    public function sessionData(Request $request)
    {
        $doctorSession = DoctorSession::whereDoctorId($request->doctorId)->first();

        return $this->sendResponse($doctorSession, __('messages.flash.session_retrieve'));
    }

    /**
     * @param  CreateQualificationRequest  $request
     * @param  Doctor  $doctor
     * @return mixed
     */
    public function addQualification(CreateQualificationRequest $request, Doctor $doctor)
    {
        $this->userRepo->addQualification($request->all());

        return $this->sendSuccess(__('messages.flash.qualification_create'));
    }

    /**
     * @param  Doctor  $doctor
     * @param  Request  $request
     * @return Application|RedirectResponse|Redirector
     *
     * @throws Exception
     */
    public function doctorAppointment(Doctor $doctor, Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of((new UserDataTable())->getAppointment($request->only([
                'status', 'doctorId', 'filter_date',
            ])))->make(true);
        }

        return redirect(route('doctors.index'));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function changeDoctorStatus(Request $request)
    {
        $doctor = User::findOrFail($request->id);
        $doctor->update(['status' => !$doctor->status]);

        return $this->sendResponse($doctor, __('messages.flash.status_update'));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        $language = $request->get('language');

        $user = getLogInUser();
        $user->update(['language' => $language]);

        return $this->sendSuccess(__('messages.flash.language_update'));
    }

    /**
     * @param  int  $id
     *  @return RedirectResponse
     */
    public function impersonate($id)
    {
        $user = User::findOrFail($id);
        getLogInUser()->impersonate($user);
        if ($user->hasRole('doctor')) {
            return redirect()->route('doctors.dashboard');
        } elseif ($user->hasRole('patient')) {
            return redirect()->route('patients.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }

    /**
     * @return RedirectResponse
     */
    public function impersonateLeave()
    {
        getLogInUser()->leaveImpersonation();

        return redirect()->route('admin.dashboard');
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function emailVerified(Request $request)
    {
        $user = User::findOrFail($request->id);
        if ($request->value) {
            $user->update([
                'email_verified_at' => Carbon::now(),
            ]);
        } else {
            $user->update([
                'email_verified_at' => null,
            ]);
        }

        return $this->sendResponse($user, __('messages.flash.verified_email'));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function emailNotification(Request $request)
    {
        $input = $request->all();
        $user = getLogInUser();
        $user->update([
            'email_notification' => isset($input['email_notification']) ? $input['email_notification'] : 0,
        ]);

        return $this->sendResponse($user, __('messages.flash.email_notification'));
    }

    /**
     * @param $userId
     * @return JsonResponse
     */
    public function resendEmailVerification($userId)
    {
        $user = User::findOrFail($userId);
        if ($user->hasVerifiedEmail()) {
            return $this->sendError(__('messages.flash.user_already_verified'));
        }

        $user->sendEmailVerificationNotification();

        return $this->sendSuccess(__('messages.flash.notification_send'));
    }

    /**
     * @return JsonResponse
     */
    public function updateDarkMode(): JsonResponse
    {
        $user = Auth::user();
        $darkEnabled = $user->dark_mode == true;
        $user->update([
            'dark_mode' => !$darkEnabled,
        ]);

        return $this->sendSuccess(__('messages.flash.theme_change'));
    }







    public function doctorsWithActivatedService($serviceId)
    {
        $doctors = Doctor::with('user')
            ->whereHas('services', function ($query) use ($serviceId) {
                $query->where('service_id', $serviceId)->where('activated', true);
            })
            ->get();

        return response()->json(['doctors' => $doctors]);
    }







    public function neweditServices(Request $request, $doctorId)
    {
        if (auth()->user()->doctor->id == $doctorId) {

            $doctor = Doctor::findOrFail($doctorId);
            $services = $doctor->services;

            // Retrieve the doctor's services and additional data
            $servicesData = $doctor->services->map(function ($service) {
                return [
                    'name' => $service->name,
                    'activated' => $service->pivot->activated,
                    'price' => $service->pivot->price,
                ];
            });

            if (request()->expectsJson()) {
                // Return a JSON response with doctor's name and services data
                return response()->json([
                    'doctor_name' => $doctor->user->first_name, // Assuming 'name' is the doctor's name field
                    'services' => $servicesData,
                ]);
            } else {

                return view('doctors.edit-services', compact('doctor', 'services', 'servicesData'));
            }
        }
    }



    public function updateServices(Request $request, $doctorId)
    {
        $doctor = Doctor::findOrFail($doctorId);

        // Validate and update the service activation status and price
        foreach ($request->input('services') as $serviceId => $data) {
            // Check if 'activated' key exists, otherwise, default to false
            $activated = isset($data['activated']) ? $data['activated'] : false;

            // Check if 'price' key exists, otherwise, default to 0
            $price = isset($data['price']) ? $data['price'] : 0;

            // Use updateExistingPivot to update 'activated' and 'price' in the pivot table
            $doctor->services()->updateExistingPivot($serviceId, [
                'activated' => $activated,
                'price' => $price,
            ]);
        }
        $services = $doctor->services;


        if (request()->expectsJson()) {
            // If the request expects JSON, return a JSON response
            return response()->json(['message' => 'Services updated successfully']);
        } else {
            // If it's a web request, return a redirect response
            return view('doctors.edit-services', compact('doctor', 'services'))->with('success', 'Services updated successfully');
        }
    }
}
