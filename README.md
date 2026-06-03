# Poly Marketplace

Poly Marketplace is a PHP web-based marketplace platform developed for Bahrain Polytechnic students as part of the IT8415 Database Programming 2 Group Project.

## Application URL

The deployed application can be accessed at:

```text
http://20.74.143.233/~u202301956/poly-marketplace
```

> **Note:** Database credentials are configured on the deployed server. If running the project locally, use your own MySQL credentials.

## Test Accounts

The following accounts can be used to test the different user roles within the system:

| Role | Email | Password |
|------|--------|----------|
| Admin | admin@poly.com | admin |
| Creator | layla@creator.poly | layla@creator123 |
| Viewer/User | fatema@gmail.com | #123fatema |

## Role Overview

- **Admin** – Manage users, listings, comments, reports, and platform administration.
- **Creator** – Create, edit, publish, and manage listings and uploaded media.
- **Viewer/User** – Browse listings, search content, rate listings, and add comments.

## Environment Configuration

Create a `.env` file in the project root and add:

```env
DB_HOST=localhost
DB_NAME=db202301956
DB_USER=u202301956
DB_PASS=<default hosting database password>
```