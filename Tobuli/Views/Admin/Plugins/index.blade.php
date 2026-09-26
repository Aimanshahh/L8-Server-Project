@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-plugins-overrides.css') }}?v=20260925-1">
@stop

@section('content')
    <div class="al-page">
        <div class="al-page__header">
            <div class="al-page__title-group">
                <div class="al-page__title-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <div>
                    <h1 class="al-page__title">{{ trans('admin.plugins') }}</h1>
                    <p class="al-page__subtitle">Enable or disable the application modules</p>
                </div>
            </div>
        </div>

        <div class="al-card">
            {!! Form::open(array('route' => 'admin.plugins.save', 'method' => 'POST', 'class' => 'form form-horizontal', 'id' => 'plugin-form')) !!}

            <div class="al-toolbar">
                <div class="al-toolbar__count" id="plugins-count">
                    {{ count($plugins) }} {{ str_plural('plugin', count($plugins)) }}
                </div>
                <div class="al-search" style="max-width:340px">
                    <i class="fas fa-search"></i>
                    <input type="text" id="plugins-search" placeholder="Search by plugin name..." autocomplete="off">
                </div>
            </div>

            <div class="table-responsive" data-table>
                <table class="table table-list">
                    <thead>
                        <tr>
                            <th class="table-checkbox" style="width:56px;padding-right:0"></th>
                            {!! tableHeader('validation.attributes.name') !!}
                            <th style="padding-left:0">{{ trans('admin.options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($plugins as $plugin)
                        <tr data-search="{{ strtolower($plugin->name.' '.$plugin->key) }}">
                            <td style="width:56px;padding-right:0">
                                <div class="checkbox">
                                    {!! Form::checkbox('plugins['.$plugin->key.'][status]', 1, $plugin->status) !!}
                                    {!! Form::label(null) !!}
                                </div>
                            </td>
                            <td>{{ $plugin->name }}</td>
                            <td class="plugin-options" style="padding-left:0">
                                @if(View::exists('Admin.Plugins.Partials.'.$plugin->key))
                                    @include('Admin.Plugins.Partials.'.$plugin->key)
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="no-data" colspan="3">
                                <div class="al-empty">
                                    <div class="al-empty__icon"><i class="fas fa-puzzle-piece"></i></div>
                                    <p class="al-empty__text">{!! trans('admin.no_data') !!}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                        <tr class="plugins-none is-hidden">
                            <td class="no-data" colspan="3">
                                <div class="al-empty al-empty--inline">
                                    <div class="al-empty__icon"><i class="fas fa-magnifying-glass"></i></div>
                                    <p class="al-empty__text">No plugin matches your search.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {!! Form::close() !!}

            <div class="al-card__foot">
                <button type="submit" class="al-add" onClick="$('#plugin-form').submit();">
                    <i class="fas fa-save"></i>
                    {{ trans('global.save') }}
                </button>
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script>
        $(function() {
            var $form = $('#plugin-form');

            /* -------- a plugin row is dimmed while its switch is off -------- */
            function syncRowState() {
                $form.find('tr').has('.checkbox input[type=checkbox]').each(function() {
                    var cb = $(this).find('.checkbox input[type=checkbox]');
                    var checked = cb.length && cb[0].checked;

                    $(this).toggleClass('is-disabled', !checked);
                });
            }

            $form.on('change', '.checkbox input[type=checkbox]', syncRowState);
            syncRowState();

            /* -------- search, applied to the rendered rows -------- */
            function rows() {
                return $form.find('table tbody tr[data-search]');
            }

            function applySearch() {
                var query = $.trim($('#plugins-search').val() || '').toLowerCase();
                var all = rows();
                var shown = 0;

                all.each(function () {
                    var hit = !query || ($(this).attr('data-search') + '').indexOf(query) !== -1;

                    $(this).toggleClass('is-hidden', !hit);

                    if (hit)
                        shown++;
                });

                $form.find('.plugins-none').toggleClass('is-hidden', shown !== 0 || all.length === 0);

                var counter = $('#plugins-count').get(0);

                if (counter)
                    counter.textContent = shown + ' of ' + all.length + ' ' + (all.length === 1 ? 'plugin' : 'plugins');
            }

            $form.on('input', '#plugins-search', applySearch);

            // the search field lives inside the settings form, so Enter must not
            // submit (and save) the whole page
            $form.on('keydown', '#plugins-search', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    applySearch();
                }
            });
        });
    </script>
@stop
