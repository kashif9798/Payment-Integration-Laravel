<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\PaymentPlatform;
use App\Resolvers\PaymentPlatformResolver;

class SubscriptionController extends Controller
{
    protected $paymentPlatformResolver;
     
    // this is the dependency injecton the PaymentPlatformResolver class imported above is resolved in the contructor meaning its object is created for us and then we store it in the protected attribute $PaymentPlatformResolver with this statement $this->paymentPlatformResolver = $paymentPlatformResolver; 
    public function __construct(PaymentPlatformResolver $paymentPlatformResolver)
    {
        $this->middleware(['auth', 'unsubscribed']);

        $this->paymentPlatformResolver = $paymentPlatformResolver; 
    }

    public function show()
    {
        $paymentPlatforms = PaymentPlatform::where('subscriptions_enabled',true)->get();
        return view('subscribe',[
            'plans' => Plan::all(),
            'paymentPlatforms' => $paymentPlatforms
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'plan'              => ['required', 'exists:plans,slug'],
            'payment_platform'  => ['required', 'exists:payment_platforms,id']
        ];

        $request->validate($rules);

        //resolve create a new object of each payment services so we can have access to all of its attributes and methods
        //remember the PaymentPlatformResolver and its method are custom class and its method created in Resolvers to detemine the payment service the client is using dynamically
        $paymentPlatform = $this->paymentPlatformResolver->resolveService($request->payment_platform);

        // put the paymenyPlatformId to be used in Approval method below
        session()->put('subscriptionPlatformId',$request->payment_platform);

        return $paymentPlatform->handleSubscription($request);

    }

    public function approval(Request $request)
    {
        $rules = [
            'plan'              => ['required', 'exists:plans,slug']
        ];

        $request->validate($rules);

        if(session()->has('subscriptionPlatformId'))
        {
            $paymentPlatform = $this->paymentPlatformResolver->resolveService(session()->get('subscriptionPlatformId'));

            if($paymentPlatform->validateSubscription($request))
            {
                $plan = Plan::where('slug', $request->plan)->firstOrFail();

                $user = $request->user();
        
                $subscription = Subscription::create([
                    'active_until'  => now()->addDays($plan->duration_in_days),
                    'user_id'       => $user->id,
                    'plan_id'       => $plan->id
                ]);
        
                return redirect()
                    ->route('home')
                    ->withSuccess(['payment' => "Thanks {$user->name}. You have now a " . ucfirst($plan->slug) . " subscription. Start using it now"]);
            }
        }

        return redirect()
            ->route('subscribe.show')
            ->withErrors('We cannot verify your Subscription, Try again please');
        
    }

    public function cancelled()
    {
        return redirect()
            ->route('subscribe.show')
            ->withErrors('You Cancelled, try again whenever you are ready');
    }
}
