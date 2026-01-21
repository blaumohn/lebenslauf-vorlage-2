# Lebenslauf Vorlage (PHP)

Modulare Lebenslauf-Vorlage für Shared Hosting (PHP + Twig). Inhalt und UI sind getrennt: Lebenslauf-Daten liegen außerhalb von `src/`, Labels/Übersetzungen liegen im Repo.

Funktionen:

- Schnelle Anpassung der Lebenslauf-Daten ohne Code-Änderungen
- Mehrsprachigkeit mit statischen HTML-Ausgaben pro Sprache
- Statischer Build für Public/Private-Varianten

## Verwendung

1) **Installieren**

```bash
composer install
```

2) **Setup**

```bash
php bin/cli setup dev
```

3) **Starten**

```bash
php bin/cli run dev
```

`run` kompiliert die Runtime-Config nach `var/config/env.php`.

Vor dem ersten Start `.env.local` anlegen (siehe `.env.template`).

## Daten bearbeiten

- `.local/content.ini` enthält inhaltliche Einstellungen (kein Env), z. B.:

```ini
[site]
name=Lebenslauf
lang=de
langs=de,en

[cv]
public_profile=default

[contact]
to=contact@example.com
from=web@example.com
subject=Kontaktformular
```

- YAML-Daten liegen standardmäßig in `.local/lebenslauf` (`LEBENSLAUF_DATEN_PFAD`).
- Nur Dateien `daten-<profil>.yaml` werden berücksichtigt (z. B. `daten-entwickler.yaml`).
- UI-Labels/Übersetzungen liegen in `src/resources/labels.json` (Repo-Beitrag möglich).

## Build (YAML -> JSON -> HTML)

```bash
php bin/cli build dev cv
php bin/cli build dev
```

## CLI-Modell

Phasen werden direkt ausgefuehrt:

```
cli <phase> <pipeline> [args]
```

Beispiele:

- `php bin/cli setup dev`
- `php bin/cli build dev cv`
- `php bin/cli run dev`

## Projektstruktur

```
/lebenslauf-vorlage-2
├── src/
│   ├── resources/
│   │   ├── templates/          # Twig-Templates
│   │   └── labels.json          # UI-Labels (Repo-Inhalt)
│   ├── http/                   # HTTP-App
│   └── cli/                    # CLI-Tools
├── .local/
│   ├── content.ini             # Inhaltliche Einstellungen
│   └── lebenslauf/             # YAML-Daten
├── config/                     # Config-Manifest
├── tests/
└── docs/
```

## Umgebungsvariablen

Die Config-Policy (Pipeline/Phase) ist in `docs/ENVIRONMENTS.md` beschrieben.
Beispielwerte stehen in `.env.template`, Regeln in `config/env.manifest.yaml`.
Fuer Deployments wird die Runtime-Config als `var/config/env.php` erzeugt (siehe `php bin/cli config compile <pipeline>`).
