# ANLAGE: ISS-011/ISS-012 Näherung und Commit-Folge

## Typ
- Anlage (Delivery/Tech)

## Bezug
- [ISS-011](ISS-011-ip-salt-runtime-verwaltung-und-guardrails.md)
- [ISS-012](ISS-012-runtime-concurrency-locking-und-atomare-zugriffe.md)
- [ISS-012 Anlage: Verwalter-Programmfluss und Betriebsvergleich](ISS-012-anlage-verwalter-programmfluss-und-betriebsvergleich.md)

## Ziel
- Eine gemeinsame, umsetzbare Näherung für `ISS-011` und `ISS-012` festlegen.
- Eine realistische Commit-Folge vorgeben, die auch mit unvollständiger Detailplanung sofort startbar ist.

## Gemeinsame Näherung (7 Schritte)
1. Scope fixieren:
   - für `ISS-011` exakt festlegen, welche IP-bezogenen Zustände bei Salt-Wechsel bereinigt werden.
2. Schnittstellen festlegen:
   - eine Runtime-Komponente als einzige Quelle für Salt laden/erzeugen/rotieren.
3. Guardrails definieren:
   - Locking über `symfony/lock`, atomisches Schreiben (`tmp` + `rename`), Dateirechte, Fingerprint-Prüfung.
4. Migrationspfad festziehen:
   - externe `IP_SALT`-Abhängigkeit abbauen, Altwerte nur als Übergang.
5. CLI-Pfad planen:
   - `--rotate-ip-salt` entfernen, expliziten Reset-Befehl (`cli ip-hash reset`) verwenden.
6. Testplan vorab festlegen:
   - Parallelzugriff, Fingerprint-Mismatch, fehlender Salt, Reset-Befehl, Bereinigungsnachweis.
7. DoD messen:
   - erst `done`, wenn Guardrails, Rückbau und Tests gemeinsam nachweisbar sind.

## Verteilung auf ISS-011 und ISS-012
| Schritt | Schwerpunkt ISS-011 | Schwerpunkt ISS-012 |
| --- | --- | --- |
| 1 | Pflicht | nur Referenz |
| 2 | Pflicht (IP_SALT) | Pflicht (Rate-Limit/CAPTCHA/Token ausrollen) |
| 3 | Pflicht (minimaler Rahmen) | Pflicht (flächige Vereinheitlichung) |
| 4 | Pflicht | nur Folgeanpassung |
| 5 | Pflicht | keine |
| 6 | Pflicht (IP_SALT-Pfade) | Pflicht (Race-nahe Tests je Verwalter) |
| 7 | Pflicht für ISS-011 | Pflicht für ISS-012 |

## Vorgeschlagene Commit-Folge
Hinweis:
- Die komplette Folge kann später verfeinert werden.
- Für den Start reichen die ersten 4 Commits als belastbare Basis.

### Startsequenz (konkret)
1. `docs(agile): dokumentiere ISS-011/012-Näherung und Commit-Folge`
2. `refactor(runtime): führe LockRunner und AtomicWriter für IP_SALT ein`
3. `feat(runtime): IP_SALT-Manager mit symfony/lock und Fingerprint-Guardrails`
4. `chore(cli+config): entferne --rotate-ip-salt und nutze ip-hash reset`

### Folgeblöcke (nachgelagert)
5. `test(runtime): ergänze IP_SALT-Parallel- und Guardrail-Tests`
6. `refactor(runtime): übernehme Locking-Rahmen für Rate-Limit und CAPTCHA-Verify`
7. `refactor(runtime): übernehme Locking-Rahmen für Token-Rotation`
8. `test(runtime): ergänze Race-nahe Tests für Rate-Limit, CAPTCHA und Token`
9. `docs(runtime): Nachweise und Betriebsnotiz für ISS-012 ergänzen`

## Einschätzung des Ablaufs
- Kohärenz: hoch, weil `ISS-011` als schmaler Referenzpfad den Rahmen setzt.
- Risiko: mittel, aber gut beherrschbar durch kleine Commits und klare Scope-Grenzen.
- Reviewbarkeit: hoch, da die Startsequenz getrennt nach Rahmen, Fachlogik und Migration arbeitet.
- Hauptgefahr: Scope-Drift durch zu frühe flächige Abstraktion.
- Gegenmaßnahme: in `ISS-011` nur minimalen Rahmen bauen und erst in `ISS-012` breit ausrollen.
