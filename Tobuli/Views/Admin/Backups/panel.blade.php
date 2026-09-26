<div class="st-card">

    <div class="st-card__head">
        <div class="st-card__icon"><i class="fas fa-database"></i></div>
        <div class="st-card__headtext">
            <span class="st-card__title">{{ trans('front.database_backups') }}</span>
            <span class="st-card__sub">Automatic dumps of the whole database, optionally copied to an FTP server.</span>
        </div>
        <a class="st-btn st-btn--ghost st-btn--sm" href="{{ route('admin.backup.index') }}" target="_blank">
            <i class="fas fa-clock-rotate-left"></i> {{ trans('front.latest_uploads') }}
        </a>
    </div>

    <div class="st-card__body">
        {!! Form::open(['route' => 'admin.backups.save', 'method' => 'POST', 'class' => 'st-form form-horizontal', 'id' => 'database-backup-form']) !!}

        <div class="st-form__row">
            <div class="st-form__label">{{ trans('validation.attributes.type') }}</div>
            <div class="st-form__control">
                {!! Form::select('type', $types, isset($settings['type']) ? $settings['type'] : null, ['class' => 'form-control']) !!}
            </div>
        </div>

        {{-- shown by the type select below; the FTP fields travel with the form
             either way, exactly as before --}}
        <div class="backup-type backup-type-custom">
            <div class="st-form__row">
                <div class="st-form__label">{{ trans('validation.attributes.ftp_server') }}</div>
                <div class="st-form__control">
                    {!! Form::text('ftp_server', isset($settings['ftp_server']) ? $settings['ftp_server'] : null, ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="st-form__row">
                <div class="st-form__label">{{ trans('validation.attributes.ftp_port') }}</div>
                <div class="st-form__control">
                    {!! Form::text('ftp_port', isset($settings['ftp_port']) ? $settings['ftp_port'] : 21, ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="st-form__row">
                <div class="st-form__label">{{ trans('validation.attributes.ftp_username') }}</div>
                <div class="st-form__control">
                    {!! Form::text('ftp_username', isset($settings['ftp_username']) ? $settings['ftp_username'] : null, ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="st-form__row">
                <div class="st-form__label">{{ trans('validation.attributes.ftp_password') }}</div>
                <div class="st-form__control">
                    {!! Form::password('ftp_password', ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="st-form__row">
                <div class="st-form__label">{{ trans('validation.attributes.ftp_path') }}</div>
                <div class="st-form__control">
                    {!! Form::text('ftp_path', isset($settings['ftp_path']) ? $settings['ftp_path'] : '/', ['class' => 'form-control']) !!}
                </div>
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">{{ trans('validation.attributes.period') }}</div>
            <div class="st-form__control">
                {!! Form::select('period', $periods, isset($settings['period']) ? $settings['period'] : null, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="st-form__row">
            <div class="st-form__label">{{ trans('validation.attributes.hour') }}</div>
            <div class="st-form__control">
                {!! Form::select('hour', $hours, isset($settings['hour']) ? $settings['hour'] : null, ['class' => 'form-control']) !!}
                <p class="st-hint">{{ trans('front.server_time_now') }} {{ date('Y-m-d H:i:s') }} (UTC 00:00)</p>
            </div>
        </div>

        @if (isset($settings['ftp_server']))
            <div class="st-form__row">
                <div class="st-form__label">{{ trans('front.test_ftp_upload') }}</div>
                <div class="st-form__control">
                    <button class="st-btn st-btn--ghost test_ftp" type="button" onClick="test_ftp();">
                        <i class="fas fa-upload"></i> {{ trans('front.test_ftp') }}
                    </button>
                    <div class="test_ftp_response alert alert-danger" style="display: none;"></div>
                </div>
            </div>
        @endif

        {!! Form::close() !!}
    </div>

    <div class="st-card__foot">
        <button type="submit" class="st-btn st-btn--primary" onClick="$('#database-backup-form').submit();">
            <i class="fas fa-check"></i> {{ trans('global.save') }}
        </button>
    </div>
</div>

@section('javascript')
    <script>
        function test_ftp() {
            $.ajax({
                type: 'GET',
                url: '{{ route('admin.backups.test') }}',
                beforeSend: function() {
                    $('.test_ftp_response').hide();
                },
                success: function(res) {
                    if (res.status == 1)
                        $('.test_ftp_response').show().removeClass('alert-danger').addClass('alert-success').html(res.message);
                    else
                        $('.test_ftp_response').show().removeClass('alert-success').addClass('alert-danger').html(res.message);
                }
            });
        }

        $(document).ready(function() {
            $(document).on('change', 'select[name="type"]', function () {
                $('.backup-type').hide();
                $('.backup-type-'+$(this).val()).show();
            });

            $('select[name="type"]').trigger('change');
        });
    </script>
@stop
