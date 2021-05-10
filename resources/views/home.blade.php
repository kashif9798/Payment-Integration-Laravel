@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Make a Payment') }}</div>

                <div class="card-body">
                    <form action="{{route('pay')}}" method="post" id="paymentForm">
                        @csrf
                        <div class="row">
                            <div class="form-group col-6">
                                <label for="paymentAmount">How much you want to pay?</label>
                                <input
                                    type="number"
                                    min="5"
                                    step="0.01"
                                    class="form-control"
                                    name="value"
                                    id="paymentAmount" 
                                    value="{{mt_rand(500,100000) / 100}}"
                                    required
                                />
                                <small class="form-text text-muted">Use values upto two decimal postions, using a dot "."</small>
                            </div>
                            <div class="form-group col-6">
                                <label for="currency">Currency</label>
                                <select name="currency" id="currency" class="form-control">
                                    @foreach ( $currencies as $currency )
                                        <option value="{{$currency->iso}}">{{strtoupper($currency->iso)}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Select the desired payment platform</label>
                            <div class="form-group" id="toggler">
                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                    @foreach ( $paymentPlatforms as $paymentPlatform )
                                        <label 
                                            for="{{$paymentPlatform->name}}"
                                            class="btn btn-outline-secondary rounded m-2 p-1 label-platforms"
                                            data-target="#{{$paymentPlatform->name}}Collapse"
                                            data-toggle="collapse"
                                        >
                                            <input type="radio"
                                                name="payment_platform"
                                                id="{{$paymentPlatform->name}}"
                                                value="{{$paymentPlatform->id}}"
                                                class="w-0"
                                                required
                                            >
                                            <img
                                                class="img-thumbnail"
                                                src="{{asset($paymentPlatform->image)}}"
                                                alt="{{$paymentPlatform->name}}"
                                            >
                                        </label>
                                        
                                    @endforeach
                                </div>
                            </div>
                            @foreach ( $paymentPlatforms as $paymentPlatform )
                                <div class="form-group collapse" id="{{$paymentPlatform->name}}Collapse" data-parent="#toggler">
                                    @includeIf('components.'. strtolower($paymentPlatform->name) . '-collapse')
                                </div>
                            @endforeach
                            <div class="row">
                                <div class="col-12">
                                    <p>
                                        @if (! optional(auth()->user())->hasActiveSubscription())
                                            Would you like a discount everytime?
                                            <a href="{{route('subscribe.show')}}" class="btn btn-sm ml-2 btn-outline-primary">Subscribe</a> 
                                        @else 
                                            You get a <span class="font-weight-bold">10% off</span> as a part of your subscription (This will be applied in the checkout)
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block" id="payButton">Pay</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        jQuery(document).ready(function($) {
            $('.label-platforms').click( function(e) {
                $('.collapse').collapse('hide');
            });
        });
    </script>
@endpush
