# ANLAGE: Verwalter-Programmfluss und Betriebsvergleich (ISS-012)

## Typ
- Anlage (Design/Tech)

## Bezug
- [ISS-012](ISS-012-runtime-concurrency-locking-und-atomare-zugriffe.md)
- [ISS-011](ISS-011-ip-salt-runtime-verwaltung-und-guardrails.md)

## Ziel
- Gemeinsamen Programmfluss fuer Runtime-Verwalter vergleichbar machen.
- Festlegen, welche Rahmen-Bausteine in `ISS-011` vorgezogen werden duerfen.
- Trennscharf halten, welche flaechigen Umstellungen in `ISS-012` verbleiben.

## Geltungsbereich
- Rate-Limit: `allow`
- CAPTCHA: `createChallenge`, `verify`, `cleanupExpired`
- Token: `rotate`, `verify`, `findProfileForToken`
- `IP_SALT`-Runtime aus `ISS-011` als Referenz fuer den Rahmen

## Gemeinsamer Rahmen (verbindlich)
- Locking fuer kritische Schreibpfade wird mit `symfony/lock` umgesetzt.
- Lock-Erzeugung erfolgt zentral ueber `LockFactory`.
- Kritische Abschnitte laufen ueber einen gemeinsamen Helfer (z. B. `runWithLock`).
- Dateischreiben erfolgt atomar (Temp-Datei + `rename`).
- Fehler im kritischen Abschnitt brechen den Vorgang konsistent ab.

## Vergleich der Verwalter-Betriebe
| Bereich | Operation | Zugriffsmuster | Lock-Key-Granularitaet | Reset/Bereinigung | Atomarer Write |
| --- | --- | --- | --- | --- | --- |
| Rate-Limit | `allow(key, max, window)` | Read-Modify-Write einer Counter-Datei | pro `key` | keine globale Bereinigung; nur Fensterlogik | ja |
| CAPTCHA | `createChallenge(ipHash)` | Write einer neuen Challenge-Datei | kein Lock zwingend, wenn ID eindeutig; optional global | keine | ja |
| CAPTCHA | `verify(captchaId, answer, ipHash)` | Read-Modify-Write derselben Challenge-Datei | pro `captchaId` | bei Erfolg: `used_at` setzen; kein globaler Reset | ja |
| CAPTCHA | `cleanupExpired()` | Iterate + Delete vieler Dateien | optional globaler Wartungs-Lock | loescht abgelaufene/verbrauchte Challenges | n/a (Delete) |
| Token | `rotate(profile, tokens)` | Ersetzen einer Profil-Datei | pro `profile` | ersetzt alten Stand vollstaendig | ja |
| Token | `verify` / `findProfileForToken` | Read-only | kein Write-Lock; Konsistenz ueber atomare Writes der Writer | keine | n/a |
| IP_SALT | `resolveSalt()` | Read + Validierung + ggf. Rotate + Bereinigung | global (`ip_salt_runtime`) | bei Mismatch/Fehlen: IP-bezogenen State bereinigen | ja |
| IP_SALT | `resetSalt()` | explizite Rotation + Bereinigung | global (`ip_salt_runtime`) | immer IP-bezogenen State bereinigen | ja |

## Einheitlicher Programmfluss (Soll)
1. Eingaben pruefen und Lock-Key bestimmen.
2. Lock ueber `symfony/lock` erwerben.
3. Aktuellen Zustand laden.
4. Entscheidungsregel auswerten (kein Write, Update, Reset, Delete).
5. Zustand atomar schreiben oder gezielt bereinigen.
6. Ergebnisobjekt zurueckgeben (Status + Kontext).
7. Lock immer in `finally` freigeben.

## Abgrenzung ISS-011 vs ISS-012
| Baustein | In ISS-011 erlaubt | In ISS-012 verpflichtend |
| --- | --- | --- |
| Zentraler Lock-Helfer (`symfony/lock`) | ja, minimal fuer `IP_SALT` | ja, fuer alle kritischen Runtime-Schreibpfade |
| Atomarer Write-Helfer | ja, falls fuer `IP_SALT` benoetigt | ja, harmonisiert fuer alle betroffenen Bereiche |
| Schluessel-/Profil-Lockstrategie fuer alle Verwalter | nein | ja |
| Flaechige Migration von Rate-Limit/CAPTCHA/Token auf den Rahmen | nein | ja |
| Race-nahe Tests pro Bereich | nur `IP_SALT`-Pfad | ja, komplett fuer alle Zielbereiche |

## Arbeitsreihenfolge (empfohlen)
1. In `ISS-011` nur den minimalen Rahmen fuer `IP_SALT` festziehen.
2. In `ISS-012` denselben Rahmen auf Rate-Limit, CAPTCHA und Token ausrollen.
3. Erst nach der Ausrollung gemeinsame Runtime-Race-Tests als Abschlussnachweis fuehren.
