# Chatbot API Documentation

## نظرة عامة
هذا التوثيق يشرح كيفية استخدام APIs الشات بوت في التطبيق. جميع الـ APIs تعمل بشكل صحيح وتم اختبارها بنجاح.

## Base URL
```
http://localhost:8000/api/v1
```

## Public APIs (للمستخدمين)

### 1. الحصول على إعدادات الشات بوت
**GET** `/chat/settings`

**الاستجابة:**
```json
{
    "success": true,
    "data": {
        "is_active": true,
        "welcome_message": "مرحبا! كيف يمكنني مساعدتك اليوم؟",
        "max_conversation_length": 50
    }
}
```

### 2. بدء محادثة جديدة
**POST** `/chat/start`

**الاستجابة:**
```json
{
    "success": true,
    "data": {
        "conversation_id": 15,
        "session_id": "tKQTLfwBynKRLuYfEqlQOievOVLb3PMa",
        "welcome_message": "مرحبا! كيف يمكنني مساعدتك اليوم؟",
        "chatbot_name": "Chatbot Assistant"
    }
}
```

**ملاحظة مهمة:** احفظ `session_id` لاستخدامه في باقي العمليات.

### 3. إرسال رسالة
**POST** `/chat/message`

**البيانات المطلوبة:**
```json
{
    "session_id": "session_id_from_start_api",
    "message": "رسالة المستخدم"
}
```

**الاستجابة:**
```json
{
    "success": true,
    "data": {
        "message": {
            "id": 10,
            "role": "assistant",
            "content": "رد الشات بوت...",
            "metadata": {
                "recommended_products": [
                    {
                        "id": 1,
                        "name": null,
                        "slug": "vitamin-c-face-moisturizer",
                        "price": "25.500",
                        "image": null,
                        "url": "https://soapy-bubbles.com/product/vitamin-c-face-moisturizer"
                    }
                ]
            },
            "sent_at": "2025-10-21T18:39:17.000000Z"
        },
        "conversation_status": "active"
    }
}
```

### 4. الحصول على تاريخ المحادثة
**GET** `/chat/history?session_id={session_id}`

**الاستجابة:**
```json
{
    "success": true,
    "data": {
        "conversation": {
            "session_id": "tKQTLfwBynKRLuYfEqlQOievOVLb3PMa",
            "status": "active",
            "message_count": 2,
            "created_at": "2025-10-21T18:39:08.000000Z",
            "last_activity_at": "2025-10-21T18:39:18.000000Z"
        },
        "messages": {
            "session_id": "tKQTLfwBynKRLuYfEqlQOievOVLb3PMa",
            "status": "active",
            "messages": [
                {
                    "id": 9,
                    "role": "user",
                    "content": "مرحبا، أريد معرفة المنتجات المتاحة",
                    "sent_at": "2025-10-21T18:39:08.000000Z"
                },
                {
                    "id": 10,
                    "role": "assistant",
                    "content": "رد الشات بوت...",
                    "metadata": {
                        "recommended_products": [...]
                    },
                    "sent_at": "2025-10-21T18:39:17.000000Z"
                }
            ]
        }
    }
}
```

### 5. إنهاء المحادثة
**POST** `/chat/end`

**البيانات المطلوبة:**
```json
{
    "session_id": "session_id_from_start_api"
}
```

**الاستجابة:**
```json
{
    "success": true,
    "message": "Conversation ended successfully"
}
```

## Admin APIs (للإدارة)

### 1. الحصول على إعدادات الشات بوت (للإدارة)
**GET** `/admin/chatbot/settings`

**Headers:**
```
Authorization: Bearer {admin_token}
```

**الاستجابة:**
```json
{
    "success": true,
    "data": {
        "name": "Chatbot Assistant",
        "system_prompt": "أنت مساعد ذكي لمتجر الصابون...",
        "welcome_message": "مرحبا! كيف يمكنني مساعدتك اليوم؟",
        "is_active": true,
        "product_access_type": "all",
        "ai_settings": {
            "model": "gemini-2.0-flash-exp",
            "temperature": 0.7,
            "max_tokens": 1000
        },
        "max_conversation_length": 50,
        "token_limit_per_message": 1000
    }
}
```

### 2. تحديث إعدادات الشات بوت
**PUT** `/admin/chatbot/settings`

**Headers:**
```
Authorization: Bearer {admin_token}
```

**البيانات المطلوبة:**
```json
{
    "name": "اسم الشات بوت",
    "system_prompt": "التعليمات الأساسية للشات بوت",
    "welcome_message": "رسالة الترحيب",
    "is_active": true,
    "product_access_type": "all",
    "max_conversation_length": 50,
    "token_limit_per_message": 1000
}
```

### 3. إحصائيات الشات بوت
**GET** `/admin/chatbot/statistics`

**Headers:**
```
Authorization: Bearer {admin_token}
```

### 4. قائمة المحادثات
**GET** `/admin/chatbot/conversations`

**Headers:**
```
Authorization: Bearer {admin_token}
```

### 5. المنتجات المتاحة للشات بوت
**GET** `/admin/chatbot/products`

**Headers:**
```
Authorization: Bearer {admin_token}
```

### 6. اختبار التكوين
**POST** `/admin/chatbot/test`

**Headers:**
```
Authorization: Bearer {admin_token}
```

## كيفية التكامل مع Frontend

### 1. بدء محادثة جديدة
```javascript
const startChat = async () => {
    try {
        const response = await fetch('/api/v1/chat/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // احفظ session_id للاستخدام لاحقاً
            localStorage.setItem('chatbot_session_id', data.data.session_id);
            
            // اعرض رسالة الترحيب
            displayMessage(data.data.welcome_message, 'assistant');
        }
    } catch (error) {
        console.error('Error starting chat:', error);
    }
};
```

### 2. إرسال رسالة
```javascript
const sendMessage = async (message) => {
    const sessionId = localStorage.getItem('chatbot_session_id');
    
    if (!sessionId) {
        console.error('No active session');
        return;
    }
    
    try {
        const response = await fetch('/api/v1/chat/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                session_id: sessionId,
                message: message
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // اعرض رد الشات بوت
            displayMessage(data.data.message.content, 'assistant');
            
            // اعرض المنتجات المقترحة إن وجدت
            if (data.data.message.metadata?.recommended_products) {
                displayRecommendedProducts(data.data.message.metadata.recommended_products);
            }
        }
    } catch (error) {
        console.error('Error sending message:', error);
    }
};
```

### 3. الحصول على تاريخ المحادثة
```javascript
const getChatHistory = async () => {
    const sessionId = localStorage.getItem('chatbot_session_id');
    
    if (!sessionId) {
        console.error('No active session');
        return;
    }
    
    try {
        const response = await fetch(`/api/v1/chat/history?session_id=${sessionId}`, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // اعرض الرسائل السابقة
            data.data.messages.messages.forEach(message => {
                displayMessage(message.content, message.role);
            });
        }
    } catch (error) {
        console.error('Error getting chat history:', error);
    }
};
```

### 4. إنهاء المحادثة
```javascript
const endChat = async () => {
    const sessionId = localStorage.getItem('chatbot_session_id');
    
    if (!sessionId) {
        return;
    }
    
    try {
        const response = await fetch('/api/v1/chat/end', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                session_id: sessionId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // امسح session_id
            localStorage.removeItem('chatbot_session_id');
            console.log('Chat ended successfully');
        }
    } catch (error) {
        console.error('Error ending chat:', error);
    }
};
```

## معالجة الأخطاء

### أخطاء شائعة:

1. **Session ID غير صالح (422)**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "session_id": ["The selected session id is invalid."]
    }
}
```

2. **الشات بوت غير نشط**
```json
{
    "success": false,
    "message": "Chatbot is currently inactive"
}
```

3. **خطأ في الخادم (500)**
```json
{
    "success": false,
    "message": "Internal server error"
}
```

## ملاحظات مهمة

1. **Session Management**: احرص على حفظ `session_id` بعد بدء المحادثة واستخدامه في جميع العمليات اللاحقة.

2. **المنتجات المقترحة**: الشات بوت يقترح منتجات ذات صلة في `metadata.recommended_products`.

3. **حد الرسائل**: هناك حد أقصى لعدد الرسائل في المحادثة الواحدة (افتراضياً 50 رسالة).

4. **التوكن**: هناك حد أقصى لعدد التوكنز في الرسالة الواحدة (افتراضياً 1000 توكن).

5. **الأمان**: APIs الإدارة تتطلب توكن المصادقة في الـ Header.

## حالة الاختبار

✅ جميع الـ APIs تم اختبارها وتعمل بشكل صحيح
✅ الشات بوت يتفاعل مع المستخدمين باللغة العربية
✅ يقترح منتجات ذات صلة بناءً على استفسارات المستخدمين
✅ إدارة الجلسات تعمل بشكل صحيح
✅ معالجة الأخطاء تعمل كما هو متوقع

تاريخ آخر تحديث: 21 أكتوبر 2025