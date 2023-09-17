@extends('layouts.auth')
@section('title')
    {{__('messages.register')}}
@endsection
@section('content')
    <div class="d-flex flex-column flex-column-fluid align-items-center justify-content-center p-4">
        <div class="col-12 text-center">
            <a href="{{ route('medical') }}" class="image mb-7 mb-sm-10">
                <img alt="Logo" src="{{ asset(getAppLogo()) }}" class="img-fluid" style="width:90px;">
            </a>
        </div>
        <div class="width-540">
            @include('flash::message')
            @include('layouts.errors')
        </div>
        <div class="bg-white rounded-15 shadow-md width-540 px-5 px-sm-7 py-10 mx-auto">
            <h1 class="text-center mb-7">Doctor Registeration</h1>
            <form method="POST" action="{{ route('registerDoctor') }}" >
                @csrf
                <div class="row">
                        <label for="formInputFirstName" class="form-label">
                            {{ __('messages.patient.first_name').':' }}<span class="required"></span>
                        </label>
                        <input name="first_name" type="text" class="form-control" id="name" aria-describedby="firstName" placeholder="{{ __('messages.patient.first_name') }}" value="{{ old('first_name') }}" required>
                    
                </div>
                <div class="row">
                    <div class="col-md-12 mb-sm-7 mb-4">
                        <label for="email" class="form-label">
                            {{ __('messages.patient.email').':' }}<span class="required"></span>
                        </label>
                        <input name="email" type="email" class="form-control" id="email" aria-describedby="email" placeholder="{{ __('messages.patient.email') }}" value="{{ old('email') }}" required>
                    </div>
                </div>
                <div class="mb-5 fv-row">
                    <div class="row">
                        <div class="col-md-6 mb-sm-7 mb-4">
                            <label for="password" class="form-label">
                                {{ __('messages.patient.password').':' }}<span class="required"></span>
                            </label>
                            <input type="password" name="password" class="form-control" id="password" placeholder="{{ __('messages.patient.password') }}" aria-describedby="password" required>
                        </div>
                        <div class="col-md-6 mb-sm-7 mb-4">
                            <label for="password_confirmation" class="form-label">
                                {{ __('messages.patient.confirm_password') .':' }}<span class="required"></span>
                            </label>
                            <input name="password_confirmation" type="password" class="form-control" placeholder="{{ __('messages.patient.confirm_password') }}" id="password_confirmation" aria-describedby="confirmPassword" required>
                        </div>
                    </div>
                    <div class="mb-5">
                        {{ Form::label('Specialization',__('messages.doctor.specialization').':' ,['class' => 'form-label required']) }}
                        {{ Form::select('specializations[]',$specializations, null,['class' => 'io-select2 form-select', 'data-control'=>"select2", 'multiple', 'data-placeholder' => __('messages.doctor.specialization')]) }}
                    </div>

                    <div class="col-md-6">
                        <div class="mb-5">
                            <label class="form-label required">
                                {{__('messages.doctor.select_gender')}}
                                :
                            </label>
                            <span class="is-valid">
                                <div class="mt-2">
                                    <input class="form-check-input" type="radio" checked name="gender" value="1">
                                    <label class="form-label mr-3">{{__('messages.doctor.male')}}</label>
                                    <input class="form-check-input ms-2" type="radio" name="gender" value="2">
                                    <label class="form-label mr-3">{{__('messages.doctor.female')}}</label>
                                </div>
                            </span>
                        </div>
                    </div>


                    <label for="birthday">Birthday:</label>
                    <input type="date" id="dob" name="dob">



                    
                    {{---------------- image-----------------}}
                    <div class="col-lg-6 mt-5">
                        <div class="image-picker">
                            <div class="image previewImage" id="exampleInputImage" style="background-image: url({{ !empty($patient->profile) ? $patient->profile : asset('web/media/avatars/male.png') }})">
                            </div>
                            <span class="picker-edit rounded-circle text-gray-500 fs-small" data-bs-toggle="tooltip"
                                  data-placement="top" data-bs-original-title="{{ __('messages.user.edit_profile') }}">
                                <label> 
                                    <i class="fa-solid fa-pen" id="profileImageIcon"></i> 
                                    <input type="file"  class="custom-file-input" id="profilePicture" name="profile" accept="">
                                    <input type="file" name="profile" id="profilePicture" class="image-upload d-none " accept="" /> 
                                </label> 
                            </span>
                           
                        </div>
            </div>

            <div class="col-md-6 mb-7">
                Country :
               <input type="text" id="countryid" name="countryid">
            </div>
           
            <div class="col-md-6 mb-7">
                city :
                <input type="text" id="stateid" name="stateid">
                 </div>


            <div class="col-md-6 mb-5">
                {{ Form::label('contact', __('messages.patient.contact_no').':', ['class' => 'form-label']) }}
                {{ Form::tel('contact', !empty($patient->user) ? '+'.$patient->user->region_code.$patient->user->contact : null, ['class' => 'form-control', 
                    'placeholder' => __('messages.patient.contact_no'),'onkeyup' => 'if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,"")','id'=>'phoneNumber']) }}
                {{ Form::hidden('region_code',!empty($patient->user) ? $patient->user->region_code : null,['id'=>'prefix_code']) }}
                <span id="valid-msg" class="text-success d-none fw-400 fs-small mt-2">{{ __('messages.valid_number') }}</span>
                <span id="error-msg" class="text-danger d-none fw-400 fs-small mt-2">{{ __('messages.invalid_number') }}</span>
            </div>






                    <div class="mb-sm-7 mb-4 form-check">
                        <input type="checkbox" class="form-check-input" name="toc" value="1" required/>
                        <span class="text-gray-700 me-2 ml-1">{{__('messages.web.i_agree')}}
									<a href="{{ route('terms.conditions') }}"
                                       class="ms-1 link-primary">{{__('messages.web.terms_and_conditions')}}</a>.
                        </span>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                    <div class="d-flex align-items-center mt-4">
                        <span class="text-gray-700 me-2">{{__('messages.web.already_have_an_account').'?'}}</span>
                        <a href="{{ route('login') }}" class="link-info fs-6 text-decoration-none">
                            {{__('messages.web.sign_in_here')}}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
