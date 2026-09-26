@extends('Admin.Billing.Gateways.layout')

@section('form-fields')
    <div id="twocheckout">
        <div class="st-form__row">
            <div class="st-form__label">API URL</div>
            <div class="st-form__control">
                {!! Form::text('api_url', settings('payments.twocheckout.api_url'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">URL</div>
            <div class="st-form__control">
                {!! Form::text('front_url', settings('payments.twocheckout.front_url'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">{{ trans('validation.attributes.merchant_code') }}</div>
            <div class="st-form__control">
                {!! Form::text('merchant_code', settings('payments.twocheckout.merchant_code'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">{{ trans('validation.attributes.secret_key') }}</div>
            <div class="st-form__control">
                {!! Form::text('secret_key', settings('payments.twocheckout.secret_key'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">Demo mode</div>
            <div class="st-form__control">
                {!! Form::hidden('demo_mode', 0) !!}
                <label class="st-switch">
                    {!! Form::checkbox('demo_mode', 1, settings('payments.twocheckout.demo_mode'), ['id' => 'twocheckout_demo_mode']) !!}
                    <span class="st-switch__track"></span>
                </label>
                <label for="twocheckout_demo_mode" class="st-switch__label">Sandbox instead of live</label>
            </div>
        </div>
    </div>
@overwrite
