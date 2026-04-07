# Library Management System Backend

Lightweight PHP + MySQL backend for a library management system.

Features:

- User registration, login, logout, dashboard, and role-based access
- Book CRUD, availability tracking, and search by title, author, genre, ISBN
- Issue and return workflows with due dates, fine calculation, and history
- Reports for issued books, overdue books, and activity logs
- Notification plumbing for due date reminders and book availability alerts
- Password hashing, token-based authentication, field encryption for sensitive data

## Setup

1. Copy `.env.example` to `.env` and update database credentials.
2. Create the MySQL database.
3. Import `database/schema.sql`.
4. Run:

```bash
php -S localhost:8000
```

5. Optional reminder processor:

```bash
php scripts/process_notifications.php
```

## Seed Data

The schema seeds sample books only. Create librarian and student accounts through `POST /register`.

## Main Routes

- `POST /register`
- `POST /login`
- `POST /logout`
- `GET /dashboard`
- `GET /books`
- `GET /books/search`
- `GET /books/{id}`
- `POST /books`
- `PUT /books/{id}`
- `DELETE /books/{id}`
- `GET /users`
- `GET /users/{id}`
- `POST /issue`
- `POST /return`
- `GET /circulation/history`
- `GET /reports/issued-books`
- `GET /reports/overdue-books`
- `GET /reports/activity-logs`
- `GET /notifications`
- `POST /notifications/alerts`
