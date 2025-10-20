# نظام تسجيل الزيارات والإحصائيات - دليل الاستخدام

## نظرة عامة

نظام تسجيل الزيارات والإحصائيات يوفر تتبع شامل لزيارات الموقع مع التركيز على مصادر الإحالة (Referrers) والإحصائيات التفصيلية. النظام يدعم تتبع الزيارات من Facebook، Instagram، Twitter، المصادر الأخرى، والزيارات المباشرة.

## المميزات الرئيسية

- ✅ تسجيل الزيارات في الوقت الفعلي
- ✅ تحليل مصادر الإحالة (Facebook, Instagram, Twitter, Other, Direct)
- ✅ تتبع الصفحات الأكثر زيارة
- ✅ إحصائيات الأجهزة والمتصفحات
- ✅ تتبع الزوار الفريدين
- ✅ إحصائيات يومية وشهرية
- ✅ واجهة برمجة تطبيقات شاملة للإدارة

---

## 1. تسجيل الزيارات في الـ Front-End

### الطريقة الأولى: JavaScript API Call

```javascript
// تسجيل زيارة عادية
async function trackVisit() {
    try {
        const response = await fetch('/api/v1/visits/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                page_url: window.location.href,
                page_title: document.title,
                referer_url: document.referrer || null
            })
        });
        
        const result = await response.json();
        console.log('Visit tracked:', result);
    } catch (error) {
        console.error('Error tracking visit:', error);
    }
}

// استدعاء الدالة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', trackVisit);
```

### الطريقة الثانية: Pixel Tracking (صامت)

```html
<!-- إضافة هذا الكود في <head> أو قبل إغلاق </body> -->
<img src="/api/v1/visits/pixel.gif" 
     style="width:1px;height:1px;position:absolute;left:-9999px;" 
     alt="" />
```

### الطريقة الثالثة: تتبع متقدم مع معلومات إضافية

```javascript
class VisitTracker {
    constructor() {
        this.apiUrl = '/api/v1/visits/track';
        this.sessionId = this.generateSessionId();
    }

    generateSessionId() {
        return 'session_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    }

    async trackPageView(additionalData = {}) {
        const visitData = {
            page_url: window.location.href,
            page_title: document.title,
            referer_url: document.referrer || null,
            session_id: this.sessionId,
            user_agent: navigator.userAgent,
            screen_resolution: `${screen.width}x${screen.height}`,
            viewport_size: `${window.innerWidth}x${window.innerHeight}`,
            language: navigator.language,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            ...additionalData
        };

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(visitData)
            });

            if (response.ok) {
                const result = await response.json();
                console.log('Visit tracked successfully:', result);
                return result;
            }
        } catch (error) {
            console.error('Visit tracking failed:', error);
        }
    }

    // تتبع الأحداث المخصصة
    async trackEvent(eventName, eventData = {}) {
        return this.trackPageView({
            event_name: eventName,
            event_data: eventData
        });
    }
}

// الاستخدام
const tracker = new VisitTracker();

// تتبع زيارة الصفحة
tracker.trackPageView();

// تتبع أحداث مخصصة
tracker.trackEvent('button_click', { button_id: 'cta-button' });
tracker.trackEvent('form_submit', { form_name: 'contact_form' });
```

### تتبع Single Page Applications (SPA)

```javascript
// للتطبيقات التي تستخدم React Router أو Vue Router
class SPATracker extends VisitTracker {
    constructor() {
        super();
        this.setupRouteTracking();
    }

    setupRouteTracking() {
        // للـ React Router
        if (window.history && window.history.pushState) {
            const originalPushState = window.history.pushState;
            window.history.pushState = (...args) => {
                originalPushState.apply(window.history, args);
                setTimeout(() => this.trackPageView(), 100);
            };

            window.addEventListener('popstate', () => {
                setTimeout(() => this.trackPageView(), 100);
            });
        }
    }
}

// الاستخدام في SPA
const spaTracker = new SPATracker();
```

---

## 2. واجهات برمجة التطبيقات للإحصائيات (Admin APIs)

### المصادقة

جميع APIs الخاصة بالإحصائيات تتطلب مصادقة Admin:

```javascript
const headers = {
    'Authorization': 'Bearer YOUR_JWT_TOKEN',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
};
```

### 2.1 الإحصائيات العامة

```javascript
// GET /api/v1/admin/analytics/visits/statistics
async function getVisitStatistics(filters = {}) {
    const params = new URLSearchParams(filters);
    const response = await fetch(`/api/v1/admin/analytics/visits/statistics?${params}`, {
        headers
    });
    return response.json();
}

// مثال على الاستخدام
const stats = await getVisitStatistics({
    start_date: '2024-01-01',
    end_date: '2024-01-31',
    referer_type: 'facebook'
});

console.log(stats);
// النتيجة:
// {
//     total_visits: 15420,
//     unique_visitors: 8930,
//     page_views: 23450,
//     bounce_rate: 45.2,
//     avg_session_duration: 180,
//     top_pages: [...],
//     referer_breakdown: {...}
// }
```

### 2.2 إحصائيات مصادر الإحالة

```javascript
// GET /api/v1/admin/analytics/visits/referer-types
async function getRefererTypeStats(dateRange = {}) {
    const params = new URLSearchParams(dateRange);
    const response = await fetch(`/api/v1/admin/analytics/visits/referer-types?${params}`, {
        headers
    });
    return response.json();
}

// مثال
const refererStats = await getRefererTypeStats({
    start_date: '2024-01-01',
    end_date: '2024-01-31'
});

console.log(refererStats);
// النتيجة:
// {
//     facebook: { visits: 5420, percentage: 35.2 },
//     instagram: { visits: 3210, percentage: 20.8 },
//     twitter: { visits: 1890, percentage: 12.3 },
//     direct: { visits: 3200, percentage: 20.8 },
//     other: { visits: 1700, percentage: 11.0 }
// }
```

### 2.3 أهم المواقع المُحيلة

```javascript
// GET /api/v1/admin/analytics/visits/top-referers
async function getTopReferers(limit = 10) {
    const response = await fetch(`/api/v1/admin/analytics/visits/top-referers?limit=${limit}`, {
        headers
    });
    return response.json();
}

// مثال
const topReferers = await getTopReferers(20);
console.log(topReferers);
// النتيجة:
// [
//     { domain: 'facebook.com', visits: 5420, percentage: 35.2 },
//     { domain: 'instagram.com', visits: 3210, percentage: 20.8 },
//     { domain: 't.co', visits: 1890, percentage: 12.3 }
// ]
```

### 2.4 الإحصائيات اليومية

```javascript
// GET /api/v1/admin/analytics/visits/daily
async function getDailyVisits(dateRange = {}) {
    const params = new URLSearchParams(dateRange);
    const response = await fetch(`/api/v1/admin/analytics/visits/daily?${params}`, {
        headers
    });
    return response.json();
}

// مثال
const dailyStats = await getDailyVisits({
    start_date: '2024-01-01',
    end_date: '2024-01-31'
});

console.log(dailyStats);
// النتيجة:
// [
//     { date: '2024-01-01', visits: 420, unique_visitors: 320 },
//     { date: '2024-01-02', visits: 380, unique_visitors: 290 },
//     ...
// ]
```

### 2.5 الصفحات الأكثر زيارة

```javascript
// GET /api/v1/admin/analytics/visits/popular-pages
async function getPopularPages(limit = 10) {
    const response = await fetch(`/api/v1/admin/analytics/visits/popular-pages?limit=${limit}`, {
        headers
    });
    return response.json();
}

// مثال
const popularPages = await getPopularPages(15);
console.log(popularPages);
// النتيجة:
// [
//     { page_url: '/products', visits: 2340, page_title: 'Products' },
//     { page_url: '/', visits: 1890, page_title: 'Home' },
//     { page_url: '/about', visits: 1420, page_title: 'About Us' }
// ]
```

### 2.6 الإحصائيات في الوقت الفعلي

```javascript
// GET /api/v1/admin/analytics/visits/real-time
async function getRealTimeStats() {
    const response = await fetch('/api/v1/admin/analytics/visits/real-time', {
        headers
    });
    return response.json();
}

// مثال
const realTimeStats = await getRealTimeStats();
console.log(realTimeStats);
// النتيجة:
// {
//     last_24_hours: {
//         visits: 1420,
//         unique_visitors: 890,
//         top_pages: [...],
//         top_referers: [...]
//     }
// }
```

### 2.7 إحصائيات الأجهزة والمتصفحات

```javascript
// GET /api/v1/admin/analytics/visits/devices
async function getDeviceStats(dateRange = {}) {
    const params = new URLSearchParams(dateRange);
    const response = await fetch(`/api/v1/admin/analytics/visits/devices?${params}`, {
        headers
    });
    return response.json();
}

// مثال
const deviceStats = await getDeviceStats({
    start_date: '2024-01-01',
    end_date: '2024-01-31'
});

console.log(deviceStats);
// النتيجة:
// {
//     devices: {
//         mobile: { visits: 8420, percentage: 54.6 },
//         desktop: { visits: 5890, percentage: 38.2 },
//         tablet: { visits: 1110, percentage: 7.2 }
//     },
//     browsers: {
//         chrome: { visits: 9420, percentage: 61.1 },
//         safari: { visits: 3210, percentage: 20.8 },
//         firefox: { visits: 1890, percentage: 12.3 }
//     },
//     operating_systems: {
//         android: { visits: 6420, percentage: 41.6 },
//         ios: { visits: 4210, percentage: 27.3 },
//         windows: { visits: 3890, percentage: 25.2 }
//     }
// }
```

---

## 3. أمثلة تطبيقية للوحة التحكم

### 3.1 Dashboard Widget للإحصائيات

```javascript
class VisitAnalyticsDashboard {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.apiHeaders = {
            'Authorization': 'Bearer ' + localStorage.getItem('admin_token'),
            'Content-Type': 'application/json'
        };
    }

    async loadDashboard() {
        try {
            // تحميل الإحصائيات العامة
            const stats = await this.getVisitStatistics();
            
            // تحميل إحصائيات المصادر
            const refererStats = await this.getRefererTypeStats();
            
            // تحميل الإحصائيات اليومية للرسم البياني
            const dailyStats = await this.getDailyVisits({
                start_date: this.getDateDaysAgo(30),
                end_date: this.getTodayDate()
            });

            // عرض البيانات
            this.renderDashboard(stats, refererStats, dailyStats);
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    }

    renderDashboard(stats, refererStats, dailyStats) {
        this.container.innerHTML = `
            <div class="analytics-dashboard">
                <div class="stats-cards">
                    <div class="stat-card">
                        <h3>إجمالي الزيارات</h3>
                        <p class="stat-number">${stats.total_visits.toLocaleString()}</p>
                    </div>
                    <div class="stat-card">
                        <h3>الزوار الفريدين</h3>
                        <p class="stat-number">${stats.unique_visitors.toLocaleString()}</p>
                    </div>
                    <div class="stat-card">
                        <h3>معدل الارتداد</h3>
                        <p class="stat-number">${stats.bounce_rate}%</p>
                    </div>
                </div>
                
                <div class="charts-section">
                    <div class="chart-container">
                        <h3>مصادر الزيارات</h3>
                        <div id="referer-chart"></div>
                    </div>
                    
                    <div class="chart-container">
                        <h3>الزيارات اليومية</h3>
                        <div id="daily-chart"></div>
                    </div>
                </div>
            </div>
        `;

        // رسم المخططات (يمكن استخدام Chart.js أو أي مكتبة أخرى)
        this.renderRefererChart(refererStats);
        this.renderDailyChart(dailyStats);
    }

    // دوال مساعدة للتواريخ
    getDateDaysAgo(days) {
        const date = new Date();
        date.setDate(date.getDate() - days);
        return date.toISOString().split('T')[0];
    }

    getTodayDate() {
        return new Date().toISOString().split('T')[0];
    }

    // دوال API
    async getVisitStatistics(filters = {}) {
        const params = new URLSearchParams(filters);
        const response = await fetch(`/api/v1/admin/analytics/visits/statistics?${params}`, {
            headers: this.apiHeaders
        });
        return response.json();
    }

    async getRefererTypeStats(dateRange = {}) {
        const params = new URLSearchParams(dateRange);
        const response = await fetch(`/api/v1/admin/analytics/visits/referer-types?${params}`, {
            headers: this.apiHeaders
        });
        return response.json();
    }

    async getDailyVisits(dateRange = {}) {
        const params = new URLSearchParams(dateRange);
        const response = await fetch(`/api/v1/admin/analytics/visits/daily?${params}`, {
            headers: this.apiHeaders
        });
        return response.json();
    }
}

// الاستخدام
const dashboard = new VisitAnalyticsDashboard('analytics-container');
dashboard.loadDashboard();
```

### 3.2 تقرير تفصيلي

```javascript
class DetailedAnalyticsReport {
    constructor() {
        this.apiHeaders = {
            'Authorization': 'Bearer ' + localStorage.getItem('admin_token'),
            'Content-Type': 'application/json'
        };
    }

    async generateReport(startDate, endDate) {
        const reportData = await Promise.all([
            this.getVisitStatistics({ start_date: startDate, end_date: endDate }),
            this.getRefererTypeStats({ start_date: startDate, end_date: endDate }),
            this.getTopReferers(50),
            this.getPopularPages(20),
            this.getDeviceStats({ start_date: startDate, end_date: endDate })
        ]);

        return {
            period: { start: startDate, end: endDate },
            overview: reportData[0],
            referer_types: reportData[1],
            top_referers: reportData[2],
            popular_pages: reportData[3],
            device_stats: reportData[4]
        };
    }

    async exportToCSV(reportData) {
        // تصدير البيانات إلى CSV
        const csvContent = this.convertToCSV(reportData);
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `visit-analytics-${reportData.period.start}-${reportData.period.end}.csv`;
        a.click();
        
        window.URL.revokeObjectURL(url);
    }

    convertToCSV(data) {
        // تحويل البيانات إلى تنسيق CSV
        let csv = 'التقرير,القيمة\n';
        csv += `فترة التقرير,${data.period.start} إلى ${data.period.end}\n`;
        csv += `إجمالي الزيارات,${data.overview.total_visits}\n`;
        csv += `الزوار الفريدين,${data.overview.unique_visitors}\n`;
        csv += `معدل الارتداد,${data.overview.bounce_rate}%\n\n`;
        
        csv += 'مصادر الإحالة,الزيارات,النسبة المئوية\n';
        Object.entries(data.referer_types).forEach(([type, stats]) => {
            csv += `${type},${stats.visits},${stats.percentage}%\n`;
        });
        
        return csv;
    }
}
```

---

## 4. معالجة الأخطاء والتحسينات

### معالجة الأخطاء

```javascript
class RobustVisitTracker {
    constructor() {
        this.apiUrl = '/api/v1/visits/track';
        this.retryAttempts = 3;
        this.retryDelay = 1000;
    }

    async trackVisitWithRetry(visitData, attempt = 1) {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(visitData)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            console.warn(`Visit tracking attempt ${attempt} failed:`, error);
            
            if (attempt < this.retryAttempts) {
                await this.delay(this.retryDelay * attempt);
                return this.trackVisitWithRetry(visitData, attempt + 1);
            }
            
            // إذا فشلت جميع المحاولات، احفظ البيانات محلياً
            this.saveToLocalStorage(visitData);
            throw error;
        }
    }

    saveToLocalStorage(visitData) {
        try {
            const failedVisits = JSON.parse(localStorage.getItem('failed_visits') || '[]');
            failedVisits.push({
                ...visitData,
                failed_at: new Date().toISOString()
            });
            localStorage.setItem('failed_visits', JSON.stringify(failedVisits));
        } catch (e) {
            console.error('Failed to save visit data to localStorage:', e);
        }
    }

    async retryFailedVisits() {
        try {
            const failedVisits = JSON.parse(localStorage.getItem('failed_visits') || '[]');
            
            for (const visit of failedVisits) {
                try {
                    await this.trackVisitWithRetry(visit);
                } catch (e) {
                    console.warn('Failed to retry visit:', e);
                }
            }
            
            // مسح الزيارات المحفوظة بعد المحاولة
            localStorage.removeItem('failed_visits');
        } catch (e) {
            console.error('Error retrying failed visits:', e);
        }
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}
```

### تحسين الأداء

```javascript
class OptimizedVisitTracker {
    constructor() {
        this.apiUrl = '/api/v1/visits/track';
        this.batchSize = 10;
        this.batchTimeout = 5000;
        this.visitQueue = [];
        this.batchTimer = null;
    }

    trackVisit(visitData) {
        this.visitQueue.push({
            ...visitData,
            timestamp: Date.now()
        });

        if (this.visitQueue.length >= this.batchSize) {
            this.sendBatch();
        } else if (!this.batchTimer) {
            this.batchTimer = setTimeout(() => this.sendBatch(), this.batchTimeout);
        }
    }

    async sendBatch() {
        if (this.visitQueue.length === 0) return;

        const batch = [...this.visitQueue];
        this.visitQueue = [];
        
        if (this.batchTimer) {
            clearTimeout(this.batchTimer);
            this.batchTimer = null;
        }

        try {
            await fetch(this.apiUrl + '/batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ visits: batch })
            });
        } catch (error) {
            console.error('Batch tracking failed:', error);
            // إعادة إضافة البيانات للطابور للمحاولة مرة أخرى
            this.visitQueue.unshift(...batch);
        }
    }

    // إرسال أي بيانات متبقية عند إغلاق الصفحة
    setupBeforeUnload() {
        window.addEventListener('beforeunload', () => {
            if (this.visitQueue.length > 0) {
                // استخدام sendBeacon للإرسال الموثوق
                navigator.sendBeacon(
                    this.apiUrl + '/batch',
                    JSON.stringify({ visits: this.visitQueue })
                );
            }
        });
    }
}
```

---

## 5. أمثلة متقدمة

### تتبع التفاعل مع الصفحة

```javascript
class InteractionTracker extends VisitTracker {
    constructor() {
        super();
        this.setupInteractionTracking();
    }

    setupInteractionTracking() {
        // تتبع وقت البقاء في الصفحة
        this.startTime = Date.now();
        
        // تتبع التمرير
        let maxScroll = 0;
        window.addEventListener('scroll', () => {
            const scrollPercent = Math.round(
                (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100
            );
            maxScroll = Math.max(maxScroll, scrollPercent);
        });

        // تتبع النقرات
        document.addEventListener('click', (e) => {
            this.trackEvent('click', {
                element: e.target.tagName,
                class: e.target.className,
                id: e.target.id,
                text: e.target.textContent?.substring(0, 100)
            });
        });

        // إرسال بيانات التفاعل عند مغادرة الصفحة
        window.addEventListener('beforeunload', () => {
            const timeSpent = Date.now() - this.startTime;
            this.trackEvent('page_exit', {
                time_spent: timeSpent,
                max_scroll_percent: maxScroll
            });
        });
    }
}
```

### تتبع الحملات التسويقية

```javascript
class CampaignTracker extends VisitTracker {
    constructor() {
        super();
        this.extractCampaignData();
    }

    extractCampaignData() {
        const urlParams = new URLSearchParams(window.location.search);
        
        this.campaignData = {
            utm_source: urlParams.get('utm_source'),
            utm_medium: urlParams.get('utm_medium'),
            utm_campaign: urlParams.get('utm_campaign'),
            utm_term: urlParams.get('utm_term'),
            utm_content: urlParams.get('utm_content'),
            gclid: urlParams.get('gclid'), // Google Ads
            fbclid: urlParams.get('fbclid'), // Facebook Ads
        };

        // إزالة المعاملات من URL للحفاظ على نظافة التاريخ
        if (Object.values(this.campaignData).some(val => val !== null)) {
            const cleanUrl = window.location.pathname + window.location.hash;
            window.history.replaceState({}, document.title, cleanUrl);
        }
    }

    async trackPageView(additionalData = {}) {
        return super.trackPageView({
            ...additionalData,
            ...this.campaignData
        });
    }
}
```

---

## 6. الأمان والخصوصية

### حماية البيانات الحساسة

```javascript
class PrivacyAwareTracker extends VisitTracker {
    constructor() {
        super();
        this.respectDoNotTrack();
    }

    respectDoNotTrack() {
        // احترام إعداد "عدم التتبع" في المتصفح
        if (navigator.doNotTrack === '1' || 
            window.doNotTrack === '1' || 
            navigator.msDoNotTrack === '1') {
            console.log('Do Not Track is enabled, skipping tracking');
            return false;
        }
        return true;
    }

    sanitizeData(data) {
        // إزالة أو تشفير البيانات الحساسة
        const sanitized = { ...data };
        
        // إزالة معلومات شخصية من URL
        if (sanitized.page_url) {
            sanitized.page_url = this.removePersonalInfo(sanitized.page_url);
        }
        
        if (sanitized.referer_url) {
            sanitized.referer_url = this.removePersonalInfo(sanitized.referer_url);
        }

        return sanitized;
    }

    removePersonalInfo(url) {
        try {
            const urlObj = new URL(url);
            // إزالة معاملات قد تحتوي على معلومات شخصية
            const sensitiveParams = ['email', 'phone', 'name', 'user_id', 'token'];
            
            sensitiveParams.forEach(param => {
                urlObj.searchParams.delete(param);
            });
            
            return urlObj.toString();
        } catch (e) {
            return url;
        }
    }

    async trackPageView(additionalData = {}) {
        if (!this.respectDoNotTrack()) {
            return;
        }

        const sanitizedData = this.sanitizeData(additionalData);
        return super.trackPageView(sanitizedData);
    }
}
```

---

## 7. استكشاف الأخطاء وإصلاحها

### مشاكل شائعة وحلولها

#### 1. عدم تسجيل الزيارات

```javascript
// فحص حالة API
async function debugVisitTracking() {
    try {
        const response = await fetch('/api/v1/visits/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                page_url: window.location.href,
                page_title: document.title,
                debug: true
            })
        });

        console.log('Response status:', response.status);
        console.log('Response headers:', [...response.headers.entries()]);
        
        const result = await response.text();
        console.log('Response body:', result);
        
        if (response.ok) {
            console.log('✅ Visit tracking is working');
        } else {
            console.error('❌ Visit tracking failed');
        }
    } catch (error) {
        console.error('❌ Network error:', error);
    }
}

// تشغيل الفحص
debugVisitTracking();
```

#### 2. مشاكل CORS

```javascript
// فحص إعدادات CORS
async function checkCORS() {
    try {
        const response = await fetch('/api/v1/visits/track', {
            method: 'OPTIONS'
        });
        
        console.log('CORS headers:');
        console.log('Access-Control-Allow-Origin:', response.headers.get('Access-Control-Allow-Origin'));
        console.log('Access-Control-Allow-Methods:', response.headers.get('Access-Control-Allow-Methods'));
        console.log('Access-Control-Allow-Headers:', response.headers.get('Access-Control-Allow-Headers'));
    } catch (error) {
        console.error('CORS check failed:', error);
    }
}
```

#### 3. مراقبة الأداء

```javascript
class PerformanceMonitor {
    static measureTrackingPerformance() {
        const startTime = performance.now();
        
        return {
            end: () => {
                const endTime = performance.now();
                const duration = endTime - startTime;
                console.log(`Visit tracking took ${duration.toFixed(2)}ms`);
                
                // تسجيل الأداء إذا كان بطيئاً
                if (duration > 1000) {
                    console.warn('⚠️ Visit tracking is slow');
                }
            }
        };
    }
}

// الاستخدام
const monitor = PerformanceMonitor.measureTrackingPerformance();
await trackVisit();
monitor.end();
```

---

## خلاصة

نظام تسجيل الزيارات والإحصائيات يوفر حلاً شاملاً لتتبع زيارات الموقع وتحليل مصادر الإحالة. النظام يدعم:

- ✅ تسجيل الزيارات بطرق متعددة (JavaScript, Pixel, Advanced)
- ✅ تحليل مصادر الإحالة (Facebook, Instagram, Twitter, etc.)
- ✅ واجهات برمجة تطبيقات شاملة للإحصائيات
- ✅ لوحة تحكم متقدمة للإدارة
- ✅ معالجة الأخطاء والتحسينات
- ✅ الأمان والخصوصية

للحصول على أفضل النتائج، يُنصح بـ:
- استخدام الطريقة المتقدمة للتتبع في التطبيقات المعقدة
- تنفيذ معالجة الأخطاء والإعادة المحاولة
- احترام إعدادات الخصوصية للمستخدمين
- مراقبة الأداء بانتظام
- اختبار النظام في بيئات مختلفة

---

**ملاحظة**: تأكد من تحديث رمز JWT في جميع طلبات Admin APIs، وتأكد من أن الخادم يدعم CORS للنطاقات المطلوبة.