@extends('Admin.Layouts.default')

@section('content')
<div class="al-page al-formpage">

    @if (Session::has('errors'))
        <div class="alert alert-danger">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach (Session::get('errors')->all() as $error)
                    <li>{!! $error !!}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="al-page__header">
        <div class="al-page__title-group">
            <div class="al-page__title-icon"><i class="fas fa-comment-sms"></i></div>
            <div>
                <h1 class="al-page__title">{!! trans('front.sms_gateway') !!}</h1>
                <p class="al-page__subtitle">Send alerts and notifications through your own SMS provider</p>
            </div>
        </div>

        <div class="al-page__actions">
            <button type="button" class="al-ghost"
                    data-url="{!! route('sms_gateway.test_sms') !!}"
                    data-modal="send_test_sms">
                <i class="fas fa-paper-plane"></i> {!! trans('front.send_test_sms') !!}
            </button>
        </div>
    </div>

    <div class="al-card">
        {!! Form::open(['route' => 'admin.sms_gateway.store', 'method' => 'POST']) !!}

        <div class="al-card__body" id="setup-form-sms-gateway">

            <div class="al-switch-row">
                <label class="al-switch">
                    {!! Form::checkbox('enabled', 1, \Illuminate\Support\Arr::get($params, 'enabled')) !!}
                    <span class="al-switch__track"></span>
                    <span class="al-switch__label">{!! trans('front.enable_sms_gateway') !!}</span>
                </label>

                <label class="al-switch">
                    {!! Form::checkbox('use_as_system_gateway', 1, \Illuminate\Support\Arr::get($params, 'use_as_system_gateway')) !!}
                    <span class="al-switch__track"></span>
                    <span class="al-switch__label">{!! trans('front.use_as_system_gateway') !!}</span>
                </label>
            </div>

            <div class="al-form-row">
                <div class="al-form-field">
                    {!! Form::label('request_method', trans('validation.attributes.request_method')) !!}
                    {!! Form::select('request_method', config('sms.gateways'), \Illuminate\Support\Arr::get($params, 'request_method'), ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="request-method request-method-post">
                <div class="al-form-row">
                    <div class="al-form-field">
                        {!! Form::label('encoding', trans('validation.attributes.encoding')) !!}
                        {!! Form::select('encoding', config('sms.encodings'), \Illuminate\Support\Arr::get($params, 'encoding'), ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>

            <div class="request-method request-method-get request-method-post">

                <div class="al-form-row">
                    <div class="al-form-field">
                        {!! Form::label('authentication', trans('validation.attributes.authentication')) !!}
                        {!! Form::select('authentication', config('sms.authentications'), \Illuminate\Support\Arr::get($params, 'authentication'), ['class' => 'form-control']) !!}
                    </div>

                    <div class="al-form-field sms-gateway-auth">
                        {!! Form::label('username', trans('validation.attributes.username')) !!}
                        {!! Form::text('username', \Illuminate\Support\Arr::get($params, 'username'), ['class' => 'form-control']) !!}
                    </div>

                    <div class="al-form-field sms-gateway-auth">
                        {!! Form::label('password', trans('validation.attributes.password')) !!}
                        {{-- a plain input keeps the stored password: the form builder
                             always renders its own (empty) value for type=password --}}
                        <div class="al-input-reveal">
                            <input type="password"
                                   name="password"
                                   class="form-control"
                                   value="{{ \Illuminate\Support\Arr::get($params, 'password') }}"
                                   autocomplete="new-password">
                            <button type="button" class="al-input-reveal__toggle" title="Show password" aria-label="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="al-form-row">
                    <div class="al-form-field al-form-field--wide">
                        {!! Form::label('custom_headers', trans('validation.attributes.sms_gateway_headers')) !!}
                        {!! Form::textarea('custom_headers', \Illuminate\Support\Arr::get($params, 'custom_headers'), ['class' => 'form-control', 'rows' => 3]) !!}
                        <span class="al-hint">(e.g. Accept: text/plain; Accept-Language: en-US;)</span>
                    </div>
                </div>

                <div class="al-form-row">
                    <div class="al-form-field al-form-field--wide">
                        {!! Form::label('sms_gateway_url', trans('validation.attributes.sms_gateway_url')) !!}
                        {!! Form::textarea('sms_gateway_url', \Illuminate\Support\Arr::get($params, 'sms_gateway_url'), ['class' => 'form-control', 'rows' => 3]) !!}
                        <span class="al-hint al-url-check"
                              data-missing="{!! trans('front.sms_gateway_url_must_containt') !!}"
                              data-ok="The URL contains the required variables."></span>
                    </div>
                </div>

                <div class="al-callout">
                    <i class="fas fa-circle-info"></i>
                    <span>{!! trans('front.sms_gateway_text') !!}</span>
                </div>
            </div>

            <div class="request-method request-method-app">
                <div class="al-form-row">
                    <div class="al-form-field">
                        {!! Form::label('user_id', trans('validation.app_gateway_admin_settings')) !!}
                        {!! Form::select('user_id', $users, \Illuminate\Support\Arr::get($params, 'user_id'), ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>

            <div class="request-method request-method-plivo">
                <div class="al-form-row">
                    <div class="al-form-field">
                        {!! Form::label('auth_id', trans('validation.attributes.auth_id')) !!}
                        {!! Form::text('auth_id', \Illuminate\Support\Arr::get($params, 'auth_id'), ['class' => 'form-control']) !!}
                    </div>

                    <div class="al-form-field">
                        {!! Form::label('auth_token', trans('validation.attributes.auth_token')) !!}
                        {!! Form::text('auth_token', \Illuminate\Support\Arr::get($params, 'auth_token'), ['class' => 'form-control']) !!}
                    </div>

                    <div class="al-form-field">
                        {!! Form::label('senders_phone', trans('validation.attributes.senders_phone')) !!}
                        {!! Form::text('senders_phone', \Illuminate\Support\Arr::get($params, 'senders_phone'), ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>

        </div>

        <div class="al-card__foot">
            <button type="submit" class="al-add">
                <i class="fas fa-save"></i>
                {{ trans('global.save') }}
            </button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
@stop

@section('javascript')
    <script>
        var $sms_gateway_container = $('#setup-form-sms-gateway');

        $sms_gateway_container.on('change', 'select[name="request_method"]', function () {
            dd('select[name="request_method"]');
            $('.request-method', $sms_gateway_container).hide();
            $('.request-method-' + $(this).val(), $sms_gateway_container).show();
        });

        $sms_gateway_container.on('change', 'select[name="authentication"]', function () {
            if ($(this).val() == 1)
                $('.sms-gateway-auth', $sms_gateway_container).show();
            else
                $('.sms-gateway-auth', $sms_gateway_container).hide();
        });

        $('select[name="request_method"]', $sms_gateway_container).trigger('change');
        $('select[name="authentication"]', $sms_gateway_container).trigger('change');

        /* -------- password is only shown when it is asked for -------- */
        $sms_gateway_container.on('click', '.al-input-reveal__toggle', function () {
            var $button = $(this);
            var $input = $button.siblings('input');
            var reveal = $input.attr('type') === 'password';

            $input.attr('type', reveal ? 'text' : 'password');
            $button.find('i').attr('class', reveal ? 'fas fa-eye-slash' : 'fas fa-eye');
            $button.attr('title', reveal ? 'Hide password' : 'Show password');
        });

        /* -------- the gateway URL is checked as it is typed -------- */
        function validateGatewayUrl() {
            var $input = $sms_gateway_container.find('textarea[name="sms_gateway_url"]');
            var $hint = $input.closest('.al-form-field').find('.al-url-check');
            var value = $.trim($input.val() || '');

            $hint.removeClass('is-ok is-error');

            if (value === '') {
                $hint.text('');
                return;
            }

            var complete = value.indexOf('%NUMBER%') !== -1 && value.indexOf('%MESSAGE%') !== -1;

            $hint
                .addClass(complete ? 'is-ok' : 'is-error')
                .text(complete ? $hint.attr('data-ok') : $hint.attr('data-missing'));
        }

        $sms_gateway_container.on('input', 'textarea[name="sms_gateway_url"]', validateGatewayUrl);

        $(function () {
            validateGatewayUrl();
        });
    </script>
@stop
