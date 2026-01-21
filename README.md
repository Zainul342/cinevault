```text
   ______ _             _    __            _ __ 
  / ____/(_)___  ___   | |  / /___ ___  __/ / /_
 / /    / / __ \/ _ \  | | / / __ `/ / / / / __/
/ /___ / / / / /  __/  | |/ / /_/ / /_/ / / /_  
\____//_/_/ /_/\___/   |___/\__,_/\__,_/_/\__/  

[ v1.0.0 - PRODUCTION BUILD ]
```

# SYSTEM ARCHITECTURE

CineVault is a high-performance, framework-agnostic MVC application engineered for movie data aggregation and user library management. Built on bare-metal PHP 8.3 principles without the overhead of heavy-duty frameworks.

## > TECHNICAL SPECIFICATIONS

### [ BACKEND KERNEL ]
- **Runtime**: PHP 8.3 (Strict Types)
- **Architecture**: Custom MVC (Model-View-Controller) implementation
- **Database**: MySQL 8.0 via PDO (Strict Error Mode)
- **External API**: TMDB (The Movie Database) Integration v3
- **Network**: GuzzleHttp for synchronous/asynchronous data transport

### [ FRONTEND INTERFACE ]
- **Language**: ECMAScript 2023 (Vanilla JS)
- **State Management**: Local Proxy Store
- **Styling**: Native CSS3 (CSS Variables, Flexbox/Grid)
- **DOM Manipulation**: Direct native selection, no Virtual DOM overhead

## > INSTALLATION PROTOCOL

### 1. CLONE REPOSITORY
```bash
git clone https://github.com/Zainul342/cinevault.git
cd cinevault
```

### 2. DEPENDENCY INJECTION
```bash
composer install --no-dev --optimize-autoloader
```

### 3. ENVIRONMENT CONFIGURATION
```bash
cp .env.example .env
# Edit .env and populate DB_ credentials and TMDB_API_KEY
```

### 4. DATABASE INITIALIZATION
Import the provided schema into your MySQL instance:
```bash
mysql -u root -p cinevault < database/schema.sql
```

### 5. EXECUTE RUNTIME
```bash
php -S localhost:8080 -t public
```

## > DEPLOYMENT PIPELINE (RAILWAY)

This repository is configured for automated deployment via Railway.

**Configuration Strategy:**
- **Build Method**: Dockerfile (PHP 8.3 + PDO MySQL extension)
- **Environment**: Linux/Debian Bookworm
- **Process Manager**: PHP Built-in Server (Production Mode)

**Required Environment Variables:**
| KEY               | DESCRIPTION                     |
|-------------------|---------------------------------|
| DB_HOST           | Database endpoint               |
| DB_PORT           | Database port (default: 3306)   |
| DB_DATABASE       | Database name                   |
| DB_USERNAME       | Database user                   |
| DB_PASSWORD       | Database password               |
| TMDB_API_KEY      | v3 API Key from TheMovieDB      |
| APP_DEBUG         | Boolean (false for production)  |

## > API INTERFACE REFERENCE

RESTful endpoints for data consumption.

### AUTH
- `POST /api/auth/register` - Create new operator identity
- `POST /api/auth/login`    - Obtain JWT access token
- `GET  /api/auth/me`       - Validate session context

### MOVIES
- `GET  /api/movies`       - Retrieve library index (Paginated)
- `POST /api/movies/sync`  - Synchronize remote entity to local storage

### INTERACTIONS
- `POST /api/watchlist/{id}` - Toggle watchlist status
- `POST /api/movies/{id}/like` - Toggle validation status

---
[ SYSTEM STATUS: ONLINE ]
[ COPYRIGHT (C) 2026 CINEVAULT SYSTEMS ]
