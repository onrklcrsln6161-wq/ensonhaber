document.addEventListener('DOMContentLoaded', function () {
    initHeroSlider();
    initMobileNav();
    initTabWidgets();
});

function initTabWidgets() {
    document.querySelectorAll('.tabs-widget').forEach(function(widget, wi) {
        var buttons = Array.from(widget.querySelectorAll('.tab-btn'));
        var panels = Array.from(widget.querySelectorAll('.tab-panel'));
        widget.querySelector('.widget-tabs').setAttribute('role', 'tablist');
        function activate(index, focus) {
            buttons.forEach(function(button, i) {
                button.classList.toggle('active', i === index);
                button.setAttribute('aria-selected', String(i === index));
                button.tabIndex = i === index ? 0 : -1;
            });
            panels.forEach(function(panel) {
                var active = panel.dataset.panel === buttons[index].dataset.tab;
                panel.hidden = !active;
                panel.classList.toggle('active', active);
            });
            if (focus) buttons[index].focus();
        }
        buttons.forEach(function(button, i) {
            var panel = panels.find(function(p) { return p.dataset.panel === button.dataset.tab; });
            button.id = 'tab-' + wi + '-' + i;
            button.setAttribute('role', 'tab');
            panel.id = 'panel-' + wi + '-' + i;
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', button.id);
            button.setAttribute('aria-controls', panel.id);
            button.addEventListener('click', function() { activate(i, false); });
            button.addEventListener('keydown', function(event) {
                var next = {ArrowRight:(i+1)%buttons.length, ArrowLeft:(i+buttons.length-1)%buttons.length, Home:0, End:buttons.length-1}[event.key];
                if (next !== undefined) { event.preventDefault(); activate(next, true); }
            });
        });
        if (buttons.length) activate(0, false);
    });
}

function initMobileNav() {
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('mainNav');
    if (!toggle || !nav) return;

    function close() {
        document.body.classList.remove('nav-open');
        toggle.setAttribute('aria-expanded', 'false');
    }
    function open() {
        document.body.classList.add('nav-open');
        toggle.setAttribute('aria-expanded', 'true');
    }

    toggle.addEventListener('click', function () {
        if (document.body.classList.contains('nav-open')) close(); else open();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 700) close();
    });
}

function initHeroSlider() {
    var root = document.getElementById('heroSlider');
    if (!root) return;

    var slides = Array.prototype.slice.call(root.querySelectorAll('.slide'));
    var dots = Array.prototype.slice.call(root.querySelectorAll('.slider-dots button'));
    var thumbs = Array.prototype.slice.call(document.querySelectorAll('.slider-thumbs .thumb'));
    var prevBtn = root.querySelector('.slider-arrow.prev');
    var nextBtn = root.querySelector('.slider-arrow.next');
    var current = 0;
    var timer = null;
    var DELAY = 5000;

    function goTo(index) {
        if (index < 0) index = slides.length - 1;
        if (index >= slides.length) index = 0;
        slides[current].classList.remove('active');
        if (dots[current]) dots[current].classList.remove('active');
        if (thumbs[current]) thumbs[current].classList.remove('active');
        current = index;
        slides[current].classList.add('active');
        if (dots[current]) dots[current].classList.add('active');
        if (thumbs[current]) thumbs[current].classList.add('active');
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    function start() {
        stop();
        timer = setInterval(next, DELAY);
    }
    function stop() {
        if (timer) clearInterval(timer);
    }

    if (nextBtn) nextBtn.addEventListener('click', function () { next(); start(); });
    if (prevBtn) prevBtn.addEventListener('click', function () { prev(); start(); });
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () { goTo(parseInt(dot.dataset.index, 10)); start(); });
    });
    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () { goTo(parseInt(thumb.dataset.index, 10)); start(); });
    });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);

    if (slides.length > 1) start();
}
