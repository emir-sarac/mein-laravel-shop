# Mein Laravel Online-Shop

Ein moderner und einfacher Online-Shop, der auf dem Handy und am Computer gut aussieht. 

## Technologien
* **Framework:** Laravel
* **Frontend:** Livewire & Tailwind CSS

## Installation (Lokal starten)

1. Repository klonen
2. Abhängigkeiten installieren:
   ```bash
   composer install
   npm install
   ```
3. Umgebungsvariablen kopieren:
   ```bash
   cp .env.example .env
   ```
4. Datenbank-Migrationen ausführen:
   ```bash
   php artisan migrate
   ```
5. App-Key generieren:
   ```bash
   php artisan key:generate
   ```
6. Server starten:
   ```bash
   php artisan serve --no-reload
   npm run dev
   ```
