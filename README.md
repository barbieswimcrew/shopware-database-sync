# Database Sync Plugin für Shopware 6

Dieses Plugin ermöglicht die Synchronisation von Datenbanken zwischen verschiedenen Shopware 6 Instanzen via SSH.

## Features

-   Synchronisation der Datenbank von einer Remote-Shopware-Instanz zur lokalen Instanz
-   Unterstützung für SSH-Key und Passwort-Authentifizierung
-   Konfigurierbarer SSH-Port und Remote-Pfad
-   Interaktive Verbindungsauswahl
-   Fortschrittsanzeige während der Synchronisation
-   Umgebungsbasierte Konfiguration (Production/Staging)

## Installation

1. Klonen Sie das Repository in Ihr Shopware 6 `custom/plugins` Verzeichnis:

```bash
cd custom/plugins
git clone https://github.com/attic-concepts/database-sync.git AtticConceptsDatabaseSync
```

2. Installieren Sie das Plugin über die Kommandozeile:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate AtticConceptsDatabaseSync
bin/console cache:clear
```

## Konfiguration

Die Konfiguration erfolgt über Umgebungsvariablen in Ihrer `.env.local` Datei. Sie können mehrere Verbindungen (z.B. Production, Staging) konfigurieren.

### Production-Umgebung

```bash
# SSH Host
DATABASE_SYNC_PROD_HOST=production.example.com

# SSH Benutzer
DATABASE_SYNC_PROD_USER=shopware

# SSH Port (Standard: 22)
DATABASE_SYNC_PROD_PORT=22

# Pfad zur Shopware-Installation auf dem Remote-Server
DATABASE_SYNC_PROD_PATH=/var/www/html

# Pfad zum SSH-Key (optional bei Passwort-Authentifizierung)
DATABASE_SYNC_PROD_KEY=%kernel.project_dir%/.ssh/id_rsa
```

### Staging-Umgebung

```bash
# SSH Host
DATABASE_SYNC_STAGING_HOST=staging.example.com

# SSH Benutzer
DATABASE_SYNC_STAGING_USER=shopware

# SSH Port
DATABASE_SYNC_STAGING_PORT=22

# Pfad zur Shopware-Installation auf dem Remote-Server
DATABASE_SYNC_STAGING_PATH=/var/www/staging

# SSH Passwort (optional bei Key-Authentifizierung)
DATABASE_SYNC_STAGING_PASSWORD=geheim
```

## Verwendung

Der Befehl kann auf zwei Arten ausgeführt werden:

1. Interaktiver Modus (empfohlen):

```bash
bin/console database:sync
```

2. Direkte Verbindungsangabe:

```bash
bin/console database:sync production
# oder
bin/console database:sync staging
```

Der Befehl wird:

1. Eine SSH-Verbindung zum Remote-Server herstellen
2. Einen Datenbank-Dump auf dem Remote-Server erstellen
3. Den Dump in die lokale Datenbank importieren

## Sicherheitshinweise

-   Speichern Sie niemals sensible Daten (Passwörter, SSH-Keys) im Code
-   Bevorzugen Sie SSH-Key-Authentifizierung gegenüber Passwort-Authentifizierung
-   Stellen Sie sicher, dass die `.env.local` Datei nicht im Git-Repository getrackt wird
-   Beschränken Sie den SSH-Zugriff auf die notwendigen Verzeichnisse
-   Verwenden Sie einen dedizierten SSH-Benutzer mit eingeschränkten Rechten

## Anforderungen

-   PHP 8.1 oder höher
-   Shopware 6.5.x
-   SSH-Zugriff auf den Remote-Server
-   Remote-Server muss Shopware 6 installiert haben mit verfügbarem `system:dump` Befehl
-   Lokale Shopware-Instanz muss den `system:restore` Befehl unterstützen

## Fehlerbehebung

### Keine Verbindungen konfiguriert

Prüfen Sie, ob die erforderlichen Umgebungsvariablen korrekt in Ihrer `.env.local` Datei gesetzt sind.

### SSH-Verbindung fehlgeschlagen

-   Prüfen Sie, ob SSH-Key/Passwort korrekt ist
-   Stellen Sie sicher, dass der Benutzer Zugriff auf das Remote-System hat
-   Überprüfen Sie, ob der konfigurierte Port korrekt ist
-   Prüfen Sie die SSH-Key-Berechtigungen (sollten 600 sein)

### Datenbank-Dump fehlgeschlagen

-   Überprüfen Sie, ob der Remote-Pfad korrekt ist
-   Stellen Sie sicher, dass der Benutzer die notwendigen Berechtigungen hat
-   Prüfen Sie den verfügbaren Speicherplatz
-   Überprüfen Sie, ob die Shopware-Konsolenbefehle zugänglich sind

## Support

Bei Fragen oder Problemen öffnen Sie bitte ein Issue auf GitHub oder kontaktieren Sie uns unter support@attic-concepts.com

## Lizenz

MIT Lizenz
