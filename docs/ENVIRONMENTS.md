# Environments

Dieses Dokument beschreibt die Ebenen und Variablen fuer Runtime, Deployment und Inhalts-Deployment.

## Ebenen

- Runtime (PHP-Server): laeuft ueber `index.php` und nutzt Templates, Renderer und Storage.
- Deployment: wie die Runtime ausgeliefert wird (Dev lokal, Preview/Prod via CI/CD).
- Inhalts-Deployment: YAML -> JSON -> HTML via `cv:build`/`cv:upload`.

## Variablen-Matrix

| Ebene | Zweck | Variablen (Beispiele) | Quelle |
| --- | --- | --- | --- |
| Runtime | Allgemeine App-Defaults | `APP_ENV`, `APP_URL`, `APP_LANG`, `LABELS_PATH`, `DEFAULT_CV_PROFILE` | `config/env-default.ini` |
| Runtime | CV Datenpfade | `LEBENSLAUF_DATEN_PFAD`, `LEBENSLAUF_JSON_PFAD` | Defaults + `.local/env-*.ini` |
| Runtime | Sicherheit/Rate Limits | `IP_SALT`, `CAPTCHA_TTL_SECONDS`, `RATE_LIMIT_WINDOW_SECONDS` | `config/env-default.ini` |
| Runtime | Mail/SMTP | `CONTACT_*`, `SMTP_*`, `MAIL_STDOUT` | `config/env-default.ini` |
| Deployment (CI) | Preview-Build | `APP_ENV`, `LEBENSLAUF_DATEN_PFAD` (Fixtures), `CV_PROFILE` | `config/deploy-default.ini` + Secrets |
| Inhalts-Deployment | Build der HTMLs | `LEBENSLAUF_DATEN_PFAD` oder `LEBENSLAUF_YAML_PFAD` | Local/CI `env` |

## Regeln

- Defaults gehoeren in `config/env-default.ini` (keine Secrets).
- Deployment-Defaults gehoeren in `config/deploy-default.ini` (keine Secrets).
- Demo-Defaults fuer lokale Dev-Profile: `tests/fixtures/env-gueltig.ini`.
- Lokale Overrides gehoeren in `.local/env-common.ini` und `.local/env-<profil>.ini`.
- CI/CD setzt Build-Variablen im Workflow und kann Defaults ueberschreiben.

## CI-Export

Die GitHub Actions laden Defaults ueber `bin/export-env.php`; die Preview-Pipeline nutzt danach `composer run setup` und `composer run build`.
