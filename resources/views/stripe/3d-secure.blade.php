@extends('layouts.app')

@push('head')
    <script src="https://js.stripe.com/v3/"></script>
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Complete the security steps') }}</div>

                <div class="card-body">
                    {{ __('You need to follow some additional steps from your bank to complete this payment. Lets go!') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')    
    <script>
        var stripe      = Stripe('{{ config('services.stripe.key') }}');
        stripe.handleCardAction("{{$clientSecret}}")
            .then(function(result){
                if(result.error){
                    window.location.replace("{{route('cancelled')}}");
                }else{
                    window.location.replace("{{route('approval')}}");
                }
            });
    </script>

@endpush
