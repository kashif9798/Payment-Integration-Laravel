@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Subscribe') }}</div>

                <div class="card-body">
                    <form action="{{route('subscribe.store')}}" method="post" id="paymentForm">
                        @csrf
                        {{-- Subscription Plans --}}
                        <div class="form-group">
                            <label>Select your Plan</label>
                            <div class="form-group">
                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                    @foreach ( $plans as $plan )
                                        <label 
                                            for="{{$plan->slug}}"
                                            class="btn btn-outline-primary rounded m-2 p-3"
                                        >
                                            <input type="radio"
                                                name="plan"
                                                id="{{$plan->slug}}"
                                                value="{{$plan->slug}}"
                                                class="w-0"
                                                required
                                            >
                                            <p class="h2 font-weight-bold text-capitalize">{{$plan->slug}}</p>
                                            <p class="h3 text-capitalize">{{$plan->visual_price}}</p>
                                        </label>
                                        
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Payment Platforms --}}
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
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block" id="payButton">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
