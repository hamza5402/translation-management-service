# Translation Management Service

A Laravel-based Translation Management System that helps developers and teams manage multilingual content easily.  
It provides APIs to store, retrieve, and export translation keys, values, and tags, making it ideal for integration with frontend frameworks like Vue or React.

---

## 🚀 Features
- Manage translation keys with descriptions.
- Store translations in multiple locales.
- Tag system for categorizing translations (e.g., `web`, `mobile`).
- RESTful API for CRUD operations.
- Export translations in structured JSON (Vue i18n ready).
- Search and filter by key, locale, or tag.
- Pagination for efficient data handling.

---

## 🛠 Tech Stack
- **Framework:** Laravel 12  (PHP 8.2)
- **Database:** MySQL / MariaDB
- **Authentication:** Laravel Sanctum
- **Caching:** Laravel Cache 

---

## 📋 Requirements
- PHP >= 8.0
- Composer >= 2.x
- MySQL >= 5.7 or MariaDB >= 10

---

## ⚙️ Installation

1. **Clone the repository**

   git https://github.com/hamza5402/translation-management-service.git
   cd translation-management-service

2. **Install dependencies**

   composer install

3. **Copy .env file**

   cp .env.example .env

4. **Generate application key**

   php artisan key:generate

5. **Configure database**

   DB_DATABASE=translation_service
   DB_USERNAME=root
   DB_PASSWORD=yourpassword

6. **Run migration and seed**

   php artisan migrate --seed

7. **Start the local server**

   php artisan serve


   ---

## ⚙️ Populate Fake Data

You can populate fake translations into the database using the custom artisan command:

php artisan seed:translations {number}	

   ---


📡 API Endpoints

| Method | Endpoint                      | Description                          |
| ------ | ----------------------------- | ------------------------------------ |
| GET    | `/api/v1/locales`             | List locales                         |
| POST   | `/api/v1/locales`             | Create new locale                    |
| GET    | `/api/v1/keys`                | List translation keys (with filters) |
| POST   | `/api/v1/keys`                | Create new translation key           |
| PUT    | `/api/v1/keys/{id}`           | Update translation key               |
| DELETE | `/api/v1/keys/{id}`           | Delete translation key               |
| GET    | `/api/v1/translations/export` | Export translations by locale/tags   |


   ---

📂 Project Structure

app/
  Models/        # TranslationKey, Translation, Tag, Locale
  Http/Controllers/Api/
  Services/
routes/
  api.php        # API routes
database/
  migrations/    # Tables for translations, locales, tags



🔹 See Postman Collection

	postman/translation_management_service.postman_collection.json


> **Note:**  
> The system only accepts translations for locales that already exist in the `locales` table.  
> If you want to insert translations for a new language, you must first add that locale to the `locales` table, and then you can add translations for it.

