@push('head')
    <script src="https://js.stripe.com/v3/"></script>
@endpush
<label class="mt-3" for="cardElement">
    Card Details:
</label>

<div id="cardElement">
    
</div>

<small class="form-text text-muted" id="cardErrors" role="alert"></small>

{{-- to store the token as the stripe requires token that is developed from the form details to autheticate the payment --}}
<input type="hidden" name="payment_method" id="paymentMethod">

@push('scripts')    
    <script>
        // this code below uses the Stripe function linked above to create a form in cardElement Id div above
        var stripe      = Stripe('{{ config('services.stripe.key') }}');
        var elements    = stripe.elements( { locale: 'en'} );
        var cardElement = elements.create('card');
        cardElement.mount('#cardElement');
    </script>

    <script>
        var form        = document.getElementById('paymentForm');
        var payButton   = document.getElementById('payButton');

        payButton.addEventListener('click', async(e) => {
            // this if condition is for to do this only when stript paymentplatform is selected.
            // and how that works is that we are checking payment_platform input field with $paymentPlatform->id as this component will only be rended when $paymentPlatform->id is equals to of stripe thats why we are checkign this with $paymentPlatform->id
            if(form.elements.payment_platform.value == "{{ $paymentPlatform->id }}")
            {
                e.preventDefault();
                //{ paymentMethod, error } means it may have a paymentMethod (basically that token) or a possible error
                // await is a promise keyword in js i think to sent basically a request to stripe createPaymentMethod
                //createPaymentMethod receieves serveral arguments first one is type of element we need to create and that will be a card in our situation, second is the card eleement where we are taking the information which is cardElement variable on line 22 and next we can specify an object with different options 
                // 
                // var { paymentMethod, error } = await stripe.createPaymentMethod(
                //     'card', cardElement, {
                //         billing_details: {
                //             "name"  : "{{auth()->user()->name}}",
                //             "email" : "{{auth()->user()->email}}"
                //         } 
                //     }
                // );

                const { paymentMethod, error } = await stripe.createPaymentMethod(
                    'card', cardElement, {
                        billing_details: {
                            "name": "{{ auth()->user()->name }}",
                            "email": "{{ auth()->user()->email }}"
                        }
                    }
                );

                // at this point either we have obtained the token i.e paymentMethod or the error
                // if we obtained the error
                if(error){
                    var displayError            = document.getElementById('cardErrors');
                    displayError.textContent    = error.message;
                }else{
                    // if the token gets returned as response i.e paymentMethod
                    var tokenInput              = document.getElementById('paymentMethod');
                    // store the response that is paymentMethod in it its id in the input field
                    tokenInput.value            = paymentMethod.id;
                    // submit the form and form is the variable is created
                    form.submit();
                }
            }
        });
    </script>
@endpush
