<?php
/**
 * Zpracování kontaktního formuláře.
 *
 * Konfigurace (SMTP údaje, cílový e-mail) je v config.local.php, který do gitu
 * nepatří a deploy ho v docrootu nechává na místě. Vzor: config.local.php.vzor
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

/**
 * Pozor na volbu stavového kódu.
 *
 * nginx má pro tyhle weby `fastcgi_intercept_errors on` a `error_page` pro
 * 403, 404, 500 a 503. Kdyby endpoint některý z nich vrátil, nginx naše JSON
 * tělo zahodí a nahradí chybovou stránkou (a pro 503 žádná neexistuje, takže
 * z toho vypadne 404). Používáme proto jen kódy, které nginx nechá projít.
 */
function odpoved(bool $ok, string $zprava, int $stav = 200): void
{
    http_response_code($stav);
    echo json_encode(['ok' => $ok, 'zprava' => $zprava], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    odpoved(false, 'Neplatný požadavek.', 405);
}

$config = __DIR__ . '/config.local.php';
if (!is_file($config)) {
    error_log('odeslat.php: chybí config.local.php');
    odpoved(false, 'Formulář zatím není nastavený. Napište nám prosím přímo na truhlarstvi.hesu@seznam.cz.', 502);
}
$nastaveni = require $config;

// ------------------------------------------------------------- antispam --

// 1) Skryté pole, které vyplní jen robot.
if (trim((string) ($_POST['web'] ?? '')) !== '') {
    // Robotovi tvrdíme, že se povedlo — ať to nezkouší jinak.
    odpoved(true, 'Děkujeme, zprávu jsme přijali.');
}

// 2) Formulář odeslaný do 3 vteřin od načtení stránky.
$cas = (int) ($_POST['cas'] ?? 0);
if ($cas > 0 && (microtime(true) * 1000 - $cas) < 3000) {
    odpoved(false, 'Formulář byl odeslán příliš rychle. Zkuste to prosím znovu.', 429);
}

// 3) Jednoduchý limit na IP — nejvýš 5 odeslání za hodinu.
$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$stopa = sys_get_temp_dir() . '/poptavka-' . hash('sha256', $ip) . '.log';
$pokusy = is_file($stopa) ? array_filter(explode("\n", (string) file_get_contents($stopa))) : [];
$pokusy = array_values(array_filter($pokusy, static fn($t) => (int) $t > time() - 3600));

if (count($pokusy) >= 5) {
    odpoved(false, 'Z této adresy přišlo příliš mnoho zpráv. Zkuste to prosím za hodinu, nebo nám zavolejte.', 429);
}

// -------------------------------------------------------------- validace --

$jmeno    = trim((string) ($_POST['jmeno'] ?? ''));
$prijmeni = trim((string) ($_POST['prijmeni'] ?? ''));
$telefon  = trim((string) ($_POST['telefon'] ?? ''));
$email    = trim((string) ($_POST['email'] ?? ''));
$text     = trim((string) ($_POST['zprava'] ?? ''));

$chyby = [];

if ($telefon === '') {
    $chyby[] = 'telefon';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $chyby[] = 'e-mail';
}

// Vložené hlavičky do jména/e-mailu = pokus o injection.
foreach ([$jmeno, $prijmeni, $email, $telefon] as $hodnota) {
    if (preg_match('/[\r\n]/', $hodnota)) {
        odpoved(false, 'Neplatný vstup.', 400);
    }
}

if ($chyby) {
    odpoved(false, 'Vyplňte prosím správně: ' . implode(', ', $chyby) . '.', 422);
}

if (mb_strlen($text) > 5000) {
    odpoved(false, 'Zpráva je příliš dlouhá.', 422);
}

// -------------------------------------------------------------- odeslání --

$celeJmeno = trim($jmeno . ' ' . $prijmeni);

$telo = "Nová poptávka z webu truhlarstvi-suchanek.cz\n"
      . str_repeat('-', 46) . "\n\n"
      . 'Jméno:    ' . ($celeJmeno !== '' ? $celeJmeno : '(neuvedeno)') . "\n"
      . 'Telefon:  ' . $telefon . "\n"
      . 'E-mail:   ' . $email . "\n\n"
      . "Zpráva:\n" . ($text !== '' ? $text : '(prázdná)') . "\n\n"
      . str_repeat('-', 46) . "\n"
      . 'Odesláno: ' . date('j. n. Y H:i:s') . "\n"
      . 'IP:       ' . $ip . "\n";

require __DIR__ . '/lib/Smtp.php';

try {
    $smtp = new Smtp($nastaveni['smtp']);
    $smtp->odesli([
        'od'            => $nastaveni['odesilatel'],
        'odJmeno'       => $nastaveni['odesilatelJmeno'] ?? 'Web truhlářství Suchánek',
        'komu'          => $nastaveni['prijemce'],
        'odpovedetKomu' => $email,
        'predmet'       => 'Poptávka z webu' . ($celeJmeno !== '' ? ' – ' . $celeJmeno : ''),
        'telo'          => $telo,
    ]);
} catch (Throwable $e) {
    error_log('odeslat.php: ' . $e->getMessage());
    odpoved(false, 'Zprávu se nepodařilo odeslat. Zavolejte nám prosím na +420 774 308 086.', 502);
}

$pokusy[] = (string) time();
@file_put_contents($stopa, implode("\n", $pokusy));

odpoved(true, 'Děkujeme, zprávu jsme přijali. Ozveme se vám co nejdříve.');
