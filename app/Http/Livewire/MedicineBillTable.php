<?php

namespace App\Http\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\MedicineBill;

class MedicineBillTable extends LivewireTableComponent
{

    public bool $showButtonOnHeader = true;

    public string $buttonComponent = 'medicine-bills.add-button';

    protected $listeners = ['refresh' => '$refresh', 'changeFilter', 'resetPage'];

    protected $model = MedicineBill::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
        ->setDefaultSort('medicine_bills.created_at', 'desc');

        $this->setThAttributes(function (Column $column) {
            if($column->isField('id')){
                return [
                    'class' => 'text-center ml-5',
                ];
            }
            if($column->isField('net_amount')){
                return [
                    'class' => 'text-end',
                ];
            }
            if($column->isField('payment_status')){
                return [
                    'class' => 'text-center',
                ];
            }

            return [];
        });

    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.medicine_bills.bill_number'), "bill_number")
                ->sortable()->view('medicine-bills.columns.bill_id'),
            Column::make(__('messages.appointment.date'), "created_at")
                ->sortable()->view('medicine-bills.columns.bill_date'),
            Column::make(__('messages.visit.patient'), "patient_id")
                ->sortable()->view('medicine-bills.columns.patient'),
            Column::make(__('messages.doctor.doctor'), "doctor_id")
                ->sortable()->view('medicine-bills.columns.doctor'),
            Column::make(__('messages.purchase_medicine.discount'), "discount")
                ->sortable()->view('medicine-bills.columns.discount'),
            Column::make(__('messages.purchase_medicine.net_amount'), "net_amount")
                ->sortable()->view('medicine-bills.columns.amount'),
            Column::make(__('messages.medicine_bills.payment_status'), "payment_status")
                ->sortable()->view('medicine-bills.columns.payment_status'),
            Column::make(__('messages.common.action'), "id")
                ->sortable()->view('medicine-bills.columns.action'),
        ];
    }
}
