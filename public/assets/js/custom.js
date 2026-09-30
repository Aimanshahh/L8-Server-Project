/* GPSWOX custom.js — clean rewrite */
(function (window, document) {
    'use strict';

    // ── Config ─────────────────────────────────────────────
    var META_CSRF   = 'meta[name="csrf-token"]';
    var REFRESH_KEY = 'gpswox_refresh_token';

    // ── Helpers ────────────────────────────────────────────
    function getCsrf() {
        var el = document.querySelector(META_CSRF);
        return el ? el.getAttribute('content') : '';
    }

    function setCsrf(value) {
        var el = document.querySelector(META_CSRF);
        if (el && value) {
            el.setAttribute('content', value);
        }
    }

    function getRefresh() {
        try { return localStorage.getItem(REFRESH_KEY) || ''; }
        catch (e) { return ''; }
    }

    function setRefresh(value) {
        if (!value) return;
        try { localStorage.setItem(REFRESH_KEY, value); }
        catch (e) { /* storage disabled */ }
    }

    // ── AJAX setup (jQuery + axios) ───────────────────────
    function applyHeaders() {
        var csrf    = getCsrf();
        var refresh = getRefresh();
        var headers = {
            'X-Requested-With': 'XMLHttpRequest'
        };
        if (csrf)    headers['X-CSRF-TOKEN']    = csrf;
        if (refresh) headers['X-Refresh-Token'] = refresh;

        if (window.jQuery) {
            window.jQuery.ajaxSetup({ headers: headers });
        }

        if (window.axios) {
            if (csrf)    window.axios.defaults.headers.common['X-CSRF-TOKEN']    = csrf;
            if (refresh) window.axios.defaults.headers.common['X-Refresh-Token'] = refresh;
        }
    }

    // ── Response interceptor — refresh tokens ─────────────
    function installInterceptor() {
        if (!window.jQuery) return;

        window.jQuery(document).ajaxComplete(function (event, xhr) {
            if (!xhr || !xhr.getResponseHeader) return;

            var newRefresh = xhr.getResponseHeader('X-Refresh-Token');
            if (newRefresh) setRefresh(newRefresh);

            var newCsrf = xhr.getResponseHeader('X-CSRF-TOKEN');
            if (newCsrf) {
                setCsrf(newCsrf);
                applyHeaders();
            }
        });
    }

    // ── Initialize ─────────────────────────────────────────
    function init() {
        applyHeaders();
        installInterceptor();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose globals for debugging / other modules
    window.GPSWOX = window.GPSWOX || {};
    window.GPSWOX.getCsrf       = getCsrf;
    window.GPSWOX.getRefresh    = getRefresh;
    window.GPSWOX.setRefresh    = setRefresh;
    window.GPSWOX.applyHeaders  = applyHeaders;

})(window, document);
