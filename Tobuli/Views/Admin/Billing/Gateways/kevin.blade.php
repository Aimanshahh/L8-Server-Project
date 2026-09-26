@extends('Admin.Billing.Gateways.layout')

@section('form-fields')
    <div id="kevin">
        <div class="st-form__row">
            <div class="st-form__label">Client ID</div>
            <div class="st-form__control">
                {!! Form::text('client_id', settings('payments.kevin.client_id'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">Client secret</div>
            <div class="st-form__control">
                {!! Form::text('client_secret', settings('payments.kevin.client_secret'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">Endpoint secret</div>
            <div class="st-form__control">
                {!! Form::text('endpoint_secret', settings('payments.kevin.endpoint_secret'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">Currency</div>
            <div class="st-form__control">
                {!! Form::text('currency', settings('payments.kevin.currency'), ['class' => 'form-control', 'placeholder' => 'EUR']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">Language</div>
            <div class="st-form__control">
                {!! Form::text('language', settings('payments.kevin.language'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">Receiver name</div>
            <div class="st-form__control">
                {!! Form::text('receiver_name', settings('payments.kevin.receiver_name'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">Receiver IBAN</div>
            <div class="st-form__control">
                {!! Form::text('receiver_iban', settings('payments.kevin.receiver_iban'), ['class' => 'form-control']) !!}
            </div>
        </div>
    </div>
@overwrite
