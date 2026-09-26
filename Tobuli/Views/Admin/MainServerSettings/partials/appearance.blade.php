<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title">
            <i class="icon stylize-1"></i>
            <span class="st-card__headtext">
                <span class="st-card__title">{{ trans('validation.attributes.logo') }}</span>
                <span class="st-card__sub">Branding, template colour and login page appearance</span>
            </span>
        </div>
    </div>

    <div class="panel-body">
        {!! Form::open(array('route' => 'admin.main_server_settings.logo_save', 'method' => 'POST', 'class' => 'form form-horizontal', 'enctype' => 'multipart/form-data', 'id' => 'logos-form')) !!}

        <details class="st-acc" open>
            <summary class="st-acc__head">
                <span class="st-acc__chev"><i class="fas fa-chevron-right"></i></span>
                <span class="st-acc__text">
                    <span class="st-acc__title">Logos &amp; template colour</span>
                    <span class="st-acc__sub">Frontpage logo, favicon and the colour scheme</span>
                </span>
            </summary>
            <div class="st-acc__body">

            <div class="row">
                <div class="col-xs-6">
                    <div class="form-group">
                        {!! Form::label(null, trans('validation.attributes.frontpage_logo'), ['class' => 'col-xs-12 control-label"']) !!}
                        <div class="col-xs-12">
                            <div class="form-image">
                                <div class="form-image-controls">
                                    <label for="frontpage_logo" class="btn btn-default"><i class="icon upload"></i></label>
                                    {{--
                                    <button class="btn btn-default"><i class="icon delete"></i></button>
                                    --}}
                                </div>
                                @if (Appearance::getAssetFilePath('logo'))
                                    <img src="{{ Appearance::getAssetFileUrl('logo') }}" alt="Logo" class="img-responsive" id="img-frontpage-logo">
                                @endif
                                <img src="{{ asset('assets/images/no-image.jpg') }}" class="no-image img-responsive">
                                {!! Form::file('frontpage_logo', ['class' => 'hidden', 'id' => 'frontpage_logo', 'onChange' => 'readImage(this, "#img-frontpage-logo")']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xs-6">
                    <div class="form-group">
                        {!! Form::label(null, trans('validation.attributes.favicon') . ' (16x16 .ICO)', ['class' => 'col-xs-12 control-label"']) !!}
                        <div class="col-xs-12">
                            <div class="form-image">
                                <div class="form-image-controls">
                                    <label for="favicon" class="btn btn-default"><i class="icon upload"></i></label>
                                    {{--
                                    <button class="btn btn-default"><i class="icon delete"></i></button>
                                    --}}
                                </div>
                                @if (Appearance::getAssetFilePath('favicon'))
                                    <img src="{{ Appearance::getAssetFileUrl('favicon') }}" alt="Logo" class="img-responsive" id="img-favicon">
                                @endif
                                <img src="{{ asset('assets/images/no-image.jpg') }}" class="no-image img-responsive">
                                {!! Form::file('favicon', ['class' => 'hidden', 'id' => 'favicon', 'onChange' => 'readImage(this, "#img-favicon")']) !!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xs-6">
                    <div class="form-group">
                        {!! Form::label(null, trans('validation.attributes.template_color'), ['class' => 'col-xs-12 control-label"']) !!}
                        <div class="col-xs-12">
                            <p class="al-hint" style="margin: 6px 0 0;">
                                <i class="fas fa-circle-half-stroke"></i>
                                {!! trans('global.theme_hint') !!}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            </div>
        </details>

        <details class="st-acc">
            <summary class="st-acc__head">
                <span class="st-acc__chev"><i class="fas fa-chevron-right"></i></span>
                <span class="st-acc__text">
                    <span class="st-acc__title">Login page &amp; branding</span>
                    <span class="st-acc__sub">Login page artwork, colours, welcome text and app links</span>
                </span>
            </summary>
            <div class="st-acc__body">

            <div class="row">
                <div class="col-xs-6">
                    <div class="form-group">
                        {!! Form::label(null, trans('validation.attributes.login_page_logo'), ['class' => 'col-xs-12 control-label"']) !!}
                        <div class="col-xs-12">
                            <div class="form-image">
                                <div class="form-image-controls">
                                    <label for="login_page_logo" class="btn btn-default"><i class="icon upload"></i></label>
                                    {{--
                                    <button class="btn btn-default"><i class="icon delete"></i></button>
                                    --}}
                                </div>
                                @if (Appearance::getAssetFilePath('logo-main'))
                                <img src="{{ Appearance::getAssetFileUrl('logo-main') }}" alt="Logo" class="img-responsive" id="img-login-page-logo">
                                @endif
                                <img src="{{ asset('assets/images/no-image.jpg') }}" class="no-image img-responsive">
                                {!! Form::file('login_page_logo', ['class' => 'hidden', 'id' => 'login_page_logo', 'onChange' => 'readImage(this, "#img-login-page-logo")']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xs-6">
                    <div class="form-group">
                        {!! Form::label(null, trans('validation.attributes.background'), ['class' => 'col-xs-12 control-label"']) !!}
                        <div class="col-xs-12">
                            <div class="form-image">
                                <div class="form-image-controls">
                                    <label for="background" class="btn btn-default"><i class="icon upload"></i></label>
                                    {{--
                                    <button class="btn btn-default"><i class="icon delete"></i></button>
                                    --}}
                                </div>
                                @if (Appearance::getAssetFilePath('background'))
                                <img src="{{ Appearance::getAssetFileUrl('background') }}" alt="Logo" class="img-responsive" id="img-backgroud">
                                @endif
                                <img src="{{ asset('assets/images/no-image.jpg') }}" class="no-image img-responsive">
                                {!! Form::file('background', ['class' => 'hidden', 'id' => 'background', 'onChange' => 'readImage(this, "#img-backgroud")']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xs-6">
                    <div class="form-group">
                        {!! Form::label('login_page_text_color', trans('validation.attributes.login_page_text_color'), ['class' => 'col-xs-12 control-label"']) !!}
                        <div class="col-xs-12">
                            {!! Form::text('login_page_text_color', Appearance::getSetting('login_page_text_color'), ['class' => 'form-control colorpicker']) !!}
                        </div>
                    </div>
                </div>

                <div class="col-xs-6">
                    <div class="form-group">
                        {!! Form::label('login_page_background_color', trans('validation.attributes.login_page_background_color'), ['class' => 'col-xs-12 control-label"']) !!}
                        <div class="col-xs-12">
                            {!! Form::text('login_page_background_color', Appearance::getSetting('login_page_background_color'), ['class' => 'form-control colorpicker']) !!}
                        </div>
                    </div>
                </div>

                <div class="col-xs-6">
                    <div class="form-group">
                        {!! Form::label('login_page_panel_background_color', trans('validation.attributes.login_page_panel_background_color'), ['class' => 'col-xs-12 control-label"']) !!}
                        <div class="col-xs-12">
                            {!! Form::text('login_page_panel_background_color', Appearance::getSetting('login_page_panel_background_color'), ['class' => 'form-control colorpicker']) !!}
                        </div>
                    </div>
                </div>

                <div class="col-xs-6">
                    <div class="form-group">
                        {!! Form::label('login_page_panel_transparency', trans('validation.attributes.login_page_panel_transparency'), ['class' => 'col-xs-12 control-label"']) !!}
                        <div class="col-xs-12">
                            {!! Form::selectRange('login_page_panel_transparency', 0, 100, Appearance::getSetting('login_page_panel_transparency'), ['class' => 'form-control']) !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('welcome_text', trans('validation.attributes.welcome_text'), ['class' => 'col-xs-12 control-label"']) !!}
                <div class="col-xs-12">
                    {!! Form::text('welcome_text', Appearance::getSetting('welcome_text'), ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('bottom_text', trans('validation.attributes.bottom_text'), ['class' => 'col-xs-12 control-label"']) !!}
                <div class="col-xs-12">
                    {!! Form::text('bottom_text', Appearance::getSetting('bottom_text'), ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('apple_store_link', trans('validation.attributes.apple_store_link'), ['class' => 'col-xs-12 control-label"']) !!}
                <div class="col-xs-12">
                    {!! Form::text('apple_store_link', Appearance::getSetting('apple_store_link'), ['class' => 'form-control', 'placeholder' => 'http://']) !!}
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('google_play_link', trans('validation.attributes.google_play_link'), ['class' => 'col-xs-12 control-label"']) !!}
                <div class="col-xs-12">
                    {!! Form::text('google_play_link', Appearance::getSetting('google_play_link'), ['class' => 'form-control', 'placeholder' => 'http://']) !!}
                </div>
            </div>
            </div>
        </details>

        {!! Form::close() !!}
    </div>

    <div class="panel-footer">
        <button type="submit" class="btn btn-action" onClick="$('#logos-form').submit();">{{ trans('global.save') }}</button>
    </div>
</div>


