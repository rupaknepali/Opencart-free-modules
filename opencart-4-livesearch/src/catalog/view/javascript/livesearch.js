/* Live Search for OpenCart 4: suggestions under the header search box (an ARIA combobox). */
(function () {
    'use strict';

    function init(form) {
        var input = form.querySelector('input[name="search"]');
        if (!input || form.dataset.livesearchReady) return;
        form.dataset.livesearchReady = '1';

        var url = form.dataset.livesearch, min = parseInt(form.dataset.min, 10) || 2;
        var list = document.createElement('div');
        var id = 'livesearch-' + Math.random().toString(36).slice(2, 8);
        var cache = new Map(), timer = null, controller = null, active = -1, last = '';

        list.id = id;
        list.className = 'livesearch';
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', form.dataset.label || 'Search suggestions');
        list.hidden = true;
        form.classList.add('livesearch-form');
        form.appendChild(list);

        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', id);
        input.setAttribute('autocomplete', 'off');

        function options() { return list.querySelectorAll('[role="option"]'); }

        function close() {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            active = -1;
        }

        function highlight(i) {
            var items = options();
            if (!items.length) return;
            active = (i + items.length) % items.length;
            items.forEach(function (el, n) { el.setAttribute('aria-selected', n === active ? 'true' : 'false'); });
            input.setAttribute('aria-activedescendant', items[active].id);
            items[active].scrollIntoView({ block: 'nearest' });
        }

        function el(tag, cls, text) {
            var node = document.createElement(tag);
            if (cls) node.className = cls;
            if (text) node.textContent = text;   // textContent, never innerHTML: names come from the database
            return node;
        }

        function render(data) {
            list.textContent = '';
            var n = 0;

            function option(href, build) {
                var a = el('a', 'livesearch-item');
                a.href = href;
                a.id = id + '-' + n++;
                a.setAttribute('role', 'option');
                a.setAttribute('aria-selected', 'false');
                a.tabIndex = -1;
                build(a);
                list.appendChild(a);
            }

            (data.categories || []).forEach(function (c) {
                option(c.href, function (a) {
                    a.classList.add('livesearch-category');
                    a.appendChild(el('i', 'fa-solid fa-folder-open'));
                    a.appendChild(el('span', 'livesearch-name', c.name));
                });
            });

            (data.products || []).forEach(function (p) {
                option(p.href, function (a) {
                    if (p.thumb) {
                        var img = el('img');
                        img.src = p.thumb; img.alt = ''; img.width = 48; img.height = 48; img.loading = 'lazy';
                        a.appendChild(img);
                    }
                    var text = el('span', 'livesearch-text');
                    text.appendChild(el('span', 'livesearch-name', p.name));
                    if (p.price) {
                        var price = el('span', 'livesearch-price');
                        if (p.special) {
                            price.appendChild(el('span', 'livesearch-new', p.special));
                            price.appendChild(el('s', 'livesearch-old', p.price));
                        } else {
                            price.textContent = p.price;
                        }
                        text.appendChild(price);
                    }
                    a.appendChild(text);
                });
            });

            if (data.all) {
                option(data.all.href, function (a) {
                    a.classList.add('livesearch-all');
                    a.textContent = data.all.text;
                });
            }

            if (!n) list.appendChild(el('div', 'livesearch-status', form.dataset.empty || 'No products found'));

            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            active = -1;
        }

        function lookup(q) {
            if (cache.has(q)) return render(cache.get(q));
            if (controller) controller.abort();   // a newer keystroke wins; the older request is cancelled
            controller = new AbortController();

            list.textContent = '';
            list.appendChild(el('div', 'livesearch-status', form.dataset.loading || 'Searching…'));
            list.hidden = false;

            fetch(url + '&search=' + encodeURIComponent(q), { signal: controller.signal, headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) { cache.set(q, data); if (input.value.trim() === q) render(data); })
                .catch(function (e) { if (e.name !== 'AbortError') close(); });
        }

        input.addEventListener('input', function () {
            var q = input.value.trim();
            clearTimeout(timer);
            if (q.length < min) { close(); last = ''; return; }
            if (q === last) return;
            last = q;
            timer = setTimeout(function () { lookup(q); }, 250);
        });

        input.addEventListener('keydown', function (e) {
            if (list.hidden) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); highlight(active + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(active - 1); }
            else if (e.key === 'Escape') { close(); }
            else if (e.key === 'Enter' && active > -1) { e.preventDefault(); options()[active].click(); }
        });

        input.addEventListener('focus', function () { if (list.childNodes.length && input.value.trim().length >= min) { list.hidden = false; input.setAttribute('aria-expanded', 'true'); } });
        document.addEventListener('click', function (e) { if (!form.contains(e.target)) close(); });
    }

    function start() { document.querySelectorAll('form[data-livesearch]').forEach(init); }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
