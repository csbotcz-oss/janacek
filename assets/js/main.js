/* Truhlářství Suchánek — chování stránky. Bez závislostí. */
(function () {
    'use strict';

    var neanimovat = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ------------------------------------------ zvýrazněná slova v nadpisech --
       Pod zvýrazněné slovo se dokresluje zelená klikatá čára. Tvar i časování
       odpovídají původnímu webu (Elementor, marker "underline_zigzag").        */

    var ZIGZAG = 'M9.3,127.3c49.3-3,150.7-7.6,199.7-7.4c121.9,0.4,189.9,0.4,282.3,7.2C380.1,129.6,181.2,130.6,70,139 c82.6-2.9,254.2-1,335.9,1.3c-56,1.4-137.2-0.3-197.1,9';
    var PRODLEVA = 8000;   // pauza mezi opakováními
    var TRVANI   = 1200;   // délka kresby

    function pripravZvyrazneni(prvek) {
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 500 150');
        svg.setAttribute('preserveAspectRatio', 'none');
        svg.setAttribute('aria-hidden', 'true');

        var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', ZIGZAG);
        svg.appendChild(path);
        prvek.appendChild(svg);

        // Délka dráhy určuje dash offset, jinak by čára "problikla" celá naráz.
        var delka = path.getTotalLength();
        prvek.style.setProperty('--delka', delka);

        return prvek;
    }

    var zvyraznena = [].slice.call(document.querySelectorAll('.zvyraznene'));
    zvyraznena.forEach(pripravZvyrazneni);

    if (!neanimovat && 'IntersectionObserver' in window) {
        var pozorovatel = new IntersectionObserver(function (zaznamy) {
            zaznamy.forEach(function (z) {
                if (!z.isIntersecting) return;
                var prvek = z.target;
                if (prvek.dataset.bezi) return;
                prvek.dataset.bezi = '1';

                var kresli = function () {
                    prvek.classList.remove('kresli');
                    void prvek.offsetWidth;          // vynutí restart animace
                    prvek.classList.add('kresli');
                };

                kresli();
                setInterval(kresli, PRODLEVA + TRVANI);
            });
        }, { threshold: 0.5 });

        zvyraznena.forEach(function (p) { pozorovatel.observe(p); });
    } else {
        zvyraznena.forEach(function (p) { p.classList.add('kresli'); });
    }


    /* ------------------------------------------------------------ mobilní menu -- */

    var prepinac = document.querySelector('.menu-prepinac');
    var menu = document.getElementById('menu');

    function zavriMenu() {
        if (!prepinac) return;
        menu.hidden = true;
        prepinac.setAttribute('aria-expanded', 'false');
        prepinac.setAttribute('aria-label', 'Otevřít menu');
    }

    if (prepinac && menu) {
        // Na mobilu startuje zavřené; na širších displejích ho CSS zobrazí vždy.
        var mobil = window.matchMedia('(max-width: 767px)');
        var srovnej = function () { if (mobil.matches) zavriMenu(); else menu.hidden = false; };
        srovnej();
        mobil.addEventListener('change', srovnej);

        prepinac.addEventListener('click', function () {
            var otevreno = menu.hidden;
            menu.hidden = !otevreno;
            prepinac.setAttribute('aria-expanded', String(otevreno));
            prepinac.setAttribute('aria-label', otevreno ? 'Zavřít menu' : 'Otevřít menu');
        });

        menu.addEventListener('click', function (e) {
            if (e.target.tagName === 'A' && mobil.matches) zavriMenu();
        });
    }


    /* ---------------------------------------------------------------- karusel -- */

    /* Nekonečný karusel — nastavení převzaté z originálu:
       autoplay po 5 s, přejezd 500 ms, pauza při najetí myší, po ruční
       interakci se autoplay vypne.

       Před i za skutečné položky se vloží celá jejich kopie. Stojíme vždy
       ve střední sadě; jakmile by krok vyjel ven, nejdřív se okamžitě
       přemístíme o celou sadu na vizuálně shodné místo a teprve odtud se
       plynule popojede. Díky tomu nikdy nenarazíme na okraj rozsahu.

       Plynulost řídíme parametrem scrollTo, ne vlastností scroll-behavior
       v CSS — ta totiž animuje i přímé zápisy do scrollLeft, takže by se
       „okamžitý" přeskok na klon viditelně přejel. */

    function postavKarusel(karusel, zpet, vpred) {
        var puvodni = [].slice.call(karusel.children);
        var n = puvodni.length;
        if (n < 2) return;

        var PRODLEVA = 5000;   // pauza mezi automatickými posuvy, jako na originále

        var predek = document.createDocumentFragment();
        var zadek = document.createDocumentFragment();

        puvodni.forEach(function (p) {
            [predek, zadek].forEach(function (kam) {
                var k = p.cloneNode(true);
                k.dataset.klon = '1';
                k.setAttribute('aria-hidden', 'true');
                kam.appendChild(k);
            });
        });

        karusel.insertBefore(predek, karusel.firstChild);
        karusel.appendChild(zadek);

        var deti = [].slice.call(karusel.children);
        var i = n;                 // index položky u levého okraje

        // Absolutní pozice položky v rámci posuvníku. Nezávisí na tom,
        // kde zrovna jsme, takže se dá počítat i uprostřed animace.
        function pozice(k) {
            return deti[k].getBoundingClientRect().left
                 - karusel.getBoundingClientRect().left
                 + karusel.scrollLeft;
        }

        /* Posun necháváme na prohlížeči. Vlastní animace přes
           requestAnimationFrame by se v zabrzděné nebo skryté záložce
           nemusela vůbec rozběhnout a karusel by stál. */
        function jedNa(cil, hned) {
            if (hned || neanimovat) {
                karusel.scrollLeft = cil;
            } else {
                karusel.scrollTo({ left: cil, behavior: 'smooth' });
            }
        }

        function jdi(smer) {
            var cil = i + smer;

            if (cil > 2 * n - 1) { i -= n; cil -= n; jedNa(pozice(i), true); }
            else if (cil < n)    { i += n; cil += n; jedNa(pozice(i), true); }

            i = cil;
            jedNa(pozice(i));
        }

        /* ---- automatické posouvání ---- */

        var tik = null;

        function spustAutoplay() {
            if (tik) return;
            tik = setInterval(function () { jdi(1); }, PRODLEVA);
        }

        function zastavAutoplay() {
            clearInterval(tik);
            tik = null;
        }

        // Po ručním zásahu se autoplay vypne natrvalo, stejně jako na originálu.
        var uzivatelZasahl = false;

        function rucne(smer) {
            uzivatelZasahl = true;
            zastavAutoplay();
            jdi(smer);
        }

        zpet.addEventListener('click', function () { rucne(-1); });
        vpred.addEventListener('click', function () { rucne(1); });

        karusel.addEventListener('mouseenter', zastavAutoplay);
        karusel.addEventListener('mouseleave', function () {
            if (!uzivatelZasahl) spustAutoplay();
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) zastavAutoplay();
            else if (!uzivatelZasahl) spustAutoplay();
        });

        // Po ručním přetažení prstem dohledáme, kde jsme skončili,
        // a vrátíme se do střední sady.
        function dorovnej() {
            var nejblizsi = i;
            var nejmensi = Infinity;

            for (var k = 0; k < deti.length; k++) {
                var vzdalenost = Math.abs(pozice(k) - karusel.scrollLeft);
                if (vzdalenost < nejmensi) { nejmensi = vzdalenost; nejblizsi = k; }
            }

            i = nejblizsi;
            if (i < n) { i += n; jedNa(pozice(i), true); }
            else if (i > 2 * n - 1) { i -= n; jedNa(pozice(i), true); }
        }

        if ('onscrollend' in window) {
            karusel.addEventListener('scrollend', dorovnej);
        } else {
            var casovac;
            karusel.addEventListener('scroll', function () {
                clearTimeout(casovac);
                casovac = setTimeout(dorovnej, 160);
            }, { passive: true });
        }

        karusel.addEventListener('pointerdown', function () {
            uzivatelZasahl = true;
            zastavAutoplay();
        }, { passive: true });

        window.addEventListener('resize', function () { jedNa(pozice(i), true); });

        jedNa(pozice(i), true);
        spustAutoplay();
    }

    document.querySelectorAll('[data-karusel-zpet]').forEach(function (zpet) {
        var karusel = document.getElementById(zpet.dataset.karuselZpet);
        var vpred = document.querySelector('[data-karusel-vpred="' + zpet.dataset.karuselZpet + '"]');
        if (karusel && vpred) postavKarusel(karusel, zpet, vpred);
    });


    /* ------------------------------------------------ náběh při scrollování -- */

    var nabihajici = [].slice.call(document.querySelectorAll('.nabiha'));

    if (nabihajici.length) {
        if ('IntersectionObserver' in window) {
            var sledovac = new IntersectionObserver(function (zaznamy, self) {
                zaznamy.forEach(function (z) {
                    if (!z.isIntersecting) return;
                    z.target.classList.add('nabehlo');
                    self.unobserve(z.target);
                });
            }, { threshold: 0.15 });

            nabihajici.forEach(function (p) { sledovac.observe(p); });
        } else {
            nabihajici.forEach(function (p) { p.classList.add('nabehlo'); });
        }
    }


    /* --------------------------------------------------------------- lightbox -- */

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


    /* --------------------------------------------------------------- formulář -- */

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
                .then(function (r) { return r.json().catch(function () { return { ok: false, zprava: 'Server vrátil neočekávanou odpověď.' }; }); })
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
