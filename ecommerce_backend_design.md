# 🛍️ E-Commerce Backend (Laravel) — Design Document

## 1) Database Schema (DB Schema)

### Tables

#### `users`
- id (bigint, PK)
- name (string, nullable)
- email (string, nullable)
- phone (string, nullable)
- password (string, nullable)
- role (enum: admin, customer, default=customer)
- created_at, updated_at

#### `categories`
- id (bigint, PK)
- name (string)
- image (string)
- slug (string, unique)
- parent_id (bigint, nullable, FK)
- created_at, updated_at

#### `products`
- id (bigint, PK)
- title (string)
- slug (string, unique)
- description (text)
- short_description (string, nullable)
- price (decimal)
- currency (string, default=KWD)
- is_available (boolean, default=true)
- category_id (FK → categories.id)
- images (json)
- meta (json)
- created_at, updated_at

#### `orders`
- id (bigint, PK)
- order_number (string, unique)
- customer_name (string)
- customer_phone (string)
- customer_email (string, nullable)
- shipping_address (json)
- total_amount (decimal)
- currency (string, default=KWD)
- status (enum: pending, awaiting_payment, paid, shipped, delivered, cancelled, refunded)
- payment_id (FK → payments.id, nullable)
- notes (text, nullable)
- created_at, updated_at

#### `order_items`
- id (bigint, PK)
- order_id (FK → orders.id)
- product_id (FK → products.id)
- product_price (decimal)
- quantity (int)
- product_snapshot (json) — snapshot of product details at purchase time

#### `payments`
- id (bigint, PK)
- order_id (FK → orders.id)
- provider (string) — e.g., "myfatoorah"
- payment_method (string)
- invoice_reference (string) — returned InvoiceId/PaymentId
- amount (decimal)
- currency (string)
- status (enum: initiated, pending, paid, failed, refunded)
- response_raw (json)
- created_at, updated_at

#### `webhook_logs`
- id (bigint, PK)
- provider (string)
- payload (json)
- received_at (timestamp)
- processed (boolean)
- processing_notes (text, nullable)

#### `admin_notifications`
- id (bigint, PK)
- type (string: new_order, payment_success, payment_failed, etc.)
- payload (json)
- read_at (timestamp, nullable)
- created_at

---

## 2) Payment Flow with MyFatoorah

1. **Create Order (Checkout)**
   - API `/api/checkout/create-order` receives customer info, shipping address, and items.
   - Insert record in `orders` and `order_items`, set status = `awaiting_payment`.

2. **Initiate & Execute Payment**
   - Backend calls MyFatoorah API (`InitiatePayment` if needed, then `ExecutePayment`).
   - Receive `PaymentURL` from MyFatoorah and return it to frontend.

3. **Redirect Customer**
   - Customer completes payment via `PaymentURL`.

4. **Store Response**
   - Save gateway response in `payments.response_raw` with status = `pending`.

5. **Webhook Handling**
   - MyFatoorah sends webhook (to `/api/payment/webhook/myfatoorah`).
   - Backend verifies payment status via MyFatoorah `GetPaymentStatus` API.
   - Update `payments.status` and `orders.status` accordingly (e.g., `paid`).
   - Store webhook payload in `webhook_logs`.

6. **Notifications**
   - If order paid → notify Admin and mark order as `paid`.
   - Later, Admin updates status to `shipped` or `delivered` manually.

---

## 3) API Endpoints

### Public APIs (Customer)

#### Products
- `GET /api/products` — List all products (filters: category, availability, search)
- `GET /api/products/{slug}` — Get product details
- `GET /api/categories` — List categories

#### Orders & Checkout
- `POST /api/checkout/create-order`
  - Input: `{ customer_name, customer_phone, customer_email?, shipping_address, items: [{product_id, quantity}], notes? }`
  - Output: `{ order_id, order_number, total_amount, currency, payment_redirect_url }`
- `GET /api/orders/{order_number}` — Get order status (secured by phone + order number)

### Payments
- `POST /api/payments/initiate` — (Optional) Get payment methods from MyFatoorah
- `POST /api/payments/execute` — Execute payment and return PaymentURL
- `POST /api/payment/webhook/myfatoorah` — Webhook endpoint for MyFatoorah

### Admin APIs (Protected with JWT)

#### Auth
- `POST /api/admin/login`
- `GET /api/admin/me`

#### Orders
- `GET /api/admin/orders` — List all orders (filter by status/date)
- `GET /api/admin/orders/{id}` — Get full details (items + payment info)
- `PUT /api/admin/orders/{id}/update-status` — Update order status

#### Products
- `POST /api/admin/products` — Create new product
- `PUT /api/admin/products/{id}` — Update product
- `DELETE /api/admin/products/{id}` — Delete product

#### Notifications
- `GET /api/admin/notifications` — List admin notifications

---

## 4) Notes
- Use `.env` to store MyFatoorah API keys (Test & Live).
- Always verify webhooks via MyFatoorah’s `GetPaymentStatus` API before updating order status.
- Store all raw responses for auditing and debugging.
- Use Laravel Queues for async tasks (notifications, webhook processing).
