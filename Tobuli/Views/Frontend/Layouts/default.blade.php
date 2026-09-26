<!DOCTYPE html>
<html lang="{{ Language::iso() }}" data-theme="{{ current_theme() }}">
<head>
    @include('Frontend.Layouts.partials.head')
    @yield('styles')
</head>
<body class="page">

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
    @yield('header-menu-items')

     
     
      <ul class="nav__list nav__list_bottom">
      @include('Frontend.Layouts.partials.theme-toggle')
      <li class="nav__item">
      <a class="nav__link sub-btn"><span class="icon account"></span><span class="nav__link-txt">Profile</span><i class="fas fa-angle-right dropdown"></i></a>
            <div class="sub-menu">
                     
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
      
      <li class="nav__item"><a class="nav__link" href="javascript:" data-url="{{ route('languages.index') }}" data-modal="language-selection"><span class="icon language"></span><span class="nav__link-txt">Language</span></a></li>
      <li class="nav__item"><a class="nav__link" href="{!!route('logout')!!}" ><span class="icon logout"></span><span class="nav__link-txt">Logout</span></a></li>
      </ul>
    </ul>

  </nav>
<main class="main page__main">
<div class="content">
    <div class="container-fluid">
        @yield('content')
    </div>
</div>
</div>
@include('Frontend.Layouts.partials.trans')

@yield('self-scripts')

<script src="{{ asset_resource('assets/js/core.js') }}" type="text/javascript"></script>
<script src="{{ asset_resource('assets/js/app.js') }}" type="text/javascript"></script>
<script type="text/javascript">
   $(document).ready(function(){
     // Expandable sidebar items open as flyouts on hover (same as admin layout).
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

     // Keep click-to-toggle for touch devices
     $('.sub-btn').click(function(e){
       var $next = $(this).next('.sub-menu');
       if ($next.length && !$next.hasClass('nave-flyout')) {
         e.preventDefault();
         $next.slideToggle(100);
         $(this).find('.dropdown').toggleClass('rotate');
       }
     });
   });
   </script>
@yield('scripts')

</body>
</html>