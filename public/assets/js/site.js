/* TEIA | ADE SAMPA — interações do site público (JavaScript puro, sem dependências).
   O site funciona sem JavaScript; este arquivo só melhora a experiência. */
(function () {
    'use strict';
    document.documentElement.classList.remove('no-js');

    // ---------- Menu mobile ----------
    var header = document.querySelector('.site-header');
    var toggle = header && header.querySelector('.site-header__toggle');
    if (toggle) {
        var close = function () {
            header.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        };
        toggle.addEventListener('click', function () {
            var open = !header.classList.contains('is-open');
            header.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && header.classList.contains('is-open')) {
                close();
                toggle.focus();
            }
        });
        document.addEventListener('click', function (ev) {
            if (header.classList.contains('is-open') && !header.contains(ev.target)) {
                close();
            }
        });
    }

    // ---------- Âncoras: move o foco para a seção (acessibilidade) ----------
    document.addEventListener('click', function (ev) {
        var link = ev.target.closest && ev.target.closest('a[href^="#"]');
        if (!link) { return; }
        var id = link.getAttribute('href').slice(1);
        var target = id && document.getElementById(id);
        if (!target) { return; }
        if (header && header.classList.contains('is-open')) {
            header.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        }
        if (!target.hasAttribute('tabindex')) { target.setAttribute('tabindex', '-1'); }
        window.setTimeout(function () { target.focus({ preventScroll: true }); }, 0);
    });

    // ---------- Carrossel de fotos ----------
    Array.prototype.forEach.call(document.querySelectorAll('[data-gallery]'), function (gallery) {
        var slides = gallery.querySelectorAll('[data-slide]');
        var dots = gallery.querySelectorAll('[data-dot]');
        if (slides.length < 2) { return; }
        var current = 0;
        var show = function (index) {
            current = (index + slides.length) % slides.length;
            Array.prototype.forEach.call(slides, function (s, i) { s.hidden = i !== current; });
            Array.prototype.forEach.call(dots, function (d, i) { d.setAttribute('aria-pressed', i === current ? 'true' : 'false'); });
        };
        Array.prototype.forEach.call(dots, function (dot) {
            dot.addEventListener('click', function () { show(parseInt(dot.getAttribute('data-dot'), 10)); });
            dot.addEventListener('keydown', function (ev) {
                if (ev.key === 'ArrowRight' || ev.key === 'ArrowLeft') {
                    ev.preventDefault();
                    show(current + (ev.key === 'ArrowRight' ? 1 : -1));
                    dots[current].focus();
                }
            });
        });
        // Gesto de arrastar no celular.
        var frame = gallery.querySelector('.gallery__frame');
        var startX = null;
        frame.addEventListener('touchstart', function (ev) { startX = ev.touches[0].clientX; }, { passive: true });
        frame.addEventListener('touchend', function (ev) {
            if (startX === null) { return; }
            var dx = ev.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 40) { show(current + (dx < 0 ? 1 : -1)); }
            startX = null;
        });
    });

    // ---------- Mapa: troca de unidade ----------
    var map = document.querySelector('[data-map]');
    var select = map && map.querySelector('[data-map-select]');
    if (select) {
        var frameEl = map.querySelector('[data-map-frame]');
        var title = map.querySelector('[data-map-title]');
        select.addEventListener('change', function () {
            var chosen = null;
            Array.prototype.forEach.call(map.querySelectorAll('[data-map-unit]'), function (unit) {
                var match = unit.getAttribute('data-map-unit') === select.value;
                unit.hidden = !match;
                if (match) { chosen = unit; }
            });
            if (chosen && frameEl) {
                frameEl.src = chosen.getAttribute('data-map-src');
                frameEl.title = 'Mapa: ' + chosen.getAttribute('data-map-name');
                if (title) { title.textContent = chosen.getAttribute('data-map-name'); }
            }
        });
    }
})();
