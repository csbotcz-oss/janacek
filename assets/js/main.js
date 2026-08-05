/* Chata Prášilka — chování stránky. Bez závislostí. */
(function () {
    'use strict';

    var neanimovat = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* --------------------------------------------------------------- najíždění -- */

    /* Výchozí skrytý stav zapíná třída `animace` na <html>, kterou nasazuje
       krátký skript v hlavičce. Když sem doběhneme, stačí bloky pozorovat
       a odkrývat. Originál je odkrývá jen jedním směrem — po odscrollování
       zpátky zůstanou vidět. */
    var NAJIZDEJICI = '.hero__obsah, .dlazdice .radek, .sekce .radek, .rezervace .obsah,'
                    + ' .paticka .obsah, .galerie__polozka, #aktivity .foto-aktivita';

    if (document.documentElement.classList.contains('animace')) {
        // Signál pro pojistku v hlavičce, že se o odkrývání někdo stará.
        window.najizdeniBezi = true;

        var bloky = [].slice.call(document.querySelectorAll(NAJIZDEJICI));

        if (!('IntersectionObserver' in window)) {
            // Bez podpory nemá smysl nic schovávat.
            document.documentElement.classList.remove('animace');
        } else {
            var pozorovatel = new IntersectionObserver(function (zaznamy) {
                zaznamy.forEach(function (z) {
                    if (!z.isIntersecting) return;
                    z.target.classList.add('je-videt');
                    pozorovatel.unobserve(z.target);
                });
            });

            bloky.forEach(function (b) { pozorovatel.observe(b); });
        }
    }

    /* ---------------------------------------------------- odkazy navíc (tablet) -- */

    /* Mezi 720 a 992 px se odkazy do lišty nevejdou. Originál je neschová za
       hamburger — přesune ty přebývající do rozbalovátka „Více". Počítáme to
       podle skutečné šířky, ne podle pevného zlomu, protože záleží na tom,
       jak dlouhé popisky zrovna jsou. */

    var menuPrvek = document.getElementById('menu');
    var vice = document.querySelector('.menu__vice');

    if (menuPrvek && vice) {
        var vicePrepinac = vice.querySelector('.menu__vice-prepinac');
        var viceObsah = vice.querySelector('.menu__vice-obsah');
        var odkazy = [].slice.call(menuPrvek.querySelectorAll(':scope > a'));
        var siroko = window.matchMedia('(min-width: 720px)');

        function vratVsechny() {
            odkazy.forEach(function (a) {
                if (a.parentElement !== menuPrvek) menuPrvek.insertBefore(a, vice);
            });
        }

        function prerovnej() {
            zavriVice();
            vratVsechny();
            vice.hidden = true;

            if (!siroko.matches) return;               // pod 720 se řeší hamburgerem

            var lista = document.querySelector('.hlavicka__lista');
            var logo = document.querySelector('.hlavicka__logo');
            var vpravo = document.querySelector('.hlavicka__vpravo');
            var cta = document.querySelector('.hlavicka .tlacitko');

            function misto() {
                var sl = getComputedStyle(lista);
                var k = lista.clientWidth - parseFloat(sl.paddingLeft) - parseFloat(sl.paddingRight);
                k -= logo.getBoundingClientRect().width + (parseFloat(sl.columnGap) || 0);
                if (cta && cta.offsetParent) {
                    k -= cta.getBoundingClientRect().width
                       + (parseFloat(getComputedStyle(vpravo).columnGap) || 0);
                }
                return k;
            }

            function sirkaMenu() {
                var soucet = 0;
                [].slice.call(menuPrvek.children).forEach(function (e) {
                    if (e.hidden) return;
                    soucet += e.getBoundingClientRect().width;
                });
                return soucet;
            }

            if (sirkaMenu() > misto()) vice.hidden = false;

            var k = odkazy.length - 1;
            while (k >= 0 && sirkaMenu() > misto()) {
                viceObsah.insertBefore(odkazy[k], viceObsah.firstChild);
                k--;
            }

            if (!viceObsah.children.length) vice.hidden = true;
        }

        function zavriVice() {
            vice.removeAttribute('data-otevreno');
            vicePrepinac.setAttribute('aria-expanded', 'false');
        }

        vicePrepinac.addEventListener('click', function () {
            var otevreno = vice.hasAttribute('data-otevreno');
            if (otevreno) zavriVice();
            else {
                vice.setAttribute('data-otevreno', '');
                vicePrepinac.setAttribute('aria-expanded', 'true');
            }
        });

        document.addEventListener('click', function (u) {
            if (!vice.contains(u.target)) zavriVice();
        });

        var casovac;
        window.addEventListener('resize', function () {
            clearTimeout(casovac);
            casovac = setTimeout(prerovnej, 150);
        });

        prerovnej();
    }

    /* ------------------------------------------------------------ mobilní menu -- */

    var prepinac = document.querySelector('.menu-prepinac');
    var menu = document.getElementById('menu');

    if (prepinac && menu) {
        // Pod 720 px se odkazy schovají za přepínač; na tabletu je řeší „Více".
        var mobil = window.matchMedia('(max-width: 719px)');

        // Rozbalené menu překrývá celou stránku bílou plochou, jako originál.
        // Třída na <body> zprůhlední lištu a zamkne rolování pod překryvem.
        function otevriMenu() {
            menu.classList.add('menu--otevrene');
            document.body.classList.add('je-menu');
            prepinac.setAttribute('aria-expanded', 'true');
            prepinac.setAttribute('aria-label', 'Zavřít menu');
        }

        function zavriMenu() {
            menu.classList.remove('menu--otevrene');
            document.body.classList.remove('je-menu');
            prepinac.setAttribute('aria-expanded', 'false');
            prepinac.setAttribute('aria-label', 'Otevřít menu');
        }

        zavriMenu();
        mobil.addEventListener('change', zavriMenu);

        document.addEventListener('keydown', function (u) {
            if (u.key === 'Escape') zavriMenu();
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
                    if (data.ok) {
                        formular.reset();
                        // Měření si to odchytí samo, pokud k němu je souhlas.
                        document.dispatchEvent(new CustomEvent('poptavka-odeslana'));
                    }
                })
                .catch(function () {
                    ukazHlasku('Zprávu se nepodařilo odeslat. Zkuste to prosím znovu, nebo nám zavolejte.', false);
                })
                .finally(function () { tlacitko.disabled = false; });
        });
    }

})();
