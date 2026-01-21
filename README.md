```text
   ______ _             _    __            _ __ 
  / ____/(_)___  ___   | |  / /___ ___  __/ / /_
 / /    / / __ \/ _ \  | | / / __ `/ / / / / __/
/ /___ / / / / /  __/  | |/ / /_/ / /_/ / / /_  
\____//_/_/ /_/\___/   |___/\__,_/\__,_/_/\__/  

[ v1.0.0 - PRODUCTION ]
```

# SYSTEM ARCHITECTURE

just pure php muscle, no bulky frameworks slowing things down. we built this beast to handle movie metadata aggregation and user libraries without the overhead.

## > TECH SPECS

### [ THE KERNEL ]
running on **php 8.3** with strict typing cause we catch bugs at compile time.
custom mvc architecture. we wrote the router, the di container, everything.
**mysql 8.0** storage via pdo. transactions enabled.
**guzzle** handles the network traffic to tmdb api v3.

### [ THE VIEW ]
**vanilla js** all the way. no react, no vue, no problem.
state management via local proxy stores.
native dom manipulation. fast. responsive.

## > SETUP PROTOCOL

### 1. CLONE
get the code on your machine.
```bash
git clone https://github.com/Zainul342/cinevault.git
cd cinevault
```

### 2. INJECT DEPS
fetch the vendor packages.
```bash
composer install --no-dev --optimize-autoloader
```

### 3. CONFIG
duplicate the env example. fill in the secrets.
```bash
cp .env.example .env
```

### 4. DB INIT
dump the schema into your mysql instance.
```bash
mysql -u root -p cinevault < database/schema.sql
```

### 5. EXECUTE
spin up the built-in server.
```bash
php -S localhost:8080 -t public
```

## > DEPLOY (RAILWAY)

automated pipeline via dockerfile.
base image is **php:8.3-cli**.
extensions wired up: `pdo_mysql` `zip` `pcntl`.

ensure these vars are set in your project settings
`DB_HOST` `DB_PORT` `DB_DATABASE` `DB_USERNAME` `DB_PASSWORD` `TMDB_API_KEY`

## > ENDPOINTS

REST interface for the frontend consumption.

`POST /api/auth/register` creates new user identity
`POST /api/auth/login` gets you the jwt access token
`GET  /api/movies` pulls the library index
`POST /api/movies/sync` pulls data from remote and saves locally

---
[ SYSTEM ONLINE ]
[ CODE IS LAW ]
