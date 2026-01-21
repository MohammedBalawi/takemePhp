<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\OffersService;
use App\Services\FirestoreRestService;
use App\Support\FeatureFlags;

class ServiceController extends Controller
{
    public function index(OffersService $offersService)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.service')] );
        $button = '<a href="'.route('service.create').'" class="float-right btn btn-sm border-radius-10 btn-primary me-2"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.service')]).'</a>';
        $offers = $offersService->listOffers();

        return view('service.index', compact('pageTitle', 'button', 'offers'));
    }

    public function create()
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.service')]);
        
        return view('service.form', compact('pageTitle'));
    }

    public function store(Request $request, FirestoreRestService $firestore)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'discount_percent' => ['nullable', 'numeric', 'min:0'],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'dropoff_address' => ['nullable', 'string', 'max:255'],
            'rider_name' => ['nullable', 'string', 'max:255'],
            'rider_phone' => ['nullable', 'string', 'max:50'],
            'ride_id' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        if (!FeatureFlags::offersFirestoreEnabled()) {
            return redirect()->back()->withErrors(['message' => 'Firestore disabled']);
        }

        $docId = (string) Str::uuid();
        $now = now();
        $fields = [
            'title' => $validated['title'],
            'basePrice' => (float) $validated['base_price'],
            'currency' => $validated['currency'] ?? 'SAR',
            'discountPercent' => isset($validated['discount_percent']) ? (float) $validated['discount_percent'] : 0,
            'pickup' => ['address' => $validated['pickup_address'] ?? ''],
            'dropoff' => ['address' => $validated['dropoff_address'] ?? ''],
            'riderName' => $validated['rider_name'] ?? '',
            'riderPhone' => $validated['rider_phone'] ?? '',
            'rideId' => $validated['ride_id'] ?? '',
            'status' => $validated['status'] ?? 'new',
            'submittedByDriverUid' => 'admin',
            'createdAt' => $now,
            'updatedAt' => $now,
            'submittedAt' => $now,
        ];

        $saved = $firestore->patchDocumentTyped('offers', $docId, $fields);
        if (!$saved) {
            return redirect()->back()->withErrors(['message' => 'Failed to save offer']);
        }

        return redirect()->route('service.index')->withSuccess(__('message.save_form', ['form' => __('message.service')]));
    }

    public function bidders(string $offerId, OffersService $offersService)
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.service')]) . ' - المتقدمين';
        $rows = $offersService->listOfferBidders($offerId);

        return view('service.bidders', compact('pageTitle', 'offerId', 'rows'));
    }

    public function approve(Request $request, string $offerId, OffersService $offersService)
    {
        $validated = $request->validate([
            'driverUid' => ['required', 'string'],
        ]);

        $ok = $offersService->approveBidder($offerId, $validated['driverUid']);
        if (!$ok) {
            return redirect()->back()->withErrors(['message' => 'Failed to approve driver']);
        }

        return redirect()->back()->withSuccess('تم الاعتماد');
    }
}
