# ISSUE: Runtime-Concurrency, Locking und atomare Zugriffe

## Typ
- Issue (Delivery/Tech)

## Status
- Offen (vor Abschluss von ISS-005)

## Abgeleitet aus
- [ISS-011](ISS-011-ip-salt-runtime-verwaltung-und-guardrails.md)
- Laufzeit-Befund zu Race-Conditions in `src/http`

## Problem
- Mehrere Runtime-Module schreiben gemeinsamen Dateizustand ohne durchgaengiges Locking.
- Read-Modify-Write-Pfade koennen unter Parallelzugriff inkonsistent werden.
- Atomare Writes sind teilweise vorhanden, aber nicht konsistent in allen kritischen Pfaden.

## Ziel
- Kritische Runtime-Schreibpfade sind unter Parallelzugriff konsistent.
- Locking wird einheitlich ueber `symfony/lock` umgesetzt.
- Read-Modify-Write-Operationen werden pro Schluessel/Datei sauber serialisiert.

## Ergaenzende Doku
- [Anlage: Verwalter-Programmfluss und Betriebsvergleich](ISS-012-anlage-verwalter-programmfluss-und-betriebsvergleich.md)
- [Anlage: ISS-011/ISS-012 Näherung und Commit-Folge](ISS-011-012-anlage-naeherung-und-commitfolge.md)

## Scope
- Einfuehrung von `symfony/lock` fuer Runtime-Dateizugriffe.
- Architekturrahmen gemaess Anlage flaechig ausrollen:
  - Komposition mit `LockRunner`, `AtomicWriter`, `StateReader/StateValidator`, `ResetExecutor`.
  - Trigger-/Policy-Muster (`TriggerReason`, `DecisionPolicy`, `ActionPlan`) konsistent je Verwalter anwenden.
- Lock-Strategie festlegen:
  - schluesselbezogene Locks fuer Rate-Limit und CAPTCHA-Verify.
  - profilbezogene Locks fuer Token-Rotation.
  - zentrale Lock-Helfer fuer wiederverwendbare Guards.
- Atomare Write-Helfer harmonisieren (eindeutige Temp-Datei + `rename`).
- Tests:
  - Parallel-/Race-nahe Tests fuer kritische Pfade.
  - Regression-Tests fuer bestehendes Verhalten.
- Doku:
  - kurze Betriebsnotiz, welche Runtime-Bereiche gelockt sind und warum.

## Nicht im Scope
- FTP/FTPS-Remote-Verwaltung (eigene Folge-Issue).
- Allgemeines Refactoring ausserhalb der betroffenen I/O-Pfade.

## Akzeptanzkriterien
- Rate-Limit, CAPTCHA-Verify und Token-Rotation laufen ohne bekannte Read-Modify-Write-Races.
- File-Writes nutzen konsistent atomare Muster.
- Locking ist in Runtime-Code zentral erkennbar und testbar.
- `ISS-005` kann fuer Runtime-Stabilitaet auf diesen Nachweis verweisen.

## Abhaengigkeiten
- Story-Kontext:
  - [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md)
- Voraussetzungen:
  - [ISS-011](ISS-011-ip-salt-runtime-verwaltung-und-guardrails.md)
- Wirkt auf:
  - [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md)
- Folge-Issue:
  - [ISS-013](ISS-013-ftp-ftps-verwaltungs-skripte-fuer-preview-betrieb.md) (nachgelagert, nicht blockierend fuer ISS-005)
