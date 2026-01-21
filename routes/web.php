<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\RideRequestController;
use App\Http\Controllers\AdminPermissionsController;
use App\Http\Controllers\RideAdminController;
use App\Http\Controllers\RideController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\AdditionalFeesController;
use App\Http\Controllers\ClientTestimonialsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DriverDocumentController;
use App\Http\Controllers\SosController;
use App\Http\Controllers\WithdrawRequestController;

use App\Http\Controllers\ComplaintCommentController;
use App\Http\Controllers\CustomerSupportController;
use App\Http\Controllers\DefaultkeywordController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\PushNotificationController;

use App\Http\Controllers\DispatchController;
use App\Http\Controllers\Frontendwebsite\FrontendController;
use App\Http\Controllers\LanguageListController;
use App\Http\Controllers\LanguageWithKeywordListController;
use App\Http\Controllers\MailTemplateController;
use App\Http\Controllers\OurMissionController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\SubAdminController;
use App\Http\Controllers\SupportchatHistoryController;
use App\Http\Controllers\SurgePriceController;
use App\Http\Controllers\WhyChooseController;
use App\Http\Controllers\MonthlyRequestsController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\PricingModifiersController;
use App\Http\Controllers\SosAlertsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require __DIR__.'/auth.php';

Route::get('migrate', function(){
    try {
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => 'true']);
        return 'Migrations have been run successfully';
    } catch (\Exception $e) {
        return 'Migration failed: ' . $e->getMessage();
    }
});

Route::get('storage-link', function () {
    Artisan::call('storage:link');
    return 'Storage link created';
});

Route::get('/mqtt/publish/{topic}/{message}', [ HomeController::class, 'SendMsgViaMqtt' ]);
Route::get('/mqtt/subscribe/{topic}', [ HomeController::class, 'SubscribetoTopic' ]);

//Auth pages Routs
Route::group(['prefix' => 'auth'], function() {
    Route::get('login', [HomeController::class, 'authLogin'])->name('auth.login');
    Route::get('register', [HomeController::class, 'authRegister'])->name('auth.register');
    Route::get('recover-password', [HomeController::class, 'authRecoverPassword'])->name('auth.recover-password');
    Route::get('confirm-email', [HomeController::class, 'authConfirmEmail'])->name('auth.confirm-email');
    Route::get('lock-screen', [HomeController::class, 'authlockScreen'])->name('auth.lock-screen');
});

Route::get('ride-invoice/{id}', [RideRequestController::class, 'rideInvoicePdf'])->name('ride-invoice');
Route::get('language/{locale}', [ HomeController::class, 'changeLanguage'])->name('change.language');

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::resource('rider', RiderController::class);
Route::get('driver/pending', [ DriverController::class,'pending' ])->name('driver.pending');
Route::get('driver/{id}/documents', [ DriverController::class,'documents' ])->name('driver.documents');
Route::resource('driver', DriverController::class)->except(['show','edit','update','destroy']);
Route::resource('sub-admin', SubAdminController::class)->only(['index', 'create', 'store']);

Route::group(['middleware' => ['admin.session']], function()
{
    // Route::get('/', [HomeController::class, 'index']);
    Route::get('/permissions', [AdminPermissionsController::class, 'index'])->name('permissions.index');
    Route::post('/permissions/{id}/update', [AdminPermissionsController::class, 'update'])->name('permissions.update');

    Route::group(['namespace' => '' ], function () {
        Route::resource('permission', PermissionController::class);
        Route::get('permission/add/{type}',[ PermissionController::class,'addPermission' ])->name('permission.add');
        Route::post('permission/save',[ PermissionController::class,'savePermission' ])->name('permission.save');
	});

	Route::resource('role', RoleController::class);
	Route::resource('region', RegionController::class);
	Route::resource('service', ServiceController::class)->only(['index', 'create', 'store']);
    Route::get('service/{offerId}/bidders', [ ServiceController::class, 'bidders' ])->name('service.bidders');
    Route::post('service/{offerId}/approve', [ ServiceController::class, 'approve' ])->name('service.approve');

    // rider/driver/sub-admin moved above to be public

	Route::resource('fleet', FleetController::class);
	Route::resource('additionalfees', AdditionalFeesController::class);
	Route::resource('document', DocumentController::class);
	Route::resource('driverdocument', DriverDocumentController::class);


    Route::resource('riderequest', RideRequestController::class)->except(['create', 'edit']);
    Route::get('ride/all', [RideController::class, 'all'])->name('ride.all');
    Route::get('ride/new', [RideController::class, 'newToday'])->name('ride.new');
    Route::get('ride/completed', [RideController::class, 'completed'])->name('ride.completed');
    Route::get('ride/cancelled', [RideController::class, 'cancelled'])->name('ride.cancelled');
    Route::get('ride/inprogress', [RideController::class, 'inProgress'])->name('ride.inprogress');
    Route::get('ride/view/{source}/{id}', [RideController::class, 'view'])->name('ride.view');
    Route::get('rides/{id}', [RideController::class, 'showDetails'])->name('rides.show');
    Route::get('ride/new-request', [RideController::class, 'key'])->defaults('key', 'new-request')->name('ride.new-request');
    Route::get('ride/accepted', [RideController::class, 'key'])->defaults('key', 'accepted')->name('ride.accepted');
    Route::get('ride/offer-submitted', [RideController::class, 'key'])->defaults('key', 'offer-submitted')->name('ride.offer-submitted');
    Route::get('ride/offer-accepted', [RideController::class, 'key'])->defaults('key', 'offer-accepted')->name('ride.offer-accepted');
    Route::get('ride/offer-declined', [RideController::class, 'key'])->defaults('key', 'offer-declined')->name('ride.offer-declined');
    Route::get('ride/enroute', [RideController::class, 'key'])->defaults('key', 'enroute')->name('ride.enroute');
    Route::get('ride/arrived', [RideController::class, 'key'])->defaults('key', 'arrived')->name('ride.arrived');
    Route::get('ride/cancelled-driver', [RideController::class, 'key'])->defaults('key', 'cancelled-driver')->name('ride.cancelled-driver');
    Route::get('ride/cancelled-rider', [RideController::class, 'key'])->defaults('key', 'cancelled-rider')->name('ride.cancelled-rider');
    Route::get('ride/payment-status', [RideController::class, 'key'])->defaults('key', 'payment-status')->name('ride.payment-status');
	Route::resource('coupon', CouponController::class)->except(['show','edit','update','destroy']);
    Route::resource('complaint', ComplaintController::class);
    Route::resource('surge-prices', SurgePriceController::class)->only(['index', 'create', 'store']);

    Route::get('pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::get('pricing/create', [PricingController::class, 'create'])->name('pricing.create');
    Route::post('pricing', [PricingController::class, 'store'])->name('pricing.store');

    Route::get('pricing-modifiers', [PricingModifiersController::class, 'index'])->name('pricing_modifiers.index');
    Route::get('pricing-modifiers/create', [PricingModifiersController::class, 'create'])->name('pricing_modifiers.create');
    Route::post('pricing-modifiers', [PricingModifiersController::class, 'store'])->name('pricing_modifiers.store');

    Route::get('monthly/employee', [MonthlyRequestsController::class, 'employeeIndex'])->name('monthly.employee.index');
    Route::get('monthly/employee/create', [MonthlyRequestsController::class, 'employeeCreate'])->name('monthly.employee.create');
    Route::post('monthly/employee', [MonthlyRequestsController::class, 'employeeStore'])->name('monthly.employee.store');

    Route::get('monthly/schools', [MonthlyRequestsController::class, 'schoolsIndex'])->name('monthly.schools.index');
    Route::get('monthly/schools/create', [MonthlyRequestsController::class, 'schoolsCreate'])->name('monthly.schools.create');
    Route::post('monthly/schools', [MonthlyRequestsController::class, 'schoolsStore'])->name('monthly.schools.store');

    Route::get('monthly/airports', [MonthlyRequestsController::class, 'airportsIndex'])->name('monthly.airports.index');
    Route::get('monthly/airports/create', [MonthlyRequestsController::class, 'airportsCreate'])->name('monthly.airports.create');
    Route::post('monthly/airports', [MonthlyRequestsController::class, 'airportsStore'])->name('monthly.airports.store');

    Route::get('monthly/special-needs', [MonthlyRequestsController::class, 'specialNeedsIndex'])->name('monthly.special_needs.index');
    Route::get('monthly/special-needs/create', [MonthlyRequestsController::class, 'specialNeedsCreate'])->name('monthly.special_needs.create');
    Route::post('monthly/special-needs', [MonthlyRequestsController::class, 'specialNeedsStore'])->name('monthly.special_needs.store');
    Route::resource('sos', SosController::class)->only(['index']);
    Route::get('sos-alerts', [SosAlertsController::class, 'index'])->name('sos.alerts.index');
    Route::resource('withdrawrequest', WithdrawRequestController::class)->only(['index']);
    Route::post('withdrawrequest/{id}/approve', [ WithdrawRequestController::class, 'approve' ])->name('withdrawrequest.approve');
    Route::post('withdrawrequest/{id}/decline', [ WithdrawRequestController::class, 'decline' ])->name('withdrawrequest.decline');
    Route::get('bank-detail/{id}', [ WithdrawRequestController::class, 'userBankDetail' ] )->name('bankdetail');


    Route::post('complaintcomment-save', [ ComplaintCommentController::class, 'store'] )->name('complaintcomment.store');
    Route::post('complaintcomment-update/{id}', [ ComplaintCommentController::class, 'update' ] )->name('complaintcomment.update');

	Route::get('changeStatus', [ HomeController::class, 'changeStatus'])->name('changeStatus');

	Route::get('setting/{page?}',[ SettingController::class, 'settings'])->name('setting.index');
    Route::post('/layout-page',[ SettingController::class, 'layoutPage'])->name('layout_page');
    Route::post('settings/save',[ SettingController::class , 'settingsUpdates'])->name('settingsUpdates');
    Route::post('appsetting/save',[ SettingController::class , 'AppSetting'])->name('AppSetting');
    Route::post('mobile-config-save',[ SettingController::class , 'settingUpdate'])->name('settingUpdate');
    Route::post('payment-settings/save',[ SettingController::class , 'paymentSettingsUpdate'])->name('paymentSettingsUpdate');
    Route::post('wallet-settings/save',[ SettingController::class , 'walletSettingsUpdate'])->name('walletSettingsUpdate');
    Route::post('ride-settings/save',[ SettingController::class , 'rideSettingsUpdate'])->name('rideSettingsUpdate');
    Route::post('notification-settings/save',[ SettingController::class , 'notificationSettingsUpdate'])->name('notificationSettingsUpdate');
    Route::post('update-appsetting', [SettingController::class, 'updateAppSetting'])->name('updateAppsSetting');
    Route::post('mail-template-settings/save',[ SettingController::class , 'mailTemplateSettingsUpdate'])->name('mailTemplateSettingsUpdate');

    Route::post('get-lang-file', [ LanguageController::class, 'getFile' ] )->name('getLanguageFile');
    Route::post('save-lang-file', [ LanguageController::class, 'saveFileContent' ] )->name('saveLangContent');

    Route::get('pages/term-condition',[ SettingController::class, 'termAndCondition'])->name('term-condition');
    Route::post('term-condition-save',[ SettingController::class, 'saveTermAndCondition'])->name('term-condition-save');

    Route::get('pages/privacy-policy',[ SettingController::class, 'privacyPolicy'])->name('privacy-policy');
    Route::post('privacy-policy-save',[ SettingController::class, 'savePrivacyPolicy'])->name('privacy-policy-save');

	Route::post('env-setting', [ SettingController::class , 'envChanges'])->name('envSetting');
    Route::post('update-profile', [ SettingController::class , 'updateProfile'])->name('updateProfile');
    Route::post('change-password', [ SettingController::class , 'changePassword'])->name('changePassword');

    Route::get('notification-list',[ NotificationController::class ,'notificationList'])->name('notification.list');
    Route::get('notification-counts',[ NotificationController::class ,'notificationCounts'])->name('notification.counts');
    Route::get('notification',[ NotificationController::class ,'index'])->name('notification.index');

    Route::post('remove-file',[ HomeController::class, 'removeFile' ])->name('remove.file');
Route::get('mapview',[ HomeController::class, 'map' ])->name('map');
Route::get('map-view',[ HomeController::class, 'driverListMap' ])->name('driver_list.map');
Route::get('driver/location-map', [HomeController::class, 'map'])->name('driver.location.map');
    // Route::get('driver-detail', [ HomeController::class, 'driverDetail' ] )->name('driverdetail');

    Route::get('driver/{id}/details', [HomeController::class, 'driverDetail'])->name('driverDetail');
    Route::get('driver/searchById/{id}', [HomeController::class, 'search'])->name('driver.search');

    Route::post('save-wallet-fund/{user_id}', [ HomeController::class, 'saveWalletHistory'] )->name('savewallet.fund');

    Route::resource('pushnotification', PushNotificationController::class);

    Route::resource('dispatch', DispatchController::class)->except(['index', 'edit']);

    // Route::get('informations', [SettingController::class, 'information'])->name('information');
    // Route::get('dowloandapp', [SettingController::class, 'downloandapp'])->name('downloandapp');
    // Route::get('contactinfo', [SettingController::class, 'contactinfo'])->name('contactinfo');
    // Route::post('setting-upload-image', [SettingController::class, 'settingUploadImage'])->name('image-save');

    Route::get('website-section/{type}', [ FrontendController::class, 'websiteSettingForm' ] )->name('frontend.website.form');
    Route::post('update-website-information/{type}', [ FrontendController::class, 'websiteSettingUpdate' ] )->name('frontend.website.information.update');

    //pages
    Route::resource('pages', PagesController::class);
    Route::get('pages-edit/{id?}', [PagesController::class, 'edit'])->name('Pages-edit.edit');

	Route::resource('our-mission', OurMissionController::class);
	Route::resource('why-choose', WhyChooseController::class);
    Route::resource('client-testimonials', ClientTestimonialsController::class);
    // Route::get('delete/{id}', [OurMissionController::class, 'destroy'])->name('data-delete');

    Route::resource('screen', ScreenController::class);
    Route::resource('defaultkeyword', DefaultkeywordController::class);
    Route::resource('languagelist', LanguageListController::class);
    Route::resource('languagewithkeyword', LanguageWithKeywordListController::class);
    Route::get('download-language-with-keyword-list', [LanguageWithKeywordListController::class, 'downloadLanguageWithKeywordList'])->name('download.language.with,keyword.list');

    Route::post('import-language-keyword', [LanguageWithKeywordListController::class, 'importlanguagewithkeyword'])->name('import.languagewithkeyword');
    Route::get('bulklanguagedata', [LanguageWithKeywordListController::class, 'bulklanguagedata'])->name('bulk.language.data');
    Route::get('help', [LanguageWithKeywordListController::class, 'help'])->name('help');
    Route::get('download-template', [LanguageWithKeywordListController::class, 'downloadtemplate'])->name('download.template');

    Route::delete('datatble/destroySelected', [HomeController::class, 'destroySelected'])->name('datatble.destroySelected');


    // report data Route
    Route::get('admin-earning-report', [ReportController::class, 'adminEarning'])->name('adminEarningReport');
    Route::get('driver-earning-report', [ ReportController::class, 'driverEarning' ])->name('driver.earning.report');
    Route::get('driver-report-report', [ ReportController::class, 'driverReport' ])->name('driver.report.list');
    Route::get('service-wise-report', [ ReportController::class, 'serviceWiseReport' ])->name('serviceWiseReport');

    // Report Excel Route
    Route::get('download-admin-earning', [ReportController::class, 'downloadAdminEarning'])->name('download-admin-earning');
    Route::get('download-driver-earning', [ReportController::class, 'downloadDriverEarning'])->name('download-driver-earning');
    Route::get('download-driver-report', [ReportController::class, 'downloadDriverReport'])->name('download.driver.report');
    Route::get('servicewise-report-export', [ReportController::class, 'serviceWiseReportExport'])->name('download.servicewise.report');

    //Report Pdf Route
    Route::get('download-adminearningpdf', [ReportController::class, 'downloadAdminEarningPdf'])->name('download-adminearningpdf');
    Route::get('download-driverearningpdf', [ReportController::class, 'downloadDriverEarningPdf'])->name('download-driverearningpdf');
    Route::get('download-driver-report-pdf', [ReportController::class, 'downloadDriverReportPdf'])->name('download.driver.report.pdf');
    Route::get('servicewise-report-pdf-export', [ReportController::class, 'serviceWiseReportPdfExport'])->name('download.servicewise.report.pdf');

    // download-withdrawrequest-list removed (Firestore-only module)

    // sub-admin moved above to be public

    Route::resource('payment', PaymentController::class);

    // Route::resource('customersupport', CustomerSupportController::class);
    // Route::resource('supportchathistory', SupportchatHistoryController::class);
    // Route::put('/support/{id}/status', [CustomerSupportController::class, 'updateStatus'])->name('support.updateStatus');

    Route::resource('mail-template', MailTemplateController::class)->except(['create','show','edit','update','destroy']);

});

Route::get('/ajax-list',[ HomeController::class, 'getAjaxList' ])->name('ajax-list');

Route::get('/', [FrontendController::class, 'index'])->name('browse');
Route::get('termofservice', [FrontendController::class, 'termofservice'])->name('termofservice');
Route::get('privacypolicy', [FrontendController::class, 'privacypolicy'])->name('privacypolicy');
Route::get('page/{slug}', [FrontendController::class, 'page'])->name('pages');

