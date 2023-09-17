<?php

use Carbon\Carbon;
use Stripe\Stripe;
use App\Models\City;
use App\Models\User;
use App\Models\State;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\Currency;
use Carbon\CarbonPeriod;
use App\Models\ZoomOAuth;
use App\Models\Notification;
use App\Models\DoctorSession;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;

/**
 * @return Authenticatable|null
 */
function getLogInUser()
{
    return Auth::user();
}

/**
 * @return mixed
 */
function getAppName()
{
    static $setting;
    if (empty($setting)) {
        $setting = Setting::all()->keyBy('key');
    }

    return $setting['clinic_name']->value;
}

/**
 * @return mixed
 */
function getAppLogo()
{
    static $setting;
    if (empty($setting)) {
        $setting = Setting::all()->keyBy('key');
    }

    return $setting['logo']->value;
}

/**
 * @return mixed
 */
function getAppFavicon()
{
    static $setting;
    if (empty($setting)) {
        $setting = Setting::all()->keyBy('key');
    }

    return $setting['favicon']->value;
}

/**
 * @return int
 */
function getLogInUserId()
{
    return Auth::user()->id;
}

/**
 * @param $countryId
 * @return mixed
 */
function getStates($countryId)
{
    return State::where('country_id', $countryId)->toBase()->pluck('name', 'id')->toArray();
}

/**
 * @param $stateId
 * @return mixed
 */
function getCities($stateId)
{
    return City::where('state_id', $stateId)->pluck('name', 'id')->toArray();
}

/**
 * @return string
 */
function getDashboardURL()
{
    if (Auth::user()->hasRole('clinic_admin')) {
        return 'admin/dashboard';
    } else {
        if (Auth::user()->hasRole('doctor')) {
            return 'doctors/dashboard';
        } else {
            if (Auth::user()->hasRole('patient')) {
                return 'patients/dashboard';
            }
        }
    }

    if (Auth::user() !== null) {
        /** @var User $user */
        $user = Auth::user();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        if (in_array('manage_admin_dashboard', $permissions, true)) {
            return 'admin/dashboard';
        }

        if (in_array('manage_doctors', $permissions, true)) {
            return 'admin/doctors';
        }

        if (in_array('manage_patients', $permissions, true)) {
            return 'admin/patients';
        }

        if (in_array('manage_staff', $permissions, true)) {
            return 'admin/staff';
        }

        if (in_array('manage_appointments', $permissions, true)) {
            return 'admin/appointments';
        }

        if (in_array('manage_patient_visits', $permissions, true)) {
            return 'admin/visits';
        }

        if (in_array('manage_settings', $permissions, true)) {
            return 'admin/settings';
        }

        if (in_array('manage_specialities', $permissions, true)) {
            return 'admin/specializations';
        }

        if (in_array('manage_services', $permissions, true)) {
            return 'admin/services';
        }

        if (in_array('manage_front_cms', $permissions, true)) {
            return 'admin/cms';
        }

        if (in_array('manage_transactions', $permissions, true)) {
            return 'admin/transactions';
        }
    }

    return RouteServiceProvider::HOME;
}

/**
 * @return string
 */
function getDoctorSessionURL()
{
    if (Auth::user()->hasRole('clinic_admin')) {
        return 'admin/doctor-sessions';
    } elseif (Auth::user()->hasRole('doctor')) {
        return 'doctors/doctor-sessions';
    } elseif (Auth::user()->hasRole('patient')) {
        return 'patients/doctor-sessions';
    }

    return RouteServiceProvider::HOME;
}

/**
 * @param $doctor_id
 */
function getDoctorSessionTime($doctor_id)
{
    $doctorSession = DoctorSession::whereDoctorId($doctor_id)->get();
}

function getSlotByGap($startTime, $endTime)
{
    $period = new CarbonPeriod($startTime, '15 minutes',
        $endTime); // for create use 24 hours format later change format
    $slots = [];
    foreach ($period as $item) {
        $slots[$item->format('h:i A')] = $item->format('h:i A');
    }

    return $slots;
}

function getSchedulesTimingSlot()
{
    $period = new CarbonPeriod('00:00', '15 minutes', '24:00'); // for create use 24 hours format later change format
    $slots = [];
    foreach ($period as $item) {
        $slots[$item->format('h:i A')] = $item->format('h:i A');
    }

    return $slots;
}

/**
 * @param $index
 * @return string
 */
function getBadgeColor($index)
{
    $colors = [
        'primary',
        'danger',
        'success',
        'info',
        'warning',
        'dark',
    ];

    $index = $index % 6;
    if (Auth::user()->dark_mode) {
        array_splice($colors, 5, 1);
        array_push($colors, 'bg-white');
    }

    return $colors[$index];
}

/**
 * @param $status
 * @return string
 */
function getBadgeStatusColor($status)
{
    $colors = [
        'danger',
        'primary',
        'success',
        'warning',
        'danger',
    ];

    return $colors[$status];
}

/**
 * @return string
 */
function getLoginDoctorSessionUrl(): string
{
    return DoctorSession::toBase()->whereDoctorId(getLogInUser()->doctor->id)->exists() ? route('doctors.doctor.schedule.edit') : route('doctors.doctor-sessions.create');
}

/**
 * @return string
 */
function doctorSessionActiveUrl(): string
{
    return DoctorSession::toBase()->whereDoctorId(getLogInUser()->doctor->id)->exists() ? 'doctors/doctor-schedule-edit*' : 'doctors/doctor-sessions/create*';
}

/**
 * @param $index
 * @return string
 */
function getStatusBadgeColor($index)
{
    $colors = [
        'danger',
        'primary',
        'success',
        'warning',
    ];

    $index = $index % 4;

    return $colors[$index];
}

/**
 * @param $index
 * @return string
 */
function getStatusColor($index)
{
    $colors = [
        '#F46387',
        '#399EF7',
        '#50CD89',
        '#FAC702',
    ];

    $index = $index % 4;

    return $colors[$index];
}

/**
 * @param $status
 * @return string
 */
function getStatusClassName($status)
{
    $classNames = [
        'bg-status-canceled',
        'bg-status-booked',
        'bg-status-checkIn',
        'bg-status-checkOut',
    ];

    $index = $status % 4;

    return $classNames[$index];
}

/**
 * @param $key
 * @return mixed
 */
function getSettingValue($key)
{
    static $setting;

    if (empty($setting)) {
        $setting = Setting::all()->keyBy('key');
    }

    return $setting[$key]->value;
}

/**
 * @param $email
 * @return string
 */
function setEmailLowerCase($email)
{
    return strtolower($email);
}

/**
 * @return string[]
 */
function getUserLanguages()
{
    $language = User::LANGUAGES;
    asort($language);

    return $language;
}

/**
 * @return mixed
 */
function getCurrencyIcon()
{
    static $setting;

    if (empty($setting)) {
        $setting = Setting::all()->keyBy('key');
    }

    static $currencies;

    if (empty($currencies)) {
        $currencies = Currency::all()->keyBy('id');
    }

    $currencyId = $setting['currency']->value;
    $currency = $currencies[$currencyId];
    $currencyIcon = $currency->currency_icon ?? '$';

    return $currencyIcon;
}

function setStripeApiKey()
{
    Stripe::setApiKey(config('services.stripe.secret_key'));
}

/**
 * @return HigherOrderBuilderProxy|mixed|string
 */
function getCurrencyCode()
{
    static $setting;
    if (empty($setting)) {
        $setting = Setting::all()->keyBy('key');
    }

    $currencyId = $setting['currency'];

    $currencies = Cache::get('currency', null);

    if (empty($currencies)) {
        $currency = Currency::find($currencyId)->first();
        Cache::put('currency', $currency);

        return $currency->currency_code;
    }

    return $currencies->currency_code;
}

function version()
{
    if (config('app.is_version') == 'true') {
        $composerFile = file_get_contents('../composer.json');
        $composerData = json_decode($composerFile, true);
        $currentVersion = $composerData['version'];

        return 'v'.$currentVersion;
    }
}

if (! function_exists('getNotification')) {
    function getNotification()
    {
        return Notification::whereReadAt(null)->where('user_id',
            getLogInUserId())->orderByDesc('created_at')->get();
    }
}

function getNotificationIcon($notificationFor)
{
    switch ($notificationFor) {
        case $notificationFor == Notification::CHECKOUT:
            return 'fas fa-check-square';
        case $notificationFor == Notification::PAYMENT_DONE:
            return 'fas fa-money-bill-wave';
        case $notificationFor == Notification::BOOKED:
            return 'fas fa-calendar-alt';
        case $notificationFor == Notification::CANCELED:
            return 'fas fa-calendar-times';
        case $notificationFor == Notification::REVIEW:
            return 'fas fa-star';
        case $notificationFor == Notification::LIVE_CONSULTATION:
            return 'fas fa-video';
    }
}

/**
 * @return mixed|null
 */
function checkLanguageSession()
{
    if (Session::has('languageName')) {
        return Session::get('languageName');
    } else {
        $user = getLogInUser();
        if ($user != null) {
            return $user->language;
        }
    }

    return 'en';
}

/**
 * @return mixed|null
 */
function getCurrentLanguageName()
{
    return User::LANGUAGES[checkLanguageSession()];
}

function getMonth()
{
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep',
        10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    return $months;
}

/**
 * @return string[]
 */
function getAllPaymentStatus()
{
    $paymentGateway = \App\Models\Appointment::PAYMENT_METHOD;

    $selectedPaymentGateway = PaymentGateway::pluck('payment_gateway', 'payment_gateway_id', )->toArray();

    $paymentMethodToReturn = array_intersect($paymentGateway, $selectedPaymentGateway);

    return $paymentMethodToReturn;
}

/**
 * @return string[]
 */
function getPaymentGateway()
{

    $paymentGateway = \App\Models\Appointment::PAYMENT_GATEWAY;
    $selectedPaymentGateway = PaymentGateway::pluck('payment_gateway')->toArray();

    $paymentGatewayToReturn = array_intersect($paymentGateway, $selectedPaymentGateway);


    return $paymentGatewayToReturn;
}

/**
 * @return string
 */
function getWeekDate(): string
{
    $date = Carbon::now();
    $startOfWeek = $date->startOfWeek()->subDays(1);
    $startDate = $startOfWeek->format('Y-m-d');
    $endOfWeek = $startOfWeek->addDays(6);
    $endDate = $endOfWeek->format('Y-m-d');

    return $startDate.' - '.$endDate;
}

/**
 * @param $currencies
 * @param $amount
 * @return string
 */
function getCurrencyFormat($currencies, $amount): string
{
    return moneyFormat($amount, $currencies);
}

/**
 * @param $filterArray
 * @return array
 */
function filterLangChange($filterArray): array
{
    foreach ($filterArray as $key => $value) {
        $array[$key] = __('messages.filter.'.strtolower($value));
    }

    return $array;
}

function paymentMethodLangChange($paymentMethodNameArray): array
{
    $array = [];
    foreach ($paymentMethodNameArray as $key => $value) {
        $array[$key] = __('messages.payment_method.'.strtolower($value));
    }

    return $array;
}

function zeroDecimalCurrencies(): array
{
    return [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];
}

/**
 * @param  array  $models
 * @param  string  $columnName
 * @param  int  $id
 * @return bool
 */
function canDelete($models, $columnName, $id)
{
    foreach ($models as $model) {
        $result = $model::where($columnName, $id)->exists();
        if ($result) {
            return true;
        }
    }

    return false;
}

/**
 * @param  array  $input
 * @param  string  $key
 * @return string|null
 */
function preparePhoneNumber($input, $key)
{
    return (! empty($input[$key])) ? '+'.$input['region_code'].$input[$key] : null;
}

/**
 * @return mixed
 */
function getCurrentCurrency()
{
    /** @var Setting $currentCurrency */
    static $currentCurrency;

    if (empty($currentCurrency)) {
        $currentCurrency = Setting::where('key', 'currency')->first();
    }

    return $currentCurrency->value;
}

/**
 * @return mixed
 */
function getCurrentLoginUserLanguageName()
{
    return Auth::user()->language;
}

function generateUniquePurchaseNumber()
{
    do {
        $code = random_int(100000, 999999);
    } while (\App\Models\PurchaseMedicine::where("purchase_no", "=", $code)->first());

    return $code;
}

function getPatientUniqueId()
{
    return mb_strtoupper(Patient::generatePatientUniqueId());
}

function generateUniqueBillNumber()
{
    do {
        $code = random_int(1000, 9999);
    } while (\App\Models\MedicineBill::where("bill_number", "=", $code)->first());

return $code;
}
function getAmountToWord(float $amount): string
{
        $amount_after_decimal = round($amount - ($num = floor($amount)), 2);
        $count_length = strlen($num);
        $x = 0;
        $string = array();
        $change_words = array(
            0  => '', 1 => 'One', 2 => 'Two',
            3  => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
            7  => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
            13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
            40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
            70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety',
        );
        $here_digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
        while ($x < $count_length) {
            $get_divider = ($x == 2) ? 10 : 100;
            $amount = floor($num % $get_divider);
            $num = floor($num / $get_divider);
            $x += $get_divider == 10 ? 1 : 2;
            if ($amount) {
                $add_plural = (($counter = count($string)) && $amount > 9) ? 's' : null;
                $amt_hundred = ($counter == 1 && $string[0]) ? ' and ' : null;
                $string[] = ($amount < 21) ? $change_words[$amount] . ' ' . $here_digits[$counter] . $add_plural . '
       ' . $amt_hundred : $change_words[floor($amount / 10)] . ' ' . $change_words[$amount % 10] . '
       ' . $here_digits[$counter] . $add_plural . ' ' . $amt_hundred;
            } else {
                $string[] = null;
            }
        }
        $implode_to_Rupees = implode('', array_reverse($string));
        $get_paise = ($amount_after_decimal > 0) ? "And " . ($change_words[$amount_after_decimal / 10] . "
   " . $change_words[$amount_after_decimal % 10]) . ' Paise' : '';

        return ($implode_to_Rupees ? $implode_to_Rupees . getCurrencyCode() : '') . $get_paise;
    }

/**
 * @param $model
 * @param $id
 * @return bool
 */
function canAccessRecord($model, $id)
{
    $recordExists = $model::where('id', $id)->exists();

    if ($recordExists) {
        return true;
    }

    return false;
}

/**
 * @return bool
 */
function getLoggedinDoctor()
{
    return Auth::user()->hasRole(['Doctor']);
}

function isRole(string $role) {

    if(getLogInUser()->hasRole($role)){

        return true;
    }

    return false;
}

function isZoomTokenExpire(){

    $isExpired = false;
    $zoomOAuth = ZoomOAuth::where('user_id', Auth::id())->first();
    $currentTime =  Carbon::now();

    $isExpired = is_null($zoomOAuth) == true ? true :  $isExpired;

    if(!is_null($zoomOAuth) && $zoomOAuth->updated_at < $currentTime->subMinutes(57)){
        $isExpired = true;
    }

    return  $isExpired;
}
