# HotelMaint Pro

## Executive Summary

HotelMaint Pro is a comprehensive hotel maintenance management application designed to streamline maintenance scheduling, guest complaint handling, and preventive facility management across all hotel assets. The system enables maintenance teams to respond swiftly to reactive issues while proactively managing the health of building infrastructure, equipment, HVAC, lighting, and all other hotel facilities.

## Project Status: ✅ 100% Production Ready

All development phases are complete. The application is ready for deployment.

## Key Features

### 🎫 Work Order Management
- Create, assign, and track maintenance work orders
- Automatic generation from guest complaints
- Priority levels and SLA tracking with escalation alerts
- Full lifecycle management (Open → In Progress → Resolved → Closed)
- Photo attachments, parts logging, and cost tracking

### 🛎️ Guest Complaint Handling
- Multi-channel complaint logging (Front Desk, QR Code, Mobile)
- Auto-classification and work order generation
- Real-time status tracking and guest notifications
- SLA-based escalation workflows
- Satisfaction follow-up and analytics

### 📅 Maintenance Scheduling
- Recurring preventive maintenance schedules (daily, weekly, monthly, yearly)
- Calendar view with drag-and-drop rescheduling
- Auto-generation of work orders from schedules
- Technician availability management

### 🔧 Preventive Maintenance
- Complete asset register with health scoring
- Manufacturer manuals and warranty tracking
- Checklist-based PM tasks
- Third-party vendor management
- Regulatory compliance checklists

### 📊 Dashboard & Reporting
- Role-specific dashboards (GM, Technician, Front Desk)
- Real-time KPIs: MTTR, compliance rates, complaint trends
- PDF and Excel report exports
- Asset health and cost analysis

### 🔔 Notifications & Alerts
- Push, SMS, and email notifications
- SLA breach escalations
- Warranty expiry reminders
- Low inventory alerts

## Tech Stack

- **Backend**: Laravel 11.x, PHP 8.2+
- **Frontend**: Blade Templates, Tailwind CSS, Alpine.js
- **Database**: MySQL 8.0+
- **Cache/Queue**: Redis
- **API**: RESTful API with Laravel Sanctum
- **Testing**: PHPUnit, Pest, Laravel Dusk

## Installation

### Requirements
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL 8.0+
- Redis

### Quick Start

```bash
# Clone the repository
git clone https://github.com/mkresna2/HotelMaintenanceApp.git
cd HotelMaintenanceApp

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env file
# Then run migrations
php artisan migrate

# Run seeders for demo data
php artisan db:seed

# Build frontend assets
npm run build

# Start local development server
php artisan serve
```

Visit `http://localhost:8000` to access the application.

## Configuration

### Environment Variables

Edit `.env` file with your settings:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hotelmaint_db
DB_USERNAME=root
DB_PASSWORD=secret

# Redis (for queues and cache)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
QUEUE_CONNECTION=redis

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

### Default User Credentials (After Seeding)

- **Email**: admin@hotelmaint.com
- **Password**: password

**⚠️ Change the default password immediately after first login!**

## Deployment

### SiteGround Cloud Hosting

Detailed deployment instructions are available in [`docs/siteground_deploy.md`](docs/siteground_deploy.md).

Quick steps:
1. Set up MySQL database and Redis in SiteGround Site Tools
2. Clone repository via SSH
3. Configure `.env` file
4. Run migrations and seeders
5. Set up Supervisor for queue workers
6. Configure cron jobs for scheduled tasks
7. Point domain to public folder

### Production Checklist

- [ ] Set `APP_DEBUG=false`
- [ ] Generate production `APP_KEY`
- [ ] Configure SSL certificate
- [ ] Set up queue workers (Supervisor)
- [ ] Configure cron for scheduler
- [ ] Set up backup strategy
- [ ] Monitor logs regularly

## API Documentation

The application provides a RESTful API for mobile clients and third-party integrations.

### Authentication
```bash
POST /api/login
POST /api/logout
```

### Endpoints

- **Complaints**: `GET/POST /api/complaints`, `GET/PATCH /api/complaints/{id}`
- **Work Orders**: `GET/POST /api/work-orders`, `PATCH /api/work-orders/{id}/status`
- **Assets**: `GET/POST /api/assets`, `GET /api/assets/{id}/history`
- **Schedules**: `GET/POST /api/schedules`
- **Reports**: `GET /api/reports/{type}`

See `routes/api.php` for complete API route listing.

### QR Code Integration

Generate QR codes for rooms/assets:
```
https://yourdomain.com/complaints/qr?asset_id={id}&room={number}
```

Guests can scan to submit complaints directly.

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

## Scheduled Tasks

The following automated tasks run via Laravel Scheduler:

- **Daily at midnight**: Generate PM work orders from schedules
- **Every hour**: Check SLA breaches and send escalation alerts
- **Nightly at 2 AM**: Calculate asset health scores
- **Weekly on Monday**: Send compliance reports to management

Ensure cron is configured:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Queue Workers

Start queue workers for background job processing:

```bash
# Development
php artisan queue:work

# Production (with Supervisor)
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

## Troubleshooting

### Common Issues

**500 Server Error**
- Check `storage/logs/laravel.log`
- Verify `.env` configuration
- Ensure `APP_KEY` is set

**Queues Not Processing**
- Verify Redis is running: `redis-cli ping`
- Check worker logs: `tail -f storage/logs/worker.log`
- Restart workers: `php artisan queue:restart`

**Emails Not Sending**
- Verify SMTP credentials
- Check mail provider requirements (app passwords)
- Test connection: `php artisan tinker` then `Mail::raw('test', fn($m) => $m->to('you@example.com')->subject('Test'));`

## Support

- **Documentation**: See `/docs` folder for detailed guides
- **GitHub Issues**: Report bugs and feature requests
- **SiteGround Support**: For hosting-specific issues

## License

Proprietary software. All rights reserved.

---

**Version**: 1.0.0  
**Release Date**: May 2026  
**Developed By**: HotelMaint Pro Team
