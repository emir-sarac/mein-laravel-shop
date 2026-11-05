# Mein Laravel Online-Shop

Ein moderner Online-Shop mit einfacher Bedienung und einer responsiven Oberfläche.

## Funktionen

- Produktübersicht
- Warenkorb und Checkout
- Bestellungen
- Benutzerkonto und Adressverwaltung
- Administrationsbereich

## Technologien

- [Laravel 12](https://laravel.com/docs/12.x)
- [Livewire](https://livewire.laravel.com/docs)
- [Tailwind CSS](https://tailwindcss.com/docs)

> **Hinweis zu den Versionen:** Das Projekt verlangt PHP `^8.2` und Laravel `^12.0`. In `composer.json` steht `livewire/flux` auf `^2.0` – das ist die Flux-Version, nicht die Livewire-Version. Eine Livewire-Hauptversion ist dort nicht direkt angegeben.

## Voraussetzungen

- PHP 8.2 oder höher
- [Composer](https://getcomposer.org/)
- Node.js und npm

## Installation

1. Repository klonen und in das Projektverzeichnis wechseln:

   ```bash
   git clone https://github.com/emir-sarac/mein-laravel-shop.git
   cd mein-laravel-shop
   ```

2. PHP- und JavaScript-Abhängigkeiten installieren:

   ```bash
   composer install
   npm install
   ```

3. Lokale Umgebungsdatei erstellen:

   ```bash
   cp .env.example .env
   ```

4. Anwendungsschlüssel erzeugen:

   ```bash
   php artisan key:generate
   ```

5. Datenbank konfigurieren und Migrationen ausführen:

   `.env.example` ist standardmäßig für SQLite konfiguriert. Wenn du diese Einstellung verwenden möchtest, lege die Datenbankdatei an:

   ```bash
   php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
   ```

   Wenn du eine andere Datenbank verwendest, passe die Datenbankeinstellungen in deiner `.env`-Datei an. Führe anschließend die Migrationen aus:

   ```bash
   php artisan migrate
   ```

## Anwendung starten

Starte die Befehle in zwei getrennten Terminals im Projektverzeichnis.

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

Öffne anschließend die im ersten Terminal angezeigte Adresse im Browser. Üblicherweise ist das `http://localhost:8000`.
