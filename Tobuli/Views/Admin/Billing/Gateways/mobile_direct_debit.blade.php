@extends('Admin.Billing.Gateways.layout')

@section('form-fields')
    <div class="st-form__row">
        <div class="st-form__label">URL</div>
        <div class="st-form__control">
            {!! Form::text('url', settings('payments.mobile_direct_debit.url'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">API key</div>
        <div class="st-form__control">
            {!! Form::text('api_key', settings('payments.mobile_direct_debit.api_key'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">Merchant ID</div>
        <div class="st-form__control">
            {!! Form::text('merchant_id', settings('payments.mobile_direct_debit.merchant_id'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">Product ID</div>
        <div class="st-form__control">
            {!! Form::text('product_id', settings('payments.mobile_direct_debit.product_id'), ['class' => 'form-control']) !!}
        </div>
    </div>
@overwrite
