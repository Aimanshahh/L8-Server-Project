<nav class="nave page__nav">
            <div class="hidee logo nav__logo">
                @if ( Appearance::assetFileExists('logo') )
                  <a class="nav__link" href="/" title="{{ Appearance::getSetting('server_name') }}"><img class="nav__link-logo" src="{{ asset_resource('assets/images/logo.svg') }}"></a>
                @endif
            </div>
          <div class="logo nav__logo">
            <a class="nav__link" href="/" title="{{ Appearance::getSetting('server_name') }}"><img class="nav__link-favicon" src="{{ asset_resource('assets/images/favicon.svg') }}"><span class="nav__link-txt"><img class="nav__link-logo" src="{{ asset_resource('assets/images/logo.svg') }}"></span></a>
           </div>
    <ul class="nav__list navigation-accordion">
        
      <li class="nav__item"><a class="nav__link" href="{{ route('dashboard') }}" title="Dashboard"><span class="icon dashboard"></span><span class="nav__link-txt">Dashboard</span></a></li>
      <li class="nav__item">
         <a class="nav__link sub-btn" title="Tools"><span class="icon tool"></span><span class="nav__link-txt">Tools</span><i class="fas fa-angle-right dropdown"></i></a>
            <div class="sub-menu" data-title="Tools">
                     
                            @if ( Auth::User()->perm('reports', 'view') )
                           
                                <a class="sub-item nav__link" href="javascript:" data-url="{!!route('reports.create')!!}" data-modal="reports_create" role="button">
                                    <span class="icon reportss"></span>
                                    <span class="nav__link-txt">{!!trans('front.reports')!!}</span>
                                </a>
                            
                            @endif
                            @if ( Auth::User()->perm('alerts', 'view') ) 
                            <a class="sub-item nav__link" href="javascript:" data-url="{!! route('alerts.index_modal') !!}" data-modal="alerts" role="button"><span class="icon alertss"></span><span class="nav__link-txt">{!!trans('front.alerts')!!}</span></a>
                            @endif
                            @if ( Auth::User()->perm('geofences', 'view') )
                             <a class="sub-item nav__link" href="javascript:" onclick="app.geofences.list();app.openTab('geofencing_tab');"><span class="icon geofencing"></span><span class="nav__link-txt">{!!trans('front.geofencing')!!}</span></a>
                            @endif
                            @if ( Auth::User()->perm('routes', 'view') )
                             <a class="sub-item nav__link" href="javascript:" onclick="app.routes.list();app.openTab('routes_tab');"><span class="icon routess"></span><span class="nav__link-txt">{!!trans('front.routes')!!}</span></a>
                            @endif
                            @if ( Auth::User()->perm('poi', 'view') )
                             <a class="sub-item nav__link" href="javascript:" onClick="app.pois.list();app.openTab('pois_tab');"><span class="icon pois"></span><span class="nav__link-txt">{!!trans('front.poi')!!}</span></a>
                               @endif 
                            <a class="sub-item nav__link" href="#objects_tab" data-toggle="tab" onclick="app.ruler();">
                                    <span class="icon ruler"></span>
                                    <span class="nav__link-txt">{!!trans('front.ruler')!!}</span>
                                </a>
                                <a class="sub-item nav__link" href="javascript:" data-toggle="modal" data-target="#showPoint">
                                    <span class="icon point"></span>
                                    <span class="nav__link-txt">{!!trans('front.show_point')!!}</span>

                                </a>
                            
                            
                                <a class="sub-item nav__link" href="javascript:" data-toggle="modal" data-target="#showAddress">
                                    <span class="icon addreesss"></span>
                                    <span class="nav__link-txt">{!! trans('front.show_address') !!}</span>
                                </a>
                            
                            @if ( Auth::User()->perm('send_command', 'view') )
                            
                                <a class="sub-item nav__link" href="javascript:" data-url="{{ route('send_command.create') }}" data-modal="send_command">
                                    <span class="icon send-commandd"></span>
                                    <span class="nav__link-txt">{!!trans('front.send_command')!!}</span>
                                </a>
                           
                            @endif
                            @if ( Auth::User()->perm('camera', 'view') )
                              
                                    <a class="sub-item nav__link" href="javascript:" data-url="{{ route('device_media.create') }}" data-modal="camera_photos"  role="button">
                                        <span class="icon camera"></span>
                                        <span class="nav__link-txt">{!!trans('front.camera')!!}</span>
                                    </a>
                                
                            @endif
                            @if ( Auth::User()->perm('tasks', 'view') )
                           
                                <a class="sub-item nav__link" href="javascript:" data-url="{{ route('tasks.index') }}" data-modal="tasks"  role="button">
                                    <span class="icon task"></span>
                                    <span class="nav__link-txt">{!!trans('front.tasks')!!}</span>
                                </a>
                            
                            @endif
                            @if ( Auth::User()->perm('maintenance', 'view') )
                           
                                <a class="sub-item nav__link" href="{!!route('maintenance.index')!!}" target="_blank" role="button">
                                    <span class="icon servicess"></span>
                                    <span class="nav__link-txt">{!!trans('front.maintenance')!!}</span>
                                </a>
                           
                            @endif
                            @if( Auth::User()->perm('device_expenses', 'view') && expensesTypesExist())
                            
                                <a class="sub-item nav__link" href="javascript:" data-url="{{ route('device_expenses.modal') }}" data-modal="devices_expenses">
                                    <span class="icon money"></span>
                                    <span class="nav__link-txt">{!!trans('front.expenses')!!}</span>
                                </a>
                           
                            @endif
                           
                            @if ( Auth::User()->perm('sharing', 'view') )
                           
                                <a class="sub-item nav__link" href="javascript:" data-url="{{ route('sharing.index') }}" data-modal="sharing">
                                    <span class="icon sharingg"></span>
                                    <span class="nav__link-txt">{!!trans('front.sharing')!!}</span>
                                </a>
                           
                            @endif
                            @if (Auth::user()->able('configure_device'))
                            
                           
                                <a class="sub-item nav__link" href="javascript:" data-url="{{ route('device_config.index') }}" data-modal="device_config"  role="button">
                                    <span class="icon devicess"></span>
                                    <span class="nav__link-txt">{!!trans('front.device_configuration')!!}</span>
                                </a>
                           
                            @endif
                            @if ( Auth::User()->perm('call_actions', 'view') )
                            
                                <a class="sub-item nav__link" href="javascript:" data-url="{{ route('call_actions.index') }}" data-modal="call_actions">
                                    <span class="icon call_action"></span>
                                    <span class="nav__link-txt">{!! trans('front.call_actions') !!}</span>
                                </a>
                            
                            @endif
                            @if ( Auth::User()->perm('forwards', 'view') )
                            <li>
                                <a class="sub-item nav__link" href="javascript:" data-url="{{ route('forwards.index') }}" data-modal="forwards">
                                    <span class="icon forwards"></span>
                                    <span class="nav__link-txt">{!! trans('front.forwards') !!}</span>
                                </a>
                            </li>
                            @endif
          
         </div>
      </li>
            @if (isAdmin())
            @php($adminExpanded = strpos((string) Route::currentRouteName(), 'admin.') === 0)
            <li class="nav__item admin-nav-item {{ $adminExpanded ? 'admin-expanded' : '' }}">
                <a class="nav__link sub-btn" href="javascript:" title="Admin" aria-expanded="{{ $adminExpanded ? 'true' : 'false' }}">
                    <span class="icon admin"></span>
                    <span class="nav__link-txt">Admin</span>
                    <i class="fas fa-angle-right dropdown"></i>
                </a>
                <div class="sub-menu admin-submenu" data-title="Admin" data-expanded="{{ $adminExpanded ? 'true' : 'false' }}">
                    <ul class="admin-submenu-list">{!! getNavigation(true) !!}</ul>
                </div>
            </li>
            @endif
      <li class="nav__item"><a class="nav__link" href="javascript:" title="Setup" data-url="{!!route('my_account_settings.edit')!!}" data-modal="my_account_settings_edit"><span class="icon setup"></span><span class="nav__link-txt">Setup</span></a></li>
      <li class="nav__item"><a class="nav__link" href="javascript:" title="Chat" data-url="{!!route('chat.index')!!}" data-modal="chat"><span class="icon chat"></span><span class="nav__link-txt">Chat</span></a></li>
     
      <ul class="nav__list nav__list_bottom">
      @include('Frontend.Layouts.partials.theme-toggle')
      <li class="nav__item">
      <a class="nav__link sub-btn" title="Profile"><span class="icon account"></span><span class="nav__link-txt">Profile</span><i class="fas fa-angle-right dropdown"></i></a>
            <div class="sub-menu" data-title="Profile">
                     
            <a class="sub-item nav__link" href="javascript:" data-url="{{ route('subscriptions.index') }}" data-modal="subscriptions_edit"><span class="icon membership"></span><span class="nav__link-txt">Membership</span></a>
           
                  @if (Auth::User()->perm('custom_device_add', 'view'))
                     <a class="sub-item nav__link" href="javascript:" data-url="{{ route('devices.subscriptions') }}" data-modal="device_subscriptions_index">
                        <span class="icon device_plan"></span>
                         <span class="nav__link-txt">{!!trans('admin.device_plans')!!}</span>
                        </a>
                              
                        @elseif (settings('main_settings.enable_device_plans') ?? false)
                          
                       <a class="sub-item nav__link" href="javascript:" data-url="{{ route('device_plans.index') }}" data-modal="device_plans_index">
                           <span class="icon device_plan"></span>
                            <span class="nav__link-txt">{!!trans('admin.device_plans')!!}</span>
                      </a>
                          
                            @endif
                   @if (isPublic())
                  <a href="{{ config('tobuli.frontend_change_password').auth()->user()->email }}">
                    <span class="icon password"></span>
                    <span class="nav__link-txt">{!!trans('front.change_password')!!}</span>
                  </a>
            @else
                <a class="sub-item nav__link" href="javascript:" data-url="{{ route('my_account.edit') }}" data-modal="subscriptions_edit">
                  <span class="icon password"></span>
                  <span class="nav__link-txt">{!!trans('front.change_password')!!}</span>
                </a>
               @endif
                            
                       
          
         </div>
      
      <li class="nav__item"><a class="nav__link" href="javascript:" title="Language" data-url="{{ route('languages.index') }}" data-modal="language-selection"><span class="icon language"></span><span class="nav__link-txt">Language</span></a></li>
      <li class="nav__item"><a class="nav__link" href="{!!route('logout')!!}" title="Logout"><span class="icon logout"></span><span class="nav__link-txt">Logout</span></a></li>
      </ul>
    </ul>

  </nav>
  <script type="text/javascript">
   $(document).ready(function(){
     // Expandable sidebar items open as flyouts on hover.
     // Only process TOP-LEVEL .nav__item elements (direct children of
     // .nav__list), not nested items inside sub-menus (which would
     // break the Admin flyout rendered by parseNavigation).
     var $nav = $('.page__nav.nave');
     if ($nav.length) {
       $nav.find('.nav__list > .nav__item, .nav__list .nav__list_bottom > .nav__item').each(function(){
         var $item = $(this);
         var $menu = $item.children('.sub-menu').first();
         if (!$menu.length) return;

         $menu.addClass('nave-flyout').appendTo('body');

         var timer = null;
         var show = function() { clearTimeout(timer); $menu.addClass('open'); };
         var hide = function() { timer = setTimeout(function() { $menu.removeClass('open'); }, 120); };

         $item.on('mouseenter', function(){
           var pos = $item.offset();
           $menu.css({
             top: Math.min(pos.top, $(window).height() - $menu.outerHeight() - 8),
             left: pos.left + $item.outerWidth() + 8
           });
           show();
         });
         $item.on('mouseleave', hide);
         $menu.on('mouseenter', show).on('mouseleave', hide);
       });
     }
   });
   </script>
