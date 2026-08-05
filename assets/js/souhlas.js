/* Souhlas s cookies a spuštění měření. Bez závislostí.
 *
 * Analytika se nespouští předem a „zamítnutá" — Google se nekontaktuje vůbec,
 * dokud návštěvník nesouhlasí. Je to přísnější výklad než samotný Consent
 * Mode a odpadá tím otázka, jestli je i to zamítnuté volání v pořádku.
 *
 * Volba se pamatuje rok. Po odvolání souhlasu se cookies Google smažou
 * a stránka se načte znovu, protože jednou nahraný gtag.js už zpátky nevrátíme.
 */
(function () {
    'use strict';

    // Měřicí ID z Google Analytics (G-XXXXXXXXXX). Dokud je prázdné,
    // nic se nenačítá a lišta se chová jako by analytika neexistovala.
    var MERICI_ID = '';

    var KLIC = 'cookies-souhlas';
    var VERZE = 1;                  // zvýšit, když přibude kategorie — vyžádá si souhlas znovu
    var PLATNOST_DNI = 365;

    var lista = document.getElementById('cookie-lista');
    var nastaveni = document.getElementById('cookie-nastaveni');
    if (!lista || !nastaveni) return;

    var prepinacAnalytika = nastaveni.querySelector('[name="analytika"]');

    /* ------------------------------------------------------------- úložiště -- */

    function nacti() {
        try {
            var v = JSON.parse(localStorage.getItem(KLIC));
            if (!v || v.verze !== VERZE) return null;
            var stari = (Date.now() - Date.parse(v.kdy)) / 86400000;
            return stari > PLATNOST_DNI ? null : v;
        } catch (e) {
            return null;                      // rozbitý nebo nedostupný localStorage
        }
    }

    function uloz(analytika) {
        try {
            localStorage.setItem(KLIC, JSON.stringify({
                verze: VERZE,
                kdy: new Date().toISOString(),
                analytika: !!analytika
            }));
        } catch (e) { /* soukromý režim — volba prostě nepřežije zavření */ }
    }

    /* ------------------------------------------------------------- analytika -- */

    var bezi = false;

    function spustAnalytiku() {
        if (bezi || !MERICI_ID) return;
        bezi = true;

        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };

        // Reklamní kategorie tu nemáme, drží se tedy zamítnuté. Kdyby se
        // někdy přidaly, mění se jen tohle a přibude přepínač v nastavení.
        gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'granted'
        });

        gtag('js', new Date());
        gtag('config', MERICI_ID);

        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(MERICI_ID);
        document.head.appendChild(s);
    }

    function smazCookiesGoogle() {
        var domena = location.hostname.replace(/^www\./, '');
        document.cookie.split(';').forEach(function (c) {
            var jmeno = c.split('=')[0].trim();
            if (!/^_ga/.test(jmeno) && jmeno !== '_gid') return;
            ['/', location.pathname].forEach(function (cesta) {
                [domena, '.' + domena, ''].forEach(function (d) {
                    document.cookie = jmeno + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=' + cesta
                                    + (d ? '; domain=' + d : '');
                });
            });
        });
    }

    /* ------------------------------------------------------------ zobrazení -- */

    function ukazListu() {
        lista.hidden = false;
    }

    function skryjListu() {
        lista.hidden = true;
    }

    function otevriNastaveni() {
        var v = nacti();
        prepinacAnalytika.checked = !!(v && v.analytika);
        if (typeof nastaveni.showModal === 'function') nastaveni.showModal();
        else nastaveni.setAttribute('open', '');
    }

    function zavriNastaveni() {
        if (typeof nastaveni.close === 'function') nastaveni.close();
        else nastaveni.removeAttribute('open');
    }

    /* ------------------------------------------------------------ rozhodnutí -- */

    function rozhodni(analytika) {
        var predtim = nacti();
        uloz(analytika);
        skryjListu();
        zavriNastaveni();

        if (analytika) {
            spustAnalytiku();
        } else if (predtim && predtim.analytika) {
            // Souhlas byl odvolaný — uklidit po sobě a začít načisto.
            smazCookiesGoogle();
            location.reload();
        }
    }

    /* --------------------------------------------------------------- obsluha -- */

    document.addEventListener('click', function (u) {
        var cil = u.target.closest('[data-souhlas]');
        if (!cil) return;
        u.preventDefault();

        switch (cil.getAttribute('data-souhlas')) {
            case 'vse':       rozhodni(true); break;
            case 'nic':       rozhodni(false); break;
            case 'nastaveni': otevriNastaveni(); break;
            case 'ulozit':    rozhodni(prepinacAnalytika.checked); break;
            case 'zavrit':    zavriNastaveni(); break;
        }
    });

    /* ------------------------------------------------------------------ start -- */

    var volba = nacti();

    if (!volba) {
        ukazListu();
    } else if (volba.analytika) {
        spustAnalytiku();
    }
})();
