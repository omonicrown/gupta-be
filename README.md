# GUPTA APP

# SMS Messaging Platform

A Laravel-based SMS messaging platform with HollaTags integration for SMS delivery and Flutterwave integration for payments.

## Features

- User authentication and management
- Contact and contact group management
- Messaging with support for templates and campaigns
- Sender ID registration and verification
- Wallet system for payment handling
- Analytics and reporting
- HollaTags integration for SMS delivery
- Flutterwave integration for payment processing

## Installation

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Node.js and NPM (for frontend assets)

### Installation Steps

1. Clone the repository:
```
git clone https://github.com/yourusername/sms-platform.git
cd sms-platform
```

2. Install PHP dependencies:
```
composer install
```

3. Copy the environment file:
```
cp .env.example .env
```

4. Generate application key:
```
php artisan key:generate
```

5. Configure your database in the `.env` file:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms_platform
DB_USERNAME=root
DB_PASSWORD=
```

6. Configure HollaTags API and Flutterwave API settings in the `.env` file:
```
HOLLATAGS_API_URL=https://api.hollatags.com/api/v1
HOLLATAGS_API_KEY=your_hollatags_api_key
HOLLATAGS_DEFAULT_SENDER_ID=your_default_sender_id

FLUTTERWAVE_API_URL=https://api.flutterwave.com/v3
FLUTTERWAVE_SECRET_KEY=your_flutterwave_secret_key
FLUTTERWAVE_PUBLIC_KEY=your_flutterwave_public_key
FLUTTERWAVE_ENCRYPTION_KEY=your_flutterwave_encryption_key
FLUTTERWAVE_WEBHOOK_HASH=your_flutterwave_webhook_hash
```

7. Run migrations and seed the database:
```
php artisan migrate
php artisan db:seed
```

8. Install frontend dependencies and build assets:
```
npm install
npm run dev
```

9. Create storage symbolic link:
```
php artisan storage:link
```

10. Start the development server:
```
php artisan serve
```

## API Documentation

The API documentation is available at `/api/documentation` when the application is running.

### Authentication

The API uses Laravel Sanctum for authentication. To authenticate, make a POST request to `/api/login` with your credentials to obtain a token.

Use this token in the Authorization header for subsequent requests:
```
Authorization: Bearer YOUR_TOKEN
```

## Default Users

After seeding the database, the following users are available:

- Admin User:
  - Email: admin@example.com
  - Password: password

- Regular User:
  - Email: user@example.com
  - Password: password

## Queue Worker

This application uses Laravel's queue system for processing messages. Start a queue worker:

```
php artisan queue:work
```

For production, consider setting up a supervisor configuration to keep the queue worker running.

## Scheduled Tasks

The application uses Laravel's scheduler for running periodic tasks. Set up a cron job to run the scheduler:

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Testing

Run the test suite with:

```
php artisan test
```

## License

This project is licensed under the MIT License.