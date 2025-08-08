Требования
Docker
Docker Compose
PHP 8.1+
Composer

Клонируйте репозиторий:
git clone [ваш-репозиторий]
cd [папка-проекта]

Установите зависимости:
composer install

Скопируйте файл окружения:
cp .env.example .env

Запустите Sail:
./vendor/bin/sail up -d

Сгенерируйте ключ приложения:
./vendor/bin/sail artisan key:generate

Запустите миграции и сиды:
./vendor/bin/sail artisan migrate --seed

Отредактируйте .env файл при необходимости:
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

Запуск тестов:
./vendor/bin/sail test

Использование API

Аутентификация:
Authorization: Bearer [ваш-api-токен]

Доступные endpoint'ы:

Получить список бронирований пользователя
GET /api/bookings

Создать новое бронирование
POST /api/bookings
Body: {
"slots": [
{
"start_time": "2023-01-01 10:00:00",
"end_time": "2023-01-01 11:00:00"
}
]
}
Удалить бронирование
DELETE /api/bookings/{id}

Добавить слот к бронированию
POST /api/bookings/{id}/slots
Body: {
"start_time": "2023-01-01 12:00:00",
"end_time": "2023-01-01 13:00:00"
}

Обновить слот бронирования

PATCH /api/bookings/{bookingId}/slots/{slotId}
Body: {
"start_time": "2023-01-01 14:00:00",
"end_time": "2023-01-01 15:00:00"
}
