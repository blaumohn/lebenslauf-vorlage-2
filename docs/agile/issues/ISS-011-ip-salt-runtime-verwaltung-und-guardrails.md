# ISSUE: IP_SALT Runtime-Verwaltung und Guardrails

## Typ
- Issue (Delivery/Design)

## Status
- Offen (nächste Aufgabe)

## Abgeleitet aus
- [ISS-010](ISS-010-preview-workflow-testmatrix-und-entscheidungen.md) (`IP_SALT`-Strategie)

## Problem
- `IP_SALT` wird aktuell über externe Config/CLI-Rotation mitgesteuert, obwohl es fachlich ein runtime-interner Betriebszustand ist.
- CI-/Workflow-seitige Rotation ohne konsistente Bereinigung von IP-bezogenem State erzeugt Inkonsistenzen.
- Die externe Pflicht-/Override-Logik für `IP_SALT` erhöht Komplexität ohne kohärenten Mehrwert.

## Ziel
- Runtime besitzt `IP_SALT` vollständig in `var/state`.
- Fehlt der Salt oder passt der Fingerprint nicht, erzeugt Runtime einen neuen Salt und bereinigt denselben Schritt konsistent für IP-bezogenen State.
- Externe `IP_SALT`-Pflichten und `--rotate-ip-salt` werden im gleichen Umsetzungsschritt entfernt.

## Scope
- Runtime-Guardrails:
  - Salt-Datei atomisch schreiben (`tmp` + `rename`).
  - Parallelen Zugriff mit Dateilock absichern.
  - Restriktive Dateirechte für Salt-Datei setzen.
  - Fingerprint aus dem Salt ableiten (kein separater Wahrheitswert).
- Konsistenz bei Wechsel:
  - Bei fehlendem Salt oder Fingerprint-Mismatch: Salt neu erzeugen.
  - Im selben Vorgang IP-bezogenen State leeren/zurücksetzen.
- CLI/Workflow:
  - `--rotate-ip-salt` aus Setup-Pfaden entfernen.
  - Expliziten Betriebsbefehl für bewussten Reset bereitstellen (z. B. `cli ip-hash reset`).
- Config/Manifest:
  - `IP_SALT` als regulären externen Config-Key in Betriebsconfigs zurückbauen.
  - Einmalige Migration alter externer Werte dokumentieren.
- Tests:
  - Unit-/Feature-Tests für Salt-Erzeugung, Mismatch-Pfad, Bereinigung und Parallelzugriff.

## Nicht im Scope
- Allgemeine Änderungen am Rate-Limit-Fachverhalten außerhalb des Salt-/State-Lebenszyklus.
- Produktions-Hardening jenseits der hier benötigten Dateirechte/Locking-Basis.

## Akzeptanzkriterien
- Runtime kann ohne externen `IP_SALT` stabil starten und denselben Salt wiederverwenden.
- Bei Fingerprint-Mismatch wird Salt rotiert und IP-bezogener State im gleichen Lauf bereinigt.
- `--rotate-ip-salt` ist aus Setup-/Deploy-Pfaden entfernt.
- `IP_SALT` ist kein regulärer Pflicht-/Override-Key in aktiven Betriebsconfigs mehr.
- Dokumentation beschreibt Betriebsmodus, Reset-Befehl und Migrationspfad.
- Testnachweise für Erfolgs- und Fehlerpfade liegen vor.

## Abhängigkeiten
- Story-Kontext:
  - [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md)
- Voraussetzungen:
  - [ISS-010](ISS-010-preview-workflow-testmatrix-und-entscheidungen.md)
- Wirkt auf:
  - [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md)
- Folge-Issue:
  - [ISS-012](ISS-012-runtime-concurrency-locking-und-atomare-zugriffe.md) (flächige Runtime-Concurrency-Haertung)
