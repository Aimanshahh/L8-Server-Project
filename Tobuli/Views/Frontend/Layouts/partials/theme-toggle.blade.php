{{-- Single colour-scheme switch. The chosen value lives in the app_theme
     cookie and is applied by theme-script.blade.php. --}}
<li class="nav__item theme-toggle">
    <a class="nav__link js-theme-toggle" href="javascript:" title="Switch between light and dark">
        <span class="icon theme-toggle__icon" data-theme-icon><i class="fas fa-moon"></i></span>
        <span class="nav__link-txt" data-theme-label>{{ trans('global.dark_mode') }}</span>
    </a>
</li>
