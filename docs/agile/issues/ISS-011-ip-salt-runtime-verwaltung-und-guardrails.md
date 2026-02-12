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

## Ergänzende Doku
- [Anlage: ISS-011/ISS-012 Näherung und Commit-Folge](ISS-011-012-anlage-naeherung-und-commitfolge.md)

## Scope
- Architekturrahmen gemaess Anlage:
  - Komposition mit `LockRunner`, `AtomicWriter`, `StateReader/StateValidator`, `ResetExecutor` (mindestens fuer `IP_SALT`).
  - Trigger-/Policy-Muster (`TriggerReason`, `DecisionPolicy`, `ActionPlan`) minimal fuer `IP_SALT` anwenden.
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

## Offene Punkte (Abgleich: feature/iss-011-runtime-ip-salt-management, Stand 2026-02-12)
- Fehlender Konsistenzmarker für den IP-bezogenen Runtime-State (zusätzlich zum Salt/Fingerprint), um abgeschlossene vs. abgebrochene Reset-Läufe eindeutig zu unterscheiden.
- Fehlende Recovery-Regel auf Basis dieses Markers (z. B. bei Neustart nach Abbruch), damit inkonsistenter Zwischenzustand deterministisch behandelt wird.

## Entscheidungsfestlegung (Vorschlag, zur Freigabe)
Stand: 2026-02-12

- Für MVP ist ein Konsistenzmarker mit Recovery-Regel der primäre Stabilitätshebel für Runtime-State.
- Der `IP_SALT`-Fingerprint wird für MVP nicht als primärer Stabilitätshebel behandelt.
- Fingerprint kann nach MVP als zusätzlicher Guardrail erneut bewertet werden.

### Entwurf: Konsistenzmodell (MVP)
- Marker-Status mit festen Zuständen: `IN_PROGRESS`, `READY`.
- Marker enthält mindestens: `generation`, `updated_at`.
- Recovery-Regel: Inkonsistenter Markerzustand wird beim nächsten Lauf unter Lock deterministisch bereinigt.

### Begründung
- `var/` und `.local/` sind nicht VCS-validiert und benötigen eigene Laufzeit-Validierung für betriebswichtige Daten.
