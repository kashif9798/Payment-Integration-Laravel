<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\Request;

class StripeService
{
    use ConsumesExternalServices;

    protected $baseUri;

    protected $key;

    protected $secret;

    protected $plans;

    public function __construct(){
        $this->baseUri      = config('services.stripe.base_uri');
        $this->key          = config('services.stripe.key');
        $this->secret       = config('services.stripe.secret');
        $this->plans        = config('services.stripe.plans');
    }

    // the & is to indicate that the value is passed by reference meaning any change to these values will be reflected everywhere i.e in the ConsumesExternalServices
    public function resolveAuthorization(&$queryParams, &$headers, &$formParams)
    {   
        // as we need to resolve auhorization with either $queryParams or $headers or &$formParams depending on the platform we will use the $headers for paypal that asks for a basic access token in the header of the request if the request is coming from your applicaiton
        $headers['Authorization'] = $this->resolveAccessToken();
    }

    public function decodeResponse($response)
    {
        // we decode the json response so we could access it like objects in php
        return json_decode($response);
    }

    public function resolveAccessToken()
    {
        // returning the bearer access token
        return "Bearer {$this->secret}";
    }

    /**
     * this will find the approve payments in a single transaction from our website and redirect us to the approve route we defined createOrder() method in this class
     */
    public function handlePayment(Request $request)
    {
        // this is because only the stripe have payment_method input field
        $request->validate([
            'payment_method' => 'required'
        ]);

        $intent = $this->createIntent($request->value, $request->currency, $request->payment_method);

        session()->put('paymentIntentId', $intent->id);

        return redirect()->route('approval');
    }

    /**
     * This method handles after the payments gets approved
     */
    public function handleApproval()
    {
        if(session()->has('paymentIntentId')){

            $paymentIntentId    = session()->get('paymentIntentId');

            $confirmation       = $this->confirmPayment($paymentIntentId);

            if($confirmation->status === 'requires_action'){
                $clientSecret   = $confirmation->client_secret;  
                
                return view('stripe.3d-secure', ['clientSecret' => $clientSecret]);
            }

            if($confirmation->status === 'succeeded'){
                $name       = $confirmation->charges->data[0]->billing_details->name;
                $currency   = strtoupper($confirmation->currency);
                $amount     = $confirmation->amount / $this->resolveFactor($currency);

                return redirect()->route('home')->withSuccess(['payment' => "Thanks, {$name}. We recieved your {$amount} {$currency} payment"]);
            }
        }   

        return redirect()->route('home')->withErrors('We are unable to confirm your payment, Try Again please');
    }

    /** This method handle subscription by using the createCustomer() and createSubscription() created below. remember we createCustomer because stripe requires that in creating a subscription */
    public function handleSubscription(Request $request)
    {
        $customer       = $this->createCustomer(
            $request->user()->name,
            $request->user()->email,
            $request->payment_method
        );

        $subscription   = $this->createSubscription(
            $customer->id,
            $request->payment_method,
            $this->plans[$request->plan]
        );

        if($subscription->status == 'active'){
            session()->put('subscriptionId', $subscription->id);

            return redirect()->route('subscribe.approval',[
                'plan'              => $request->plan,
                'subscription_id'   => $subscription->id
            ]);
        }

        // remember we gave an extra argument createSubscription() method below that is expand to get payment intent which we use in the statemetn below and the payment intent status that we use in the if condition below also
        $paymentIntent          = $subscription->latest_invoice->payment_intent;

        if($paymentIntent->status === 'requires_action'){
            session()->put('subscriptionId', $subscription->id);

            $clientSecret           = $paymentIntent->client_secret;  

            return view('stripe.3d-secure-subscription', [
                'plan'              => $request->plan,
                'payment_method'    => $request->payment_method,
                'subscriptionId'    => $subscription->id,
                'clientSecret'      => $clientSecret
            ]);
        }

        return redirect()
            ->route('subscribe.show')
            ->withErrors('We were unable to activate the subscription. Try Again, please.');
    }

    public function validateSubscription(Request $request)
    {
        if (session()->has('subscriptionId')) {
            // this is subscription id that comes from stripe in the redirect route
            $subscriptionId = session()->get('subscriptionId');
            // we forget it from the session cause we have that stored in variable above and only need it in this method
            session()->forget('subscriptionId');
            //if the subscription id coming in the url is the same to what we stored in the session which was what stripe gave us so this person truly did subscribed and paid
            return $request->subscription_id == $subscriptionId;
        }

        return false;
    }

    public function createIntent($value, $currency, $paymentMethod)
    {
        // in case of stripe the amount needs to be multiplied with 100 if its a decimal currency and the ones that dont need decimal position in currecny they are not needed to be multiplied by 100. so as stripe puts the decimal point on there own for currencies taht need it and doesnt do this for currencies that dont need this, we dont need to do the division like we did in paypal

        // NOTE: u can see i am passing the data in the first array which is query params the paypal works by sending data to form form_parmas which is in ConsumesExternalServices.php file line 36 but stripe works by sending data to query which is in ConsumesExternalServices.php file line 38. the teacher sent it in form params but query worked for me otherwise it said required arguments missing meaning it stripe didnt got the data
        return $this->makeRequest(
            'POST',
            '/v1/payment_intents',
            [
                'amount' => round($value * $this->resolveFactor($currency)),
                'currency' => strtolower($currency),
                'payment_method' => $paymentMethod,
                'confirmation_method' => 'manual'
            ]
        );

    }

    // as the confirmation method above is manual we need to confirm the payment here
    public function confirmPayment($paymentIntentId)
    {
        return $this->makeRequest(
            'POST',
            "/v1/payment_intents/{$paymentIntentId}/confirm"
        );
    }

    /**
     * This function is to create a customer in stripe as its needed to create a subscription in stripe
     */
    public function createCustomer($name, $email, $paymentMethod)
    {
        return $this->makeRequest(
            'POST',
            '/v1/customers',
            [
                'name'              => $name,
                'email'             => $email,
                'payment_method'    => $paymentMethod,
            ],
        );
    }

    // this function send request to stripe to create subscription
    public function createSubscription($customerId, $paymentMethod, $priceId)
    {
        return $this->makeRequest(
            'POST',
            '/v1/subscriptions',
            [
                'customer'                  => $customerId,
                'items'                     => [
                                                ['price' => $priceId]
                                            ],
                'default_payment_method'    => $paymentMethod,
                // we need Expanding Responses i.e expand (which gives us extra information in response as payment intent is not included in default reseponse of subscription creation) to get payment intent to get the id of the payment and the status so we could check if the status is require_action then the payment needs 3d authetntication
                // behind the scene the stripe also sees subscription payment but its intent is already confirmed so we just need to check for its status that wheather or not its status has require_action that needs 3d authetication
                'expand'                    => ['latest_invoice.payment_intent']
            ]
        );
    }

    public function resolveFactor($currency)
    {
        $zeroDecimalCurrency = ['JPY'];

        if(in_array(strtoupper($currency),$zeroDecimalCurrency))
        {
            return 1;
        }

        return 100;
    }
}