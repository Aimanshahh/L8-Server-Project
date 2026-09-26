@extends('Admin.Billing.Gateways.layout')

@section('form-fields')
    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.client_id') }}</div>
        <div class="st-form__control">
            {!! Form::text('client_id', settings('payments.paypal.client_id'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.secret') }}</div>
        <div class="st-form__control">
            {!! Form::text('secret', settings('payments.paypal.secret'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.payment_name') }}</div>
        <div class="st-form__control">
            {!! Form::text('payment_name', settings('payments.paypal.payment_name'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.currency') }}</div>
        <div class="st-form__control">
            {!! Form::text('currency', settings('payments.paypal.currency'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.environment') }}</div>
        <div class="st-form__control">
            {!! Form::select('mode', config('payments.paypal.environments'), settings('payments.paypal.mode'), ['class' => 'form-control']) !!}
        </div>
    </div>
@overwrite
