@php
    /* $gateway comes from the including loop, $active is its current
       payments.gateways.<gateway> flag (1 = offered to customers). */
    $gwLabel  = ucwords(str_replace('_', ' ', $gateway));
    $gwConfig = config('payments.' . $gateway, []);
    $gwLogo   = ! empty($gwConfig['logo']) ? $gwConfig['logo'] : null;
    $gwDesc   = ! empty($gwConfig['description']) ? $gwConfig['description'] : $gateway;
@endphp

<details class="st-acc" data-gateway="{{ $gateway }}">
    <summary class="st-acc__head">
        <span class="st-acc__chev"><i class="fas fa-chevron-right"></i></span>

        @if ($gwLogo)
            <img class="st-gwlogo" src="{{ asset($gwLogo) }}" alt="">
        @endif

        <span class="st-acc__text">
            <span class="st-acc__title">{{ $gwLabel }}</span>
            <span class="st-acc__sub">{{ $gwDesc }}</span>
        </span>

        <span class="st-acc__meta">
            <span class="st-pill {{ $active ? 'st-pill--ok' : '' }}" data-gw-pill>
                {{ $active ? trans('global.active') : trans('global.inactive') }}
            </span>
            <button type="button" class="st-btn st-btn--ghost st-btn--sm"
                    data-modal="gateway_info"
                    data-url="{{ route('payments.gateway_info', ['gateway' => $gateway]) }}">
                <i class="fas fa-circle-info"></i> {{ trans('global.info') }}
            </button>
        </span>
    </summary>

    <div class="st-acc__body">
        {!! Form::open([
            'route'  => ['admin.billing.gateways.config_store', 'gateway' => $gateway],
            'method' => 'POST',
            'class'  => 'st-form form-horizontal',
            'id'     => $gateway,
        ]) !!}

        <div class="st-form__row">
            <div class="st-form__label">{{ trans('validation.attributes.active') }}</div>
            <div class="st-form__control">
                <label class="st-switch">
                    {!! Form::checkbox('active', 1, ! empty($active), ['id' => $gateway . '_active']) !!}
                    <span class="st-switch__track"></span>
                </label>
                <label for="{{ $gateway . '_active' }}" class="st-switch__label" data-gw-switch-label>
                    {{ $active ? trans('global.active') : trans('global.inactive') }}
                </label>
            </div>
        </div>

        @yield('form-fields')

        <div class="st-form__foot">
            <span class="st-gwtest" data-gw-testresult></span>
            <button type="button" class="st-btn st-btn--ghost" data-gw-test
                    data-test-url="{{ route('payments.config_check', ['gateway' => $gateway]) }}">
                <i class="fas fa-flask"></i> {{ trans('validation.attributes.test_config') }}
            </button>
            <button type="submit" class="st-btn st-btn--primary">
                <i class="fas fa-check"></i> {{ trans('global.save') }}
            </button>
        </div>

        {!! Form::close() !!}
    </div>
</details>
