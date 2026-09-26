@extends('Admin.Billing.Gateways.layout')

@section('form-fields')
    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.project_id') }}</div>
        <div class="st-form__control">
            {!! Form::text('project_id', settings('payments.paysera.project_id'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.project_psw') }}</div>
        <div class="st-form__control">
            {!! Form::text('project_psw', settings('payments.paysera.project_psw'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.verify_id') }}</div>
        <div class="st-form__control">
            {!! Form::text('verify_id', settings('payments.paysera.verify_id'), ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.currency') }}</div>
        <div class="st-form__control">
            {!! Form::text('currency', settings('payments.paysera.currency'), ['class' => 'form-control', 'placeholder' => 'EUR']) !!}
        </div>
    </div>

    <div class="st-form__row">
        <div class="st-form__label">{{ trans('validation.attributes.environment') }}</div>
        <div class="st-form__control">
            {!! Form::select('environment', config('payments.paysera.environments'), settings('payments.paysera.environment'), ['class' => 'form-control']) !!}
        </div>
    </div>
@overwrite
