<?php

namespace App\Http\Controllers;

use Flash;
use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Models\UserGoogleAppointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Storage;
use Stripe\Exception\ApiErrorException;
use Yajra\DataTables\Facades\DataTables;
use App\Repositories\AppointmentRepository;
use App\DataTables\DoctorAppointmentDataTable;
use App\Repositories\GoogleCalendarRepository;
use App\Http\Requests\CreateAppointmentRequest;
use Illuminate\Contracts\Foundation\Application;
use App\Events\DeleteAppointmentFromGoogleCalendar;
use App\Http\Requests\CreateFrontAppointmentRequest;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use League\CommonMark\Extension\DescriptionList\Node\Description;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AppointmentController extends AppBaseController
{
    /** @var AppointmentRepository */
    private $appointmentRepository;

    public function __construct(AppointmentRepository $appointmentRepo)
    {
        $this->appointmentRepository = $appointmentRepo;
    }

    /**
     * @return Application|Factory|View
     */
    public function index()
    {
        $allPaymentStatus = getAllPaymentStatus();
        $paymentStatus = Arr::except($allPaymentStatus, [Appointment::MANUALLY]);
        $paymentGateway = getPaymentGateway();

        if (request()->expectsJson()) {
            return response()->json(['allPaymentStatus' => $allPaymentStatus, 'paymentGateway' => $paymentGateway, 'paymentStatus' => $paymentStatus]);
        }

        return view('appointments.index', compact('allPaymentStatus', 'paymentGateway', 'paymentStatus'));
    }



    // get all doctor appointment 
    public function getAllAppointmentsForDoctor($doctorId)
    {
        $doctor = Doctor::findOrFail($doctorId);
        $appointments = $doctor->appointments()->with(['doctor.user', 'patient.user'])
            ->get();
        if (request()->expectsJson()) {
            return response()->json($appointments);
        }
        return $appointments;
    }


    public function getAllComingAppointmentsForDoctor()
    {
        // Step 2: Get the authenticated user
        $user = Auth::user();

        if (!$user) {
            // Handle the case where the user is not authenticated
            return abort(401, 'Unauthorized');
        }

        // Step 3: Retrieve the authenticated user's ID
        $userId = $user->id;

        // Step 4: Use the authenticated user's ID to find the doctor's ID
        $doctor = Doctor::where('user_id', $userId)->first();

        if (!$doctor) {
            // Handle the case where a doctor with the user's ID is not found
            return abort(404, 'Doctor not found');
        }

        // Step 5: Retrieve all coming appointments for the doctor
        $currentDate = now();

        $appointments = $doctor->appointments()
        ->where('date', '>', $currentDate->toDateString()) // Exclude today and earlier
        ->get();

        if (request()->expectsJson()) {
            return response()->json($appointments);
        }

        return $appointments;
    }



    public function getAllPassedAppointmentsForDoctor()
    {
        // Step 2: Get the authenticated user
        $user = Auth::user();

        if (!$user) {
            // Handle the case where the user is not authenticated
            return abort(401, 'Unauthorized');
        }

        // Step 3: Retrieve the authenticated user's ID
        $userId = $user->id;

        // Step 4: Use the authenticated user's ID to find the doctor's ID
        $doctor = Doctor::where('user_id', $userId)->first();

        if (!$doctor) {
            // Handle the case where a doctor with the user's ID is not found
            return abort(404, 'Doctor not found');
        }

        // Step 5: Retrieve all coming appointments for the doctor
        $currentDate = now()->startOfDay();

        $appointments = $doctor->appointments()
        ->where('date', '<', $currentDate->toDateString()) // Exclude today and earlier
        ->get();

        if (request()->expectsJson()) {
            return response()->json($appointments);
        }

        return $appointments;
    }



    public function getTodayAppointmentsForDoctor()
    {
        // Step 2: Get the authenticated user
        $user = Auth::user();

        if (!$user) {
            // Handle the case where the user is not authenticated
            return abort(401, 'Unauthorized');
        }

        // Step 3: Retrieve the authenticated user's ID
        $userId = $user->id;

        // Step 4: Use the authenticated user's ID to find the doctor's ID
        $doctor = Doctor::where('user_id', $userId)->first();

        if (!$doctor) {
            // Handle the case where a doctor with the user's ID is not found
            return abort(404, 'Doctor not found');
        }

        // Step 5: Retrieve all coming appointments for the doctor
        $currentDate = now()->startOfDay();

        $appointments = $doctor->appointments()
        ->where('date', '=', $currentDate->toDateString()) 
        ->get();

        if (request()->expectsJson()) {
            return response()->json($appointments);
        }

        return $appointments;
    }

    public function getallAppointmentsForPatient($patientId)
    {
        $patient = Patient::findOrFail($patientId);
        $appointments = $patient->appointments()
            ->with(['doctor.user', 'patient.user'])
            ->get();

        return $appointments;
    }


    public function getAllComingAppointmentsForPatient()
    {
        // Step 2: Get the authenticated user
        $user = Auth::user();

        if (!$user) {
            // Handle the case where the user is not authenticated
            return abort(401, 'Unauthorized');
        }

        // Step 3: Retrieve the authenticated user's ID
        $userId = $user->id;

        // Step 4: Use the authenticated user's ID to find the doctor's ID
        $patient = Patient::where('user_id', $userId)->first();

        if (!$patient) {
            // Handle the case where a doctor with the user's ID is not found
            return abort(404, 'Doctor not found');
        }

        // Step 5: Retrieve all coming appointments for the doctor
        $currentDate = now();

        $appointments = $patient->appointments()
        ->where('date', '>', $currentDate->toDateString()) // Exclude today and earlier
        ->get();

        if (request()->expectsJson()) {
            return response()->json($appointments);
        }

        return $appointments;
    }



    public function getAllPassedAppointmentsForPatient()
    {
        // Step 2: Get the authenticated user
        $user = Auth::user();

        if (!$user) {
            // Handle the case where the user is not authenticated
            return abort(401, 'Unauthorized');
        }

        // Step 3: Retrieve the authenticated user's ID
        $userId = $user->id;

        // Step 4: Use the authenticated user's ID to find the doctor's ID
        $patient = Patient::where('user_id', $userId)->first();

        if (!$patient) {
            // Handle the case where a doctor with the user's ID is not found
            return abort(404, 'Doctor not found');
        }

        // Step 5: Retrieve all coming appointments for the doctor
        $currentDate = now()->startOfDay();

        $appointments = $patient->appointments()
        ->where('date', '<', $currentDate->toDateString()) // Exclude today and earlier
        ->get();

        if (request()->expectsJson()) {
            return response()->json($appointments);
        }

        return $appointments;
    }



    public function getTodayAppointmentsForPatient()
    {
        // Step 2: Get the authenticated user
        $user = Auth::user();

        if (!$user) {
            // Handle the case where the user is not authenticated
            return abort(401, 'Unauthorized');
        }

        // Step 3: Retrieve the authenticated user's ID
        $userId = $user->id;

        // Step 4: Use the authenticated user's ID to find the doctor's ID
        $patient = Patient::where('user_id', $userId)->first();

        if (!$patient) {
            // Handle the case where a doctor with the user's ID is not found
            return abort(404, 'Doctor not found');
        }

        // Step 5: Retrieve all coming appointments for the doctor
        $currentDate = now()->startOfDay();

        $appointments = $patient->appointments()
        ->where('date', '=', $currentDate->toDateString()) 
        ->get();

        if (request()->expectsJson()) {
            return response()->json($appointments);
        }

        return $appointments;
    }



    /**
     * Show the form for creating a new Appointment.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $data = $this->appointmentRepository->getData();

        return view('appointments.create', compact('data'));
    }

    /**
     * @param  CreateAppointmentRequest  $request
     * @return JsonResponse
     *
     * @throws ApiErrorException
     */
    public function store(CreateAppointmentRequest $request)
    {
        $input = $request->all();
        $appointment = $this->appointmentRepository->store($input);

        if ($input['payment_type'] == Appointment::STRIPE) {
            $result = $this->appointmentRepository->createSession($appointment);

            return $this->sendResponse([
                'appointmentId' => $appointment->id,
                'payment_type' => $input['payment_type'],
                $result,
            ], 'Stripe ' . __('messages.appointment.session_created_successfully'));
        }

        if ($input['payment_type'] == Appointment::PAYSTACK) {
            if ($request->isXmlHttpRequest()) {
                return $this->sendResponse([
                    'redirect_url' => route('paystack.init', ['appointmentData' => $appointment]),
                    'payment_type' => $input['payment_type'],
                    'appointmentId' => $appointment->id,
                ], 'Paystack ' . __('messages.appointment.session_created_successfully'));
            }

            return redirect(route('paystack.init'));
        }

        if ($input['payment_type'] == Appointment::PAYPAL) {
            if ($request->isXmlHttpRequest()) {
                return $this->sendResponse([
                    'redirect_url' => route('paypal.index', ['appointmentData' => $appointment]),
                    'payment_type' => $input['payment_type'],
                    'appointmentId' => $appointment->id,
                ], 'Paypal ' . __('messages.appointment.session_created_successfully'));
            }

            return redirect(route('paypal.init'));
        }

        if ($input['payment_type'] == Appointment::RAZORPAY) {
            return $this->sendResponse([
                'payment_type' => $input['payment_type'],
                'appointmentId' => $appointment->id,
            ], 'Razorpay ' . __('messages.appointment.session_created_successfully'));
        }

        if ($input['payment_type'] == Appointment::AUTHORIZE) {
            return $this->sendResponse([
                'payment_type' => $input['payment_type'],
                'appointmentId' => $appointment->id,
            ], 'Authorize ' . __('messages.appointment.session_created_successfully'));
        }

        if ($input['payment_type'] == Appointment::PAYTM) {
            return $this->sendResponse([
                'payment_type' => $input['payment_type'],
                'appointmentId' => $appointment->id,
            ], 'Paytm ' . __('messages.appointment.session_created_successfully'));
        }

        $url = route('appointments.index');


        if (getLogInUser()->hasRole('patient')) {
            $url = route('patients.patient-appointments-index');
        }
        $data = [
            'url' => $url,
            'payment_type' => $input['payment_type'],
            'appointmentId' => $appointment->id,
        ];

        if (request()->expectsJson()) {
            return response()->json([
                'data' => $data,
                'message' => __('messages.flash.appointment_create'),
            ], 200);
        }

        // For non-JSON requests, return a different response
        return $this->sendResponse($data, __('messages.flash.appointment_create'));
    }

    /**
     * Display the specified Appointment.
     *
     * @param  Appointment  $appointment
     * @return Application|RedirectResponse|Redirector
     */
    public function show(Appointment $appointment)
    {
        if (getLogInUser()->hasRole('doctor')) {
            $doctor = Appointment::whereId($appointment->id)->whereDoctorId(getLogInUser()->doctor->id);
            if (!$doctor->exists()) {
                return redirect()->back();
            }
        } elseif (getLogInUser()->hasRole('patient')) {
            $patient = Appointment::whereId($appointment->id)->wherePatientId(getLogInUser()->patient->id);
            if (!$patient->exists()) {
                return redirect()->back();
            }
        }

        $appointment = $this->appointmentRepository->showAppointment($appointment);

        if (empty($appointment)) {
            Flash::error(__('messages.flash.appointment_not_found'));

            if (getLogInUser()->hasRole('patient')) {
                return redirect(route('patients.patient-appointments-index'));
            } else {
                return redirect(route('admin.appointments.index'));
            }
        }

        if (getLogInUser()->hasRole('patient')) {
            if (request()->expectsJson()) {
                return response()->json(['appointment' => $appointment]);
            } else {
                return view('patient_appointments.show')->with('appointment', $appointment);
            }
        } else {
            if (request()->expectsJson()) {
                return response()->json(['appointment' => $appointment]);
            }
            return view('appointments.show')->with('appointment', $appointment);
        }
    }

    /**
     * Remove the specified Appointment from storage.
     *
     * @param  Appointment  $appointment
     * @return JsonResponse
     */
    public function destroy(Appointment $appointment): JsonResponse
    {
        if (getLogInUser()->hasrole('patient')) {
            if ($appointment->patient_id !== getLogInUser()->patient->id) {
                return $this->sendError('Seems, you are not allowed to access this record.');
            }
        }
        $appointmentUniqueId = $appointment->appointment_unique_id;

        $transaction = Transaction::whereAppointmentId($appointmentUniqueId)->first();

        if ($transaction) {
            $transaction->delete();
        }

        $appointment->delete();

        return $this->sendSuccess(__('messages.flash.appointment_delete'));
    }

    /**
     * @param  Request  $request
     * @return Application|Factory|View
     *
     * @throws Exception
     */
    public function doctorAppointment(Request $request)
    {
        $appointmentStatus = Appointment::ALL_STATUS;
        $paymentStatus = getAllPaymentStatus();

        if (request()->expectsJson()) {
            return response()->json(['appointmentStatus' => $appointmentStatus, 'paymentStatus' => $paymentStatus]);
        }

        return view('doctor_appointment.index', compact('appointmentStatus', 'paymentStatus'));
    }

    /**
     * @param  Request  $request
     * @return Application|Factory|View|JsonResponse
     */
    public function doctorAppointmentCalendar(Request $request)
    {
        if ($request->ajax()) {
            $input = $request->all();
            $data = $this->appointmentRepository->getAppointmentsData();

            return $this->sendResponse($data, __('messages.flash.doctor_appointment'));
        }

        return view('doctor_appointment.calendar');
    }

    /**
     * @param  Request  $request
     * @return Application|Factory|View
     */
    public function patientAppointmentCalendar(Request $request)
    {
        if ($request->ajax()) {
            $input = $request->all();
            $data = $this->appointmentRepository->getPatientAppointmentsCalendar();

            return $this->sendResponse($data, __('messages.flash.patient_appointment'));
        }

        return view('appointments.patient-calendar');
    }

    /**
     * @param  Request  $request
     * @return Application|Factory|View|JsonResponse
     */
    public function appointmentCalendar(Request $request)
    {
        if ($request->ajax()) {
            $input = $request->all();
            $data = $this->appointmentRepository->getCalendar();

            return $this->sendResponse($data, __('messages.flash.appointment_retrieve'));
        }

        return view('appointments.calendar');
    }

    /**
     * @param  Appointment  $appointment
     * @return Application|Factory|View
     */
    public function appointmentDetail(Appointment $appointment)
    {
        $appointment = $this->appointmentRepository->showDoctorAppointment($appointment);

        if (request()->expectsJson()) {
            return response()->json(['appointment' => $appointment]);
        }

        return view('doctor_appointment.show', compact('appointment'));
    }

    /**
     * @param  Request  $request
     * @return mixed
     */
    public function changeStatus(Request $request)
    {
        $input = $request->all();

        if (getLogInUser()->hasRole('doctor')) {
            $doctor = Appointment::whereId($input['appointmentId'])->whereDoctorId(getLogInUser()->doctor->id);
            if (!$doctor->exists()) {
                return $this->sendError('Seems, you are not allowed to access this record.');
            }
        }

        $appointment = Appointment::findOrFail($input['appointmentId']);

        $appointment->update([
            'status' => $input['appointmentStatus'],
        ]);
        $fullTime = $appointment->from_time . '' . $appointment->from_time_type . ' - ' . $appointment->to_time . '' . $appointment->to_time_type . ' ' . ' ' . Carbon::parse($appointment->date)->format('jS M, Y');
        $patient = Patient::whereId($appointment->patient_id)->with('user')->first();
        $doctor = Doctor::whereId($appointment->doctor_id)->with('user')->first();
        if ($input['appointmentStatus'] == Appointment::CHECK_OUT) {
            Notification::create([
                'title' => Notification::APPOINTMENT_CHECKOUT_PATIENT_MSG . ' ' . getLogInUser()->full_name,
                'type' => Notification::CHECKOUT,
                'user_id' => $patient->user_id,
            ]);
            Notification::create([
                'title' => $patient->user->full_name . '\'s appointment check out by ' . getLogInUser()->full_name . ' at ' . $fullTime,
                'type' => Notification::CHECKOUT,
                'user_id' => $doctor->user_id,
            ]);
        } elseif ($input['appointmentStatus'] == Appointment::CANCELLED) {
            $events = UserGoogleAppointment::with(['user'])->where('appointment_id', $appointment->id)->get();

            /** @var GoogleCalendarRepository $repo */
            $repo = App::make(GoogleCalendarRepository::class);

            $repo->destroy($events);


            Notification::create([
                'title' => Notification::APPOINTMENT_CANCEL_PATIENT_MSG . ' ' . getLogInUser()->full_name,
                'type' => Notification::CANCELED,
                'user_id' => $patient->user_id,
            ]);
            Notification::create([
                'title' => $patient->user->full_name . '\'s appointment cancelled by' . getLogInUser()->full_name . ' at ' . $fullTime,
                'type' => Notification::CANCELED,
                'user_id' => $doctor->user_id,
            ]);
        }

        return $this->sendSuccess(__('messages.flash.status_update'));
    }

    /**
     * @param  Request  $request
     * @return mixed
     */
    public function cancelStatus(Request $request)
    {
        $appointment = Appointment::findOrFail($request['appointmentId']);
        if ($appointment->patient_id !== getLogInUser()->patient->id) {
            return $this->sendError('Seems, you are not allowed to access this record.');
        }
        $appointment->update([
            'status' => Appointment::CANCELLED,
        ]);

        $events = UserGoogleAppointment::with('user')
            ->where('appointment_id', $appointment->id)
            ->get()
            ->groupBy('user_id');

        foreach ($events as $userID => $event) {
            $user = $event[0]->user;
            DeleteAppointmentFromGoogleCalendar::dispatch($event, $user);
        }

        $fullTime = $appointment->from_time . '' . $appointment->from_time_type . ' - ' . $appointment->to_time . '' . $appointment->to_time_type . ' ' . ' ' . Carbon::parse($appointment->date)->format('jS M, Y');
        $patient = Patient::whereId($appointment->patient_id)->with('user')->first();

        $doctor = Doctor::whereId($appointment->doctor_id)->with('user')->first();
        Notification::create([
            'title' => $patient->user->full_name . ' ' . Notification::APPOINTMENT_CANCEL_DOCTOR_MSG . ' ' . $fullTime,
            'type' => Notification::CANCELED,
            'user_id' => $doctor->user_id,
        ]);

        return $this->sendSuccess(__('messages.flash.appointment_cancel'));
    }

    /**
     * @param  CreateFrontAppointmentRequest  $request
     * @return JsonResponse
     *
     * @throws ApiErrorException
     */
    public function frontAppointmentBook(CreateFrontAppointmentRequest $request)
    {
        $input = $request->all();
        $appointment = $this->appointmentRepository->frontSideStore($input);
        if ($input['payment_type'] == Appointment::STRIPE) {
            $result = $this->appointmentRepository->createSession($appointment);

            return $this->sendResponse([
                'payment_type' => $input['payment_type'],
                $result,
            ], 'Stripe ' . __('messages.appointment.session_created_successfully'));
        }

        if ($input['payment_type'] == Appointment::PAYPAL) {
            if ($request->isXmlHttpRequest()) {
                return $this->sendResponse([
                    'redirect_url' => route('paypal.index', ['appointmentData' => $appointment]),
                    'payment_type' => $input['payment_type'],
                    'appointmentId' => $appointment->id,
                ], 'Paypal ' . __('messages.appointment.session_created_successfully'));
            }
        }

        if ($input['payment_type'] == Appointment::PAYSTACK) {
            if ($request->isXmlHttpRequest()) {
                return $this->sendResponse([
                    'redirect_url' => route('paystack.init', ['appointmentData' => $appointment]),
                    'payment_type' => $input['payment_type'],
                ], 'Paystck ' . __('messages.appointment.session_created_successfully'));
            }

            return redirect(route('paystack.init'));
        }

        if ($input['payment_type'] == Appointment::RAZORPAY) {
            return $this->sendResponse([
                'payment_type' => $input['payment_type'],
                'appointmentId' => $appointment->id,
            ], 'Razorpay ' . __('messages.appointment.session_created_successfully'));
        }

        if ($input['payment_type'] == Appointment::PAYTM) {
            return $this->sendResponse([
                'payment_type' => $input['payment_type'],
                'appointmentId' => $appointment->id,
            ], 'Paytm ' . __('messages.appointment.session_created_successfully'));
        }

        if ($input['payment_type'] == Appointment::AUTHORIZE) {
            return $this->sendResponse([
                'payment_type' => $input['payment_type'],
                'appointmentId' => $appointment->id,
            ], 'Authorize session created successfully.');
        }

        $data['payment_type'] = $input['payment_type'];
        $data['appointmentId'] = $appointment->id;

        return $this->sendResponse($data, __('messages.flash.appointment_booked'));
    }

    /**
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function frontHomeAppointmentBook(Request $request)
    {
        $data = $request->all();

        return redirect()->route('medicalAppointment')->with(['data' => $data]);
    }

    /**
     * @param  Request  $request
     * @return HigherOrderBuilderProxy|mixed|string
     *
     * @throws Exception
     */
    public function getPatientName(Request $request)
    {
        $checkRecord = User::whereEmail($request->email)->whereType(User::PATIENT)->first();

        if ($checkRecord != '') {
            return $this->sendResponse($checkRecord->full_name, 'Patient name retrieved successfully.');
        }

        return false;
    }

    /**
     * @param  Request  $request
     * @return Application|RedirectResponse|Redirector
     *
     * @throws ApiErrorException
     */
    public function paymentSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');
        if (empty($sessionId)) {
            throw new UnprocessableEntityHttpException('session id is required');
        }
        setStripeApiKey();

        $sessionData = \Stripe\Checkout\Session::retrieve($sessionId);
        $appointment = Appointment::whereAppointmentUniqueId($sessionData->client_reference_id)->first();
        $patientId = User::whereEmail($sessionData->customer_details->email)->pluck('id')->first();

        $transaction = [
            'user_id' => $patientId,
            'transaction_id' => $sessionData->id,
            'appointment_id' => $sessionData->client_reference_id,
            'amount' => intval($sessionData->amount_total / 100),
            'type' => Appointment::STRIPE,
            'meta' => $sessionData,
        ];

        Transaction::create($transaction);

        $appointment->update([
            'payment_method' => Appointment::STRIPE,
            'payment_type' => Appointment::PAID,
        ]);

        Flash::success(__('messages.flash.appointment_created_payment_complete'));

        $patient = Patient::whereUserId($patientId)->with('user')->first();
        Notification::create([
            'title' => Notification::APPOINTMENT_PAYMENT_DONE_PATIENT_MSG,
            'type' => Notification::PAYMENT_DONE,
            'user_id' => $patient->user_id,
        ]);

        if (parse_url(url()->previous(), PHP_URL_PATH) == '/medical-appointment') {
            return redirect(route('medicalAppointment'));
        }

        if (!getLogInUser()) {
            return redirect(route('medical'));
        }

        if (getLogInUser()->hasRole('patient')) {
            return redirect(route('patients.patient-appointments-index'));
        }

        return redirect(route('appointments.index'));
    }

    /**
     * @return Application|RedirectResponse|Redirector
     */
    public function handleFailedPayment()
    {
        setStripeApiKey();

        Flash::error(__('messages.flash.appointment_created_payment_not_complete'));

        if (!getLogInUser()) {
            return redirect(route('medicalAppointment'));
        }

        if (getLogInUser()->hasRole('patient')) {
            return redirect(route('patients.patient-appointments-index'));
        }

        return redirect(route('appointments.index'));
    }

    /**
     * @param  Request  $request
     * @return mixed
     *
     * @throws ApiErrorException
     */
    public function appointmentPayment(Request $request)
    {
        $appointmentId = $request['appointmentId'];
        $appointment = Appointment::whereId($appointmentId)->first();

        $result = $this->appointmentRepository->createSession($appointment);

        return $this->sendResponse($result, 'Session created successfully.');
    }

    /**
     * @param  Request  $request
     * @return mixed
     */
    public function changePaymentStatus(Request $request)
    {
        $input = $request->all();
        if (getLogInUser()->hasRole('doctor')) {
            $doctor = Appointment::whereId($input['appointmentId'])->whereDoctorId(getLogInUser()->doctor->id);
            if (!$doctor->exists()) {
                return $this->sendError('Seems, you are not allowed to access this record.');
            }
        }

        $appointment = Appointment::with('patient')->findOrFail($input['appointmentId']);
        $transactionExist = Transaction::whereAppointmentId($appointment['appointment_unique_id'])->first();

        $appointment->update([
            'payment_type' => $input['paymentStatus'],
            'payment_method' => $input['paymentMethod'],
        ]);

        if (empty($transactionExist)) {
            $transaction = [
                'user_id' => $appointment->patient->user_id,
                'transaction_id' => Str::random(10),
                'appointment_id' => $appointment->appointment_unique_id,
                'amount' => $appointment->payable_amount,
                'type' => Appointment::MANUALLY,
                'status' => Transaction::SUCCESS,
                'accepted_by' => $input['loginUserId'],
            ];

            Transaction::create($transaction);
        } else {
            $transactionExist->update([
                'status' => Transaction::SUCCESS,
                'accepted_by' => $input['loginUserId'],
            ]);
        }

        $appointmentNotification = Transaction::with('acceptedPaymentUser')->whereAppointmentId($appointment['appointment_unique_id'])->first();

        $fullTime = $appointment->from_time . '' . $appointment->from_time_type . ' - ' . $appointment->to_time . '' . $appointment->to_time_type . ' ' . ' ' . Carbon::parse($appointment->date)->format('jS M, Y');
        $patient = Patient::whereId($appointment->patient_id)->with('user')->first();
        Notification::create([
            'title' => $appointmentNotification->acceptedPaymentUser->full_name . ' changed the payment status ' . Appointment::PAYMENT_TYPE[Appointment::PENDING] . ' to ' . Appointment::PAYMENT_TYPE[$appointment->payment_type] . ' for appointment ' . $fullTime,
            'type' => Notification::PAYMENT_DONE,
            'user_id' => $patient->user_id,
        ]);

        return $this->sendSuccess(__('messages.flash.payment_status_updated'));
    }

    /**
     * @param    $patient_id
     * @param    $appointment_unique_id
     * @return  RedirectResponse
     */
    public function cancelAppointment($patient_id, $appointment_unique_id)
    {
        $uniqueId = Crypt::decryptString($appointment_unique_id);
        $appointment = Appointment::whereAppointmentUniqueId($uniqueId)->wherePatientId($patient_id)->first();

        $appointment->update(['status' => Appointment::CANCELLED]);

        return redirect(route('medical'));
    }

    /**
     * @param  Doctor  $doctor
     * @return RedirectResponse
     */
    public function doctorBookAppointment(Doctor $doctor)
    {
        $data = [];
        $data['doctor_id'] = $doctor['id'];

        return redirect()->route('medicalAppointment')->with(['data' => $data]);
    }

    /**
     * @param  Service  $service
     * @return RedirectResponse
     */
    public function serviceBookAppointment(Service $service)
    {
        $data = [];
        $data['service'] = Service::whereStatus(Service::ACTIVE)->get()->pluck('name', 'id');
        $data['service_id'] = $service['id'];

        return redirect()->route('medicalAppointment')->with(['data' => $data]);
    }

    /**
     * @param  Request  $request
     * @return bool|JsonResponse
     */
    public function createGoogleEventForDoctor(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            return $this->sendSuccess(__('messages.flash.operation_performed_success'));
        }

        return true;
    }

    /**
     * @param  Request  $request
     * @return bool|JsonResponse
     */
    public function createGoogleEventForPatient(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            return $this->sendSuccess(__('messages.flash.operation_performed_success'));
        }

        return true;
    }

    /**
     * @param  Request  $request
     */
    public function manuallyPayment(Request $request)
    {
        $input = $request->all();
        $appointment = Appointment::findOrFail($input['appointmentId'])->load('patient');
        $transaction = [
            'user_id' => $appointment->patient->user_id,
            'transaction_id' => Str::random(10),
            'appointment_id' => $appointment->appointment_unique_id,
            'amount' => $appointment->payable_amount,
            'type' => Appointment::MANUALLY,
            'status' => Transaction::PENDING,
        ];

        Transaction::create($transaction);

        if (parse_url(url()->previous(), PHP_URL_PATH) == '/medical-appointment') {
            return redirect(route('medicalAppointment'));
        }

        if (!getLogInUser()) {
            return redirect(route('medical'));
        }

        if (getLogInUser()->hasRole('patient')) {
            return redirect(route('patients.patient-appointments-index'));
        }

        if (getLogInUser()->hasRole('doctor')) {


            return redirect(route('doctors.appointments'));
        }

        return redirect(route('appointments.index'));
    }

    public function appointmentPdf($id)
    {
        $datas = Appointment::with(['patient.user', 'doctor.user', 'services'])->findOrFail($id);
        $pdf = Pdf::loadView('appointment_pdf.invoice', array('datas' => $datas));

        return $pdf->download('invoice.pdf');
    }
}
