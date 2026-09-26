@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-settings-overrides.css') }}">
@stop

@section('content')
@php
    /* $gateways is settings('payments.gateways') narrowed to the providers whose
       config is installed: [ 'paypal' => 1|0, ... ] — the value is the live flag
       that Frontend\PaymentsController reads at checkout. */
    $totalProviders  = count($gateways);
    $activeProviders = count(array_filter($gateways));
@endphp

<div class="st-page">

    <div class="st-page__header">
        <div class="st-page__title-group">
            <div class="st-page__title-icon"><i class="fas fa-credit-card"></i></div>
            <div>
                <h1 class="st-page__title">{{ trans('admin.billing_gateways') }}</h1>
                <p class="st-page__subtitle">Connect payment providers for plans, subscriptions and one-time payments.</p>
            </div>
        </div>
        <a href="javascript:" class="st-btn st-btn--primary"
           data-modal="billing_plans_create"
           data-url="{{ route('admin.billing.create') }}">
            <i class="fas fa-plus"></i> {{ trans('admin.add_new') }}
        </a>
    </div>

    @if (Session::has('billing_errors'))
        <div class="alert alert-danger">
            <ul>
                @foreach (Session::get('billing_errors')->all() as $error)
                    <li>{!! $error !!}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (Session::has('billing_success'))
        <div class="alert alert-success">
            {!! Session::get('billing_success') !!}
        </div>
    @endif

    <div class="st-stack">

        {{-- ---------------------------------------------------- which providers --}}
        <div class="st-card">
            <div class="st-card__head">
                <div class="st-card__icon"><i class="fas fa-wallet"></i></div>
                <div class="st-card__headtext">
                    <span class="st-card__title">{{ trans('admin.payment_methods') }}</span>
                    <span class="st-card__sub">Turn a provider on to offer it to customers at checkout. Changes save immediately.</span>
                </div>
                <span class="st-acc__meta">
                    <span class="st-save-state" id="gateways-save-state"></span>
                    <span class="st-count" id="gateways-active-count">
                        {{ $activeProviders }} / {{ $totalProviders }}
                    </span>
                </span>
            </div>

            <div class="st-card__body">
                @if ($totalProviders)
                    <div class="st-checkgrid">
                        @foreach ($gateways as $gateway => $enabled)
                            @php
                                /* config('payments.<gw>.visible') is the shipped default; a provider
                                   left off there is not offered by this build at all. */
                                $available = (bool) config('payments.' . $gateway . '.visible', 0);
                                $logo      = config('payments.' . $gateway . '.logo');
                            @endphp
                            <label class="st-checkitem {{ $enabled ? 'is-on' : '' }} {{ $available ? '' : 'is-locked' }}"
                                   data-gw-tile="{{ $gateway }}">
                                <input type="checkbox"
                                       name="visible[]"
                                       value="{{ $gateway }}"
                                       data-gateway="{{ $gateway }}"
                                       @if ($enabled) checked @endif
                                       @if ( ! $available) disabled @endif>
                                @if ($logo)
                                    <img class="st-gwlogo" src="{{ asset($logo) }}" alt="">
                                @endif
                                <span class="st-checkitem__text">
                                    {{ ucwords(str_replace('_', ' ', $gateway)) }}
                                </span>
                                @if ( ! $available)
                                    <span class="st-checkitem__lock">Not in this build</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="st-empty">No payment providers are installed in this build.</div>
                @endif
            </div>
        </div>

        {{-- ------------------------------------------------- provider credentials --}}
        <div class="st-card">
            <div class="st-card__head">
                <div class="st-card__icon"><i class="fas fa-cog"></i></div>
                <div class="st-card__headtext">
                    <span class="st-card__title">{{ trans('admin.payment_config') }}</span>
                    <span class="st-card__sub">Credentials and options for each provider.</span>
                </div>
                <button type="button" class="st-acc-toggle" id="gateways-acc-toggle">Expand all</button>
            </div>

            <div class="st-card__body" id="gateway-config">
                @foreach ($gateways as $gateway => $enabled)
                    @include('Admin.Billing.Gateways.' . $gateway, ['active' => $enabled])
                @endforeach
            </div>
        </div>

    </div>
</div>
@stop

@section('javascript')
<script>
    $(document).ready(function () {
        var csrf      = $('meta[name="csrf-token"]').attr('content');
        var visibleUrl= '{{ route('admin.billing.gateways.visible') }}';
        var $state    = $('#gateways-save-state');
        var $count    = $('#gateways-active-count');
        var total     = {{ $totalProviders }};

        function flash(kind, text) {
            $state.removeClass('is-saving is-saved is-failed').text(text).addClass(kind);
            clearTimeout($state.data('t'));
            $state.data('t', setTimeout(function () {
                $state.removeClass(kind).text('');
            }, 2500));
        }

        function refreshCount() {
            var n = $('#gateway-config, .st-checkgrid')
                        .find('input[name="visible[]"]:checked').length;
            $count.text(n + ' / ' + total);
        }

        /* keep a provider's accordion pill + Active switch in step with its tile */
        function syncGateway(gateway, on) {
            var $acc = $('#gateway-config').find('details[data-gateway="' + gateway + '"]');

            $acc.find('[data-gw-pill]')
                .toggleClass('st-pill--ok', on)
                .text(on ? '{{ trans('global.active') }}' : '{{ trans('global.inactive') }}');

            $acc.find('[data-gw-switch-label]')
                .text(on ? '{{ trans('global.active') }}' : '{{ trans('global.inactive') }}');

            $acc.find('input[name="active"][type="checkbox"]').prop('checked', on);

            $('.st-checkitem[data-gw-tile="' + gateway + '"]')
                .toggleClass('is-on', on)
                .find('input[name="visible[]"]').prop('checked', on);
        }

        /* the info button lives in a <summary>; cancel the toggle but let the
           app's document-level [data-modal] delegate still open the modal */
        $('#gateway-config').on('click', 'summary .st-btn', function (e) {
            e.preventDefault();
        });

        /* ------------------------------------------------ enable / disable a provider */
        $('.st-checkgrid').on('change', 'input[name="visible[]"]', function () {
            var on  = $(this).is(':checked');
            var gw  = $(this).data('gateway');

            $(this).closest('.st-checkitem').toggleClass('is-on', on);
            syncGateway(gw, on);
            refreshCount();

            var ids = [];
            $('.st-checkgrid input[name="visible[]"]:checked').each(function () {
                ids.push($(this).val());
            });

            $state.removeClass('is-saved is-failed').text('Saving…').addClass('is-saving');
            $.post(visibleUrl, { _token: csrf, visible: ids })
                .done(function () { flash('is-saved', 'Saved'); })
                .fail(function () { flash('is-failed', 'Error'); });
        });

        /* --------------------------------------------------- expand / collapse all */
        $('#gateways-acc-toggle').on('click', function () {
            var $btn  = $(this);
            var open  = $btn.text().trim() === 'Expand all';

            $('#gateway-config').find('details.st-acc').prop('open', open);
            $btn.text(open ? 'Collapse all' : 'Expand all');
        });

        /* ------------------------------------------- per-provider save / test / toggle */
        $('#gateway-config').on('change', 'input[name="active"][type="checkbox"]', function () {
            var on = $(this).is(':checked');
            $(this).closest('.st-acc__body').find('[data-gw-switch-label]')
                .text(on ? '{{ trans('global.active') }}' : '{{ trans('global.inactive') }}');

            $(this).closest('details.st-acc').find('[data-gw-pill]')
                .toggleClass('st-pill--ok', on)
                .text(on ? '{{ trans('global.active') }}' : '{{ trans('global.inactive') }}');
        });

        $('#gateway-config').on('submit', 'form', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $foot = $form.find('.st-form__foot');

            $foot.find('[data-gw-submit]').prop('disabled', true);
            $state.removeClass('is-saved is-failed').text('Saving…').addClass('is-saving');

            $.ajax({
                url:         $form.attr('action'),
                type:        $form.attr('method'),
                data:        new FormData($form[0]),
                processData: false,
                contentType: false
            }).done(function () {
                flash('is-saved', 'Saved');
            }).fail(function () {
                flash('is-failed', 'Error');
            }).always(function () {
                $foot.find('[data-gw-submit]').prop('disabled', false);
            });
        });

        /* "Test config" — posts what is typed right now to the app's own
           payments/{gateway}/config_check endpoint and reports the result inline */
        $('#gateway-config').on('click', '[data-gw-test]', function () {
            var $btn    = $(this);
            var $form   = $btn.closest('form');
            var $result = $form.find('[data-gw-testresult]');

            $result.removeClass('is-ok is-bad').text('Testing…').addClass('is-busy');
            $btn.prop('disabled', true);

            $.get($btn.data('test-url'), $form.serialize())
                .done(function (response) {
                    if (response && response.status) {
                        $result.removeClass('is-busy is-bad').text('Connection OK').addClass('is-ok');
                    } else {
                        var message = (response && response.error) ? response.error : 'Could not reach the provider';
                        $result.removeClass('is-busy is-ok').text(message).addClass('is-bad');
                    }
                })
                .fail(function () {
                    $result.removeClass('is-busy is-ok')
                           .text('Could not reach the provider').addClass('is-bad');
                })
                .always(function () {
                    $btn.prop('disabled', false);
                });
        });
    });
</script>
@stop
