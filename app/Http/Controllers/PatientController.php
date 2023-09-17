<?php

namespace App\Http\Controllers;

use Flash;
use Exception;
use Carbon\Carbon;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use App\DataTables\PatientDataTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use App\Repositories\PatientRepository;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\CreatePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class PatientController extends AppBaseController
{
    /** @var PatientRepository */
    private $patientRepository;

    public function __construct(PatientRepository $patientRepo)
    {
        $this->patientRepository = $patientRepo;
    }

    /**
     * Display a listing of the Patient.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        return view('patients.index');
    }

    /**
     * Show the form for creating a new Patient.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $data = $this->patientRepository->getData();

        return view('patients.create', compact('data'));
    }
 
    /**
     * Store a newly created Patient in storage.
     *
     * @param  CreatePatientRequest  $request
     * @return Application|Redirector|RedirectResponse
     */
    public function store(CreatePatientRequest $request)
    {
        $input = $request->all();

      //  $patient = $this->patientRepository->store($input);

      $input['patient_unique_id'] =mb_strtoupper(Patient::generatePatientUniqueId());
        try {
            DB::beginTransaction();
            $addressInputArray = Arr::only($input,
                ['address1', 'address2', 'city_id', 'state_id', 'country_id', 'postal_code']);
            $input['patient_unique_id'] = Str::upper($input['patient_unique_id']);
            $input['email'] = setEmailLowerCase($input['email']);
            $patientArray = Arr::only($input, ['patient_unique_id']);
            $input['type'] = User::PATIENT;

            $input['password'] = Hash::make($input['password']);
            $user = User::create($input);

            $patient = $user->patient()->create($patientArray);
            $address = $patient->address()->create($addressInputArray);
            $user->assignRole('patient');
            if (isset($input['profile']) && ! empty($input['profile'])) {
                $patient->addMedia($input['profile'])->toMediaCollection(Patient::PROFILE, config('app.media_disc'));
            }

            DB::commit();


            if ($request->expectsJson()) {
                $token = $user->createToken('abdallah')->accessToken;
                return response()->json(['token' => $token], 200);       
            }
    
            Flash::success(__('messages.flash.patient_create'));
    
            if(Auth::check())
            { 
            return redirect(route('patients.index'));
            }
            return redirect(route('login'));        } 
            catch (\Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

      
    }

    /**
     * Display the specified Patient.
     *
     * @param  Patient  $patient
     * @return Application|Factory|View|RedirectResponse
     */
    public function show(Patient $patient)
    {
        if (getLogInUser()->hasRole('doctor')) {
            $doctor = Appointment::wherePatientId($patient->id)->whereDoctorId(getLogInUser()->doctor->id);
            if (! $doctor->exists()) {
                return redirect()->back();
            }
        }

        if (empty($patient)) {
            Flash::error(__('messages.flash.patient_not_found'));

            return redirect(route('patients.index'));
        }

        $patient = $this->patientRepository->getPatientData($patient);
        $appointmentStatus = Appointment::ALL_STATUS;
        $todayDate = Carbon::now()->format('Y-m-d');
        $data['todayAppointmentCount'] = Appointment::wherePatientId($patient['id'])->where('date', '=',
            $todayDate)->count();
        $data['upcomingAppointmentCount'] = Appointment::wherePatientId($patient['id'])->where('date', '>',
            $todayDate)->count();
        $data['completedAppointmentCount'] = Appointment::wherePatientId($patient['id'])->where('date', '<',
            $todayDate)->count();

        return view('patients.show', compact('patient', 'appointmentStatus', 'data'));
    }

    /**
     * Show the form for editing the specified Patient.
     *
     * @param  Patient  $patient
     * @return Application|Factory|View
     */
    public function edit(Patient $patient)
    {
        if (empty($patient)) {
            Flash::error(__('messages.flash.patient_not_found'));

            return redirect(route('patients.index'));
        }
        $data = $this->patientRepository->getData();
        unset($data['patientUniqueId']);

        return view('patients.edit', compact('data', 'patient'));
    }

    /**
     * Update the specified Patient in storage.
     *
     * @param  Patient  $patient
     * @param  UpdatePatientRequest  $request
     * @return Application|Redirector|RedirectResponse
     */
    public function update(Patient $patient, UpdatePatientRequest $request)
    {
        $input = request()->except(['_method', '_token', 'patient_unique_id']);

        if (empty($patient)) {
            Flash::error(__('messages.flash.patient_not_found'));

            return redirect(route('patients.index'));
        }

        $patient = $this->patientRepository->update($input, $patient);

        Flash::success(__('messages.flash.patient_update'));

        return redirect(route('patients.index'));
    }

    /**
     * Remove the specified Patient from storage.
     *
     * @param  Patient  $patient
     * @return JsonResponse
     */
    public function destroy(Patient $patient)
    {
        $existAppointment = Appointment::wherePatientId($patient->id)
            ->whereNotIn('status', [Appointment::CANCELLED, Appointment::CHECK_OUT])
            ->exists();

        $existVisit = Visit::wherePatientId($patient->id)->exists();

        $transactions = Transaction::whereUserId($patient->user_id)->exists();

        if ($existAppointment || $existVisit || $transactions) {
            return $this->sendError(__('messages.flash.patient_used'));
        }

        try {
            DB::beginTransaction();

            $patient->delete();
            $patient->media()->delete();
            $patient->user()->delete();
            $patient->address()->delete();

            DB::commit();

            return $this->sendSuccess(__('messages.flash.patient_delete'));
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param  Patient  $patient
     * @param  Request  $request
     * @return Application|RedirectResponse|Redirector
     *
     * @throws Exception
     */
    public function patientAppointment(Patient $patient, Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of((new PatientDataTable())->getAppointment($request->only([
                'status', 'patientId', 'filter_date',
            ])))->make(true);
        }

        return redirect(route('patients.index'));
    }
}
