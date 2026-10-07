# Arkacell Inventory Management Portal

## Quick Install

To get the project up and running quickly:

```bash
git clone https://github.com/hmasroor/arkacell-masroor.git
cd arkacell-masroor
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The application will then be available at:

```text
http://127.0.0.1:8000
```

### Default Login

After running `php artisan migrate:refresh --seed`, you can log in to the Filament panel using:

```text
Email:    hmasroor@arkacell.com
Password: Test@1234
```

> Make sure your MySQL database is running and the database credentials in `.env` are configured correctly before running the migrations.

## Description

This project is an inventory management portal developed for a Laravel developer hiring assessment at **arkacell.com**.

It was originally developed as a **Laravel Developer technical assessment** for a job application at Arkacell. The assessment was provided to me as a PDF (`Laravel + MySQL Technical Assessment.pdf`) with a **2-day deadline**.

> **Development time:** Approximately 2 days.

One of the requirements of the assessment was not to use any AI tools.

I won't lie — I did use AI during the development of this project.

However, I mainly used it as a **learning tool**, not as a way to have the project written for me. I asked questions such as:

- "How should I implement this specific thing in Laravel?"
- "What is the Laravel best practice for doing this?"
- "How does this particular Laravel feature work?"
- "What is the correct way to approach this problem?"

I still had to understand the answers, make decisions about the implementation, write the code, debug it, test it, and put everything together myself.

This project actually taught me a lot during those two days.

---

## My Experience With This Assessment

For my current level of programming experience and my familiarity with Laravel, this project was honestly quite challenging.

At the beginning, I looked at the requirements and thought:

> "This is really difficult. Why is a job assessment this complicated?"

At some point, I even started questioning myself and wondering if maybe I wasn't a good enough programmer.

But I spent two full days working on it, often for more than six hours a day and sometimes until around 2 AM, until I finally got it working.

Interestingly, a large part of those two days was spent **learning**, rather than simply writing the project.

For example, I wanted to have a proper administration UI for the project. I had heard about **Laravel Filament** before, but I had never actually used it.

So on the first day, I spent a lot of time reading the Filament website and documentation, figuring out how its resources, forms, tables, actions, filters, and other features worked.

That learning process was probably one of the most valuable parts of this project for me.

---

# What I Built

## 1. Laravel and Filament

I started by setting up the Laravel project and installing Laravel Filament for the administration panel.

The project uses:

- Laravel
- PHP
- MySQL
- Laravel Filament
- Redis
- Pest / PHPUnit

---

## 2. Database Design

I read through the assessment PDF several times to understand the database structure and requirements.

Honestly, the PDF was quite confusing to me at first, especially when I was trying to understand how all the inventory and multi-tenancy requirements were supposed to work together.

I created the required database migrations and ran the migrations many times during development.

I probably ran `migrate:refresh` more than 10 times while working on the project. :)

The main database tables are:

### `tenants`

Stores the brands/companies using the system.

### `products`

Stores products belonging to each tenant.

### `warehouses`

Stores warehouses belonging to each tenant.

### `stock_levels`

Stores the **current stock quantity** of each product in each warehouse.

### `stock_movements`

Stores the complete history of inventory movements, including:

- Stock coming into a warehouse
- Stock leaving a warehouse
- Transfers between warehouses

---

## 3. Database Factories and Seeding

I created database factories for the main entities so that the project can be populated with sample data easily.

After downloading the project and setting up the database, you can simply run:

```bash
php artisan db:seed
```

This will populate the database with sample tenants, products, warehouses, and related data.

---

## 4. Multi-Tenancy

Understanding the concept of **multi-tenancy** was one of the harder parts for me at first.

Once I started thinking of a tenant as a **brand/company**, the concept became much easier to understand.

All tenants share the same database and database schema, while tenant-specific records are separated using `tenant_id`.

I also implemented a global tenant scope so that tenant-specific models automatically query data belonging to the current tenant.

This means that when working with products, warehouses, stock levels, or stock movements, the application automatically limits the data to the current tenant.

---

## 5. Tenant Detection

The assessment suggested detecting the current tenant using either an `X-Tenant` header or a subdomain.

I decided to support **three methods**:

1. `X-Tenant` header
2. Subdomain
3. Session

For example, the API can use:

```http
X-Tenant: acme
```

while a web request can use a subdomain such as:

```text
https://acme.example.com
```

I also added a tenant selector to the administration panel.

This is useful when developing locally because accessing different subdomains is not always convenient.

The tenant detection logic is handled by a middleware called:

```text
IdentifyTenant
```

For web requests, if no tenant can be resolved from the available methods, the application falls back to the first available tenant so the local administration panel remains usable.

For API requests, an invalid or missing tenant is not silently replaced with another tenant.

---

## 6. Stock Levels vs. Stock Movements

One of the important design decisions in this project was having both:

```text
stock_movements
stock_levels
```

At first, I wondered why both tables were necessary.

`stock_movements` is the **history** of everything that happened to the inventory.

For example:

```text
IN       +100
OUT       -20
TRANSFER -10
TRANSFER +10
OUT       -5
```

`stock_levels`, on the other hand, stores the **current stock**.

You could technically calculate the current stock by going through all the historical movements, but that would become increasingly expensive as `stock_movements` grows.

The system is expected to potentially have a very large number of movements, so constantly calculating the current stock by scanning historical records would not be a good approach from a performance perspective.

Instead, every inventory operation updates `stock_levels`, while `stock_movements` keeps the complete history.

This gives us both:

- Fast access to the current stock
- A complete audit/history of inventory movements

---

## 7. Eloquent Relationships and Filament Admin Panel

I created the Eloquent relationships between the models.

The relationship part itself wasn't particularly difficult for me, but building the administration panel with Filament was a different story.

Since I had never used Filament before, I spent quite a lot of time going through its documentation and examples to figure out how to build the UI I wanted.

The administration panel includes management for:

- Tenants
- Products
- Warehouses
- Stock levels
- Stock movements

Stock levels are treated as read-only information, while stock movements are used to perform inventory operations.

The system supports:

- Stock in
- Stock out
- Warehouse transfers
- Product history
- Stock filtering
- Tenant switching

---

## 8. Inventory Service

Instead of putting the inventory logic directly inside controllers or Filament actions, I moved the core inventory operations into:

```text
app/Services/InventoryService.php
```

This service contains the main operations for:

- Adding stock
- Removing stock
- Transferring stock between warehouses

This was especially useful because the project has both a web interface and an API.

Both of them use the same inventory service instead of duplicating the business logic.

---

## 9. Stock Reconciliation Command

The assessment also required an Artisan command for reconciling stock levels.

I implemented:

```bash
php artisan inventory:reconcile {tenant}
```

The tenant can be identified using either its ID or subdomain:

```bash
php artisan inventory:reconcile 2
```

or:

```bash
php artisan inventory:reconcile acme
```

The command recalculates the expected stock from the complete `stock_movements` history and compares it with the current values stored in `stock_levels`.

If discrepancies are found, it reports:

- Product
- Warehouse
- Expected quantity
- Actual quantity

It then asks for confirmation before correcting the stock levels.

For automated/production use, it can also be run with:

```bash
php artisan inventory:reconcile acme --force
```

The corrections are performed inside a database transaction so that the operation is safe if something goes wrong during the reconciliation.

---

## 10. API

The assessment also required a separate API.

I implemented endpoints for:

### Create a stock movement

```http
POST /api/stock-movements
```

### Get stock levels

```http
GET /api/stock-levels
```

with filters such as:

```text
product_id
warehouse_id
```

and pagination.

### Get product history

```http
GET /api/products/{sku}/history
```

The API uses Laravel Form Requests for validation and API Resources for consistent responses.

Because the actual inventory logic lives inside `InventoryService`, the API and the Filament administration panel use the same business logic.

---

## 11. Transactions and Row-Level Locking

One of the most important parts of the assessment was making inventory operations safe under concurrent requests.

For example, imagine the warehouse has:

```text
50 items
```

and 100 requests arrive at almost exactly the same time, each trying to remove one item.

Without proper locking, multiple requests could read the same stock value before another request updates it, potentially causing incorrect stock levels.

For stock changes, I use database transactions together with row-level locking:

```php
lockForUpdate()
```

This ensures that concurrent operations on the same stock record are serialized correctly.

The application also prevents stock from becoming negative and throws an `InsufficientStockException` when there isn't enough stock available.

---

## 12. Concurrency Testing

This was probably one of the most challenging parts of the entire assessment for me.

I don't have a lot of practical experience writing automated tests. Most of my previous experience was theoretical — reading about testing and watching tutorials about it.

This assessment required not only basic feature tests, but also a **concurrency test** to verify that the inventory system behaves correctly when many requests happen at the same time.

The test starts with:

```text
50 items
```

and then sends:

```text
100 concurrent stock-out jobs
```

The expected result is:

```text
50 successful
50 failed
Final stock: 0
```

I was already familiar with database transactions and rollbacks, and I had used transactions in one of my personal projects before.

However, concurrency and row-level locking in the context of inventory management was a different level of problem.

When dealing with inventory, even one incorrect item can potentially represent a significant financial loss.

This was also one of the areas where I used AI the most during the project, mainly because I didn't have much practical experience with concurrency testing and wanted to understand how it should properly be implemented.

I spent quite a bit of time learning about:

- PHPUnit vs. Pest
- Database transactions
- Row-level locking
- Concurrent queue workers
- Redis queues
- Separate testing databases
- Testing concurrency with real MySQL

The final concurrency test uses multiple
