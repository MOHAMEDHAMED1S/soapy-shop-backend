# توثيق واجهات برمجة التطبيقات لإدارة الشات بوت
## Admin Chatbot API Documentation

### نظرة عامة
هذا التوثيق يغطي جميع واجهات برمجة التطبيقات الخاصة بإدارة الشات بوت في لوحة التحكم. جميع هذه الواجهات تتطلب مصادقة المدير وتستخدم رموز Bearer للمصادقة.

### المعلومات الأساسية
- **المسار الأساسي**: `/api/v1/admin/chatbot`
- **المصادقة**: Bearer Token مطلوب
- **نوع المحتوى**: `application/json`
- **الترميز**: UTF-8

### رؤوس الطلبات المطلوبة
```
Authorization: Bearer {your_admin_token}
Content-Type: application/json
Accept: application/json
```

---

## 1. إدارة إعدادات الشات بوت

### 1.1 الحصول على الإعدادات الحالية
**GET** `/api/v1/admin/chatbot/settings`

#### الوصف
استرجاع جميع إعدادات الشات بوت الحالية.

#### المعاملات
لا توجد معاملات مطلوبة.

#### الاستجابة الناجحة (200)
```json
{
    "success": true,
    "data": {
        "name": "مساعد المتجر الذكي",
        "system_prompt": "أنت مساعد ذكي لمتجر الصابون الطبيعي...",
        "welcome_message": "مرحباً! كيف يمكنني مساعدتك اليوم؟",
        "is_active": true,
        "product_access_type": "all",
        "allowed_product_ids": [],
        "ai_settings": {
            "model": "gemini-1.5-flash",
            "temperature": 0.7,
            "max_tokens": 1000,
            "top_p": 0.9
        },
        "max_conversation_length": 50,
        "token_limit_per_message": 2000
    }
}
```

#### مثال cURL
```bash
curl -X GET "https://your-domain.com/api/v1/admin/chatbot/settings" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

---

### 1.2 تحديث إعدادات الشات بوت
**PUT** `/api/v1/admin/chatbot/settings`

#### الوصف
تحديث إعدادات الشات بوت بالقيم الجديدة.

#### بيانات الطلب
| الحقل | النوع | مطلوب | الوصف | القيود |
|-------|------|--------|-------|--------|
| `name` | string | نعم | اسم الشات بوت | 1-100 حرف |
| `system_prompt` | string | نعم | التعليمات الأساسية للذكاء الاصطناعي | 10-5000 حرف |
| `welcome_message` | string | نعم | رسالة الترحيب | 1-500 حرف |
| `is_active` | boolean | نعم | حالة تفعيل الشات بوت | true/false |
| `product_access_type` | string | نعم | نوع الوصول للمنتجات | all, specific, none |
| `allowed_product_ids` | array | لا | معرفات المنتجات المسموحة | مطلوب إذا كان النوع "specific" |
| `ai_settings` | object | نعم | إعدادات الذكاء الاصطناعي | انظر التفاصيل أدناه |
| `max_conversation_length` | integer | نعم | الحد الأقصى لطول المحادثة | 10-200 |
| `token_limit_per_message` | integer | نعم | حد الرموز لكل رسالة | 100-4000 |

#### إعدادات الذكاء الاصطناعي (ai_settings)
```json
{
    "model": "gemini-1.5-flash",
    "temperature": 0.7,
    "max_tokens": 1000,
    "top_p": 0.9
}
```

#### مثال الطلب
```json
{
    "name": "مساعد المتجر المحدث",
    "system_prompt": "أنت مساعد ذكي محدث لمتجر الصابون الطبيعي...",
    "welcome_message": "أهلاً وسهلاً! كيف يمكنني خدمتك؟",
    "is_active": true,
    "product_access_type": "all",
    "allowed_product_ids": [],
    "ai_settings": {
        "model": "gemini-1.5-flash",
        "temperature": 0.8,
        "max_tokens": 1200,
        "top_p": 0.9
    },
    "max_conversation_length": 60,
    "token_limit_per_message": 2500
}
```

#### الاستجابة الناجحة (200)
```json
{
    "success": true,
    "message": "تم تحديث إعدادات الشات بوت بنجاح",
    "data": {
        // نفس بنية البيانات كما في GET
    }
}
```

#### مثال cURL
```bash
curl -X PUT "https://your-domain.com/api/v1/admin/chatbot/settings" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "مساعد المتجر المحدث",
    "system_prompt": "أنت مساعد ذكي...",
    "welcome_message": "أهلاً وسهلاً!",
    "is_active": true,
    "product_access_type": "all",
    "ai_settings": {
        "model": "gemini-1.5-flash",
        "temperature": 0.8,
        "max_tokens": 1200
    },
    "max_conversation_length": 60,
    "token_limit_per_message": 2500
  }'
```

---

## 2. إحصائيات الشات بوت

### 2.1 الحصول على الإحصائيات
**GET** `/api/v1/admin/chatbot/statistics`

#### الوصف
استرجاع إحصائيات شاملة عن استخدام الشات بوت.

#### المعاملات
| المعامل | النوع | مطلوب | الوصف | القيمة الافتراضية |
|---------|------|--------|-------|-------------------|
| `days` | integer | لا | عدد الأيام للإحصائيات | 30 |

#### الاستجابة الناجحة (200)
```json
{
    "success": true,
    "data": {
        "total_conversations": 156,
        "active_conversations": 23,
        "total_messages": 1247,
        "average_messages_per_conversation": 8.0,
        "daily_stats": [
            {
                "date": "2024-01-15",
                "conversations": 12,
                "messages": 89
            }
        ],
        "hourly_distribution": {
            "00": 2,
            "01": 1,
            "09": 15,
            "14": 22
        },
        "popular_topics": [
            {
                "topic": "استفسارات المنتجات",
                "count": 45
            }
        ]
    }
}
```

#### مثال cURL
```bash
curl -X GET "https://your-domain.com/api/v1/admin/chatbot/statistics?days=7" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

---

## 3. إدارة المحادثات

### 3.1 قائمة المحادثات
**GET** `/api/v1/admin/chatbot/conversations`

#### الوصف
استرجاع قائمة بجميع محادثات الشات بوت مع إمكانية التصفية والبحث.

#### المعاملات
| المعامل | النوع | مطلوب | الوصف | القيمة الافتراضية |
|---------|------|--------|-------|-------------------|
| `page` | integer | لا | رقم الصفحة | 1 |
| `per_page` | integer | لا | عدد العناصر في الصفحة | 15 |
| `search` | string | لا | البحث في المحادثات | - |
| `status` | string | لا | حالة المحادثة | all |
| `date_from` | date | لا | تاريخ البداية | - |
| `date_to` | date | لا | تاريخ النهاية | - |

#### قيم حالة المحادثة
- `all`: جميع المحادثات
- `active`: المحادثات النشطة
- `ended`: المحادثات المنتهية

#### الاستجابة الناجحة (200)
```json
{
    "success": true,
    "data": {
        "conversations": [
            {
                "id": 1,
                "session_id": "conv_123456789",
                "customer_name": "أحمد محمد",
                "customer_email": "ahmed@example.com",
                "status": "active",
                "message_count": 12,
                "started_at": "2024-01-15T10:30:00Z",
                "last_activity": "2024-01-15T11:45:00Z",
                "ended_at": null
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 156,
            "last_page": 11,
            "from": 1,
            "to": 15
        }
    }
}
```

#### مثال cURL
```bash
curl -X GET "https://your-domain.com/api/v1/admin/chatbot/conversations?page=1&per_page=10&status=active" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

---

### 3.2 تفاصيل محادثة محددة
**GET** `/api/v1/admin/chatbot/conversations/{session_id}`

#### الوصف
استرجاع تفاصيل محادثة محددة مع جميع الرسائل.

#### المعاملات
| المعامل | النوع | مطلوب | الوصف |
|---------|------|--------|-------|
| `session_id` | string | نعم | معرف جلسة المحادثة |

#### الاستجابة الناجحة (200)
```json
{
    "success": true,
    "data": {
        "conversation": {
            "id": 1,
            "session_id": "conv_123456789",
            "customer_name": "أحمد محمد",
            "customer_email": "ahmed@example.com",
            "status": "active",
            "message_count": 12,
            "started_at": "2024-01-15T10:30:00Z",
            "last_activity": "2024-01-15T11:45:00Z",
            "ended_at": null
        },
        "messages": [
            {
                "id": 1,
                "sender": "user",
                "message": "مرحباً، أريد معرفة المزيد عن منتجاتكم",
                "timestamp": "2024-01-15T10:30:00Z"
            },
            {
                "id": 2,
                "sender": "bot",
                "message": "أهلاً وسهلاً! يسعدني مساعدتك. لدينا مجموعة رائعة من منتجات الصابون الطبيعي...",
                "timestamp": "2024-01-15T10:30:15Z"
            }
        ]
    }
}
```

#### مثال cURL
```bash
curl -X GET "https://your-domain.com/api/v1/admin/chatbot/conversations/conv_123456789" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

---

### 3.3 حذف محادثة
**DELETE** `/api/v1/admin/chatbot/conversations/{session_id}`

#### الوصف
حذف محادثة محددة وجميع رسائلها.

#### المعاملات
| المعامل | النوع | مطلوب | الوصف |
|---------|------|--------|-------|
| `session_id` | string | نعم | معرف جلسة المحادثة |

#### الاستجابة الناجحة (200)
```json
{
    "success": true,
    "message": "تم حذف المحادثة بنجاح"
}
```

#### مثال cURL
```bash
curl -X DELETE "https://your-domain.com/api/v1/admin/chatbot/conversations/conv_123456789" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

---

### 3.4 حذف المحادثات القديمة
**DELETE** `/api/v1/admin/chatbot/conversations/clear-old`

#### الوصف
حذف جميع المحادثات الأقدم من عدد الأيام المحدد.

#### بيانات الطلب
```json
{
    "days_old": 30
}
```

#### الاستجابة الناجحة (200)
```json
{
    "success": true,
    "message": "تم حذف 45 محادثة قديمة بنجاح",
    "deleted_count": 45
}
```

#### مثال cURL
```bash
curl -X DELETE "https://your-domain.com/api/v1/admin/chatbot/conversations/clear-old" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"days_old": 30}'
```

---

## 4. إدارة المنتجات

### 4.1 قائمة المنتجات المتاحة
**GET** `/api/v1/admin/chatbot/products`

#### الوصف
استرجاع قائمة بجميع المنتجات المتاحة للشات بوت.

#### المعاملات
| المعامل | النوع | مطلوب | الوصف | القيمة الافتراضية |
|---------|------|--------|-------|-------------------|
| `search` | string | لا | البحث في المنتجات | - |
| `per_page` | integer | لا | عدد العناصر في الصفحة | 20 |
| `page` | integer | لا | رقم الصفحة | 1 |

#### الاستجابة الناجحة (200)
```json
{
    "success": true,
    "data": {
        "products": [
            {
                "id": 1,
                "title": "صابون اللافندر الطبيعي",
                "slug": "lavender-natural-soap",
                "price": 25.00,
                "currency": "KWD",
                "is_available": true,
                "category": {
                    "id": 1,
                    "name": "صابون طبيعي"
                },
                "image_url": "https://example.com/images/lavender-soap.jpg"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 45,
            "last_page": 3
        }
    }
}
```

#### مثال cURL
```bash
curl -X GET "https://your-domain.com/api/v1/admin/chatbot/products?search=لافندر&per_page=10" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

---

## 5. اختبار التكوين

### 5.1 اختبار إعدادات الشات بوت
**POST** `/api/v1/admin/chatbot/test`

#### الوصف
اختبار تكوين الشات بوت والتأكد من عمل جميع المكونات بشكل صحيح.

#### بيانات الطلب
```json
{
    "test_message": "مرحباً، هذه رسالة اختبار"
}
```

#### الاستجابة الناجحة (200)
```json
{
    "success": true,
    "data": {
        "settings_valid": true,
        "ai_connection": {
            "status": "connected",
            "model": "gemini-1.5-flash",
            "response_time": 1.2
        },
        "product_access": {
            "status": "working",
            "accessible_products_count": 45
        },
        "test_response": {
            "message": "مرحباً! أنا مساعدك الذكي، كيف يمكنني مساعدتك اليوم؟",
            "response_time": 1.5
        }
    }
}
```

#### مثال cURL
```bash
curl -X POST "https://your-domain.com/api/v1/admin/chatbot/test" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"test_message": "مرحباً، هذه رسالة اختبار"}'
```

---

## معالجة الأخطاء

### رموز الحالة والأخطاء الشائعة

#### 400 - طلب غير صحيح
```json
{
    "success": false,
    "message": "البيانات المرسلة غير صحيحة",
    "errors": {
        "name": ["حقل الاسم مطلوب"],
        "system_prompt": ["يجب أن يكون النص بين 10 و 5000 حرف"]
    }
}
```

#### 401 - غير مصرح
```json
{
    "success": false,
    "message": "رمز المصادقة غير صحيح أو منتهي الصلاحية"
}
```

#### 403 - ممنوع
```json
{
    "success": false,
    "message": "ليس لديك صلاحية للوصول إلى هذا المورد"
}
```

#### 404 - غير موجود
```json
{
    "success": false,
    "message": "المحادثة المطلوبة غير موجودة"
}
```

#### 422 - خطأ في التحقق
```json
{
    "success": false,
    "message": "فشل في التحقق من البيانات",
    "errors": {
        "max_conversation_length": ["يجب أن يكون الرقم بين 10 و 200"]
    }
}
```

#### 500 - خطأ في الخادم
```json
{
    "success": false,
    "message": "حدث خطأ داخلي في الخادم"
}
```

---

## أفضل الممارسات

### 1. المصادقة والأمان
- استخدم دائماً HTTPS في الإنتاج
- احفظ رموز المصادقة بشكل آمن
- تحقق من انتهاء صلاحية الرموز وجددها عند الحاجة
- تعامل مع أخطاء 401/403 بإعادة توجيه المستخدم لتسجيل الدخول

### 2. إدارة الطلبات
- استخدم التصفح (pagination) للقوائم الطويلة
- طبق حدود معقولة لحجم الصفحة (10-50 عنصر)
- استخدم البحث والتصفية لتحسين الأداء

### 3. معالجة الأخطاء
- تحقق دائماً من حقل `success` في الاستجابة
- اعرض رسائل الخطأ للمستخدم بشكل واضح
- سجل الأخطاء للمراجعة والتحليل

### 4. الأداء
- استخدم التخزين المؤقت للبيانات التي لا تتغير كثيراً
- طبق حدود زمنية مناسبة للطلبات
- راقب أوقات الاستجابة وحسنها عند الحاجة

---

## أمثلة التطبيق

### مثال JavaScript (React/Vue)
```javascript
// إعداد العميل
const API_BASE = 'https://your-domain.com/api/v1/admin/chatbot';
const token = localStorage.getItem('admin_token');

const headers = {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
};

// الحصول على الإعدادات
async function getChatbotSettings() {
    try {
        const response = await fetch(`${API_BASE}/settings`, {
            method: 'GET',
            headers
        });
        
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('خطأ في جلب الإعدادات:', error);
        throw error;
    }
}

// تحديث الإعدادات
async function updateChatbotSettings(settings) {
    try {
        const response = await fetch(`${API_BASE}/settings`, {
            method: 'PUT',
            headers,
            body: JSON.stringify(settings)
        });
        
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('خطأ في تحديث الإعدادات:', error);
        throw error;
    }
}
```

### مثال PHP
```php
<?php
class ChatbotAdminAPI {
    private $baseUrl;
    private $token;
    
    public function __construct($baseUrl, $token) {
        $this->baseUrl = rtrim($baseUrl, '/') . '/api/v1/admin/chatbot';
        $this->token = $token;
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception($result['message'] ?? 'خطأ في الطلب');
        }
        
        return $result;
    }
    
    public function getSettings() {
        return $this->makeRequest('/settings');
    }
    
    public function updateSettings($settings) {
        return $this->makeRequest('/settings', 'PUT', $settings);
    }
    
    public function getStatistics($days = 30) {
        return $this->makeRequest('/statistics?days=' . $days);
    }
    
    public function getConversations($page = 1, $perPage = 15) {
        return $this->makeRequest("/conversations?page={$page}&per_page={$perPage}");
    }
}

// الاستخدام
$api = new ChatbotAdminAPI('https://your-domain.com', 'your_admin_token');

try {
    $settings = $api->getSettings();
    echo "الإعدادات الحالية: " . json_encode($settings, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
```

---

## الخلاصة

هذا التوثيق يغطي جميع واجهات برمجة التطبيقات المتاحة لإدارة الشات بوت في لوحة التحكم. تأكد من:

1. **استخدام المصادقة الصحيحة** مع كل طلب
2. **معالجة الأخطاء** بشكل مناسب
3. **تطبيق أفضل الممارسات** للأداء والأمان
4. **اختبار التكوين** بانتظام للتأكد من عمل النظام

للحصول على مساعدة إضافية أو الإبلاغ عن مشاكل، يرجى مراجعة فريق التطوير.