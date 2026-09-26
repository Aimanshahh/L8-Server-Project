@extends('Admin.Billing.Gateways.layout')

@section('form-fields')
    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.public_key') }}</div>
        <div class="st-form__control">
            {!! Form::text('public_key', settings('payments.stripe.public_key'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.secret_key') }}</div>
        <div class="st-form__control">
            {!! Form::text('secret_key', settings('payments.stripe.secret_key'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.currency') }}</div>
        <div class="st-form__control">
            {!! Form::text('currency', settings('payments.stripe.currency'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.one_time_payment') }}</div>
        <div class="st-form__control">
            <label class="st-switch">
                {!! Form::hidden('one_time_payment', 0) !!}
                {!! Form::checkbox('one_time_payment', 1, settings('payments.stripe.one_time_payment'), ['id' => 'stripe_one_time_payment']) !!}
                <span class="st-switch__track"></span>
            </label>
            <label for="stripe_one_time_payment" class="st-switch__label">
                {{ trans('validation.attributes.one_time_payment') }}
            </label>
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.webhook_key') }}</div>
        <div class="st-form__control">
            {!! Form::text('webhook_key', settings('payments.stripe.webhook_key'), ['class' => 'form-control']) !!}
        </div>
    </div>
@overwrite
