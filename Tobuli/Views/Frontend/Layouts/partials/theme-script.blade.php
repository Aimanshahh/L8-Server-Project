{{-- Applies the saved colour scheme and wires every .js-theme-toggle button.
     The dark rules live in assets/css/theme-dark.css, scoped to
     html[data-theme="dark"], so switching never reloads the page.
     Include this exactly once per layout. --}}
<script>
    (function () {
        var COOKIE = 'app_theme';
        var root = document.documentElement;

        function current() {
            return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        }

        function paint(theme) {
            root.setAttribute('data-theme', theme);

            document.querySelectorAll('[data-theme-icon]').forEach(function (el) {
                el.innerHTML = theme === 'dark'
                    ? '<i class="fas fa-sun"></i>'
                    : '<i class="fas fa-moon"></i>';
            });

            document.querySelectorAll('[data-theme-label]').forEach(function (el) {
                el.textContent = theme === 'dark'
                    ? @json(trans('global.light_mode'))
                    : @json(trans('global.dark_mode'));
            });
        }

        function init() {
            document.addEventListener('click', function (e) {
                var btn = e.target.closest ? e.target.closest('.js-theme-toggle') : null;

                if (!btn) {
                    return;
                }

                e.preventDefault();

                var next = current() === 'dark' ? 'light' : 'dark';

                document.cookie = COOKIE + '=' + next + ';path=/;max-age=31536000;SameSite=Lax';

                paint(next);
            });

            paint(current());
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
