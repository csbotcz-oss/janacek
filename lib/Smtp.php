<?php
/**
 * Minimalistický SMTP klient.
 *
 * Na serveru není composer, takže žádný PHPMailer. Tohle umí přesně to, co web
 * potřebuje: připojit se, přihlásit, odeslat jeden e-mail a skončit. Podporuje
 * implicitní TLS (port 465) i STARTTLS (587).
 */
final class Smtp
{
    private $spojeni;
    private $nastaveni;
    /** @var string[] komunikace se serverem, pro logování při chybě */
    private $log = [];

    public function __construct(array $nastaveni)
    {
        $vychozi = [
            'host'      => '',
            'port'      => 587,
            'uzivatel'  => '',
            'heslo'     => '',
            'sifrovani' => 'tls',   // 'tls' = STARTTLS, 'ssl' = implicitní, '' = žádné
            'timeout'   => 20,
        ];
        $this->nastaveni = array_merge($vychozi, $nastaveni);
    }

    /**
     * @param array $zprava od, odJmeno, komu, predmet, telo, odpovedetKomu
     * @throws RuntimeException
     */
    public function odesli(array $zprava): void
    {
        $this->pripoj();

        try {
            $this->prikaz('EHLO ' . $this->nazevKlienta(), [250]);

            if ($this->nastaveni['sifrovani'] === 'tls') {
                $this->prikaz('STARTTLS', [220]);
                $ok = stream_socket_enable_crypto(
                    $this->spojeni,
                    true,
                    STREAM_CRYPTO_METHOD_TLS_CLIENT
                    | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT
                    | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
                );
                if (!$ok) {
                    throw new RuntimeException('Nepodařilo se navázat TLS.');
                }
                // Po přepnutí na TLS se EHLO posílá znovu.
                $this->prikaz('EHLO ' . $this->nazevKlienta(), [250]);
            }

            if ($this->nastaveni['uzivatel'] !== '') {
                $this->prikaz('AUTH LOGIN', [334]);
                $this->prikaz(base64_encode($this->nastaveni['uzivatel']), [334]);
                $this->prikaz(base64_encode($this->nastaveni['heslo']), [235]);
            }

            $this->prikaz('MAIL FROM:<' . $zprava['od'] . '>', [250]);
            $this->prikaz('RCPT TO:<' . $zprava['komu'] . '>', [250, 251]);
            $this->prikaz('DATA', [354]);

            $data = $this->sestavZpravu($zprava);
            $this->zapis($data . "\r\n.");
            $this->precti([250]);

            $this->prikaz('QUIT', [221]);
        } finally {
            if (is_resource($this->spojeni)) {
                fclose($this->spojeni);
            }
        }
    }

    private function pripoj(): void
    {
        $host = $this->nastaveni['sifrovani'] === 'ssl'
            ? 'ssl://' . $this->nastaveni['host']
            : $this->nastaveni['host'];

        $this->spojeni = @stream_socket_client(
            $host . ':' . $this->nastaveni['port'],
            $cislo,
            $chyba,
            $this->nastaveni['timeout'],
            STREAM_CLIENT_CONNECT
        );

        if (!$this->spojeni) {
            throw new RuntimeException(sprintf(
                'Připojení k %s:%d selhalo (%s).',
                $this->nastaveni['host'],
                $this->nastaveni['port'],
                $chyba ?: 'neznámá chyba'
            ));
        }

        stream_set_timeout($this->spojeni, $this->nastaveni['timeout']);
        $this->precti([220]);
    }

    private function prikaz(string $prikaz, array $ocekavano): string
    {
        $this->zapis($prikaz);
        return $this->precti($ocekavano);
    }

    private function zapis(string $radek): void
    {
        // Hesla se do logu nikdy nedostanou.
        $this->log[] = '> ' . (strlen($radek) > 120 ? substr($radek, 0, 120) . '…' : $radek);
        fwrite($this->spojeni, $radek . "\r\n");
    }

    private function precti(array $ocekavano): string
    {
        $odpoved = '';
        while (($radek = fgets($this->spojeni, 1024)) !== false) {
            $odpoved .= $radek;
            // Poslední řádek vícerádkové odpovědi má na 4. pozici mezeru.
            if (strlen($radek) < 4 || $radek[3] !== '-') {
                break;
            }
        }

        $this->log[] = '< ' . trim($odpoved);
        $kod = (int) substr($odpoved, 0, 3);

        if (!in_array($kod, $ocekavano, true)) {
            throw new RuntimeException(sprintf(
                'SMTP odpověděl %d, čekáno %s. Odpověď: %s',
                $kod,
                implode('/', $ocekavano),
                trim($odpoved)
            ));
        }

        return $odpoved;
    }

    private function sestavZpravu(array $z): string
    {
        $hlavicky = [
            'Date'                      => date(DATE_RFC2822),
            'From'                      => $this->kodujAdresu($z['odJmeno'] ?? '', $z['od']),
            'To'                        => $z['komu'],
            'Subject'                   => $this->kodujHlavicku($z['predmet']),
            'Message-ID'                => '<' . bin2hex(random_bytes(16)) . '@' . $this->nazevKlienta() . '>',
            'MIME-Version'              => '1.0',
            'Content-Type'              => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
        ];

        if (!empty($z['odpovedetKomu'])) {
            $hlavicky['Reply-To'] = $z['odpovedetKomu'];
        }

        $vystup = '';
        foreach ($hlavicky as $nazev => $hodnota) {
            $vystup .= $nazev . ': ' . $hodnota . "\r\n";
        }

        // Tečka na začátku řádku má v SMTP zvláštní význam, musí se zdvojit.
        $telo = preg_replace('/^\./m', '..', str_replace("\n", "\r\n", $z['telo']));

        return $vystup . "\r\n" . $telo;
    }

    private function kodujHlavicku(string $text): string
    {
        return preg_match('/[^\x20-\x7E]/', $text)
            ? '=?UTF-8?B?' . base64_encode($text) . '?='
            : $text;
    }

    private function kodujAdresu(string $jmeno, string $adresa): string
    {
        return $jmeno === '' ? $adresa : $this->kodujHlavicku($jmeno) . ' <' . $adresa . '>';
    }

    private function nazevKlienta(): string
    {
        $host = $_SERVER['SERVER_NAME'] ?? php_uname('n');
        return preg_match('/^[a-z0-9.-]+$/i', $host) ? $host : 'localhost';
    }

    public function getLog(): array
    {
        return $this->log;
    }
}
