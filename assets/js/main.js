/* Chata Prášilka — chování stránky. Bez závislostí. */
(function () {
    'use strict';

    var neanimovat = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ------------------------------------------------------------ mobilní menu -- */

    var prepinac = document.querySelector('.menu-prepinac');
    var menu = document.getElementById('menu');

    if (prepinac && menu) {
        // Originál skrývá odkazy za přepínač už od tabletu.
        var mobil = window.matchMedia('(max-width: 1024px)');

        function otevriMenu() {
            menu.classList.add('menu--otevrene');
            // Konkrétní výška obsahu — na "auto" se přechod animovat nedá.
            menu.style.maxHeight = menu.scrollHeight + 'px';
            prepinac.setAttribute('aria-expanded', 'true');
            prepinac.setAttribute('aria-label', 'Zavřít menu');
        }

        function zavriMenu() {
            menu.classList.remove('menu--otevrene');
            menu.style.maxHeight = '';
            prepinac.setAttribute('aria-expanded', 'false');
            prepinac.setAttribute('aria-label', 'Otevřít menu');
        }

        zavriMenu();
        mobil.addEventListener('change', zavriMenu);

        window.addEventListener('resize', function () {
            if (menu.classList.contains('menu--otevrene')) {
                menu.style.maxHeight = menu.scrollHeight + 'px';
            }
        });

        prepinac.addEventListener('click', function () {
            if (menu.classList.contains('menu--otevrene')) zavriMenu();
            else otevriMenu();
        });

        menu.addEventListener('click', function (e) {
            if (e.target.tagName === 'A' && mobil.matches) zavriMenu();
        });
    }


    /* ----------------------------------------------------------------- lightbox -- */

    var lightbox = document.getElementById('lightbox');

    if (lightbox && typeof lightbox.showModal === 'function') {
        var odkazy = [].slice.call(document.querySelectorAll('[data-galerie]'));
        var obrazek = lightbox.querySelector('.lightbox__obsah img');
        var pocitadlo = document.getElementById('lightbox-pocitadlo');
        var popisek = document.getElementById('lightbox-popisek');
        var index = 0;

        function zobraz(i, smer) {
            index = (i + odkazy.length) % odkazy.length;
            var odkaz = odkazy[index];
            var vnitrni = odkaz.querySelector('img');
            var popis = vnitrni ? vnitrni.alt : '';

            obrazek.src = odkaz.href;
            obrazek.alt = popis;
            popisek.textContent = popis;
            pocitadlo.textContent = (index + 1) + ' / ' + odkazy.length;

            // Fotka nalétne ze strany, ze které se přepíná.
            if (!neanimovat) {
                obrazek.classList.remove('prijizdi-zleva', 'prijizdi-zprava');
                void obrazek.offsetWidth;           // vynutí restart animace
                obrazek.classList.add(smer < 0 ? 'prijizdi-zleva' : 'prijizdi-zprava');
            }
        }

        odkazy.forEach(function (odkaz, i) {
            odkaz.addEventListener('click', function (e) {
                e.preventDefault();
                zobraz(i, 1);
                lightbox.showModal();
                // Bez tohohle by prohlížeč zaostřil první tlačítko a kolem křížku
                // by hned po otevření svítil fokusový rámeček.
                lightbox.focus();
            });
        });

        lightbox.querySelectorAll('[data-lightbox-krok]').forEach(function (tlacitko) {
            tlacitko.addEventListener('click', function () {
                var smer = Number(tlacitko.dataset.lightboxKrok);
                zobraz(index + smer, smer);
            });
        });

        lightbox.querySelector('[data-lightbox-zavrit]').addEventListener('click', function () {
            lightbox.close();
        });

        // Klik mimo fotku zavírá.
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox) lightbox.close();
        });

        document.addEventListener('keydown', function (e) {
            if (!lightbox.open) return;
            if (e.key === 'ArrowLeft')  zobraz(index - 1, -1);
            if (e.key === 'ArrowRight') zobraz(index + 1, 1);
        });
    }


    /* ----------------------------------------------------------------- formulář -- */

    var formular = document.getElementById('poptavka');

    if (formular) {
        var hlaska = document.getElementById('formular-zprava');
        var cas = formular.querySelector('input[name="cas"]');

        // Razítko načtení stránky. Odeslání do pár vteřin = skoro jistě robot.
        if (cas) cas.value = String(Date.now());

        function ukazHlasku(text, ok) {
            hlaska.textContent = text;
            hlaska.className = 'formular__zprava formular__zprava--' + (ok ? 'ok' : 'chyba');
            hlaska.hidden = false;
        }

        formular.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!formular.checkValidity()) {
                formular.reportValidity();
                return;
            }

            var tlacitko = formular.querySelector('button[type="submit"]');
            tlacitko.disabled = true;

            fetch(formular.action, {
                method: 'POST',
                body: new FormData(formular),
                headers: { 'X-Requested-With': 'fetch' }
            })
                .then(function (r) {
                    return r.json().catch(function () {
                        return { ok: false, zprava: 'Server vrátil neočekávanou odpověď.' };
                    });
                })
                .then(function (data) {
                    ukazHlasku(data.zprava || (data.ok ? 'Odesláno.' : 'Zprávu se nepodařilo odeslat.'), !!data.ok);
                    if (data.ok) formular.reset();
                })
                .catch(function () {
                    ukazHlasku('Zprávu se nepodařilo odeslat. Zkuste to prosím znovu, nebo nám zavolejte.', false);
                })
                .finally(function () { tlacitko.disabled = false; });
        });
    }

})();
