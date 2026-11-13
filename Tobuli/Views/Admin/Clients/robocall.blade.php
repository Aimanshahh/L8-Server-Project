<input type="hidden" name="robocall_present" value="1">

@if (!$globalEnabled)
    <div class="alert alert-warning">
        Robocall is switched <strong>OFF globally</strong>. You can save settings here, but no calls are placed until the global switch is turned on.
    </div>
@endif

<div class="form-group">
    <div class="checkbox">
        {!! Form::hidden('robocall_enabled', 0) !!}
        {!! Form::checkbox('robocall_enabled', 1, $enabled, ['id' => 'edit_robocall_enabled']) !!}
        {!! Form::label('robocall_enabled', 'Enable Robocall') !!}
    </div>
</div>

<hr>

<div data-disablable="#edit_robocall_enabled;hide-disable">
    <div class="row">
        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('robocall_phone_primary', 'Primary number:') !!}
                {!! Form::text('robocall_phone_primary', $primary, ['class' => 'form-control', 'placeholder' => '03XXXXXXXXX']) !!}
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('robocall_phone_secondary', 'Secondary number (optional):') !!}
                {!! Form::text('robocall_phone_secondary', $secondary, ['class' => 'form-control', 'placeholder' => '03XXXXXXXXX']) !!}
            </div>
        </div>
    </div>
    <p class="help-block">
        The secondary number is called once, {{ $fallbackMinutes }} minutes later, only if the primary call was not answered. It is never called again after that.
    </p>

    <div class="form-group">
        {!! Form::label('robocall_alerts', 'Alerts and Voice ID:') !!}

        @if (empty($alerts))
            <p class="text-muted">This client has no alerts.</p>
        @else
            <div style="max-height: 320px; overflow: auto;">
                <table class="table table-condensed">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Alert</th>
                            <th style="width: 140px;">Voice ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($alerts as $a)
                            <tr>
                                <td>
                                    <input type="checkbox" name="robocall_alerts[{{ $a['id'] }}][active]" value="1" {{ $a['active'] ? 'checked' : '' }}>
                                </td>
                                <td>{{ $a['name'] }} <small class="text-muted">{{ $a['type'] }}</small></td>
                                <td>
                                    <input type="text" class="form-control input-sm" name="robocall_alerts[{{ $a['id'] }}][voice]" value="{{ $a['voice'] }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="help-block">Voice IDs: 32 geofence, 33 battery, 34 NR status, 35 feedback calls.</p>
        @endif
    </div>
</div>
