<div class="tab-pane-header events-header">
    <div class="form">
        <div class="input-group">
            <div class="form-group search">
                {!!Form::text('search', null, ['class' => 'form-control', 'id' => 'events_search_field', 'placeholder' => trans('front.search'), 'autocomplete' => 'off'])!!}
            </div>
            <span class="input-group-btn">

                <button class="btn btn-default" type="button"  data-url="{!! \Tobuli\Lookups\Tables\EventsLookupTable::route('index') !!}" data-modal="events_lookup">
                    <i class="icon lookup"></i>
                </button>

                @if(Auth::user()->perm('events', 'remove'))
                    <button class="btn btn-default" type="button" data-url="{!!route('events.do_destroy')!!}" data-modal="events_do_destroy">
                        <i class="icon remove-all"></i>
                    </button>
                @endif
            </span>
        </div>
    </div>
    <div class="events-col-head">
        <span class="events-col-head__time">{{ trans('front.time') }}</span>
        <span class="events-col-head__object">{{ trans('front.object') }}</span>
        <span class="events-col-head__event">{{ trans('front.event') }}</span>
        <span class="events-col-head__actions"></span>
    </div>
</div>

<div class="tab-pane-body">
    <table class="table table-condensed events-table">
        <thead>
            <tr>
                <th></th>
                <th></th>
            </tr>
        </thead>

        <tbody id="ajax-events"></tbody>
    </table>
</div>