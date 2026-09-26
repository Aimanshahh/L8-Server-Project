@extends('Admin.Billing.Gateways.layout')

@section('form-fields')
<div id="paystack">
        <div class="form-group">
            {!! Form::label('paystack_api_url', 'API URL', ['class' => 'col-xs-12 col-sm-4 control-label']) !!}
            <div class="col-xs-12 col-sm-8">
                {!! Form::text('paystack_api_url', settings('payments.paystack.api_url'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('paystack_merchant_code', trans('validation.attributes.merchant_code'), ['class' => 'col-xs-12 col-sm-4 control-label']) !!}
            <div class="col-xs-12 col-sm-8">
                {!! Form::text('paystack_merchant_code', settings('payments.paystack.merchant_code'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('paystack_secret_key', trans('validation.attributes.secret_key'), ['class' => 'col-xs-12 col-sm-4 control-label']) !!}
            <div class="col-xs-12 col-sm-8">
                {!! Form::text('paystack_secret_key', settings('payments.paystack.secret_key'), ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label(null, null, ['class' => 'col-xs-12 col-sm-4 control-label']) !!}
            <div class="col-xs-12 col-sm-8">
                {!! Form::hidden('paystack_demo_mode', 0) !!}
                <div class="checkbox">
                    {!! Form::checkbox('paystack_demo_mode', 1, settings('payments.paystack.demo_mode')) !!}
                    {!! Form::label('paystack_demo_mode', 'Demo mode') !!}
                </div>
            </div>
        </div>
    </div>

@overwrite