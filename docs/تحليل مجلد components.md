# تحليل مجلد components

تحليل ثابت لملف components.zip (89 ملف Blade) — 8 أكتوبر 2026

## نظرة عامة

المجلد يحتوي 89 ملف Blade (حوالي 345 KB) لتطبيق Laravel يبدو أنه نظام ERP للمترولوجيا باسم ERP-GMTM. يشمل شهادات المعايرة وأجهزة القياس والـ chromatograph والـ prover. التقنيات المستخدمة:

- Alpine.js وTailwind، ويبدو أنه v4 من استخدام `shadow-xs`.
- Chart.js.
- حزمة `LaravelLocalization`.
- دعم RTL واضح.

محتوى المجلد:

- مكوّنات أساسية: أزرار وalert وbadge وmodal وجداول وdropdown وحقول.
- تنقل: خمسة ملفات tabs ونظام `system-tabs` وقوائم `nav`.
- نوافذ CRUD وفلاتر.
- 25 أيقونة SVG مرسومة يدويًا.
- ملفان كبيران: `ai-extractor` (1069 سطرًا) و`curve` (589 سطرًا).

## نقاط القوة

- الاعتماد على `ps-` و`pe-` و`start-` و`end-` و`rtl:rotate-180` يجعل دعم RTL جيدًا.
- تمرير البيانات إلى JavaScript يتم غالبًا عبر `Js::from` و`@js`، وهذا آمن.
- لا يوجد `x-html`، وعرض النصوص يتم بـ `x-text`، فلا ثغرات XSS ظاهرة.
- الـ variants منظمة عبر `match` مع `$attributes->merge`.
- الوضع الداكن مطبق في كل مكان.
- `tool-icon` فيه fallback للأيقونات المفقودة.

## المشاكل حسب الأولوية

### أولوية عالية

**1. `ai-extractor`: الاستعلام الدوري لا ينتهي أبدًا.** فحص المهلة `pollCount > 30` موجود داخل `.catch` فقط، أي لا يعمل إلا عند فشل الشبكة. إذا بقيت الحالة `pending` أو `processing`، يستمر الاستعلام كل ثانيتين بلا نهاية. الإصلاح:

```js
tick() {
  if (++this.polls > 30) return this.fail(timeout);
  // ... fetch ثم setTimeout(tick, 2000) بدل setInterval
}
```

**2. `ai-extractor`: تطبيق نتائج الذكاء الاصطناعي بلا حماية كافية.** هذا خطير في سياق المعايرة.

- زر Apply غير معطّل حتى عند ظهور تحذير "Device Mismatch" بسبب اختلاف الرقم التسلسلي. لا يوجد تأكيد إضافي ظاهر في الماركاب.
- إعادة التطبيق تكرّر النقاط والملاحظات، لأن `points.concat(...)` و`remarks +=` لا يكشفان التكرار.
- مطابقة الجهاز تعتمد على `includes` لأجزاء من النص (`devCode.includes(eqCode)` ونموذج الجهاز). قد يقترح هذا جهازًا خاطئًا، ثم يربط زر "switch equipment" الشهادة به.
- رقم مفتاح الـ AI ("Key #N") يظهر لكل المستخدمين، وهو تفصيل داخلي.

**3. `system-tabs` بلا فحص صلاحيات.** بقية ملفات tabs تستخدم `@can`، أما هذا الملف فيعرض روابط Users وRoles وBackups وCache وSettings للجميع. يجب التأكد من حماية الـ routes نفسها بـ middleware.

**4. `crud-modal/delete`:**

- التعبير `isJsVar` (regex) يُطبَّق أيضًا على `alpineAction`. تعبير مثل `'/users/' + id` يُحوَّل خطأً إلى نص ثابت عبر `json_encode`.
- `aria-labelledby` يشير إلى `delete-modal-heading-{random}`، بينما الـ `id` الفعلي للعنوان هو `delete-modal-heading`. الربط مكسور.

### أولوية متوسطة

**5. الترجمة والـ escaping:**

- في `ai-extractor` عشر ترجمات مكتوبة بصيغة `'{{ __('...') }}'` داخل `<script>`. الـ `{{ }}` يعمل HTML-escape، ومحتوى `<script>` لا يُفك ترميزه، فأي نص فيه `'` أو `&` يظهر خطأً (مثل `&#039;`). المطلوب `@js(__('...'))`.
- المشكلة نفسها في `sort.blade` و`theme-switcher`، لكن داخل attribute، فيُكسر نص JS عند `'`.
- نصوص إنجليزية ثابتة: `Key #` و`[S/N:` و`aria-label="System Tables Sidebar"`.
- علامة `؟` العربية مكتوبة حرفيًا بعد "Are you sure you want to delete" في نافذتي الحذف، فتظهر في الواجهة الإنجليزية.

**6. `global-filter`:**

- سكربت autoSubmit يرسل النموذج عند تغيير أي `SELECT`. هذا يتجاهل `autoSubmit=false` في `sort`، ويتكرر مع `onchange` داخل `select`.
- `hasActiveFilters` يحسب كل معاملات الـ query، مثل `tab` و`spec_id`. يظهر زر Reset ويحذفها كلها.
- `addslashes` لا يهرّب السطر الجديد، فالأفضل `@js`.

**7. `nav-dropdown`:** يفتح عند `mouseenter` ويبدّل عند `click`، فالنقر أثناء التحويم يغلق القائمة. ولا يدعم لوحة المفاتيح.

**8. `curve`:**

- قيم خاصة بكل نسخة (`minBound` و`p1..p5`) مكتوبة داخل سكربت `@once`. أي نسخة ثانية في الصفحة ترث قيم الأولى. الأفضل تمريرها كوسائط مثل بيانات الرسم.
- `x-init="init()"` مع `init()` التي تعمل تلقائيًا في Alpine، فيحدث تنفيذ مزدوج.
- ألوان الرسم تُحسب مرة واحدة ولا تتحدث عند تبديل الثيم، فلا يوجد مستمع لـ `theme-changed`.
- معرّفات الـ canvas ثابتة، واستعلامات قاعدة البيانات تتم داخل الـ view.

**9. تفاصيل Tailwind وRTL:**

- `responsive-nav-link` يستخدم `border-l-4` بدل `border-s-4`.
- `origin-top-right` في `language-switcher` و`theme-switcher`.
- `ring-opacity-5` في `dropdown` أُزيل في Tailwind v4، فإن كان المشروع على v4 ستظهر حلقة سوداء كاملة.
- `date.blade` يفشل إن مُرّرت له string بدل كائن تاريخ، وصيغته `d/m/Y` ثابتة.

**10. إمكانية الوصول:**

- النوافذ (form وdelete وai-extractor) بلا focus trap، وبعضها بلا `Esc` أو `aria-labelledby`.
- القوائم المنسدلة بلا `aria-expanded`.
- روابط التبويبات بلا `aria-current`.
- `password-input` يولّد `aria-controls=""` إذا لم يُمرَّر `id`.

### صيانة وجودة

- **تكرار:**
  - منطق الأيقونات في `table/action` مكرر مرتين، لـ `<a>` ولـ `<button>`.
  - ملفات tabs الخمسة متطابقة عمليًا. يكفي مكوّن واحد مع مصفوفة تبويبات في `config`.
  - نافذتا الحذف (`crud-modal.delete` و`delete-modal`) متطابقتان تقريبًا.
  - خوارزمية المطابقة في `ai-extractor` مكررة في `buildRoutingPlan` و`autoMatchedSpecs`.
- **نظافة الملفات:**
  - ستة ملفات فيها BOM: `form` و`search` و`info-button` و`master-data-tabs` و`system-tabs` و`theme-switcher`.
  - سبعة ملفات بنهايات أسطر CRLF.
  - ملفان بلا سطر أخير: `date` و`ai-extractor`.
- **تناسق التصميم:**
  - بقايا Breeze بلون `indigo` مقابل `brand` في بقية الملفات.
  - Font Awesome في `curve` و`ai-extractor` مقابل SVG مضمّن في الباقي.
  - `success` و`warning` و`info` تكتب Tailwind مباشرة بينما الأخرى تستخدم `btn-*`.
- **`ai-extractor` ملف ضخم:** يجمع الماركاب وأكثر من 450 سطر JS. يعتمد ضمنيًا على متغيرات المكوّن الأب (`selectedEquipmentId` و`points` و`calDate`) وعلى `document.querySelector('input[name=...]')`. يستخدم `@push` بلا `@once`.
- **اسم `itemName` بدلالتين:** يعني نصًا في `table.action-delete` وتعبير JS في `crud-modal.delete`.

## ترتيب الإصلاحات المقترح

1. إصلاح مهلة الـ polling، وتعطيل Apply أو طلب تأكيد عند الاختلاف، ومنع التكرار.
2. إضافة `@can` في `system-tabs` والتحقق من حماية الـ routes.
3. استبدال `'{{ __() }}'` بـ `@js()` في كل الملفات.
4. توحيد نافذتي الحذف، وإصلاح `aria-labelledby` والـ regex.
5. دمج ملفات tabs في مكوّن واحد، وإعادة كتابة `table/action` بلا تكرار.
6. إزالة BOM وتوحيد نهايات الأسطر.

## حدود التحليل

أجريت قراءة ثابتة فقط. لم يُشغَّل التطبيق، ولم تُراجَع الـ controllers ولا ملفات CSS مثل `btn-*` و`input-base`. أيقونات SVG الكبيرة قُرئت بنيتها فقط وليس رسوماتها سطرًا بسطر. وبعض الاستنتاجات، مثل Tailwind v4، مبنية على القرائن وليست مؤكدة.
