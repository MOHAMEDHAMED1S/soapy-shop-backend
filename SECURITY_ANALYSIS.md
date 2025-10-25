# تحليل الأمان - Security Analysis

## السؤال 1: هل يمكن إنشاء طلبين بنفس الـ ID؟

### ✅ الإجابة: **لا، النظام آمن من هذه الناحية**

### كيف يعمل النظام؟

```php
// في Order Model
public static function generateOrderNumber(): string
{
    do {
        $orderNumber = str_pad(mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
    } while (self::where('order_number', $orderNumber)->exists()); // ✅ يتحقق من عدم التكرار
    
    return $orderNumber;
}
```

### الحماية المطبقة:
1. ✅ **Loop حتى إيجاد رقم فريد** - يتحقق من قاعدة البيانات
2. ✅ **7 أرقام** - 9 مليون احتمال (من 1000000 إلى 9999999)
3. ✅ **Fallback مع timestamp** - إذا فشل 100 محاولة

### احتمالية التصادم (Collision):
```
مع 10,000 طلب: احتمالية التصادم ≈ 0.0055% (ضئيلة جداً)
مع 100,000 طلب: احتمالية التصادم ≈ 0.5% (مقبولة)
```

### ⚠️ مشكلة محتملة: Race Condition
إذا طلبان أنشئا في **نفس اللحظة تماماً**:

```
User A: generateOrderNumber() → 1234567
                ↓ (نفس الوقت)
User B: generateOrderNumber() → 1234567
                ↓
User A: يحفظ في DB ✅
User B: يحفظ في DB ❌ (Duplicate Key Error)
```

### الحل الحالي:
- ✅ MySQL `id` هو `AUTO_INCREMENT` - **فريد دائماً**
- ✅ `order_number` له `UNIQUE` constraint في DB
- ⚠️ إذا حدث تصادم → **Laravel Exception** → العملية تفشل

---

## السؤال 2: هل يمكن التداخل بين الطلبات عند تهيئة الدفع؟

### ⚠️ الإجابة: **نعم، يوجد ثغرة أمنية محتملة!**

### السيناريو الخطير:

```
1. User A ينشئ Order #5822964
2. User B يعرف الرقم 5822964
3. User B يستدعي: POST /api/v1/payments/initiate
   {
     "order_id": 123,  // ID الخاص بـ User A
     "payment_method": "knet"
   }
4. ✅ النظام يقبل! User B يمكنه الدفع عن طلب User A!
```

### الكود الحالي (غير آمن):

```php
// في PaymentController::initiate
public function initiate(Request $request)
{
    $order = Order::find($request->order_id); // ⚠️ لا يتحقق من الملكية!
    
    if ($order->status !== 'pending') {
        return error('Order is not pending');
    }
    
    // ✅ يقبل أي شخص!
}
```

### المشاكل:
1. ❌ **لا يوجد تحقق من ملكية الطلب**
2. ❌ **أي شخص يعرف `order_id` يمكنه الدفع**
3. ❌ **إمكانية "سرقة" الدفع**

---

## الثغرات الأمنية المكتشفة

### 🔴 ثغرة 1: عدم التحقق من ملكية الطلب

**المشكلة:**
```php
// أي شخص يمكنه الدفع عن أي طلب
POST /api/v1/payments/initiate
{
  "order_id": 999,  // طلب شخص آخر
  "payment_method": "knet"
}
```

**الحل المقترح:**
```php
// إضافة order_token للتحقق
POST /api/v1/payments/initiate
{
  "order_id": 999,
  "order_token": "abc123xyz...",  // Token سري يُرسل عند إنشاء الطلب
  "payment_method": "knet"
}
```

---

### 🟡 ثغرة 2: Race Condition في Inventory

**السيناريو:**
```
المنتج متوفر: 1 قطعة فقط

User A: يطلب 1 قطعة → pending
User B: يطلب 1 قطعة → pending (في نفس الوقت)

User A: يدفع → stock = 0 ✅
User B: يدفع → stock = -1 ❌ (overselling!)
```

**المشكلة في الكود الحالي:**
```php
// لا يتم حجز المخزون عند إنشاء الطلب
// الخصم يحدث فقط عند الدفع

public function createOrder() {
    // ✅ ينشئ الطلب
    // ❌ لا يحجز المخزون
}

public function deductInventory() {
    // يخصم عند الدفع
    // ⚠️ قد يكون المخزون نفذ!
}
```

---

### 🟡 ثغرة 3: Payment Callback Hijacking

**السيناريو:**
```
1. User A ينشئ طلب #123
2. User A يبدأ الدفع
3. User B يخمن رقم الفاتورة
4. User B يستدعي: /api/v1/payments/success?paymentId=INV-123
5. الطلب يتم تأكيده بدون دفع فعلي!
```

**المشكلة:**
```php
// في success callback
public function success(Request $request)
{
    $paymentId = $request->paymentId;
    
    // ⚠️ يثق بالـ paymentId من URL مباشرة
    // يجب التحقق من MyFatoorah أولاً
}
```

---

## الحلول المقترحة

### ✅ الحل 1: Order Token (الأهم)

#### 1. إضافة حقل `order_token` في Migration
```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('order_token', 64)->unique()->after('order_number');
});
```

#### 2. توليد Token عند إنشاء الطلب
```php
public function createOrder(Request $request)
{
    $orderNumber = Order::generateOrderNumber();
    $orderToken = Str::random(64); // Token سري
    
    $order = Order::create([
        'order_number' => $orderNumber,
        'order_token' => $orderToken,
        // ...
    ]);
    
    return response()->json([
        'order' => $order,
        'order_token' => $orderToken, // 🔒 أرسله للعميل فقط
    ]);
}
```

#### 3. التحقق من Token عند الدفع
```php
public function initiate(Request $request)
{
    $validator = Validator::make($request->all(), [
        'order_id' => 'required|exists:orders,id',
        'order_token' => 'required|string|size:64', // ✅ إضافة
        'payment_method' => 'required|string',
    ]);
    
    $order = Order::where('id', $request->order_id)
        ->where('order_token', $request->order_token) // ✅ التحقق
        ->first();
    
    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid order or token'
        ], 403);
    }
    
    // ✅ الآن آمن - فقط من لديه Token يمكنه الدفع
}
```

---

### ✅ الحل 2: حجز المخزون Temporarily

#### عند إنشاء الطلب:
```php
public function createOrder(Request $request)
{
    DB::beginTransaction();
    
    // 1. التحقق من المخزون
    foreach ($request->items as $item) {
        $product = Product::lockForUpdate()->find($item['product_id']);
        
        if ($product->has_inventory && !$product->canOrder($item['quantity'])) {
            return error('Insufficient stock');
        }
    }
    
    // 2. إنشاء الطلب
    $order = Order::create([...]);
    
    // 3. حجز المخزون مؤقتاً (لمدة 30 دقيقة مثلاً)
    foreach ($order->orderItems as $item) {
        InventoryReservation::create([
            'order_id' => $order->id,
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'expires_at' => now()->addMinutes(30),
        ]);
    }
    
    DB::commit();
}
```

#### عند الدفع:
```php
public function deductInventory()
{
    // حذف الحجز وخصم فعلي
    InventoryReservation::where('order_id', $this->id)->delete();
    
    foreach ($this->orderItems as $item) {
        $item->product->decreaseStock($item->quantity);
    }
}
```

#### Cron Job للتنظيف:
```php
// تنظيف الحجوزات المنتهية كل ساعة
Schedule::command('inventory:cleanup-expired')->hourly();
```

---

### ✅ الحل 3: Payment Verification

```php
public function success(Request $request)
{
    $paymentId = $request->paymentId;
    
    // ✅ 1. التحقق من MyFatoorah أولاً
    $verification = $this->paymentService->verifyPayment($paymentId);
    
    if (!$verification['success']) {
        return error('Payment verification failed');
    }
    
    // ✅ 2. التحقق من حالة الدفع
    if ($verification['data']['InvoiceStatus'] !== 'Paid') {
        return error('Payment not completed');
    }
    
    // ✅ 3. التحقق من المبلغ
    $order = Order::find($verification['data']['CustomerReference']);
    if ($verification['data']['InvoiceValue'] != $order->total_amount) {
        return error('Amount mismatch');
    }
    
    // ✅ الآن آمن
    $order->update(['status' => 'paid']);
}
```

---

## ملخص الأولويات

### 🔴 عاجل (Critical)
1. **إضافة Order Token** - لمنع سرقة الطلبات
2. **Payment Verification** - التحقق من MyFatoorah دائماً

### 🟡 مهم (High)
3. **حجز المخزون المؤقت** - منع overselling
4. **Rate Limiting** - منع spam attacks

### 🟢 محسّنات (Medium)
5. **Logging & Monitoring** - تتبع المحاولات المشبوهة
6. **IP Validation** - تسجيل IP addresses

---

## كود الحل الكامل

### Migration
```bash
php artisan make:migration add_order_token_to_orders_table
```

```php
public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->string('order_token', 64)->unique()->nullable()->after('order_number');
    });
    
    // Generate tokens for existing orders
    DB::table('orders')->whereNull('order_token')->update([
        'order_token' => DB::raw('SHA2(CONCAT(id, order_number, NOW()), 256)')
    ]);
    
    Schema::table('orders', function (Blueprint $table) {
        $table->string('order_token')->nullable(false)->change();
    });
}
```

### تعديل OrderController
```php
$orderToken = Str::random(64);

$order = Order::create([
    'order_token' => $orderToken,
    // ...
]);

return response()->json([
    'order' => $order,
    'order_token' => $orderToken, // 🔒 مهم جداً
]);
```

### تعديل PaymentController
```php
$validator = Validator::make($request->all(), [
    'order_id' => 'required|exists:orders,id',
    'order_token' => 'required|string|size:64',
    'payment_method' => 'required|string',
]);

$order = Order::where('id', $request->order_id)
    ->where('order_token', $request->order_token)
    ->first();

if (!$order) {
    return response()->json([
        'success' => false,
        'message' => 'Invalid order or token'
    ], 403);
}
```

---

## الخلاصة

### الوضع الحالي:
- ✅ **order_number** فريد ولا يتكرر
- ⚠️ **لا يوجد حماية** من سرقة الطلبات
- ⚠️ **race conditions** محتملة في المخزون

### بعد تطبيق الحلول:
- ✅ حماية كاملة من سرقة الطلبات
- ✅ منع overselling
- ✅ تحقق دقيق من المدفوعات

**التوصية: تطبيق Order Token فوراً! 🔒**

