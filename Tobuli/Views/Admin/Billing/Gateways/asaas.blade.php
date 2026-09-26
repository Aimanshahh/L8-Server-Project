@extends('Admin.Billing.Gateways.layout')

@section('form-fields')
    <div id="asaas">
        <div class="st-form__row">
            <div class="st-form__label">{{ trans('validation.attributes.environment') }}</div>
            <div class="st-form__control">
                {!! Form::select('environment', config('payments.asaas.environments'), settings('payments.asaas.environment'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">API key</div>
            <div class="st-form__control">
                {!! Form::text('api_key', settings('payments.asaas.api_key'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">Access token (webhook)</div>
            <div class="st-form__control">
                {!! Form::text('access_token', settings('payments.asaas.access_token'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">Currency</div>
            <div class="st-form__control">
                {!! Form::text('currency', 'R$', ['class' => 'form-control', 'disabled' => 'disabled']) !!}
            </div>
        </div>
    </div>
@overwrite
