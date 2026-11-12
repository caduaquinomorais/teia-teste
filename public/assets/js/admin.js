/* TEIA — painel administrativo: listas repetíveis e confirmações (JavaScript puro). */
(function () {
    'use strict';
    document.documentElement.classList.add('js');

    function randomKey() {
        return 'n' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
    }

    function updateListState(list) {
        var items = list.querySelector(':scope > [data-items]');
        var count = items ? items.children.length : 0;
        var max = parseInt(list.getAttribute('data-max') || '50', 10);
        var add = list.querySelector(':scope > [data-add]');
        if (add) { add.disabled = count >= max; }
    }

    document.addEventListener('click', function (ev) {
        var target = ev.target;
        if (!(target instanceof Element)) { return; }

        var add = target.closest('[data-add]');
        if (add) {
            var list = add.closest('[data-list]');
            var tpl = list.querySelector(':scope > template[data-template]');
            var items = list.querySelector(':scope > [data-items]');
            var max = parseInt(list.getAttribute('data-max') || '50', 10);
            if (items.children.length >= max) { return; }
            var placeholder = list.getAttribute('data-placeholder');
            var html = tpl.innerHTML.split(placeholder).join(randomKey());
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            var item = wrapper.firstElementChild;
            items.appendChild(item);
            updateListState(list);
            var first = item.querySelector('input:not([type=hidden]), textarea');
            if (first) { first.focus(); }
            return;
        }

        var remove = target.closest('[data-remove]');
        if (remove) {
            var itemEl = remove.closest('[data-item]');
            var parentList = itemEl.closest('[data-list]');
            var min = parseInt(parentList.getAttribute('data-min') || '0', 10);
            var siblings = itemEl.parentElement.children.length;
            if (siblings <= min) {
                window.alert('Esta lista precisa ter pelo menos ' + min + ' item(ns).');
                return;
            }
            if (window.confirm('Remover este item? A remoção só vale depois de clicar em “Publicar alterações”.')) {
                itemEl.remove();
                updateListState(parentList);
            }
            return;
        }

        var move = target.closest('[data-move]');
        if (move) {
            var it = move.closest('[data-item]');
            if (move.getAttribute('data-move') === 'up' && it.previousElementSibling) {
                it.parentElement.insertBefore(it, it.previousElementSibling);
            } else if (move.getAttribute('data-move') === 'down' && it.nextElementSibling) {
                it.parentElement.insertBefore(it.nextElementSibling, it);
            }
            move.focus();
        }
    });

    // Atualiza o título do item da lista enquanto se digita.
    document.addEventListener('input', function (ev) {
        var input = ev.target;
        if (!(input instanceof Element) || !input.hasAttribute('data-item-label')) { return; }
        var item = input.closest('[data-item]');
        var title = item && item.querySelector('[data-item-title]');
        if (title) { title.textContent = input.value.trim() || 'Novo item'; }
    });

    // Confirmações em formulários sensíveis.
    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        var message = form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            ev.preventDefault();
            return;
        }
        var prompt = form.getAttribute('data-prompt');
        if (prompt) {
            var answer = window.prompt(prompt);
            if (answer === null) { ev.preventDefault(); return; }
            var field = form.querySelector('input[name="confirmacao"]');
            if (field) { field.value = answer.trim(); }
        }
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-list]'), updateListState);
})();
