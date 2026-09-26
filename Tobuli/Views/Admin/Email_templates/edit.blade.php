@extends('Admin.Layouts.modal')

@section('modal_class', 'modal-lg')

@section('icon')
    <i class="fas fa-envelope"></i>
@stop

@section('title')
    <i class="icon edit"></i> {{ trans('global.edit') }}
@stop

@section('subtitle')
    Update your email template content and settings
@stop

@section('body')
    {!! Form::open(array('route' => ['admin.email_templates.update', $item->id], 'method' => 'PUT')) !!}
    {!! Form::hidden('id', $item->id) !!}
        <!-- title field -->
        <div class="form-group">
            {!! Form::label('title', trans('validation.attributes.title').':') !!}
            {!! Form::text('title', $item->title, ['class' => 'form-control']) !!}
        </div>
        <!-- note field -->
        <div class="form-group">
            {!! Form::label('note', trans('validation.attributes.note').':') !!}
            {!! Form::textarea('note', $item->note, ['class' => 'form-control wysihtml5']) !!}
        </div>
        @if (!empty($replacers))
            <div class="mo-panel">
                <div class="mo-panel__head">
                    <div class="mo-panel__icon"><i class="fas fa-code"></i></div>
                    <div>
                        <p class="mo-panel__title">Available Template Variables</p>
                        <p class="mo-panel__caption">
                            You can use these variables in your template. They will be replaced with actual
                            data when the email is sent.
                        </p>
                    </div>
                </div>
                <ul class="mo-panel__items">
                    @foreach($replacers as $key => $text)
                        <li class="mo-panel__item">
                            <span class="mo-chip">{{ $key }}</span>
                            <span class="mo-chip__label">{{ $text }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    {!! Form::close() !!}
    <script type="text/javascript">
    	$('.wysihtml5').wysihtml5({
            "image": false
        });
    </script>
@stop
