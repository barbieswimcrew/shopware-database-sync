# Shopware Database Sync Plugin

Dieses Plugin ermöglicht die Synchronisation einer Datenbank von einer Remote-Shopware-Instanz via SSH.

## Features

-   Unterstützung für SSH-Key und Passwort-Authentifizierung
-   Konfigurierbarer SSH-Port und Remote-Pfad
-   Interaktive Verbindungsauswahl mit Validierung
-   Automatische Bereinigung temporärer Dateien
-   Unterstützung für Production und Staging Umgebungen

## Installation

1. Klone das Repository in das `custom/plugins` Verzeichnis:

```bash
cd custom/plugins
git clone https://github.com/barbieswimcrew/shopware-database-sync.git AtticConceptsDatabaseSync
```

2. Installiere das Plugin über die Shopware CLI:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate AtticConceptsDatabaseSync
```

## Konfiguration

Die Konfiguration erfolgt über die `.env.local` Datei. Es werden zwei Verbindungen unterstützt:

### Production

```bash
# Production Verbindung
DATABASE_SYNC_PROD_HOST=example.com
DATABASE_SYNC_PROD_USER=ssh-user
DATABASE_SYNC_PROD_PORT=22
DATABASE_SYNC_PROD_PATH=/var/www/shopware
DATABASE_SYNC_PROD_KEY=/path/to/ssh/key
```

### Staging

```bash
# Staging Verbindung
DATABASE_SYNC_STAGING_HOST=staging.example.com
DATABASE_SYNC_STAGING_USER=ssh-user
DATABASE_SYNC_STAGING_PORT=22
DATABASE_SYNC_STAGING_PATH=/var/www/shopware
DATABASE_SYNC_STAGING_PASSWORD=ssh-password
```

## Verwendung

### Datenbank synchronisieren

```bash
# Interaktive Auswahl der Verbindung
bin/console database:sync

# Direkte Angabe der Verbindung
bin/console database:sync production
bin/console database:sync staging
```

Der Befehl führt folgende Schritte aus:

1. Validierung der Verbindungsparameter
2. Erstellung eines Dumps auf dem Remote-Server
3. Download des Dumps
4. Import in die lokale Datenbank
5. Automatische Bereinigung temporärer Dateien

### Datenbank-Dump erstellen

```bash
# Dump erstellen und Pfad ausgeben
bin/console database:dump --path-only

# Dump erstellen mit Statusmeldungen
bin/console database:dump
```

## Fehlerbehebung

### Ungültige Verbindung

Wenn eine ungültige Verbindung angegeben wird (z.B. "test"), wird eine Fehlermeldung mit den erlaubten Werten angezeigt:

```
Ungültige Verbindung "test". Erlaubte Werte sind: "production" oder "staging"
```

### Fehlende Konfiguration

Fehlende Konfigurationsparameter werden klar angezeigt:

```
Fehlende Konfigurationsparameter:
DATABASE_SYNC_*_HOST
DATABASE_SYNC_*_USER
...
```

### SSH Verbindungsprobleme

Bei SSH Verbindungsproblemen:

1. Prüfen Sie die Verbindungsparameter (Host, User, Port)
2. Stellen Sie sicher, dass der SSH-Key oder das Passwort korrekt ist
3. Testen Sie die SSH-Verbindung manuell: `ssh -p PORT USER@HOST`

## Sicherheit

-   SSH-Keys werden gegenüber Passwörtern bevorzugt
-   Temporäre Dateien werden automatisch gelöscht
-   Sensitive Informationen werden nicht geloggt
-   Verbindungsparameter werden validiert

## Requirements

-   PHP 8.1 or higher
-   Shopware 6.5.x
-   SSH access to the remote server
-   Remote server must have Shopware 6 installed with `system:dump` command available
-   Local Shopware instance must support `system:restore` command

## Support

For questions or issues, please open an issue on GitHub or contact us at support@attic-concepts.com

## License

MIT License
