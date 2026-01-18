# Twilio WhatsApp Templates Documentation

## Overview

This system uses **Twilio Content Templates** for sending WhatsApp messages. Templates must be created and approved in Twilio Console first.

> **Important:** WhatsApp doesn't allow variables at the START or END of templates. Always add text before and after variables.

---

## Configuration

### Environment Variables

```env
# Twilio Credentials
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886

# Store Settings
TWILIO_STORE_NAME=اسم المتجر
TWILIO_ADMIN_PHONES=+965XXXXXXXX
TWILIO_DELIVERY_PHONES=+965ZZZZZZZZ

# Template SIDs (from Twilio Console)
TWILIO_TEMPLATE_ORDER_CREATED=HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_TEMPLATE_STATUS_UPDATE=HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_TEMPLATE_ORDER_SHIPPED=HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_TEMPLATE_ORDER_DELIVERED=HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_TEMPLATE_ADMIN_NEW_ORDER=HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_TEMPLATE_DELIVERY_NEW_ORDER=HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## Templates to Create in Twilio Console

### 1. order_created (تأكيد الطلب)

**Template Type:** Text with Call-to-Action Button

**Template Content:**
```
مرحبا بك في {{1}}

عميلنا الكريم {{2}}، نشكرك على طلبك.

رقم الطلب: {{3}}
المبلغ الإجمالي: {{4}} {{5}}

سيتم التواصل معك قريبا لتأكيد موعد التوصيل. شكرا لثقتك بنا.
```

**Button:**
- Type: URL
- Label: تتبع طلبك
- URL: `https://soapy-bubbles.com/track-order/{{6}}`

**Variables:**
| Variable | Description | Code Value |
|----------|-------------|------------|
| `{{1}}` | اسم المتجر | `$storeName` |
| `{{2}}` | اسم العميل | `$order->customer_name` |
| `{{3}}` | رقم الطلب | `$order->order_number` |
| `{{4}}` | المبلغ | `$order->total_amount` |
| `{{5}}` | العملة | `$order->currency` |
| `{{6}}` | رقم الطلب للرابط | `$order->order_number` |

---

### 2. status_update (تحديث الحالة)

**Template Type:** Text with Call-to-Action Button

**Template Content:**
```
اشعار من {{1}}

عميلنا الكريم {{2}}، تم تحديث حالة طلبك.

رقم الطلب: {{3}}
الحالة الجديدة: {{4}}

شكرا لتسوقك معنا.
```

**Button:**
- Type: URL
- Label: تتبع طلبك
- URL: `https://soapy-bubbles.com/track-order/{{5}}`

**Variables:**
| Variable | Description |
|----------|-------------|
| `{{1}}` | اسم المتجر |
| `{{2}}` | اسم العميل |
| `{{3}}` | رقم الطلب |
| `{{4}}` | الحالة الجديدة |
| `{{5}}` | رقم الطلب للرابط |

---

### 3. order_shipped (تم الشحن)

**Template Type:** Text with Call-to-Action Button

**Template Content:**
```
اشعار من {{1}}

عميلنا الكريم {{2}}، تم شحن طلبك.

رقم الطلب: {{3}}
رقم التتبع: {{4}}

سيصلك الطلب في اقرب وقت ممكن. شكرا لثقتك بنا.
```

**Button:**
- Type: URL
- Label: تتبع طلبك
- URL: `https://soapy-bubbles.com/track-order/{{5}}`

**Variables:**
| Variable | Description |
|----------|-------------|
| `{{1}}` | اسم المتجر |
| `{{2}}` | اسم العميل |
| `{{3}}` | رقم الطلب |
| `{{4}}` | رقم التتبع |
| `{{5}}` | رقم الطلب للرابط |

---

### 4. order_delivered (تم التوصيل)

**Template Type:** Text with Call-to-Action Button

**Template Content:**
```
اشعار من {{1}}

عميلنا الكريم {{2}}، تم توصيل طلبك رقم {{3}} بنجاح.

نتمنى ان تنال منتجاتنا رضاك. شكرا لتسوقك معنا.
```

**Button:**
- Type: URL
- Label: تتبع طلبك
- URL: `https://soapy-bubbles.com/track-order/{{4}}`

**Variables:**
| Variable | Description |
|----------|-------------|
| `{{1}}` | اسم المتجر |
| `{{2}}` | اسم العميل |
| `{{3}}` | رقم الطلب |
| `{{4}}` | رقم الطلب للرابط |

---

### 5. admin_new_order (إشعار الأدمن)

**Template Type:** Text Only

**Template Content:**
```
طلب جديد مدفوع من المتجر

رقم الطلب: {{1}}
العميل: {{2}}
الهاتف: {{3}}
المبلغ: {{4}} {{5}}

يرجى متابعة الطلب.
```

**Variables:**
| Variable | Description |
|----------|-------------|
| `{{1}}` | رقم الطلب |
| `{{2}}` | اسم العميل |
| `{{3}}` | رقم الهاتف |
| `{{4}}` | المبلغ |
| `{{5}}` | العملة |

---

### 6. delivery_new_order (إشعار المندوب)

**Template Type:** Text Only

**Template Content:**
```
طلب جديد للتوصيل

رقم الطلب: {{1}}
العميل: {{2}}
الهاتف: {{3}}
العنوان: {{4}}

يرجى التواصل مع العميل لتحديد موعد التوصيل.
```

**Variables:**
| Variable | Description |
|----------|-------------|
| `{{1}}` | رقم الطلب |
| `{{2}}` | اسم العميل |
| `{{3}}` | رقم الهاتف |
| `{{4}}` | العنوان |

---

## How to Create Templates in Twilio

1. Go to **Twilio Console** → **Messaging** → **Content Template Builder**
2. Click **Create New Template**
3. Select **WhatsApp** as the channel
4. For customer templates, select **Call to Action** and add a URL button
5. Enter the template content (make sure no variables at start/end)
6. Add button with dynamic URL: `https://soapy-bubbles.com/track-order/{{6}}`
7. Submit for **WhatsApp approval**
8. Copy the **Content SID** (starts with `HX`) and add to `.env`

---

## Code Variables Mapping

| Template | Variables Array |
|----------|-----------------|
| order_created | `['1' => storeName, '2' => name, '3' => orderNum, '4' => amount, '5' => currency, '6' => orderNum]` |
| status_update | `['1' => storeName, '2' => name, '3' => orderNum, '4' => status, '5' => orderNum]` |
| order_shipped | `['1' => storeName, '2' => name, '3' => orderNum, '4' => tracking, '5' => orderNum]` |
| order_delivered | `['1' => storeName, '2' => name, '3' => orderNum, '4' => orderNum]` |
| admin_new_order | `['1' => orderNum, '2' => name, '3' => phone, '4' => amount, '5' => currency]` |
| delivery_new_order | `['1' => orderNum, '2' => name, '3' => phone, '4' => address]` |
