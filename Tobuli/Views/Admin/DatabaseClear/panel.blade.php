<div class="st-card">

    <div class="st-card__head">
        <div class="st-card__icon"><i class="fas fa-broom"></i></div>
        <div class="st-card__headtext">
            <span class="st-card__title">{{ trans('admin.database_clear') }}</span>
            <span class="st-card__sub">Drop old position history so the database does not grow without limit.</span>
        </div>
    </div>

    <div class="st-card__body">
        {!! Form::open(array('route' => 'admin.db_clear.save', 'method' => 'POST', 'class' => 'st-form form-horizontal', 'id' => 'database-clear-form')) !!}

        <div class="st-form__row">
            <div class="st-form__label">{{ trans('validation.attributes.database_clear_status') }}</div>
            <div class="st-form__control">
                <label class="st-switch">
                    {!! Form::checkbox('status', 1, !empty($settings['status']), ['id' => 'db_clear_status']) !!}
                    <span class="st-switch__track"></span>
                </label>
                <label for="db_clear_status" class="st-switch__label">
                    {{ trans('validation.attributes.database_clear_status') }}
                </label>
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">{{ trans('front.from') }}</div>
            <div class="st-form__control">
                <div class="st-radios">
                    <label class="st-radio">
                        {!! Form::radio('from', 'server_time', 'server_time' == \Illuminate\Support\Arr::get($settings,'from')) !!}
                        {{ trans('front.server_time') }}
                    </label>
                    <label class="st-radio">
                        {!! Form::radio('from', 'last_connection', 'last_connection' == \Illuminate\Support\Arr::get($settings,'from')) !!}
                        {{ trans('admin.last_connection') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">{{ trans('validation.attributes.database_clear_days') }}</div>
            <div class="st-form__control">
                {!! Form::text('days', isset($settings['days']) ? $settings['days'] : 90, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row" id="db-size-field">
            <div class="st-form__label">{{ trans('front.database_size') }}</div>
            <div class="st-form__control">
                {!! Form::text(null, null, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
            </div>
        </div>

        {!! Form::close() !!}
    </div>

    <div class="st-card__foot">
        <button type="submit" class="st-btn st-btn--primary" onClick="$('#database-clear-form').submit();">
            <i class="fas fa-check"></i> {{ trans('global.save') }}
        </button>
    </div>
</div>

@push('javascript')
    <script>
        $(document).ready(function() {
            let container = $('#db-size-field');
            $.ajax({
                type: 'GET',
                url: '{{ route('admin.db_clear.size') }}',
                beforeSend: function() {
                    loader.add(container);
                },
                success: function(response) {
                    $('input', container).val(response);
                },
                complete: function () {
                    loader.remove(container);
                }
            });
        });
    </script>
@endpush
