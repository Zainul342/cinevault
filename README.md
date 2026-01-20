# 🎬 CineVault

Yo, welcome to **CineVault**. 

This ain't your grandma's streaming dashboard. We're building a sleek, high-performance backend for a premium movie streaming platform. No bloated frameworks, no magic hidden behind a thousand abstractions. Just pure, raw PHP muscle. 💪

## 🚀 What's Cookin'?

We're crafting a custom MVC framework from scratch because we like control. Current status? **Phase 1** is done and dusted. The core engine is purring.

### The Stack (So Far)
- **PHP 8.1+**: Keeping it modern, strict types or bust.
- **Custom Core**: We built our own Router, App container, and Request/Response handlers. 
- **Database**: efficient PDO wrapper with transaction support.
- **Middleware**: Custom pipeline for things like CORS, Rate Limiting, and Role checks.

## 🛠️ Getting Started

Wanna spin this up? Easy peasy.

1. **Grab the goods:**
   ```bash
   git clone https://github.com/Zainul342/cinevault.git
   cd cinevault
   ```

2. **Load dependencies:**
   (Yeah, we use Composer, we're not savages)
   ```bash
   composer install
   ```

3. **Config setup:**
   Copy that env example and tweak it if you have to.
   ```bash
   cp .env.example .env
   ```

4. **Fire it up:**
   Since we don't have a fancy Nginx setup just yet, the built-in server works fine for dev:
   ```bash
   php -S localhost:8080 -t public
   ```

   Hit up `http://localhost:8080` and verify the engine is running. 🟢

## 🔜 What's Next?
We just laid the foundation. **Phase 2** is where it gets real:
- JWT Authentication (Locking it down) 🔒
- User Roles (Who's the boss?)
- Actual movie data fetching

Stay tuned, we're just getting started. 🍿
