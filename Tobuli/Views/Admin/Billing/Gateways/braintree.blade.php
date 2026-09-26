@extends('Admin.Billing.Gateways.layout')

@section('form-fields')
    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.merchant_id') }}</div>
        <div class="st-form__control">
            {!! Form::text('merchantId', settings('payments.braintree.merchantId'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.public_key') }}</div>
        <div class="st-form__control">
            {!! Form::text('publicKey', settings('payments.braintree.publicKey'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.private_key') }}</div>
        <div class="st-form__control">
            {!! Form::text('privateKey', settings('payments.braintree.privateKey'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.merchant_account_id') }}</div>
        <div class="st-form__control">
            {!! Form::text('merchant_account_id', settings('payments.braintree.merchant_account_id'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.environment') }}</div>
        <div class="st-form__control">
            {!! Form::select('environment', config('payments.braintree.environments'), settings('payments.braintree.environment'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.three_d_secure') }}</div>
        <div class="st-form__control">
            <label class="st-switch">
                {!! Form::hidden('3d_secure', 0) !!}
                {!! Form::checkbox('3d_secure', 1, settings('payments.braintree.3d_secure'), ['id' => 'braintree_3d_secure']) !!}
                <span class="st-switch__track"></span>
            </label>
            <label for="braintree_3d_secure" class="st-switch__label">
                3D secure
            </label>
        </div>
    </div>

    <div class="st-form__label">{{ trans('validation.attributes.braintree_plan_ids') }}</div>

    @if(empty($plans))
        <div class="st-note">
            <div class="st-note__icon"><i class="fas fa-info-circle"></i></div>
            <div class="st-note__text">
                <strong>{{ trans('front.plan_not_found') }}</strong>
            </div>
        </div>
    @else
        <?php $i = 0; ?>
        @foreach($plans as $plan_id => $plan)
            {!! Form::hidden("billing_plans[$i]", $plan_id) !!}
            <div class="st-form__row">
                <div class="st-form__label">{{ trans('front.plan') }}: {{ ucfirst($plan['title']) }}</div>
                <div class="st-form__control">
                    {!! Form::select("plan_ids[$i]", $braintree_plan_ids, $plan['braintree_id'], ['class' => 'form-control']) !!}
                </div>
            </div>
            <?php $i++ ?>
        @endforeach
    @endif
@overwrite
