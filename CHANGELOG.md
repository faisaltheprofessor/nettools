# Changelog
Alle relevanten Änderungen an diesem Projekt werden hier dokumentiert.  
Dieses Projekt verwendet [Semantic Versioning](https://semver.org/lang/de/).

## [Unreleased]

## [0.9.0-beta.3] - 2025-08-19
### Hinzugefügt
- Anliegen: Anliegen-Einträge können nun als **Diskussion/Forum** genutzt werden
    - Nutzer können Kommentare zu Anliegen schreiben
    - Anliegen besitzen jetzt einen **Status** (open, in progress, resolved, closed, wontfix)
    - Filtermöglichkeiten für Anliegen nach **Typ**, **Status**, **User** und **Volltextsuche**
    - Filter sind in einem **Accordion** organisiert für bessere Übersicht
    - Reaktionen
- Gruppen- und Detailansicht: Nutzer können nun einzelne Gruppen kopieren
- DNS und DHCP: Hinweis zur Service-Migration per Rechtsklick wird nun angezeigt

## [0.9.0-beta.2] - 2025-08-18
### Behoben / Geändert
- Usersuche: PID-Suchfeld ergänzt automatisch ein führendes **"p"**, falls nicht vorhanden
- Gruppen-Detailansicht: **Letzter Login** zeigt nun **`--`**, wenn kein gültiges Datum vorhanden ist
- Changelog-Ansicht: Verbesserte Markdown-Render-Logik mit CommonMark und Tailwind-Typography, Überschriften, Bullet-Listen und verschachtelte Listen werden korrekt angezeigt

## [0.9.0-beta.1] - 2025-08-15
### Hinzugefügt
- Erste Beta-Version der Anwendung
- Statusabfrage (Polling) für DHCP- und DNS-Dienste
- Funktionen zum Start, Neustart und zur Migration von DHCP- und DNS-Diensten
- Abruf von LDAP-Einträgen
    - freie Mailbox PIDs
    - freie User PIDs
    - User PID Lücken
    - Usersuche
    - Export von LDAP-Einträgen
- Passwort-Generator
- Seriennummern-Generator für oVirt
- IP-Rechner mit Subnetting-Funktionalität
- Signatur-Generator
- Lesezeichen-Verwaltung (Erstellen, Lesen, Bearbeiten, Löschen)
