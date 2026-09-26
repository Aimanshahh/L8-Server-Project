@extends('Admin.Billing.Gateways.layout')

@section('form-fields')
    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.master_key') }}</div>
        <div class="st-form__control">
            {!! Form::text('master_key', settings('payments.paydunya.master_key'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.public_key') }}</div>
        <div class="st-form__control">
            {!! Form::text('public_key', settings('payments.paydunya.public_key'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.private_key') }}</div>
        <div class="st-form__control">
            {!! Form::text('private_key', settings('payments.paydunya.private_key'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.token') }}</div>
        <div class="st-form__control">
            {!! Form::text('token', settings('payments.paydunya.token'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.payment_name') }}</div>
        <div class="st-form__control">
            {!! Form::text('payment_name', settings('payments.paydunya.payment_name'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.environment') }}</div>
        <div class="st-form__control">
            {!! Form::select('mode', config('payments.paydunya.environments'), settings('payments.paydunya.mode'), ['class' => 'form-control']) !!}
        </div>
    </div>
@overwrite
