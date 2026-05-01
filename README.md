# Invoice Management App

## Table of Contents
- [Quick Start](#quick-start)
- [System Architecture](#system-architecture)
- [Frontend Architecture](#frontend-architecture)
- [Backend Architecture](#backend-architecture)
- [API Specification](#api-specification)
- [Database Schema](#database-schema)
- [Development](#development)
- [Production Deployment](#production-deployment)

---

## Quick Start

### Prerequisites
- Docker & Docker Compose

```bash
# Clone the repository
git clone https://github.com/DanielShaqfeh/Telution-Coding-Challenge.git
cd Telution-Coding-Challenge

# Set up the environment file
cd backend
cp .env.example .env
cd ..

# Build and start all services (backend + frontend)
docker-compose up --build -d

# Generate JWT keys inside the container
docker-compose exec backend php bin/console lexik:jwt:generate-keypair

# Run migrations (first time only)
docker-compose exec backend php bin/console doctrine:migrations:migrate

# (Optional) Seed sample data
docker-compose exec backend php bin/console doctrine:fixtures:load

# Restart backend so it picks up the new JWT keys
docker-compose restart backend

# Frontend will be available at http://localhost:4200
# Backend API at http://localhost:8000
```

To stop services:
```bash
docker-compose down
```

---

## System Architecture

```
┌─────────────────────────────────────────────────────┐
│                    Client Browser                   │
│              Angular 21 SPA (Port 4200)             │
└───────────────────────┬─────────────────────────────┘
                        │ HTTP + JWT Bearer Token
                        ▼
┌─────────────────────────────────────────────────────┐
│              Symfony 7.2 REST API (Port 8000)       │
│         JWT Auth  │  CORS  │  Doctrine ORM          │
└───────────────────────┬─────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────┐
│                  SQLite Database                    │
│                   (var/app.db)                      │
└─────────────────────────────────────────────────────┘
```

---

## Frontend Architecture

### Directory Structure

```
frontend/src/
├── app/
│   ├── components/
│   │   ├── login/                  # Auth page (login & register)
│   │   ├── invoice-list/           # Invoice listing with filters & pagination
│   │   ├── invoice-create/         # Create invoice with line items
│   │   ├── invoice-import/         # CSV/Excel import with results
│   │   └── client-list/            # Client listing with search
│   │
│   ├── services/
│   │   ├── api.ts                  # HTTP client with base URL config
│   │   └── auth.ts                 # JWT storage, login, register, logout
│   │
│   ├── auth.guard.ts               # Route protection (CanActivateFn)
│   ├── auth.interceptor.ts         # Attaches Bearer token to requests
│   ├── app.routes.ts               # Route definitions with auth guard
│   ├── app.config.ts               # App bootstrap with HTTP interceptors
│   ├── app.ts                      # Root component with sidebar & layout
│   └── app.html                    # Shell template
│
├── environments/
│   ├── environment.ts              # Dev API URL
│   └── environment.prod.ts         # Prod API URL
│
└── styles/
    ├── _variables.css              # CSS custom properties (colors, spacing)
    ├── _layout.css                 # Sidebar, main content, hamburger
    ├── _components.css             # Buttons, forms, tables, badges, cards
    ├── _topbar.css                 # Top navigation bar
    ├── _responsive.css             # Mobile & tablet breakpoints
    └── styles.css                  # Global imports
```

### Auth Flow

```
User submits login form
        │
        ▼
AuthService.login() → POST /auth/login
        │
        ▼
JWT token received → stored in localStorage
        │
        ▼
authInterceptor attaches token to every request
        │
        ▼
authGuard protects all routes — redirects to /login if no valid token
```

### Component Architecture

Each component is a standalone Angular component with its own HTML, CSS, and TypeScript file. All components use template-driven forms and Angular's `HttpClient` via the `ApiService`.

---

## Backend Architecture

### Directory Structure

```
backend/
├── src/
│   ├── Controller/
│   │   ├── AuthController.php       # /auth/login (stub) & /auth/register
│   │   ├── ClientController.php     # CRUD for clients
│   │   ├── InvoiceController.php    # CRUD for invoices
│   │   └── ImportController.php     # CSV/Excel import endpoint
│   │
│   ├── Entity/
│   │   ├── Client.php               # Client entity (name, email, company, address)
│   │   ├── Invoice.php              # Invoice entity (status, items, total, date)
│   │   └── User.php                 # User entity for JWT auth
│   │
│   ├── Repository/
│   │   ├── ClientRepository.php
│   │   ├── InvoiceRepository.php
│   │   └── UserRepository.php
│   │
│   └── DataFixtures/
│       └── AppFixtures.php          # Sample clients and invoices
│
├── config/
│   ├── packages/
│   │   ├── security.yaml            # Firewalls, JWT, access control
│   │   └── lexik_jwt_authentication.yaml  # JWT key paths and TTL
│   └── jwt/
│       ├── private.pem              # RSA private key (sign tokens)
│       └── public.pem               # RSA public key (verify tokens)
│
├── migrations/                      # Auto-generated Doctrine migrations
├── data/
│   ├── sample_invoices.xlsx         # Sample Excel import file
│   └── sample_invoices.csv          # Sample CSV import file
├── .env.example                     # Environment variable template
└── Dockerfile
```

---

## API Specification

### Base URL
- Development: `http://127.0.0.1:8000`

### Authentication
All endpoints except `/auth/login` and `/auth/register` require a JWT Bearer token:
```
Authorization: Bearer <token>
```

---

### Endpoints

#### 1. Register
`POST /auth/register`

Request body:
```json
{
  "email": "user@example.com",
  "password": "secret123"
}
```

Response (201):
```json
{
  "message": "User registered successfully."
}
```

---

#### 2. Login
`POST /auth/login`

Request body:
```json
{
  "email": "user@example.com",
  "password": "secret123"
}
```

Response (200):
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

---

#### 3. List Clients
`GET /api/clients`

Query Parameters:

| Parameter | Type    | Default | Description              |
|-----------|---------|---------|--------------------------|
| page      | integer | 1       | Page number              |
| limit     | integer | 10      | Items per page           |
| search    | string  | —       | Filter by name or company|

Response (200):
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "company": "Acme Corp",
      "address": "123 Main St"
    }
  ],
  "total": 50,
  "page": 1,
  "limit": 10
}
```

---

#### 4. Create Client
`POST /api/clients`

Request body:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "company": "Acme Corp",
  "address": "123 Main St"
}
```

---

#### 5. List Invoices
`GET /api/invoices`

Query Parameters:

| Parameter | Type    | Default | Description                        |
|-----------|---------|---------|------------------------------------|
| page      | integer | 1       | Page number                        |
| limit     | integer | 10      | Items per page                     |
| status    | string  | —       | Filter: `draft`, `sent`, `paid`    |

---

#### 6. Create Invoice
`POST /api/invoices`

Request body:
```json
{
  "clientId": 1,
  "status": "draft",
  "date": "2026-05-01",
  "items": [
    {
      "description": "Web Development",
      "quantity": 10,
      "unitPrice": 150
    }
  ]
}
```

---

#### 7. Import Clients & Invoices
`POST /api/import`

- Content-Type: `multipart/form-data`
- Body: `file` — `.csv` or `.xlsx` file

Response:
```json
{
  "created": 1,
  "failed": 0,
  "errors": []
}
```

---

#### 8. Export Invoices CSV
`GET /api/invoices/export`

Returns a downloadable `.csv` file with all invoices.

---

### Status Codes

| Code | Meaning                        |
|------|-------------------------------|
| 200  | OK                            |
| 201  | Created                       |
| 400  | Bad Request                   |
| 401  | Unauthorized (invalid/missing token) |
| 404  | Not Found                     |
| 422  | Validation Error              |
| 500  | Internal Server Error         |

---

## Database Schema

### Entity Relationship

```
User
 └── (authenticates via JWT)

Client ──────────────────── Invoice
 id (PK)                     id (PK)
 name                        client_id (FK → Client)
 email (unique)              status (draft/sent/paid)
 company                     date
 address                     items (JSON)
                             total
```

### Table Definitions

**client**
```sql
CREATE TABLE client (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    name     VARCHAR(255) NOT NULL,
    email    VARCHAR(255) NOT NULL UNIQUE,
    company  VARCHAR(255),
    address  TEXT
);
```

**invoice**
```sql
CREATE TABLE invoice (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL REFERENCES client(id),
    status    VARCHAR(50) NOT NULL DEFAULT 'draft',
    date      DATE NOT NULL,
    items     TEXT NOT NULL,
    total     NUMERIC(10,2) NOT NULL DEFAULT 0
);
```

**user**
```sql
CREATE TABLE `user` (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    email    VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);
```

---

## Development

### Start Services Manually

```bash
# Backend (from /backend)
php -S 127.0.0.1:8000 -t public

# Frontend (from /frontend)
ng serve
```

### Sample Import Files

Ready-to-use import files are in `backend/data/`:
- `sample_invoices.xlsx`
- `sample_invoices.csv`

Upload either file through the Import page in the app.

---

## Production Deployment

### Environment Variables

Backend (`backend/.env`):
```env
APP_ENV=prod
APP_SECRET=your_strong_secret_here
DATABASE_URL="sqlite:///%kernel.project_dir%/var/app.db"
CORS_ALLOW_ORIGIN='^https?://(your-domain\.com)(:[0-9]+)?$'
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_strong_passphrase_here
```

### Build Commands

```bash
# Backend
cd backend
composer install --optimize-autoloader --no-dev
php bin/console cache:warmup --env=prod

# Frontend
cd frontend
npm ci
ng build --configuration production
# Output: frontend/dist/
```

### Docker Production

```bash
docker-compose up --build -d
```
