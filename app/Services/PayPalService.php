<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\Request;

class PayPalService
{
    use ConsumesExternalServices;

    protected $baseUri;

    protected $clientId;

    protected $clientSecret;

    protected $plans;

    public function __construct(){
        $this->baseUri      = config('services.paypal.base_uri');
        $this->clientId     = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
        $this->plans        = config('services.paypal.plans');
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
        // the basic access token is made up from username and password which is client id and client screent key in this case respectively which should be encoded in base64
        $credentials = base64_encode("{$this->clientId}:{$this->clientSecret}");
        // returning the basic access token
        return "Basic {$credentials}";
    }

    /**
     * this will find the approve payments in a single transaction from our website and redirect us to the approve route we defined createOrder() method in this class
     */
    public function handlePayment(Request $request)
    {
        // create an order of by our request data
        $order = $this->createOrder($request->value, $request->currency);
        // convert the response into a collection meanng the resonse is already a collection of links so convert them to laravel collection
        $orderLinks = collect($order->links);
        // find approve links in them the first one u find
        $approve = $orderLinks->where('rel','approve')->first();
        // and redirect us to the approve link we defined in the createOrder() method

        // we save id in the session because we need this in handleApproval() method belows
        session()->put('approvalId',$order->id);

        return redirect($approve->href);
    }

    /**
     * This method handles after the payments gets approved
     */
    public function handleApproval()
    {
        if(session()->has('approvalId')){

            $approvalId = session()->get('approvalId');

            $payment    = $this->capturePayment($approvalId);

            $name       = $payment->payer->name->given_name;

            $payment    = $payment->purchase_units[0]->payments->captures[0]->amount;

            $amount     = $payment->value;

            $currency   = $payment->currency_code;

            return redirect()->route('home')->withSuccess(['payment' => "Thanks, {$name}. We recieved your {$amount} {$currency} payment"]);
        }   

        return redirect()->route('home')->withErrors('We cannot capture the payment, Try Again please');
    }

    public function handleSubscription(Request $request)
    {
        // create a subscription of our request data
        $subscription = $this->createSubscription(
            $request->plan,
            $request->user()->name,
            $request->user()->email
        );
        // convert the response into a collection meanng the resonse is already a collection of links so convert them to laravel collection
        $subscriptionLinks = collect($subscription->links);
        // find approve links in them the first one u find
        $approve = $subscriptionLinks->where('rel','approve')->first();
        // and redirect us to the approve link we defined in the createSubscription() method

        // we save id in the session because we need this in validateSubscription() method below to verify it
        session()->put('subscriptionId',$subscription->id);

        return redirect($approve->href);

    }

    public function validateSubscription(Request $request)
    {
        if (session()->has('subscriptionId')) {
            // this is subscription id that paypals gives
            $subscriptionId = session()->get('subscriptionId');
            // we forget it from the session cause we have that stored in variable above and only need it in this method
            session()->forget('subscriptionId');
            //if the subscription id coming in the url is the same we stored in the session which was what paypal gave us so this person truly did subscribed and paid
            return $request->subscription_id == $subscriptionId;
        }

        return false;
    }

    public function createOrder($value, $currency)
    {
        return $this->makeRequest(
            'POST',
            '/v2/checkout/orders',
            [],
            [
                // Look at paypal api docs, this means the merchent immediately capture payments
                'intent'            => 'CAPTURE',
                // remember that this will have only 0 index below
                'purchase_units'    => [
                    '0'                 => [
                        'amount'            => [
                            'currency_code'     => strtoupper($currency),
                            //This is like currencies japanes yen doesnt support decimal values so it will round those currencies while leaving the others where decimal point matters. this logic is in resolveFactor method below
                            'value'             => round($value * $factor = $this->resolveFactor($currency)) / $factor
                        ]
                    ]
                ],
                // The content on how the paypal payment page will appear
                'application_context' => [
                    'brand_name'            => config('app.name'),
                    'shipping_preference'   => 'NO_SHIPPING',
                    'user_action'           => 'PAY_NOW',
                    'return_url'            => route('approval'),
                    'cancel_url'            => route('cancelled'),
                ]
            ],
            [],
            // the body above will be converted to json by guzzlehttp as we want to send a json request as thats what paypal requires
            $isJsonRequest = true
        );
    }

    public function capturePayment($approvalId)
    {
        return $this->makeRequest(
            'POST',
            "/v2/checkout/orders/{$approvalId}/capture",
            [],
            [],
            [
                'Content-Type' => 'application/json'
            ],

        );
    }

    /**
     * This function actually creates the subscription meaning this function makes the request which will be used in handleSubscription above.
     * we are using $planSlug instead of the plan id because we store the plan id in a slug in services.php under config that matches with the input value of the each plan slug 
     */
    public function createSubscription($planSlug, $name, $email)
    {
        return $this->makeRequest(
            'POST',
            '/v1/billing/subscriptions',
            [],
            [
                'plan_id'       => $this->plans[$planSlug],
                'subscriber'    => [
                    'subscriber'    => [
                        'name'          => [
                            'given_name'     => $name
                        ],
                        'email_address' => $email
                    ]
                ],
                // The content on how the paypal payment page will appear
                'application_context' => [
                    'brand_name'            => config('app.name'),
                    'shipping_preference'   => 'NO_SHIPPING',
                    'user_action'           => 'SUBSCRIBE_NOW',
                    'return_url'            => route('subscribe.approval', ['plan' => $planSlug]),
                    'cancel_url'            => route('subscribe.cancelled'),
                ]
            ],
            [],
            // the body above will be converted to json by guzzlehttp as we want to send a json request as thats what paypal requires
            $isJsonRequest = true
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