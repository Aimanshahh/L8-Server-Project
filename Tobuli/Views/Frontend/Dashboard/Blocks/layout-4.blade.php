<div class="dashboard-block @yield('width', 'col-sm-4 col-md-4 col-lg-4')" id="{{ "block_$name" }}">
    <div class="panel panel-default panel-dashboard">
        <div class="panel-heading">
            <div class="panel-title">
                <div class="pull-left">
                    @yield('header')
                </div>

                <div class="pull-right">
                    @if(View::exists("Frontend.Dashboard.Blocks.$name.options"))
                        <div class="btn-group droparrow" data-position="fixed">
                            <i class="btn icon options"
                               data-toggle="dropdown"
                               aria-haspopup="true"
                               aria-expanded="false"></i>

                            <div class="dropdown-menu dropdown-menu-right">
                                @include("Frontend.Dashboard.Blocks.$name.options", [
                                    'options' => $config['options'],
                                    'block'   => $name,
                                ])
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="panel-body dashboard-content">
            @yield('body')
        </div>
    </div>

    @yield('scripts')
</div>