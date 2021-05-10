<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Resolvers\PaymentPlatformResolver;

class PaymentController extends Controller
{
        /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $paymentPlatformResolver;
     
    // this is the dependency injecton the PaymentPlatformResolver class imported above is resolved in the contructor meaning its object is created for us and then we store it in the protected attribute $PaymentPlatformResolver with this statement $this->paymentPlatformResolver = $paymentPlatformResolver; 
    public function __construct(PaymentPlatformResolver $paymentPlatformResolver)
    {
        $this->middleware('auth');

        $this->paymentPlatformResolver = $paymentPlatformResolver; 
    }

    /**
     * Obtain Payment Details
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function pay(Request $request)
    {
        $rules = [
            'value'             => ['required', 'numeric', 'min:5'],
            'currency'          => ['required', 'exists:currencies,iso'],
            'payment_platform'  => ['required', 'exists:payment_platforms,id']
        ];

        $request->validate($rules);

        //resolve create a new object of each payment services so we can have access to all of its attributes and methods
        //remember the PaymentPlatformResolver and its method are custom class and its method created in Resolvers to detemine the payment service the client is using dynamically
        $paymentPlatform = $this->paymentPlatformResolver->resolveService($request->payment_platform);

        // put the paymenyPlatformId to be used in Approval method below
        session()->put('paymentPlatformId',$request->payment_platform);

        // applying the 10% off for subscription users
        if($request->user()->hasActiveSubscription())
        {
            $request->value = round($request->value * 0.9, 2); 
        }

        return $paymentPlatform->handlePayment($request);
    }

    /**
     * Payment Approved
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function approval(){
        // this if condition is for so if someone doesnt run this route directly
        if(session()->has('paymentPlatformId')){

            $paymentPlatform = $this->paymentPlatformResolver->resolveService(session()->get('paymentPlatformId'));

            return $paymentPlatform->handleApproval();

        }

        return redirect()
            ->route('home')
            ->withErrors('We cannot retrive your payment platform, Please try again');
    }

    /**
     * Payment Cancelled
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function cancelled(){
        return redirect()
            ->route('home')
            ->withErrors('The payment was cancelled');
    }
}
