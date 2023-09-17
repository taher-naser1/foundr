<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Medicine;
use Laracasts\Flash\Flash;
use App\Models\MedicineBill;
use App\Models\SaleMedicine;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;
use App\Repositories\DoctorRepository;
use App\Repositories\PatientRepository;
use App\Repositories\MedicineRepository;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreatePatientRequest;
use App\Repositories\MedicineBillRepository;
use App\Repositories\PrescriptionRepository;
use App\Http\Requests\CreateMedicineBillRequest;
use App\Http\Requests\UpdateMedicineBillRequest;
use App\Repositories\IpdPatientDepartmentRepository;

class MedicineBillController extends AppBaseController
{

/* @var  PrescriptionRepository
      @var DoctorRepository
     */
    private $prescriptionRepository;

    private $medicineRepository;

    private $patientRepository;
    private $medicineBillRepository;

    public function __construct(
        PrescriptionRepository $prescriptionRepo,
        MedicineRepository $medicineRepository,
        PatientRepository $patientRepo,
        MedicineBillRepository $medicineBillRepository,
    ) {
        $this->prescriptionRepository = $prescriptionRepo;
        $this->medicineRepository = $medicineRepository;
        $this->patientRepository = $patientRepo;
        $this->medicineBillRepository = $medicineBillRepository;
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        return view('medicine-bills.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $patients = $this->prescriptionRepository->getPatients();
        $doctors = $this->prescriptionRepository->getDoctors();
        $medicines = $this->prescriptionRepository->getMedicines();
        $data = $this->medicineRepository->getSyncList();
        $medicineList =  $this->medicineRepository->getMedicineList();
        $mealList = $this->medicineRepository->getMealList();
        $medicineCategories = $this->medicineBillRepository->getMedicinesCategoriesData();
        $medicineCategoriesList = $this->medicineBillRepository->getMedicineCategoriesList();

        return view('medicine-bills.create',
        compact('patients', 'doctors', 'medicines', 'medicineList', 'mealList','medicineCategoriesList','medicineCategories'))->with($data);
}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateMedicineBillRequest $request)
    {
        $input = $request->all();
        if(empty($input['medicine'])){

            flash::error(__('messages.medicine_bills.medicine_not_selected'));

            return Redirect::route('medicine-bills.create');

        }
        $arr = collect($input['medicine']);
        $duplicateIds = $arr->duplicates();

        $input['payment_status'] =  isset($input['payment_status']) ? 1 : 0 ;

        foreach ($input['medicine'] as $key => $value) {
            $medicine = Medicine :: find($input['medicine'][$key]);
            if(!empty($duplicateIds)){
                foreach($duplicateIds as $key => $value){
                    $medicine = Medicine :: find($duplicateIds[$key]);

                     Flash::error(__('messages.medicine_bills.duplicate_medicine'));

                     return Redirect::route('medicine-bills.create');
                }
            }
            $qty = $input['quantity'][$key];
            if($medicine->available_quantity <  $qty  )
            {
                $available = $medicine->available_quantity == null ? 0 :$medicine->available_quantity;
                 Flash::error(__('messages.medicine_bills.available_quantity').' '.$medicine->name.' '.__('messages.medicine_bills.is').' '.$available.'.');

                 return Redirect::route('medicine-bills.create');

                }
        }

        // dd($input);
        $medicineBill = MedicineBill::create([
            'bill_number'   => 'BIL'.generateUniqueBillNumber(),
            'patient_id' =>  $input['patient_id'],
            'net_amount' =>  $input['net_amount'],
            'discount' =>  $input['discount'],
            'payment_status' =>  $input['payment_status'],
            'payment_type' =>  $input['payment_type'],
            'note' =>  $input['note'],
            'total' =>  $input['total'],
            'tax_amount' =>  $input['tax'],
            'payment_note' =>  $input['payment_note'],
            'model_type'  =>  'App\Models\MedicineBill',
            'bill_date'   => $input['bill_date'],
        ]);
        $medicineBill->update([
            'model_id'    =>  $medicineBill->id,
        ]);
        if($input['category_id']){
            foreach($input['category_id'] as $key => $value){
                $medicine = Medicine::find($input['medicine'][$key]);
                $tax= $input['tax_medicine'][$key]==null? $input['tax_medicine'][$key] : 0;
                SaleMedicine::create([
                    'medicine_bill_id' => $medicineBill->id,
                    'medicine_id'=>$medicine->id,
                    'sale_price' => $input['sale_price'][$key],
                    'expiry_date' => $input['expiry_date'][$key],
                    'sale_quantity' => $input['quantity'][$key],
                    'tax' => $tax,

                ]);
            if($input['payment_status'] == 1){
                $medicine->update([
                    'available_quantity'=>  $medicine->available_quantity - $input['quantity'][$key],
                ]);
            }
        }
        Flash::success(__('messages.medicine_bills.medicine_bill').' '.__('messages.medicine.saved_successfully'));

        return Redirect::route('medicine-bills.index');

        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(MedicineBill $medicineBill)
    {
        $medicineBill->load(['saleMedicine.medicine']);

        return view('medicine-bills.show',compact('medicineBill'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  $medicineBill
     * @return \Illuminate\Http\Response
     */
    public function edit(MedicineBill $medicineBill)
    {
         $medicineBill->load(['saleMedicine.medicine.category','saleMedicine.medicine.purchasedMedicine','patient','doctor']);

         $patients = $this->prescriptionRepository->getPatients();
         $doctors = $this->prescriptionRepository->getDoctors();
         $medicines = $this->prescriptionRepository->getMedicines();
         $data = $this->medicineRepository->getSyncList();
         $medicineList =  $this->medicineRepository->getMedicineList();
         $mealList = $this->medicineRepository->getMealList();
         $medicineCategories = $this->medicineBillRepository->getMedicinesCategoriesData();
         $medicineCategoriesList = $this->medicineBillRepository->getMedicineCategoriesList();

         return view('medicine-bills.edit',
             compact('patients', 'doctors', 'medicines', 'medicineList', 'mealList','medicineBill','medicineCategoriesList','medicineCategories'))->with($data);


    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(MedicineBill $medicineBill,UpdateMedicineBillRequest $request)
    {
        $input = $request->all();
        if(empty($input['medicine']) && $input['payment_status'] == false){

            return $this->sendError(__('messages.medicine_bills.medicine_not_selected'));
        }
        $this->medicineBillRepository->update($medicineBill,$input);

        return $this->sendSuccess(__('messages.medicine_bills.medicine_bill').' '.__('messages.medicine.saved_successfully'));

    }

    /**
      * Remove the specified resource from storage.
        * *  @return \Illuminate\Http\Response
     */
    public function destroy(MedicineBill $medicineBill)
    {
        $medicineBill->saleMedicine()->delete();
        $medicineBill->delete();

        return $this->sendSuccess(__('messages.medicine_bills.medicine_bill').' '.__('messages.common.deleted_successfully'));
    }

    /** Store a newly created Patient in storage.

      * @param  CreatePatientRequest  $request
      * @return JsonResponse
     */
    public function storePatient(CreatePatientRequest $request)
    {
        $input = $request->all();
        $input['status'] = isset($input['status']) ? 1 : 0;


        $this->patientRepository->store($input);
        $this->patientRepository->createNotification($input);
        $patients = $this->prescriptionRepository->getPatients();


        return $this->sendResponse($patients,__('messages.flash.Patient_saved'));
    }

    /**
     * @param $id
     * @return Response
     */
    public function convertToPDF($id): Response
    {
        $data = $this->prescriptionRepository->getSettingList();
        $medicineBill = MedicineBill::with(['saleMedicine.medicine'])->where('id',$id)->first();

        $pdf = Pdf::loadView('medicine-bills.medicine_bill_pdf', compact('medicineBill', 'data'));

        return $pdf->stream('medicine-bill.pdf');
    }

    /**
     * @param Category $category
     * @return JsonResponse
     */
    public  function getMedicineCategory(Category $category)
    {
        $data = [];
        $data['category'] = $category;
        $data['medicine'] = Medicine::whereCategoryId($category->id)->pluck('name','id')->toArray();

        return $this->sendResponse($data,'retrieved');
    }
}
