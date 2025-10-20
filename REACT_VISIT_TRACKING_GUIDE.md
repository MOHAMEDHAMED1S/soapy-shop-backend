# دليل تتبع الزيارات للواجهة الأمامية React

## نظرة عامة

نظام تتبع الزيارات يوفر إمكانيات شاملة لتتبع زيارات المستخدمين وتحليل البيانات في الوقت الفعلي. يدعم النظام تتبع المصادر (Referers) والإحصائيات التفصيلية لكل نطاق (Domain).

## المميزات الأساسية

- ✅ تتبع الزيارات تلقائياً
- ✅ تتبع المصادر والنطاقات
- ✅ إحصائيات في الوقت الفعلي
- ✅ تتبع الأجهزة والمتصفحات
- ✅ تتبع الصفحات الأكثر شعبية
- ✅ تتبع البكسل للتحليلات المتقدمة

---

## 📋 جدول المحتويات

1. [تسجيل الزيارات](#تسجيل-الزيارات)
2. [تتبع البكسل](#تتبع-البكسل)
3. [الإحصائيات العامة](#الإحصائيات-العامة)
4. [الإحصائيات في الوقت الفعلي](#الإحصائيات-في-الوقت-الفعلي)
5. [إحصائيات المصادر](#إحصائيات-المصادر)
6. [أهم النطاقات المرجعية](#أهم-النطاقات-المرجعية)
7. [الصفحات الأكثر شعبية](#الصفحات-الأكثر-شعبية)
8. [الزيارات اليومية](#الزيارات-اليومية)
9. [إحصائيات الأجهزة](#إحصائيات-الأجهزة)
10. [التطبيق في React](#التطبيق-في-react)
11. [لوحة التحكم](#لوحة-التحكم)

---

## 1. تسجيل الزيارات

### الوصف
تسجيل زيارة جديدة مع تفاصيل المستخدم والصفحة والجهاز.

### Endpoint
```
POST /api/v1/visits/track
```

### Headers المطلوبة
```javascript
{
  "Content-Type": "application/json",
  "Accept": "application/json"
}
```

### البيانات المطلوبة
```javascript
{
  "page_url": "string",      // رابط الصفحة (مطلوب)
  "page_title": "string",    // عنوان الصفحة (اختياري)
  "referer": "string",       // المصدر المرجعي (اختياري)
  "user_agent": "string",    // معلومات المتصفح (اختياري)
  "session_id": "string"     // معرف الجلسة (اختياري)
}
```

### مثال على الطلب
```javascript
const trackVisit = async (pageData) => {
  try {
    const response = await fetch('/api/v1/v1/visits/track', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        page_url: window.location.href,
        page_title: document.title,
        referer: document.referrer,
        user_agent: navigator.userAgent,
        session_id: getSessionId() // دالة للحصول على معرف الجلسة
      })
    });

    const data = await response.json();
    console.log('تم تسجيل الزيارة:', data);
    return data;
  } catch (error) {
    console.error('خطأ في تسجيل الزيارة:', error);
  }
};
```

### الاستجابة الناجحة
```javascript
{
  "success": true,
  "message": "تم تسجيل الزيارة بنجاح",
  "data": {
    "visit_id": 123,
    "page_url": "https://example.com/page",
    "ip_address": "192.168.1.1",
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

### أخطاء محتملة
```javascript
// خطأ في التحقق من البيانات
{
  "success": false,
  "message": "بيانات غير صحيحة",
  "errors": {
    "page_url": ["حقل رابط الصفحة مطلوب"]
  }
}
```

---

## 2. تتبع البكسل

### الوصف
تتبع الزيارات باستخدام صورة بكسل شفافة (مفيد للتحليلات المتقدمة).

### Endpoint
```
GET /api/v1/visits/pixel
```

### المعاملات (Query Parameters)
```
?page_url=https://example.com&page_title=الصفحة الرئيسية&referer=https://google.com
```

### مثال على الاستخدام
```javascript
// إنشاء صورة بكسل لتتبع الزيارة
const trackPixelVisit = () => {
  const img = new Image();
  const params = new URLSearchParams({
    page_url: window.location.href,
    page_title: document.title,
    referer: document.referrer
  });
  
  img.src = `/api/v1/visits/pixel?${params.toString()}`;
  img.style.display = 'none';
  document.body.appendChild(img);
};

// استخدام البكسل عند تحميل الصفحة
window.addEventListener('load', trackPixelVisit);
```

### الاستجابة
```
صورة PNG شفافة 1x1 بكسل
Content-Type: image/png
```

---

## 3. الإحصائيات العامة

### الوصف
الحصول على إحصائيات عامة للزيارات خلال فترة زمنية محددة.

### Endpoint
```
GET /api/v1/analytics/statistics
```

### المعاملات الاختيارية
```
?start_date=2024-01-01&end_date=2024-01-31
```

### مثال على الطلب
```javascript
const getGeneralStatistics = async (startDate, endDate) => {
  try {
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    const response = await fetch(`/api/v1/analytics/statistics?${params.toString()}`);
    const data = await response.json();
    
    return data;
  } catch (error) {
    console.error('خطأ في جلب الإحصائيات:', error);
  }
};

// استخدام الدالة
const stats = await getGeneralStatistics('2024-01-01', '2024-01-31');
```

### الاستجابة
```javascript
{
  "success": true,
  "data": {
    "total_visits": 1250,
    "unique_visitors": 890,
    "date_range": {
      "start": "2024-01-01",
      "end": "2024-01-31"
    }
  }
}
```

---

## 4. الإحصائيات في الوقت الفعلي

### الوصف
إحصائيات شاملة للـ 24 ساعة الماضية مع تفاصيل إضافية.

### Endpoint
```
GET /api/v1/analytics/real-time
```

### مثال على الطلب
```javascript
const getRealTimeStats = async () => {
  try {
    const response = await fetch('/api/v1/v1/analytics/real-time');
    const data = await response.json();
    
    return data;
  } catch (error) {
    console.error('خطأ في جلب الإحصائيات الفورية:', error);
  }
};
```

### الاستجابة
```javascript
{
  "success": true,
  "data": {
    "total_visits_24h": 156,
    "unique_visitors_24h": 98,
    "visits_by_referer_type": {
      "direct": 45,
      "search": 67,
      "social": 23,
      "referral": 21
    },
    "hourly_visits": [
      {"hour": "00", "visits": 5},
      {"hour": "01", "visits": 3},
      {"hour": "02", "visits": 2}
      // ... باقي الساعات
    ],
    "top_pages_24h": [
      {
        "page_url": "/",
        "page_title": "الصفحة الرئيسية",
        "visits": 45
      },
      {
        "page_url": "/products",
        "page_title": "المنتجات",
        "visits": 32
      }
    ]
  }
}
```

---

## 5. إحصائيات المصادر

### الوصف
تحليل أنواع المصادر المرجعية للزيارات.

### Endpoint
```
GET /api/v1/analytics/referer-types
```

### المعاملات الاختيارية
```
?start_date=2024-01-01&end_date=2024-01-31
```

### مثال على الطلب
```javascript
const getRefererStats = async (startDate, endDate) => {
  try {
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    const response = await fetch(`/api/v1/analytics/referer-types?${params.toString()}`);
    const data = await response.json();
    
    return data;
  } catch (error) {
    console.error('خطأ في جلب إحصائيات المصادر:', error);
  }
};
```

### الاستجابة
```javascript
{
  "success": true,
  "data": {
    "direct": 245,      // زيارات مباشرة
    "search": 189,      // من محركات البحث
    "social": 67,       // من وسائل التواصل الاجتماعي
    "referral": 43,     // من مواقع أخرى
    "email": 12         // من الإيميل
  }
}
```

---

## 6. أهم النطاقات المرجعية

### الوصف
قائمة بأهم النطاقات التي ترسل زيارات للموقع.

### Endpoint
```
GET /api/v1/analytics/top-referer-domains
```

### المعاملات الاختيارية
```
?start_date=2024-01-01&end_date=2024-01-31&limit=10
```

### مثال على الطلب
```javascript
const getTopRefererDomains = async (startDate, endDate, limit = 10) => {
  try {
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    params.append('limit', limit);
    
    const response = await fetch(`/api/v1/analytics/top-referer-domains?${params.toString()}`);
    const data = await response.json();
    
    return data;
  } catch (error) {
    console.error('خطأ في جلب أهم النطاقات:', error);
  }
};
```

### الاستجابة
```javascript
{
  "success": true,
  "data": [
    {
      "referer_domain": "google.com",
      "visits": 156
    },
    {
      "referer_domain": "facebook.com",
      "visits": 89
    },
    {
      "referer_domain": "twitter.com",
      "visits": 45
    },
    {
      "referer_domain": "instagram.com",
      "visits": 32
    }
  ]
}
```

---

## 7. الصفحات الأكثر شعبية

### الوصف
قائمة بالصفحات الأكثر زيارة في الموقع.

### Endpoint
```
GET /api/v1/analytics/popular-pages
```

### المعاملات الاختيارية
```
?start_date=2024-01-01&end_date=2024-01-31&limit=10
```

### مثال على الطلب
```javascript
const getPopularPages = async (startDate, endDate, limit = 10) => {
  try {
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    params.append('limit', limit);
    
    const response = await fetch(`/api/v1/analytics/popular-pages?${params.toString()}`);
    const data = await response.json();
    
    return data;
  } catch (error) {
    console.error('خطأ في جلب الصفحات الشعبية:', error);
  }
};
```

### الاستجابة
```javascript
{
  "success": true,
  "data": [
    {
      "page_url": "/",
      "page_title": "الصفحة الرئيسية",
      "visits": 245
    },
    {
      "page_url": "/products",
      "page_title": "المنتجات",
      "visits": 189
    },
    {
      "page_url": "/about",
      "page_title": "من نحن",
      "visits": 156
    }
  ]
}
```

---

## 8. الزيارات اليومية

### الوصف
إحصائيات الزيارات مقسمة حسب الأيام.

### Endpoint
```
GET /api/v1/analytics/daily-visits
```

### المعاملات الاختيارية
```
?start_date=2024-01-01&end_date=2024-01-31
```

### مثال على الطلب
```javascript
const getDailyVisits = async (startDate, endDate) => {
  try {
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    const response = await fetch(`/api/v1/analytics/daily-visits?${params.toString()}`);
    const data = await response.json();
    
    return data;
  } catch (error) {
    console.error('خطأ في جلب الزيارات اليومية:', error);
  }
};
```

### الاستجابة
```javascript
{
  "success": true,
  "data": [
    {
      "date": "2024-01-01",
      "visits": 45,
      "unique_visitors": 32
    },
    {
      "date": "2024-01-02",
      "visits": 67,
      "unique_visitors": 48
    },
    {
      "date": "2024-01-03",
      "visits": 89,
      "unique_visitors": 61
    }
  ]
}
```

---

## 9. إحصائيات الأجهزة

### الوصف
تحليل أنواع الأجهزة والمتصفحات وأنظمة التشغيل.

### Endpoint
```
GET /api/v1/analytics/device-stats
```

### المعاملات الاختيارية
```
?start_date=2024-01-01&end_date=2024-01-31
```

### مثال على الطلب
```javascript
const getDeviceStats = async (startDate, endDate) => {
  try {
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    const response = await fetch(`/api/v1/analytics/device-stats?${params.toString()}`);
    const data = await response.json();
    
    return data;
  } catch (error) {
    console.error('خطأ في جلب إحصائيات الأجهزة:', error);
  }
};
```

### الاستجابة
```javascript
{
  "success": true,
  "data": {
    "devices": [
      {
        "device_type": "desktop",
        "visits": 156
      },
      {
        "device_type": "mobile",
        "visits": 234
      },
      {
        "device_type": "tablet",
        "visits": 45
      }
    ],
    "browsers": [
      {
        "browser": "Chrome",
        "visits": 189
      },
      {
        "browser": "Safari",
        "visits": 123
      },
      {
        "browser": "Firefox",
        "visits": 67
      }
    ],
    "operating_systems": [
      {
        "operating_system": "Windows",
        "visits": 145
      },
      {
        "operating_system": "macOS",
        "visits": 98
      },
      {
        "operating_system": "Android",
        "visits": 156
      },
      {
        "operating_system": "iOS",
        "visits": 78
      }
    ]
  }
}
```

---

## 10. التطبيق في React

### إعداد خدمة التتبع

```javascript
// services/VisitTrackingService.js
class VisitTrackingService {
  constructor(baseURL = '') {
    this.baseURL = baseURL;
    this.sessionId = this.generateSessionId();
  }

  generateSessionId() {
    return localStorage.getItem('visit_session_id') || 
           this.createNewSession();
  }

  createNewSession() {
    const sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    localStorage.setItem('visit_session_id', sessionId);
    return sessionId;
  }

  async trackVisit(pageData = {}) {
    const visitData = {
      page_url: window.location.href,
      page_title: document.title,
      referer: document.referrer,
      user_agent: navigator.userAgent,
      session_id: this.sessionId,
      ...pageData
    };

    try {
      const response = await fetch(`${this.baseURL}/api/v1/visits/track`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(visitData)
      });

      return await response.json();
    } catch (error) {
      console.error('خطأ في تتبع الزيارة:', error);
      return null;
    }
  }

  trackPixelVisit() {
    const img = new Image();
    const params = new URLSearchParams({
      page_url: window.location.href,
      page_title: document.title,
      referer: document.referrer
    });
    
    img.src = `${this.baseURL}/api/v1/visits/pixel?${params.toString()}`;
    img.style.display = 'none';
    document.body.appendChild(img);
  }
}

export default new VisitTrackingService();
```

### Hook للتتبع التلقائي

```javascript
// hooks/useVisitTracking.js
import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import VisitTrackingService from '../services/VisitTrackingService';

export const useVisitTracking = (options = {}) => {
  const location = useLocation();
  const { 
    trackOnMount = true, 
    trackOnRouteChange = true,
    usePixelTracking = false 
  } = options;

  useEffect(() => {
    if (trackOnMount) {
      if (usePixelTracking) {
        VisitTrackingService.trackPixelVisit();
      } else {
        VisitTrackingService.trackVisit();
      }
    }
  }, []);

  useEffect(() => {
    if (trackOnRouteChange) {
      if (usePixelTracking) {
        VisitTrackingService.trackPixelVisit();
      } else {
        VisitTrackingService.trackVisit();
      }
    }
  }, [location.pathname]);

  return {
    trackVisit: VisitTrackingService.trackVisit.bind(VisitTrackingService),
    trackPixelVisit: VisitTrackingService.trackPixelVisit.bind(VisitTrackingService)
  };
};
```

### مكون التتبع

```javascript
// components/VisitTracker.jsx
import React from 'react';
import { useVisitTracking } from '../hooks/useVisitTracking';

const VisitTracker = ({ children, ...options }) => {
  useVisitTracking(options);
  
  return children || null;
};

export default VisitTracker;
```

### استخدام في التطبيق الرئيسي

```javascript
// App.jsx
import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import VisitTracker from './components/VisitTracker';
import HomePage from './pages/HomePage';
import ProductsPage from './pages/ProductsPage';

function App() {
  return (
    <Router>
      <VisitTracker trackOnRouteChange={true}>
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/products" element={<ProductsPage />} />
        </Routes>
      </VisitTracker>
    </Router>
  );
}

export default App;
```

---

## 11. لوحة التحكم

### خدمة الإحصائيات

```javascript
// services/AnalyticsService.js
class AnalyticsService {
  constructor(baseURL = '') {
    this.baseURL = baseURL;
  }

  async getGeneralStats(startDate, endDate) {
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    const response = await fetch(`${this.baseURL}/api/v1/analytics/statistics?${params.toString()}`);
    return await response.json();
  }

  async getRealTimeStats() {
    const response = await fetch(`${this.baseURL}/api/v1/analytics/real-time`);
    return await response.json();
  }

  async getRefererStats(startDate, endDate) {
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    const response = await fetch(`${this.baseURL}/api/v1/analytics/referer-types?${params.toString()}`);
    return await response.json();
  }

  async getTopRefererDomains(startDate, endDate, limit = 10) {
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    params.append('limit', limit);
    
    const response = await fetch(`${this.baseURL}/api/v1/analytics/top-referer-domains?${params.toString()}`);
    return await response.json();
  }

  async getPopularPages(startDate, endDate, limit = 10) {
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    params.append('limit', limit);
    
    const response = await fetch(`${this.baseURL}/api/v1/analytics/popular-pages?${params.toString()}`);
    return await response.json();
  }

  async getDailyVisits(startDate, endDate) {
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    const response = await fetch(`${this.baseURL}/api/v1/analytics/daily-visits?${params.toString()}`);
    return await response.json();
  }

  async getDeviceStats(startDate, endDate) {
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    const response = await fetch(`${this.baseURL}/api/v1/analytics/device-stats?${params.toString()}`);
    return await response.json();
  }
}

export default new AnalyticsService();
```

### مكون لوحة التحكم

```javascript
// components/AnalyticsDashboard.jsx
import React, { useState, useEffect } from 'react';
import AnalyticsService from '../services/AnalyticsService';

const AnalyticsDashboard = () => {
  const [stats, setStats] = useState({});
  const [realTimeStats, setRealTimeStats] = useState({});
  const [loading, setLoading] = useState(true);
  const [dateRange, setDateRange] = useState({
    startDate: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    endDate: new Date().toISOString().split('T')[0]
  });

  useEffect(() => {
    loadDashboardData();
  }, [dateRange]);

  const loadDashboardData = async () => {
    setLoading(true);
    try {
      const [
        generalStats,
        realTime,
        refererStats,
        topDomains,
        popularPages,
        dailyVisits,
        deviceStats
      ] = await Promise.all([
        AnalyticsService.getGeneralStats(dateRange.startDate, dateRange.endDate),
        AnalyticsService.getRealTimeStats(),
        AnalyticsService.getRefererStats(dateRange.startDate, dateRange.endDate),
        AnalyticsService.getTopRefererDomains(dateRange.startDate, dateRange.endDate),
        AnalyticsService.getPopularPages(dateRange.startDate, dateRange.endDate),
        AnalyticsService.getDailyVisits(dateRange.startDate, dateRange.endDate),
        AnalyticsService.getDeviceStats(dateRange.startDate, dateRange.endDate)
      ]);

      setStats({
        general: generalStats.data,
        referer: refererStats.data,
        topDomains: topDomains.data,
        popularPages: popularPages.data,
        dailyVisits: dailyVisits.data,
        devices: deviceStats.data
      });

      setRealTimeStats(realTime.data);
    } catch (error) {
      console.error('خطأ في تحميل بيانات لوحة التحكم:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <div className="loading">جاري تحميل الإحصائيات...</div>;
  }

  return (
    <div className="analytics-dashboard">
      <h1>لوحة تحكم الإحصائيات</h1>
      
      {/* فلتر التاريخ */}
      <div className="date-filter">
        <input
          type="date"
          value={dateRange.startDate}
          onChange={(e) => setDateRange(prev => ({ ...prev, startDate: e.target.value }))}
        />
        <input
          type="date"
          value={dateRange.endDate}
          onChange={(e) => setDateRange(prev => ({ ...prev, endDate: e.target.value }))}
        />
      </div>

      {/* الإحصائيات العامة */}
      <div className="stats-overview">
        <div className="stat-card">
          <h3>إجمالي الزيارات</h3>
          <p>{stats.general?.total_visits || 0}</p>
        </div>
        <div className="stat-card">
          <h3>الزوار الفريدون</h3>
          <p>{stats.general?.unique_visitors || 0}</p>
        </div>
        <div className="stat-card">
          <h3>زيارات آخر 24 ساعة</h3>
          <p>{realTimeStats.total_visits_24h || 0}</p>
        </div>
        <div className="stat-card">
          <h3>زوار فريدون آخر 24 ساعة</h3>
          <p>{realTimeStats.unique_visitors_24h || 0}</p>
        </div>
      </div>

      {/* إحصائيات المصادر */}
      <div className="referer-stats">
        <h2>مصادر الزيارات</h2>
        {Object.entries(stats.referer || {}).map(([type, count]) => (
          <div key={type} className="referer-item">
            <span>{type}</span>
            <span>{count}</span>
          </div>
        ))}
      </div>

      {/* أهم النطاقات */}
      <div className="top-domains">
        <h2>أهم النطاقات المرجعية</h2>
        {stats.topDomains?.map((domain, index) => (
          <div key={index} className="domain-item">
            <span>{domain.referer_domain}</span>
            <span>{domain.visits}</span>
          </div>
        ))}
      </div>

      {/* الصفحات الشعبية */}
      <div className="popular-pages">
        <h2>الصفحات الأكثر شعبية</h2>
        {stats.popularPages?.map((page, index) => (
          <div key={index} className="page-item">
            <span>{page.page_title || page.page_url}</span>
            <span>{page.visits}</span>
          </div>
        ))}
      </div>

      {/* إحصائيات الأجهزة */}
      <div className="device-stats">
        <h2>إحصائيات الأجهزة</h2>
        
        <div className="devices">
          <h3>أنواع الأجهزة</h3>
          {stats.devices?.devices?.map((device, index) => (
            <div key={index} className="device-item">
              <span>{device.device_type}</span>
              <span>{device.visits}</span>
            </div>
          ))}
        </div>

        <div className="browsers">
          <h3>المتصفحات</h3>
          {stats.devices?.browsers?.map((browser, index) => (
            <div key={index} className="browser-item">
              <span>{browser.browser}</span>
              <span>{browser.visits}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default AnalyticsDashboard;
```

---

## 📝 ملاحظات مهمة

### الأمان
- جميع الـ APIs محمية ضد هجمات CSRF
- يتم التحقق من صحة البيانات المدخلة
- عناوين IP يتم تشفيرها في قاعدة البيانات

### الأداء
- استخدم تتبع البكسل للمواقع عالية الحركة
- يمكن تخزين الإحصائيات مؤقتاً لتحسين الأداء
- استخدم pagination للبيانات الكبيرة

### التخصيص
- يمكن إضافة معاملات مخصصة لتتبع أحداث معينة
- يمكن تخصيص فترات الإحصائيات حسب الحاجة
- يمكن إضافة فلاتر إضافية للبيانات

### استكشاف الأخطاء
- تحقق من console المتصفح للأخطاء
- تأكد من صحة الـ URLs المستخدمة
- تحقق من إعدادات CORS في الخادم

---

## 🚀 البدء السريع

```javascript
// 1. إضافة التتبع لتطبيق React
import VisitTracker from './components/VisitTracker';

function App() {
  return (
    <VisitTracker>
      {/* محتوى التطبيق */}
    </VisitTracker>
  );
}

// 2. إضافة لوحة التحكم
import AnalyticsDashboard from './components/AnalyticsDashboard';

function AdminPanel() {
  return (
    <div>
      <AnalyticsDashboard />
    </div>
  );
}
```

هذا النظام يوفر تتبعاً شاملاً وإحصائيات مفصلة لزيارات موقعك مع واجهة سهلة الاستخدام في React.