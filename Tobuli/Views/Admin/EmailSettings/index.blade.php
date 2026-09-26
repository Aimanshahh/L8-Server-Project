@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-settings-overrides.css') }}">
@stop

@section('content')
<div class="st-page">

    <div class="st-page__header">
        <div class="st-page__title-group">
            <div class="st-page__title-icon"><i class="fas fa-envelope-open-text"></i></div>
            <div>
                <h1 class="st-page__title">{{ trans('validation.attributes.email') }}</h1>
                <p class="st-page__subtitle">Configure outgoing email and SMTP delivery settings.</p>
            </div>
        </div>
    </div>

    @if (Session::has('errors'))
        <div class="alert alert-danger">
            <ul>
                @foreach (Session::get('errors')->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {!! Form::open(array('route' => 'admin.email_settings.save', 'method' => 'POST', 'class' => 'form form-horizontal st-form', 'id' => 'email_settings_form')) !!}

    <div class="st-grid">

        {{-- ------------------------------------------------ general settings --}}
        <div class="st-card">
            <div class="st-card__head">
                <span class="st-card__icon"><i class="fas fa-sliders"></i></span>
                <div class="st-card__headtext">
                    <span class="st-card__title">General email settings</span>
                    <span class="st-card__sub">These settings will be used for all outgoing emails.</span>
                </div>
            </div>

            <div class="st-card__body">
                <div class="form-group">
                    {!! Form::label('from_name', trans('validation.attributes.from_name')) !!}
                    {!! Form::text('from_name', isset($settings['from_name']) ? $settings['from_name'] : null, ['class' => 'form-control', 'placeholder' => 'e.g. GPS Server']) !!}
                </div>

                <div class="form-group">
                    {!! Form::label('noreply_email', trans('validation.attributes.noreply_email')) !!}
                    {!! Form::text('noreply_email', isset($settings['noreply_email']) ? $settings['noreply_email'] : null, ['class' => 'form-control', 'placeholder' => 'e.g. noreply@gpsserver.com']) !!}
                </div>

                <div class="form-group">
                    {!! Form::label('signature', trans('validation.attributes.signature')) !!}
                    {!! Form::textarea('signature', isset($settings['signature']) ? $settings['signature'] : null, ['class' => 'form-control', 'rows' => 3]) !!}
                </div>

                <div class="form-group">
                    {!! Form::label('provider', trans('validation.attributes.provider')) !!}
                    {!! Form::select('provider', $providers, isset($settings['provider']) ? $settings['provider'] : null, ['class' => 'form-control']) !!}
                </div>
            </div>
        </div>

        {{-- --------------------------------------------------- delivery provider --}}
        <div class="st-card">
            <div class="st-card__head">
                <span class="st-card__icon"><i class="fas fa-server"></i></span>
                <div class="st-card__headtext">
                    <span class="st-card__title">SMTP configuration</span>
                    <span class="st-card__sub">Configure SMTP settings for email delivery.</span>
                </div>
                <div class="st-card__headactions">
                    <button type="button" class="st-btn st-btn--ghost"
                            data-modal="test_email"
                            data-url="{{ route('admin.email_settings.test_email') }}">
                        <i class="fas fa-paper-plane"></i> {{ trans('front.test_email') }}
                    </button>
                </div>
            </div>

            <div class="st-card__body">
                <div class="form-group provider-sendgrid provider-postmark provider-mailgun provider-gpswoxmailer">
                    {!! Form::label('api_key', trans('validation.attributes.api_key')) !!}
                    {!! Form::text('api_key', isset($settings['api_key']) ? $settings['api_key'] : null, ['class' => 'form-control']) !!}
                </div>

                <div class="form-group provider-mailgun">
                    {!! Form::label('domain', trans('validation.attributes.domain')) !!}
                    {!! Form::text('domain', isset($settings['domain']) ? $settings['domain'] : null, ['class' => 'form-control']) !!}
                </div>

                <div class="form-group provider-mailgun">
                    {!! Form::label('region', trans('validation.attributes.region')) !!}
                    {!! Form::select('region', ['0' => trans('front.default'), 'eu' => 'EU'], $settings['region'] ?? null, ['class' => 'form-control']) !!}
                </div>

                <div class="form-group provider-smtp">
                    {!! Form::label('use_smtp_server', trans('validation.attributes.use_smtp_server')) !!}
                    {!! Form::select('use_smtp_server', ['0' => trans('global.no'), '1' => trans('global.yes')], isset($settings['use_smtp_server']) ? $settings['use_smtp_server'] : null, ['class' => 'form-control']) !!}
                </div>

                <div class="form-group provider-smtp">
                    {!! Form::label('smtp_server_host', trans('validation.attributes.smtp_server_host')) !!}
                    {!! Form::text('smtp_server_host', isset($settings['smtp_server_host']) ? $settings['smtp_server_host'] : null, ['class' => 'form-control', 'placeholder' => 'e.g. smtp.gmail.com']) !!}
                </div>

                <div class="form-group provider-smtp">
                    {!! Form::label('smtp_server_port', trans('validation.attributes.smtp_server_port')) !!}
                    {!! Form::text('smtp_server_port', isset($settings['smtp_server_port']) ? $settings['smtp_server_port'] : null, ['class' => 'form-control', 'placeholder' => 'e.g. 587']) !!}
                </div>

                <div class="form-group provider-smtp" data-disablable="#provider;hide-disable;smtp">
                    {!! Form::label('smtp_security', trans('validation.attributes.smtp_security')) !!}
                    {!! Form::select('smtp_security', ['0' => trans('global.no'), 'tls' => 'TLS', 'ssl' => 'SSL'], isset($settings['smtp_security']) ? $settings['smtp_security'] : null, ['class' => 'form-control']) !!}
                </div>

                <div class="form-group provider-smtp" data-disablable="#provider;hide-disable;smtp">
                    {!! Form::label('smtp_authentication', trans('validation.attributes.smtp_authentication')) !!}
                    {!! Form::select('smtp_authentication', ['1' => trans('global.yes'), '0' => trans('global.no')], isset($settings['smtp_authentication']) ? $settings['smtp_authentication'] : null, ['class' => 'form-control']) !!}
                </div>

                <div class="form-group provider-smtp">
                    {!! Form::label('smtp_username', trans('validation.attributes.smtp_username')) !!}
                    {!! Form::text('smtp_username', isset($settings['smtp_username']) ? $settings['smtp_username'] : null, ['class' => 'form-control', 'placeholder' => 'your@email.com']) !!}
                </div>

                <div class="form-group provider-smtp">
                    {!! Form::label('smtp_password', trans('validation.attributes.smtp_password')) !!}
                    {!! Form::password('smtp_password', ['class' => 'form-control', 'placeholder' => 'Leave blank to keep the current password']) !!}
                </div>
            </div>
        </div>
    </div>

    {!! Form::close() !!}

    <div class="st-note">
        <span class="st-note__icon"><i class="fas fa-info"></i></span>
        <div class="st-note__text">
            <strong>Email delivery status</strong>
            <span>Check your SMTP configuration to ensure emails are being delivered correctly.</span>
        </div>
    </div>

    <div class="st-savebar">
        <button type="submit" class="st-btn st-btn--primary" form="email_settings_form">
            <i class="fas fa-floppy-disk"></i> {{ trans('global.save') }}
        </button>
    </div>
</div>
@stop

@section('javascript')
    <script>
        $(document).ready(function() {
            $('select[name="use_smtp_server"]').on('change', function() {
                var val = $(this).val();
                if (val == 0)
                    $('input[name^="smtp_"], select[name^="smtp_"]').attr('disabled', 'disabled');
                else
                    $('input[name^="smtp_"], select[name^="smtp_"]').removeAttr('disabled', 'disabled');

                $('select[name^="smtp_"]').selectpicker('refresh');
            });
            $('select[name="use_smtp_server"]').trigger('change');


            $('select[name="provider"]').on('change', function() {
                $('div[class*="provider-"]').hide();
                $('.provider-' + $(this).val()).show();
            });
            $('select[name="provider"]').trigger('change');
        });
    </script>
@stop
