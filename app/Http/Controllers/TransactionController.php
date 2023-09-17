<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Transaction;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use App\Repositories\TransactionRepository;
use Illuminate\Contracts\Foundation\Application;

class TransactionController extends AppBaseController
{
    /** @var TransactionRepository */
    private $transactionRepository;

    public function __construct(TransactionRepository $transactionRepository)
    {
        $this->TransactionRepository = $transactionRepository;
    }

    /**
     * @return Application|Factory|View
     */
    public function index()
    {
        if (getLogInUser()->hasRole('patient')) {
            return view('transactions.patient_transaction');
        }

        if (getLogInUser()->hasRole('doctor')) {
            return view('transactions.doctors_transaction');
        }

        return view('transactions.index');
    }

    /**
     * @param $id
     *
     * @return Application|Factory|View|RedirectResponse
     */
    public function show($id)
    {
        if (getLogInUser()->hasRole('patient')) {
            $transaction = Transaction::findOrFail($id);
            if ($transaction->user_id !== getLogInUserId()) {
                return redirect()->back();
            }
        }

        if (getLogInUser()->hasRole('doctor')) {
            $transaction = Transaction::with('doctorappointment')->findOrFail($id);
            if (!$transaction->doctorappointment) {
                return redirect()->back();
            }
        }

        $transaction = $this->TransactionRepository->show($id);

        if (request()->expectsJson()) {
            return response()->json($transaction);
        }

        // Render the view for non-JSON requests
        return view('transactions.show', compact('transaction'));
    }

    // show all bills
    public function showAllBills()
    {
        $user = getLogInUser();

        if ($user->hasRole('patient')) {
            // If the user is a patient, retrieve all transactions related to them
            $transactions = Transaction::where('user_id', $user->id)
            ->with('appointment.doctor.user', 'appointment.patient.user')->get();
        } elseif ($user->hasRole('doctor')) {
            // If the user is a doctor, retrieve all transactions related to their appointments
           $transactions = Transaction::whereHas('doctorappointment', function ($query) use ($user) {
                $query->where('doctor_id', $user->id);
            })->get();
        } else {
            // Handle other roles as needed
            $transactions = [];
        }

        if (request()->expectsJson()) {
            return response()->json(['data' => $transactions]);
        }

        // Render the view for non-JSON requests
        return view('transactions.index', compact('transactions'));
    }

    public function showAllBillsDoctor()
    {
        
        // Get the currently logged-in doctor
        $loggedInDoctor = Auth::user()->doctor;
        
        if ($loggedInDoctor) {
            // Use the user ID of the logged-in doctor to query transactions
            $doctorId = $loggedInDoctor->id;
        
            // Retrieve all transactions related to the doctor
            $transactions = Transaction::whereHas('appointment', function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId);
            })->with('appointment.doctor.user', 'appointment.patient.user')->get();
        
            // Return the transactions as JSON
            return response()->json(['transactions' => $transactions]);
        } else {
            // Handle the case where the logged-in user is not a doctor
            return response()->json(['error' => 'User is not a doctor'], 403);
        }
        
    }



    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function changeTransactionStatus(Request $request)
    {
        $input = $request->all();

        $transaction = Transaction::findOrFail($input['id']);
        $appointment = Appointment::where('appointment_unique_id', $transaction->appointment_id)->first();

        if (getLogInUser()->hasrole('doctor')) {
            $doctor = Appointment::where('appointment_unique_id', $transaction->appointment_id)->whereDoctorId(getLogInUser()->doctor->id);
            if (!$doctor->exists()) {
                return $this->sendError('Seems, you are not allowed to access this record.');
            }
        }

        $appointment->update([
            'payment_method' => Appointment::MANUALLY,
            'payment_type' => Appointment::PAID,
        ]);

        $transaction->update([
            'status' => !$transaction->status,
            'accepted_by' => $input['acceptPaymentUserId'],
        ]);

        $appointmentNotification = Transaction::with('acceptedPaymentUser')->whereAppointmentId($appointment['appointment_unique_id'])->first();

        $fullTime = $appointment->from_time . '' . $appointment->from_time_type . ' - ' . $appointment->to_time . '' . $appointment->to_time_type . ' ' . ' ' . Carbon::parse($appointment->date)->format('jS M, Y');
        $patient = Patient::whereId($appointment->patient_id)->with('user')->first();
        Notification::create([
            'title' => $appointmentNotification->acceptedPaymentUser->full_name . ' changed the payment status ' . Appointment::PAYMENT_TYPE[Appointment::PENDING] . ' to ' . Appointment::PAYMENT_TYPE[$appointment->payment_type] . ' for appointment ' . $fullTime,
            'type' => Notification::PAYMENT_DONE,
            'user_id' => $patient->user_id,
        ]);

        return response()->json(['success' => true, 'message' => __('messages.flash.status_update')]);
    }
}
