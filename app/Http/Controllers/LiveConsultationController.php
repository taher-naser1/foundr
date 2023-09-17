<?php

namespace App\Http\Controllers;

use App;
use Exception;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use Laracasts\Flash\Flash;
use Illuminate\Http\Request;
use App\Models\LiveConsultation;
use Illuminate\Http\JsonResponse;
use App\Models\UserZoomCredential;
use Illuminate\Contracts\View\View;
use App\Repositories\ZoomRepository;
use Illuminate\Contracts\View\Factory;
use App\Http\Requests\LiveConsultationRequest;
use App\Repositories\LiveConsultationRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\App as FacadesApp;
use App\Http\Requests\CreateZoomCredentialRequest;

class LiveConsultationController extends AppBaseController
{
    /** @var LiveConsultationRepository */
    /** @var ZoomRepository */
    private $liveConsultationRepository;
    private $zoomRepository;

    /**
     * LiveConsultationController constructor.
     *
     * @param  LiveConsultationRepository  $liveConsultationRepository
     */
    public function __construct(
        LiveConsultationRepository $liveConsultationRepository,
        ZoomRepository $zoomRepository
    ) {
        $this->liveConsultationRepository = $liveConsultationRepository;
        $this->zoomRepository = $zoomRepository;
    }

    /**
     * @return Application|Factory|View
     *
     * @throws Exception
     */
    public function index()
    {
        $doctors = Doctor::with('user')->get()->where('user.status', '=', User::ACTIVE)->pluck(
            'user.full_name',
            'id'
        )->sort();
        $patients = Patient::with('user')->get()->where('user.status', '=', User::ACTIVE)->pluck(
            'user.full_name',
            'id'
        )->sort();
        $type = LiveConsultation::STATUS_TYPE;
        $status = LiveConsultation::status;

        return view('live_consultations.index', compact('doctors', 'patients', 'type', 'status'));
    }

    /**
     * @param  LiveConsultationRequest  $request
     * @return JsonResponse
     */
    public function store(LiveConsultationRequest $request)
    {
        try {
            $this->liveConsultationRepository->store($request->all());

            $this->liveConsultationRepository->createNotification($request->all());

            return $this->sendSuccess(__('messages.flash.live_consultation_save'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @param LiveConsultation $liveConsultation
     *
     * @return Factory|View|JsonResponse|Application
     */
    public function show(LiveConsultation $liveConsultation): Factory|View|JsonResponse|Application
    {
        if (getLogInUser()->hasrole('patient')) {
            if ($liveConsultation->patient_id !== getLogInUser()->patient->id) {
                return $this->sendError('Seems, you are not allowed to access this record.');
            }
        }
        if (getLogInUser()->hasrole('doctor')) {
            if ($liveConsultation->doctor_id !== getLogInUser()->doctor->id) {
                return $this->sendError('Seems, you are not allowed to access this record.');
            }
        }
        $data['liveConsultation'] = LiveConsultation::with([
            'user', 'patient.user', 'doctor.user',
        ])->find($liveConsultation->id);

        return $this->sendResponse($data, __('messages.flash.live_consultation_retrieved'));
    }

    /**
     * @param  LiveConsultation  $liveConsultation
     * @return JsonResponse
     */
    public function edit(LiveConsultation $liveConsultation)
    {
        if (getLogInUser()->hasrole('doctor')) {
            if ($liveConsultation->doctor_id !== getLogInUser()->doctor->id) {
                return $this->sendError('Seems, you are not allowed to access this record.');
            }
        }
        return $this->sendResponse($liveConsultation, __('messages.flash.live_consultation_retrieved'));
    }

    /**
     * @param  LiveConsultationRequest  $request
     * @param  LiveConsultation  $liveConsultation
     * @return JsonResponse
     */
    public function update(LiveConsultationRequest $request, LiveConsultation $liveConsultation)
    {
        try {
            $this->liveConsultationRepository->edit($request->all(), $liveConsultation);

            return $this->sendSuccess(__('messages.flash.live_consultation_update'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @param  LiveConsultation  $liveConsultation
     * @return JsonResponse
     */
    public function destroy(LiveConsultation $liveConsultation)
    {
        if (getLogInUser()->hasrole('doctor')) {
            if ($liveConsultation->doctor_id !== getLogInUser()->doctor->id) {
                return $this->sendError('Seems, you are not allowed to access this record.');
            }
        }
        try {
            $this->zoomRepository->destroyZoomMeeting($liveConsultation->meeting_id);
            $liveConsultation->delete();

            return $this->sendSuccess(__('messages.flash.live_consultation_delete'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getChangeStatus(Request $request)
    {
        $liveConsultation = LiveConsultation::findOrFail($request->get('id'));
        $status = null;

        if ($request->get('statusId') == LiveConsultation::STATUS_AWAITED) {
            $status = LiveConsultation::STATUS_AWAITED;
        } elseif ($request->get('statusId') == LiveConsultation::STATUS_CANCELLED) {
            $status = LiveConsultation::STATUS_CANCELLED;
        } else {
            $status = LiveConsultation::STATUS_FINISHED;
        }

        $liveConsultation->update([
            'status' => $status,
        ]);

        return $this->sendsuccess(__('messages.flash.status_change'));
    }

    public function getLiveStatus(LiveConsultation $liveConsultation)
    {
        if (getLogInUser()->hasrole('patient')) {
            if ($liveConsultation->patient_id !== getLogInUser()->patient->id) {
                return $this->sendError('Seems, you are not allowed to access this record.');
            }
        }
        if (getLogInUser()->hasrole('doctor')) {
            if ($liveConsultation->doctor_id !== getLogInUser()->doctor->id) {
                return $this->sendError('Seems, you are not allowed to access this record.');
            }
        }
        $data['liveConsultation'] = LiveConsultation::with('user')->find($liveConsultation->id);
        /** @var ZoomRepository $zoomRepo */
        $zoomRepo = App::make(ZoomRepository::class, ['createdBy' => $liveConsultation->created_by]);

        $data['zoomLiveData'] = $zoomRepo->zoomGet(
            $liveConsultation->meeting_id
        );

        return $this->sendResponse($data, 'Live Status retrieved successfully.');
    }

    public function zoomCallback(Request $request)
    {
        /** $zoomRepo Zoom */
        $zoomRepo = FacadesApp::make(ZoomRepository::class);
        $connected = $zoomRepo->connectWithZoom($request->get('code'));
        if($connected){

             Flash::success('Connected with zoom successfully.');

            return redirect(route('doctors.live-consultations.index'));
        }

        return redirect(route('doctors.live-consultations.index'));
    }


    /**
     * @param  int  $id
     * @return JsonResponse
     */
    public function zoomCredential($id)
    {
        try {
            $data = UserZoomCredential::where('user_id', $id)->first();

            return $this->sendResponse($data, __('messages.flash.user_zoom_credential_retrieved'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @param  CreateZoomCredentialRequest  $request
     * @return JsonResponse
     */
    public function zoomCredentialCreate(CreateZoomCredentialRequest $request)
    {
        try {

            $this->liveConsultationRepository->createUserZoom($request->all());

            return $this->sendSuccess(__('messages.flash.user_zoom_credential_saved'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * [Description for connectWithZoom]
     * @param Request $request
     * @return [type]
     *
     */
    public function connectWithZoom(Request $request)
    {

        $userZoomCredential = UserZoomCredential::where('user_id', getLogInUserId())->first();

        if($userZoomCredential == null){

            return redirect()->back()->withErrors('Please add zoom credentials');
        }

        $clientID = $userZoomCredential->zoom_api_key;
        $callbackURL = config('app.zoom_callback');
        $url = "https://zoom.us/oauth/authorize?client_id=$clientID&response_type=code&redirect_uri=$callbackURL";

        return redirect($url);
    }
}
