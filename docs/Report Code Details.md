# Report Code Details

> **وثيقة مرجعية معمارية وتفصيلية شاملة لكود وقواعد عمل وتدفق بيانات وحدة التقارير (Reports Module)**  
> **النظام:** GMTM Metrology Application (app.gmtm-dz.com)  
> **الإصدار الأساسي للبيئة:** Laravel 12.66 / PHP 8.5 / MySQL 8.x  
> **المعايير المترولوجية المطبقة:** OAM (Office National de Métrologie Légale - الجزائر) / OIML R 140 / OIML R 117 / ASTM D 1945 / ISO 6974-2 / ISO 6976 / IEC 60751  
> **حالة التحليل:** Grounded Code-Level Analysis (مبني بنسبة 100% على الكود الفعلي دون أي افتراضات غير موجودة).

---

## تصنيف المعلومات المعتمد في الوثيقة
* **[A. معلومة مؤكدة]:** معلومة مستخرجة ومثبتة حرفيًا من ملفات المصدر (PHP, Blade, JS, Migrations, MySQL Schema).
* **[B. استنتاج معماري]:** سلوك أو تصميم تم استنتاجه بدقة من تتبع وتكامل أكثر من ملف ومسار داخل الكود.
* **[C. غير موجود في الكود (Not found in the analyzed code)]:** أي نمط، ملف، مسار، أو جدول غير موجود في الكود الفعلي ويتم إثبات غيابه لمنع الالتباس.

---

## 1. Executive Overview

وحدة التقارير (`Reports Module`) هي النواة المركزية لإصدار واعتماد وثائق المعايرة والتحقق المترولوجي الميداني (Metrological Calibration & Verification Reports) لأجهزة القياس الصناعية المركبة على مواقع منشآت النفط والغاز التابعة لزبائن الشركة (مثل سوناطراك وفروعها).

تتولى الوحدة إدارة دورة حياة تقرير المعايرة بالكامل:
1. **ربط التقرير بمهمة ميدانية (`Mission`)** موقعية، وتحديد أجهزة الموقع المستهدفة بالمعايرة.
2. **الحقن التلقائي (`Auto-Initialization`)** لنقاط المعايرة (Calibration Points) فور إنشاء التقرير وفق النماذج الرياضية المعتمدة لكل نوع جهاز.
3. **تخصيص وتوزيع أجهزة المعايرة المرجعية (`Reference Calibrators`)** مركزيًا على مستوى التقرير أو تفصيليًا على مستوى كل جهاز وقناة قياس.
4. **تسجيل القياسات الميدانية** إما يدويًا عبر واجهات إدخال مخصصة (`Saisie UI`) أو بدون اتصال بالإنترنت عبر تصدير واستيراد ملفات Excel تفاعلية متعددة الأوراق (`Multi-Sheet Offline Field Excel`).
5. **التقييم الآلي المباشر للخطأ المترولوجي والامتثال** لحدود التسامح القانونية القصوى المسموح بها (`EMT - Erreur Maximale Tolérée`) وإصدار أحكام المطابقة (`Conforme` / `Non conforme`).
6. **التوليد الطباعي عالي الدقة لوثائق PDF الرسمية** (بنسختين: الرسمية بحدود EMT وقرار المطابقة، والأخرى الخام للمقادير المجردة `NotEMT`) بتنسيق أفقي (A4 Landscape) يطابق شروط الاعتماد القانوني.

---

## 2. Module Purpose

* **الغرض الوظيفي:** ميكنة التحقق من سلاسل القياس المترولوجية (Measurement Loops) المكونة من:
  * **مرسلات الضغط والحرارة والضغط التفاضلي (`Transmitters - PT / TT / PDT`)**.
  * **مسابير المقاومة الحرارية البلاتينية (`Temperature Probes - Sondes PT100`)**.
  * **حاسبات التدفق وقنوات التحويل التماثلي/الرقمي (`Flow Computers - Calculateurs de Débit / ADC`)**.
  * **التكامل الخلفي مع أجهزة الكروماتوغرافيا الغازية (`Gas Chromatographs - CPG`)**.
* **نطاق العزل المعماري:** استبعاد الأجهزة ذات الطبيعة الخاصة (المقاييس الحجمية العيارية `StandardGauge / TestMeasure`، وأنابيب البروفر `Provers`، والكروماتوغراف في مساره المستقل) من التقارير الحلقية القياسية لتوجيهها إلى وحدات مستقلة متخصصة.
* **الهدف التقني:** الحفاظ على سلامة الحسابات المترولوجية وفق معادلات التحويل الفيزيائي (IEC 60751 لدرجات الحرارة، محاكاة التيار 4-20 mA وحسابات التحويل التماثلي 1-5 V للـ ADC)، وضمان التتبع الكامل (Traceability) للمعدات المرجعية وشهادات المعايرة الصادرة عن مخابر ISO/IEC 17025.

---

## 3. Folder Structure

تم حصر وتتبع شجرة الملفات التي تشكل وحدة التقارير وترتبط بها وظيفيًا:

```text
app.gmtm-dz.com/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Admin/
│   │   │       ├── ReportController.php                 # [A] المتحكم الرئيسي لإدارة التقارير والعمليات العامة
│   │   │       ├── ReportPdfController.php              # [A] متحكم توليد وثائق PDF الرسمية (DomPDF)
│   │   │       └── CalibrationInstrumentsController.php # [A] متحكم إدخال بيانات المعايرة والمنحنيات
│   │   └── Requests/
│   │       └── Calibration/
│   │           ├── StoreTransmitterRequest.php          # [A] التحقق من مدخلات قياس المرسلات
│   │           ├── StoreProbeRequest.php                # [A] التحقق من مدخلات قياس المسابير
│   │           ├── StoreFlowComputerRequest.php         # [A] التحقق من مدخلات قياس حاسبات التدفق
│   │           └── StoreChromatographVerificationRequest.php # [A] التحقق من مدخلات الكروماتوغراف
│   ├── Models/
│   │   ├── Report.php                                   # [A] نموذج التقرير وعلاقاته وأحداث دورة الحياة
│   │   ├── TransmitterVerification.php                  # [A] نموذج جلسة فحص المرسل
│   │   ├── TransmitterVerificationPoint.php             # [A] نموذج نقطة قياس المرسل
│   │   ├── ProbeVerification.php                        # [A] نموذج جلسة فحص المسبار
│   │   ├── ProbeVerificationPoint.php                   # [A] نموذج نقطة قياس المسبار
│   │   ├── FlowComputerVerification.php                 # [A] نموذج جلسة فحص حاسبة التدفق
│   │   ├── FlowComputerVerificationPoint.php            # [A] نموذج نقطة قياس حاسبة التدفق
│   │   ├── ChromatographVerification.php                # [A] نموذج جلسة فحص الكروماتوغراف
│   │   ├── ChromatographCompositionPoint.php            # [A] نموذج نسب مكونات الغاز
│   │   ├── ChromatographPhysicalProperty.php            # [A] نموذج الخواص الفيزيائية والطاقوية
│   │   ├── Mission.php                                  # [A] نموذج المهمة الميدانية المرتبطة
│   │   ├── Instrument.php                               # [A] نموذج جهاز القياس الميداني
│   │   └── Equipment.php                                # [A] نموذج عتاد المعايرة المرجعي (Calibrators)
│   ├── Services/
│   │   ├── CalibratorResolutionService.php              # [A] خدمة التوزيع والربط الذكي لأجهزة المعايرة
│   │   ├── OamMetrologyService.php                      # [A] محرك العمليات الرياضية والمترولوجية وحسابات EMT
│   │   ├── ChromatographMetrologyService.php            # [A] خدمة التقييم المترولوجي للكروماتوغراف
│   │   └── SvgChartService.php                          # [A] خدمة الرسم المتجهي لمنحنيات الخطأ (SVG)
│   ├── Exports/
│   │   ├── ReportExport.php                             # [A] منسق تصدير التقرير متعدد الأوراق
│   │   ├── ReportSummarySheetExport.php                 # [A] ورقة ملخص التقرير والإحصائيات
│   │   ├── DeviceSheetExport.php                        # [A] ورقة فحص ومعايرة الجهاز الميداني
│   │   └── ChromatographSheetExport.php                 # [A] ورقة فحص جهاز الكروماتوغراف
│   └── Imports/
│       └── ReportDataImport.php                         # [A] محرك استيراد وتفسير ومعايرة بيانات Excel الميدانية
├── resources/
│   └── views/
│       ├── admin/
│       │   ├── reports/
│       │   │   ├── index.blade.php                      # [A] جدول استعراض وفلترة التقارير
│       │   │   ├── create.blade.php                     # [A] صفحة إنشاء تقرير جديد وتوزيع المعايير
│       │   │   ├── edit.blade.php                       # [A] صفحة تعديل بيانات وأجهزة التقرير
│       │   │   ├── show.blade.php                       # [A] لوحة تحكم التقرير والأجهزة والإحصائيات
│       │   │   ├── curve.blade.php                      # [A] صفحة عرض منحنى الخطأ المترولوجي
│       │   │   └── partials/
│       │   │       └── calibrators_card.blade.php       # [A] المكون المشترك لاختيار المعايير الافتراضية
│       │   └── calibration_instruments/
│       │       ├── transmitter.blade.php                # [A] واجهة إدخال قياسات المرسلات
│       │       ├── probe.blade.php                      # [A] واجهة إدخال قياسات المسابير الحرارية
│       │       ├── flow_computer.blade.php              # [A] واجهة إدخال قياسات حاسبات التدفق
│       │       ├── chromatograph.blade.php              # [A] غلاف إعادة توجيه لشاشة الكروماتوغراف
│       │       └── curve.blade.php                      # [A] قالب المنحنى المشترك
│       └── pdf/
│           ├── official_report.blade.php                # [A] قالب التقرير الرسمي الشامل (بحدود EMT)
│           └── official_report_NotEMT.blade.php         # [A] قالب التقرير الخام (بدون EMT)
├── routes/
│   └── web.php                                          # [A] تسجيل مسارات التقارير وحمايتها بالصلاحيات
└── database/
    └── migrations/
        ├── [Legacy Schema Tables in MySQL]              # [A] جداول reports, report_instruments, verifications
        └── 2026_09_18_130000_make_report_mission_id_nullable_in_chromatograph_verifications.php # [A]
```

### بطاقة تعريفية بملفات الوحدة الأساسية

| المسار الكامل للملف | نوع الملف | المسؤولية المعمارية الأساسية | التبعيات المستدعاة (Calls) | الجهات المستدعية (Called By) |
| :--- | :--- | :--- | :--- | :--- |
| `app/Http/Controllers/Admin/ReportController.php` | Controller | إدارة الـ CRUD، فلترة التقارير، إغلاق التقرير، تصدير/استيراد Excel، إرجاع بيانات AJAX | `Report`, `Mission`, `Instrument`, `CalibratorResolutionService`, `OamMetrologyService`, `ReportExport`, `ReportDataImport` | مسارات `routes/web.php`، واجهات Blade |
| `app/Http/Controllers/Admin/ReportPdfController.php` | Controller | تهيئة بيانات التقرير وتوليد ملفات PDF بنظام DomPDF | `Report`, `Barryvdh\DomPDF\Facade\Pdf` | مسارات `routes/web.php` (`reports.pdf`, `reports.pdf_not_emt`) |
| `app/Http/Controllers/Admin/CalibrationInstrumentsController.php` | Controller | توجيه وإدخال بيانات المعايرة، استدعاء التقييم المترولوجي، وتوليد منحنيات SVG | `Report`, `Instrument`, `TransmitterVerification`, `ProbeVerification`, `FlowComputerVerification`, `OamMetrologyService`, `SvgChartService` | مسارات `routes/web.php` (`reports.saisie.*`, `reports.curve`) |
| `app/Models/Report.php` | Eloquent Model | تمثيل جدول التقارير، أحداث الحقن الآلي والحذف التسلسلي، استرجاع المعايير | `Mission`, `TransmitterVerification`, `ProbeVerification`, `FlowComputerVerification`, `OamMetrologyService` | Controllers, Observers, Jobs, Blade |
| `app/Services/OamMetrologyService.php` | Domain Service | حسابات التحويل الكهروحراري (PT100) والضغط والتيار، واحتساب حدود EMT والمطابقة | PHP Native Math, IEC 60751 Standards | Controllers, Models, Excel Importer |
| `app/Services/CalibratorResolutionService.php` | Domain Service | عزل وفلترة وتوزيع أجهزة المعايرة المعتمدة بشهادات وفق العائلة المترولوجية | `Equipment`, `Mission`, `Report` | `ReportController`, `CalibrationInstrumentsController` |
| `app/Services/SvgChartService.php` | Presentation Service | بناء كود SVG الخالص هندسيًا لعرض منحنيات الخطأ المترولوجي وحدود EMT | `Collection` | `CalibrationInstrumentsController` |
| `app/Exports/ReportExport.php` | Excel Export | تصدير مصنف إكسيل تفاعلي متعدد الأوراق لجمع القياسات ميدانيًا | `ReportSummarySheetExport`, `DeviceSheetExport`, `ChromatographSheetExport` | `ReportController::exportExcel` |
| `app/Imports/ReportDataImport.php` | Excel Import | معالجة ملف إكسيل الميداني وتحديث نقاط القياس في قاعدة البيانات وتقييمها | `PhpSpreadsheet`, `OamMetrologyService`, Models | `ReportController::importExcel` |

---

## 4. Architecture Overview

تتبع وحدة التقارير نمطًا معماريًا هجينًا ضمن إطار Laravel:
1. **Routing & Presentation Layer:**
   * واجهات كلاسيكية مبنية بمحرك **Blade** مدعومة بمكتبة **Vanilla JavaScript** وبعض مكونات **Alpine.js** للتعامل التفاعلي (AJAX Fetch لحسابات وتحديثات المهمة والمعايير).
   * مسارات محمية بحزمة الصلاحيات `spatie/laravel-permission` ومسارات مخصصة لتنزيل الملفات عبر وسيط `no-download-history`.
2. **Controller Layer (Fat Orchestration):**
   * يقوم `ReportController` و`CalibrationInstrumentsController` بأدوار التنسيق، وتطبيق قواعد التحقق (Validation)، وفتح المعاملات المالية وقواعد البيانات (`DB::transaction`).
3. **Domain Service Layer:**
   * عزل المعادلات الفيزيائية المترولوجية الصارمة داخل `OamMetrologyService` و`ChromatographMetrologyService` و`CalibratorResolutionService`.
4. **Data Access & Persistence Layer:**
   * يعتمد الكود على **Eloquent ORM** إلى جانب استعلامات مباشرة عالية الأداء عبر واجهة **`DB::table()`**، خاصة داخل أحداث النموذج (`Model Lifecycle Events`) لضمان سرعة المعالجة والحقن التسلسلي للنقاط.

---

## 5. Backend Components

### A. المتحكمات (Controllers)
* `App\Http\Controllers\Admin\ReportController`: مسؤول عن دورة حياة التقرير (قائمة، إنشاء، تخزين، عرض، تعديل، تحديث، إغلاق، حذف، استيراد وتصدير إكسيل، وجلب بيانات أجهزة المهمة كـ JSON).
* `App\Http\Controllers\Admin\ReportPdfController`: مسؤول حصريًا عن تحميل وتغذية قوالب PDF وتعيين إعدادات الذاكرة وحجم الصفحة وتدفق الملف.
* `App\Http\Controllers\Admin\CalibrationInstrumentsController`: يمثل محول التوجيه المركزي (Dispatcher) لإدخال بيانات القياس (Saisie) حسب نوع الجهاز وعرض منحنى الخطأ المترولوجي.

### B. طلبات التحقق (Form Requests)
* `StoreTransmitterRequest`: التحقق من قياسات المرسلات (10 نقاط، إشارات تيار، قيم مرجعية، حرارة وضغط محيطي).
* `StoreProbeRequest`: التحقق من قياسات مسابير المقاومة الحرارية PT100 (5 نقاط، قيم حرارة ومقاومة أومية).
* `StoreFlowComputerRequest`: التحقق من قياسات قنوات حاسبة التدفق (10 نقاط، إشارة دخل ومحاكاة).
* `StoreChromatographVerificationRequest`: التحقق من مكونات الغاز الـ 11 والخواص الطاقوية وأسطوانة الغاز القياسية.
* **[A. معلومة مؤكدة]:** لا يوجد Form Request مستقل لإنشاء أو تعديل التقرير الأساسي (`StoreReportRequest` / `UpdateReportRequest`)؛ يتم التحقق عبر `$request->validate([...])` مباشرة داخل دالتي `store` و`update` في `ReportController`.

### C. خدمات النطاق (Domain Services)
* `CalibratorResolutionService`: حل المعايير المرجعية استنادًا للمهمة ونوع القياس والتصنيف المعتمد.
* `OamMetrologyService`: القلب النابض للحسابات المترولوجية وحسابات EMT للمرسلات والمسابير والـ ADC.
* `ChromatographMetrologyService`: حسابات تكرارية الكروماتوغراف (ASTM D 1945) والخواص الفيزيائية (ISO 6976).
* `SvgChartService`: التوليد الرياضي البحت لرسومات الـ SVG البيانية دون الاعتماد على مكتبات طرف ثالث في المتصفح.

### D. فئات الاستيراد والتصدير (Import/Export Classes)
* `ReportExport`: المصنف الرئيسي لتصدير ملف الإكسيل الميداني التفاعلي.
* `ReportDataImport`: محرك القراءة والمعالجة الميدانية العكسية لبيانات الإكسيل.

---

## 6. Controllers

جدول حصر جميع دوال ومسارات وحدة التقارير:

| HTTP Method | المسار (Route URI) | اسم الدالة في الـ Controller | المعاملات المدخلة (Input) | المخرجات والاستجابة (Output) | كود الحالة (Status) | الصلاحية / الوسيط (Auth/Middleware) |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/reports` | `ReportController::index` | `search, mission_id, date_from, date_to, status, per_page` | عرض صفحة `admin.reports.index` | `200 OK` | `permission:view reports` |
| `GET` | `/admin/reports/create` | `ReportController::create` | لا يوجد (يقرأ `old('mission_id')`) | عرض صفحة `admin.reports.create` | `200 OK` | `permission:create reports` |
| `POST` | `/admin/reports` | `ReportController::store` | `mission_id, report_number, status, instrument_ids[], default_calibrators[]` | إعادة توجيه إلى `admin.reports.index` مع رسالة نجاح | `302 Found` | `permission:create reports` |
| `GET` | `/admin/reports/{report}` | `ReportController::show` | كائن `Report $report` (Route Model Binding) | عرض صفحة `admin.reports.show` مع بيانات الأجهزة والإحصائيات | `200 OK` | `permission:view reports` |
| `GET` | `/admin/reports/{report}/edit` | `ReportController::edit` | كائن `Report $report` | عرض صفحة `admin.reports.edit` | `200 OK` | `permission:edit reports` |
| `PUT` | `/admin/reports/{report}` | `ReportController::update` | بيانات التقرير المعدلة + `_redirect` | إعادة توجيه إلى الصفحة السابقة أو `admin.reports.index` | `302 Found` | `permission:edit reports` |
| `DELETE` | `/admin/reports/{report}` | `ReportController::destroy` | كائن `Report $report` + `_redirect` | حذف التقرير وإعادة التوجيه مع رسالة نجاح | `302 Found` | `permission:delete reports` |
| `POST` | `/admin/reports/{report}/close` | `ReportController::close` | كائن `Report $report` | فحص اكتمال الأجهزة وتحويل الحالة إلى `completed` | `302 Found` | `permission:edit reports` |
| `POST` | `/admin/reports/{report}/calibrators` | `ReportController::syncCalibrators` | `default_calibrators[], apply_to_all` | حفظ المعايير الافتراضية ومزامنتها مع أجهزة التقرير | `302 Found` | `permission:edit reports` |
| `GET` | `/admin/reports/mission-details/{mission}` | `ReportController::getMissionDetails` | كائن `Mission $mission`, Query: `report_id` | استجابة `JsonResponse` بمعلومات الموقع والأجهزة والمعايير | `200 OK` | `permission:view reports` |
| `GET` | `/admin/reports/all-instruments-json` | `ReportController::getAllInstrumentsJson` | لا يوجد | استجابة `JsonResponse` بجميع الأجهزة النشطة غير المستبعدة | `200 OK` | `permission:view reports` |
| `GET` | `/admin/reports/{report}/export-excel` | `ReportController::exportExcel` | كائن `Report $report` | تنزيل ملف Excel (`BinaryFileResponse`) | `200 OK` | `permission:view reports`, `no-download-history` |
| `POST` | `/admin/reports/{report}/import-excel` | `ReportController::importExcel` | ملف مرفوع `excel_file` | معالجة الملف وإعادة التوجيه إلى `admin.reports.show` | `302 Found` | `permission:edit reports` |
| `GET` | `/admin/reports/{report}/pdf` | `ReportPdfController::generatePdf` | كائن `Report $report` | بث مباشر لملف PDF الرسمي (`application/pdf`) | `200 OK` | `permission:view reports`, `no-download-history` |
| `GET` | `/admin/reports/{report}/pdf-not-emt` | `ReportPdfController::generatePdfNotEmt` | كائن `Report $report` | بث مباشر لملف PDF الخام (`application/pdf`) | `200 OK` | `permission:view reports`, `no-download-history` |
| `GET` | `/admin/reports/{report}/instruments/{instrument}/curve` | `CalibrationInstrumentsController::showCurve` | `Report $report, Instrument $instrument` | عرض صفحة منحنى الخطأ المترولوجي `admin.reports.curve` | `200 OK` | `permission:view reports` |
| `GET` | `/admin/reports/{report}/instruments/{instrument}/saisie` | `CalibrationInstrumentsController::createSaisie` | `Report $report, Instrument $instrument` | توجيه وعرض واجهة إدخال القياس حسب نوع الجهاز | `200 OK` | `permission:edit reports` |
| `POST` | `/admin/reports/{report}/instruments/{instrument}/saisie/transmitter` | `CalibrationInstrumentsController::storeTransmitterSaisie` | `StoreTransmitterRequest` | حفظ نقاط المرسل وإعادة التوجيه لواجهة الإدخال | `302 Found` | `permission:edit reports` |
| `POST` | `/admin/reports/{report}/instruments/{instrument}/saisie/probe` | `CalibrationInstrumentsController::storeProbeSaisie` | `StoreProbeRequest` | حفظ نقاط المسبار وإعادة التوجيه لواجهة الإدخال | `302 Found` | `permission:edit reports` |
| `POST` | `/admin/reports/{report}/instruments/{instrument}/saisie/flow_computer` | `CalibrationInstrumentsController::storeFlowComputerSaisie` | `StoreFlowComputerRequest` | حفظ نقاط قناة الحاسبة وإعادة التوجيه لواجهة الإدخال | `302 Found` | `permission:edit reports` |
| `POST` | `/admin/reports/{report}/instruments/{instrument}/saisie/chromatograph` | `CalibrationInstrumentsController::storeChromatographSaisie` | `StoreChromatographVerificationRequest` | حفظ بيانات الكروماتوغراف وإعادة التوجيه | `302 Found` | `permission:edit reports` |

---

## 7. Requests & Validation

### 1. التحقق في إنشاء وتعديل التقرير (`ReportController::store & update`)
يتم التحقق برمجيًا عبر:
* `mission_id`: `['nullable', 'integer', 'exists:missions,id']` (يسمح بتقرير حر بدون مهمة مع اشتراط صحة المعرف إن وجد).
* `report_number`: `['required', 'string', 'max:255', 'unique:reports,report_number,...']` (رقم فريد إلزامي يمنع تكرار وثائق المعايرة).
* `status`: `['required', 'in:progress,completed']` (حصر الحالات في قيد الإنجاز أو مكتمل).
* `instrument_ids`: `['nullable', 'array']` و `instrument_ids.*`: `['integer']` (قائمة الأجهزة المختارة للمعايرة ضمن التقرير).
* `default_calibrators`: مصفوفة معايير افتراضية مفحوصة عبر `exists:equipment,id` (لتفادي تعيين أجهزة معيارية غير معرفة بالنظام).
* **قاعدة عمل خاصة أثناء التحديث (`update`):**
  * إذا قام المستخدم بإلغاء تحديد جهاز من التقرير وكان هذا الجهاز يحتوي بالفعل على نقاط فحص مسجلة في قاعدة البيانات، يتم رفض الطلب فورًا عبر:
  ```php
  if ($inst && $this->hasVerificationData($report->id, $inst)) {
      return back()->withErrors([
          'instrument_ids' => __('reports.form.cannot_remove_has_data').' ('.($inst->tag_number ?? '#'.$inst->id).')',
      ])->withInput();
  }
  ```

### 2. طلب فحص المرسلات (`StoreTransmitterRequest`)
* `verification_date`: `['required', 'date']` (تاريخ إجراء الفحص الميداني).
* `calibrator_1`, `calibrator_2`: `['nullable', 'exists:equipment,id']` (المرجع الفيزيائي وحاقن التيار).
* `ambient_temperature`, `ambient_pressure`: `['nullable', 'numeric']` (الظروف المحيطية لتعويض الضغط المطلق).
* `points`: `['required', 'array', 'size:10']` (شرط إلزامي بوجود 10 نقاط قياس تمثل دورة كاملة: 5 صعود و5 نزول).
* `points.*.applied_percentage`: `['required', 'numeric']` (0%, 25%, 50%, 75%, 100%).
* `points.*.reference_value`: `['required', 'numeric']` (القيمة الفيزيائية المرجعية).
* `points.*.measured_signal`: `['nullable', 'numeric']` (التيار بالمللي أمبير 4-20 mA).
* `points.*.indicated_value`: `['nullable', 'numeric']` (القيمة المقروءة على شاشة المرسل الرقمي الذكي أو في قياسات الغاز).

### 3. طلب فحص المسابير الحرارية (`StoreProbeRequest`)
* `points`: `['required', 'array', 'size:5']` (5 نقاط صعود تمثل النطاق: 0%, 25%, 50%, 75%, 100%).
* `points.*.reference_temperature`: `['required', 'numeric']` (الحرارة المرجعية في الحمام أو الفرن الحراري).
* `points.*.measured_resistance`: `['required', 'numeric']` (المقاومة المقاسة بالأوم $\Omega$ عبر جهاز الملتيميتر).
* `points.*.indicated_temperature`: `['required', 'numeric']` (الحرارة المقروءة أو المحسوبة).

### 4. طلب فحص حاسبات التدفق (`StoreFlowComputerRequest`)
* `transmitter_id`: `['required', 'exists:instruments,id']` (معرف المرسل المرتبط بالقناة المحاكاة).
* `shunt_resistance`: `['nullable', 'numeric']` (مقاومة التحويلة، الافتراضية $250.00\ \Omega$).
* `points`: `['required', 'array', 'size:10']` (10 نقاط لمسار الصعود والنزول).
* `points.*.expected_signal`: `['required', 'numeric']` (التيار المتوقع بالمللي أمبير).
* `points.*.measured_signal`: `['required', 'numeric']` (التيار المحقون فعليًا).
* `points.*.expected_value`: `['required', 'numeric']` (القيمة الحسابية للمقدار).
* `points.*.indicated_value`: `['required', 'numeric']` (القيمة الظاهرة على شاشة حاسبة التدفق).

### 5. استيراد ملف الإكسيل الميداني (`ReportController::importExcel`)
* `excel_file`: `['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']` (الملف الميداني، بحجم أقصى 10 ميغابايت).

---

## 8. Services / Actions

### أ. خدمة حل وتوزيع المعايير (`CalibratorResolutionService`)
* **المسؤولية:** استخراج وتصنيف عتاد المعايرة المعتمد التابع للمهمة (أو الشامل في حال عدم وجود مهمة).
* **طريقة التصنيف:**
  * فحص حقل `requires_calibration = 1` واستبعاد فئة المركبات (`category != 'Vehicle'`).
  * تصفية وفلترة العتاد حسب المقدار الفيزيائي والوصف والرموز:
    * `pressure`: البحث عن الضغط (`bar`, `pao`, `dpc`, `manom`, `adt 672`).
    * `dp_pressure`: الضغط التفاضلي (`Δp`, `diff`, `mbar`, `-1..1 bar`).
    * `electrical`: أجهزة قياس وتوليد التيار والملتيميتر (`mA`, `multi`, `process`, `adt 221`, `fluke 754`).
    * `transmitter_temperature`: مولدات ومحاكيات الحرارة للمرسلات.
    * `probe_thermal`: أفران وأحواض المعايرة الحرارية للمسابير (استبعاد علب المقاومات).
    * `probe_resistance`: أجهزة قياس المقاومة وعلب المقاومات العشرية العيارية (`boite decade`).

### ب. خدمة الحسابات المترولوجية (`OamMetrologyService`)
* **المسؤولية:** تطبيق اللوائح الوطنية الجزائرية للمترولوجيا القانونية ومعايير OIML.
* **الخوارزميات المنفذة:**
  1. **التحويل العكسي للمقاومة إلى حرارة $T(R)$ والحرارة إلى مقاومة $R(T)$ لمستشعر PT100 (IEC 60751):**
     * للنطاق الموجب $T \ge 0^\circ\text{C}$: حل المعادلة التربيعية التحليلية.
     * للنطاق السالب $T < 0^\circ\text{C}$: استخدام طريقة نيوتن-رافسون التقريبية (`Newton-Raphson`) للوصول لدقة تفوق $10^{-5}$.
  2. **تقييم المسبار الحراري PT100:**
     $$\text{Error} = T_{\text{indicated}} - T_{\text{applied}}$$
     $$\text{EMT} = 0.15 + (0.002 \times |T_{\text{applied}}|)$$
     $$\text{Conformity} = |\text{Error}| \le \text{EMT}$$
  3. **تقييم المرسلات (Transmitters):**
     * تصحيح الضغط المطلق: إضافة الضغط الجوي المحيطي $P_{\text{amb}}$ للقيمة المرجعية في حال كان نوع القياس `Absolute`.
     * احتساب القيمة الفيزيائية المقاسة من إشارة التيار 4-20 mA:
       $$\text{Value}_{\text{calc}} = \frac{I_{\text{mA}} - 4}{16} \times \text{Span} + \text{Min}$$
     * احتساب الخطأ المطلق والنسبي ومقارنته بحدود EMT حسب المائع (غاز أو سائل) وتكنولوجيا الجهاز (Smart أو تقليدي Traditional) ومقدار المجال (Span).
  4. **تقييم حاسبات التدفق وقنوات ADC:**
     * احتساب القيمة المكافئة من التيار أو فرق الجهد عبر مقاومة التحويلة (250 أوم أو 50 أوم) ومقارنة القيمة المقروءة بحسابات EMT للـ ADC.

### ج. خدمة المنحنيات البيانية المتجهية (`SvgChartService`)
* **المسؤولية:** توليد شفرة SVG نقية وذاتية الاحتواء لمنحنى الخطأ المترولوجي مع حدود الـ EMT (الحدين الموجب والسالب $\pm\text{EMT}$ باللون الأحمر) ونقاط الصعود والنزول لتضمينها بسلاسة في واجهات العرض والطباعة بدون جافاسكريبت خارجي.

---

## 9. Repositories

> **Not found in the analyzed code.**

**[A. معلومة مؤكدة]:** لا يستخدم المشروع نمط المستودعات (Repository Pattern) في وحدة التقارير (`app/Repositories` غير موجودة إطلاقًا في المشروع). يتم تنفيذ الاستعلامات والعمليات على البيانات مباشرة داخل المتحكمات (`Controllers`)، أو داخل نماذج Eloquent (`Models`)، أو عبر واجهة الاستعلام المباشرة `Illuminate\Support\Facades\DB`.

---

## 10. Models

### نموذج التقرير: `App\Models\Report`
* **الجدول:** `reports`
* **المفتاح الأساسي:** `id` (bigint unsigned)
* **الحقول القابلة للتعبئة الجملية (`$fillable`):**
  * `mission_id`
  * `report_number`
  * `status`
  * `excluded_instrument_ids`
  * `default_calibrators`
* **التحويل التلقائي (`$casts`):**
  * `excluded_instrument_ids => 'array'`
  * `default_calibrators => 'array'`
* **الثوابت المعمارية:**
  ```php
  public const NON_REPORTABLE_TYPES = [
      'Chromatograph',
      'Prover',
      'StandardGauge',
      'TestMeasure',
      'Guge_etalon',
      'Gauge',
  ];
  ```
* **الأحداث المدمجة (`booted`):**
  * `static::created`: استدعاء دالة `initializeVerificationPoints()` للحقن الآلي لنقاط المعايرة لجميع أجهزة الموقع المعتمدة في التقرير.
  * `static::deleting`: تنفيذ حذف تسلسلي صارم (`Cascade Deletion`) داخل معاملة مالية (`DB::transaction`) لتطهير جداول الفحص ونقاط القياس والمعايير من جداول:
    * `transmitter_verifications` و `transmitter_verification_points` و `transmitter_verification_calibrators`
    * `probe_verifications` و `probe_verification_points` و `probe_verification_calibrators`
    * `flow_computer_verifications` و `flow_computer_verification_points` و `flow_computer_verification_calibrators`
    * `chromatograph_verifications` و `chromatograph_composition_points` و `chromatograph_physical_properties` و `chromatograph_verification_calibrators`
    * جدول الربط `report_instruments`.
* **النطاقات البرمجية (`Scopes`):**
  * `scopeFilter(Builder $query, array $filters)`: فلترة حسب رقم التقرير (`report_number`)، المهمة (`mission_id`)، النطاق الزمني (`created_at`)، وحالة التقرير (`status`).
* **السمات والوصول (`Accessors`):**
  * `getStatusBadgeClassAttribute`: تعيين صنف الـ CSS (`rpt-badge-completed` أو `rpt-badge-progress`).
  * `getStatusLabelAttribute`: التسمية المترجمة للحالة.
  * `getActiveInstrumentsCountAttribute`: عدد الأجهزة المتبقية بعد استبعاد `excluded_instrument_ids`.
* **الدوال المساعدة:**
  * `generateReportNumber()`: توليد تلقائي للرقم التسلسلي للتقرير بالسنة الحالية بصيغة `RPT-YYYY-NNN`.
  * `getDefaultCalibratorsForInstrument(Instrument $instrument, ?Instrument $channelTransmitter = null)`: استرجاع زوج أجهزة المعايرة الافتراضي للجهاز.
  * `syncDefaultCalibratorsToAllInstruments()`: مزامنة وحفظ المعايير الافتراضية وتطبيقها في جداول الربط مع جميع أجهزة التقرير.

---

## 11. Eloquent Relationships

### مخطط العلاقات المنطقية انطلاقًا من نموذج Report

```text
                        ┌──────────────┐
                        │   Customer   │
                        └──────┬───────┘
                               │ 1
                               │ N
                        ┌──────┴───────┐
                        │    Site      │
                        └──────┬───────┘
                               │ 1
                               │ N
┌──────────────┐ 1    N ┌──────┴───────┐
│   Contract   ├────────┤   Mission    │
└──────────────┘        └──────┬───────┘
                               │ 1
                               │ N
                        ┌──────┴───────┐
                        │    Report    │
                        └──────┬───────┘
                               │
         ┌─────────────────────┼─────────────────────┬─────────────────────┐
       1 │ N                 1 │ N                 1 │ N                 1 │ N
┌────────┴─────────┐  ┌────────┴─────────┐  ┌────────┴─────────┐  ┌────────┴─────────┐
│   Transmitter    │  │      Probe       │  │  Flow Computer   │  │  Chromatograph   │
│  Verifications   │  │  Verifications   │  │  Verifications   │  │  Verifications   │
└────────┬─────────┘  └────────┬─────────┘  └────────┬─────────┘  └────────┬─────────┘
         │ 1                   │ 1                   │ 1                   │ 1
         │ N                   │ N                   │ N                   ├────────────────┐ N
┌────────┴─────────┐  ┌────────┴─────────┐  ┌────────┴─────────┐  ┌────────┴─────────┐ ┌────┴────────────┐
│   Transmitter    │  │      Probe       │  │  Flow Computer   │  │   Composition    │ │   Physical      │
│     Points       │  │     Points       │  │     Points       │  │     Points       │ │  Properties     │
└──────────────────┘  └──────────────────┘  └──────────────────┘  └──────────────────┘ └─────────────────┘
```

### تفاصيل العلاقات المترولوجية

1. **`Report -> belongsTo -> Mission`:**
   * المفتاح الخارجي: `reports.mission_id` -> `missions.id`
   * سلوك قاعدة البيانات: `ON UPDATE CASCADE, ON DELETE NO ACTION`
   * المعنى الوظيفي: كل تقرير معايرة يرتبط بمهمة عمل ميدانية مجدولة في موقع زبون معين.
2. **`Report -> hasMany -> TransmitterVerification`:**
   * المفتاح الخارجي: `transmitter_verifications.report_mission_id` -> `reports.id`
   * سلوك قاعدة البيانات: `ON DELETE CASCADE`
   * المعنى الوظيفي: يضم التقرير فحوصات جميع مرسلات الضغط والحرارة المعايرة خلاله.
3. **`Report -> hasMany -> ProbeVerification`:**
   * المفتاح الخارجي: `probe_verifications.report_mission_id` -> `reports.id`
   * سلوك قاعدة البيانات: `ON DELETE CASCADE`
   * المعنى الوظيفي: فحوصات مسابير الحرارة البلاتينية التابعة للتقرير.
4. **`Report -> hasMany -> FlowComputerVerification`:**
   * المفتاح الخارجي: `flow_computer_verifications.report_mission_id` -> `reports.id`
   * سلوك قاعدة البيانات: `ON DELETE CASCADE`
   * المعنى الوظيفي: فحوصات حاسبات التدفق (لكل قناة مرسل محاكاة فحص مستقل).
5. **`Report -> hasMany -> ChromatographVerification`:**
   * المفتاح الخارجي: `chromatograph_verifications.report_mission_id` -> `reports.id`
   * سلوك قاعدة البيانات: `ON DELETE SET NULL` (وفق التعديل الأخير للمهاجرة لعزل الكروماتوغراف وتمكينه من العمل بشكل مستقل).
6. **علاقات جداول المعايير (Calibrators Pivot):**
   * ترتبط نماذج الفحص الأربعة مع جدول `equipment` عبر جداول وسيطة (`*_verification_calibrators`) تحمل حقل `role`:
     * `role = 1`: المرجع الفيزيائي المطبق (Applied Standard / Étalon de référence).
     * `role = 2`: معيار الإشارة المقاسة أو الدخل الكهربائي (Measured Signal / Multimètre / Décade).

---

## 12. Database Schema

الجداول الفعلية المستخرجة مباشرة من قاعدة بيانات MySQL:

### 1. جدول التقارير: `reports`
| اسم الحقل (Column) | النوع (Type) | Nullable | القيمة الافتراضية | المفتاح (Key) | الوصف والمعنى الوظيفي |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | `bigint unsigned` | لا | تلقائي (AI) | Primary Key | المعرف الرقمي الفريد للتقرير |
| `mission_id` | `int` | نعم | `NULL` | Foreign Key | معرف المهمة المرتبطة (`missions.id`) |
| `report_number` | `varchar(255)` | لا | لا يوجد | Unique Key | رقم التقرير التسلسلي المرجعي الفريد |
| `status` | `enum('progress','completed')`| لا | `'progress'` | Index | حالة التقرير (قيد الإنجاز / مكتمل) |
| `excluded_instrument_ids` | `longtext` | نعم | `NULL` | Check (`json_valid`) | مصفوفة JSON بمعرفات الأجهزة المستبعدة من التقرير |
| `default_calibrators` | `longtext` | نعم | `NULL` | None | كائن JSON يحفظ المعايير المرجعية الافتراضية لكل عائلة |
| `created_at` | `timestamp` | نعم | `NULL` | None | وقت إنشاء التقرير في النظام |
| `updated_at` | `timestamp` | نعم | `NULL` | None | وقت آخر تعديل |

### 2. جدول ربط أجهزة التقرير: `report_instruments`
| اسم الحقل (Column) | النوع (Type) | Nullable | القيمة الافتراضية | المفتاح (Key) | الوصف والمعنى الوظيفي |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | `bigint unsigned` | لا | تلقائي (AI) | Primary Key | المعرف الرقمي الفريد |
| `report_mission_id` | `bigint unsigned` | لا | لا يوجد | Foreign Key (`reports.id`) | معرف التقرير (Cascade on delete) |
| `instrument_id` | `bigint unsigned` | لا | لا يوجد | Foreign Key (`instruments.id`) | معرف الجهاز الميداني المعاير |
| `sequence` | `int unsigned` | لا | `1` | Index | الرقم التسلسلي لترتيب الجهاز بالتقرير |
| `created_at` / `updated_at` | `timestamp` | نعم | `NULL` | None | أوقات الإنشاء والتعديل |
* **القيود الفريدة المدمجة:**
  * `uq_report_instrument` على الزوج (`report_mission_id`, `instrument_id`).
  * `uq_report_instrument_sequence` على الزوج (`report_mission_id`, `sequence`).

### 3. جداول جلسات ونقاط فحص الأجهزة (Verification Tables Summary)
* **`transmitter_verifications`**: رؤوس فحوصات المرسلات (`id, instrument_id, report_mission_id, verification_date, ambient_temperature, ambient_pressure, overall_status`).
* **`transmitter_verification_points`**: نقاط المرسل الـ 10 (`step_order, cycle_phase, applied_percentage, reference_value, measured_signal, indicated_value, absolute_error, emt_limit, is_conforme`).
* **`probe_verifications`**: رؤوس فحوصات المسابير الحرارية PT100.
* **`probe_verification_points`**: نقاط المسبار الـ 5 (`step_order, cycle_phase, reference_temperature, measured_resistance, indicated_temperature, absolute_error, emt_limit, is_conforme`).
* **`flow_computer_verifications`**: رؤوس قنوات حاسبات التدفق (`instrument_id, simulated_transmitter_id, shunt_resistance, report_mission_id, overall_status`).
* **`flow_computer_verification_points`**: نقاط حاسبة التدفق الـ 10 (`step_order, cycle_phase, applied_percentage, expected_signal, measured_signal, expected_value, indicated_value, absolute_error, emt_limit, is_conforme`).
* **`chromatograph_verifications`**: رؤوس فحص الكروماتوغراف الغازي (`standard_gas_bottle_number, certificate_number, repeatability_status, composition_accuracy_status, physical_properties_status, overall_status`).
* **`chromatograph_composition_points`**: نقاط التحليل الكيميائي لـ 11 مركبًا غازيًا (`step_order, component_name, component_symbol, reference_value, run_1..run_5, mean_value, repeatability, relative_error_percent, is_conforme`).
* **`chromatograph_physical_properties`**: الخصائص الطاقوية والفيزيائية للغاز الطبيعي (`property_name, property_symbol, unit, reference_value, run_1..run_5, mean_value, relative_error_percent, is_conforme`).

---

## 13. Logical ERD

```text
+---------------------------------------------------------------------------------+
|                                    MISSIONS                                     |
|---------------------------------------------------------------------------------|
| id (PK) | site_id (FK) | contract_id (FK) | reference | start_date | end_date...|
+----------------------------------------+----------------------------------------+
                                         | 1
                                         | 
                                         | N
+----------------------------------------v----------------------------------------+
|                                    REPORTS                                      |
|---------------------------------------------------------------------------------|
| id (PK, bigint)                                                                 |
| mission_id (FK -> missions.id, nullable)                                        |
| report_number (varchar, UNIQUE)                                                 |
| status (enum: 'progress', 'completed')                                          |
| excluded_instrument_ids (JSON longtext)                                         |
| default_calibrators (JSON longtext)                                             |
| created_at | updated_at                                                         |
+----+-------------------+-------------------+--------------------+---------------+
     |                   |                   |                    |
   1 | N               1 | N               1 | N                1 | N (nullable)
+----v-------------+ +---v------------+ +---v-------------+ +---v---------------+
| TRANSMITTER      | | PROBE          | | FLOW COMPUTER   | | CHROMATOGRAPH     |
| VERIFICATIONS    | | VERIFICATIONS  | | VERIFICATIONS   | | VERIFICATIONS     |
|------------------| |----------------| |-----------------| |-------------------|
| id (PK)          | | id (PK)        | | id (PK)         | | id (PK)           |
| report_mission_id| | report_mission_id| report_mission_id| report_mission_id |
| instrument_id(FK)| | instrument_id  | | instrument_id   | | instrument_id(FK) |
| overall_status   | | overall_status | | sim_trans_id(FK)| | overall_status    |
+----+-------------+ +---+------------+ +---+-------------+ +---+---------------+
     |                   |                   |                    |
   1 | N               1 | N               1 | N                1 | N
+----v-------------+ +---v------------+ +---v-------------+ +---v---------------+
| TRANSMITTER      | | PROBE          | | FLOW COMPUTER   | | CHROMATOGRAPH     |
| POINTS (10 pts)  | | POINTS (5 pts) | | POINTS (10 pts) | | COMPOSITION (11)  |
|------------------| |----------------| |-----------------| | & PHYSICAL PROPS  |
| verification_id  | | verification_id| | verification_id | +-------------------+
| applied_pct      | | ref_temperature| | expected_signal |
| ref_value        | | measured_res   | | measured_signal |
| measured_signal  | | indicated_temp | | indicated_value |
| absolute_error   | | absolute_error | | absolute_error  |
| emt_limit        | | emt_limit      | | emt_limit       |
| is_conforme      | | is_conforme    | | is_conforme     |
+------------------+ +----------------+ +-----------------+
```

---

## 14. API Endpoints

> **[A. معلومة مؤكدة]:** لا يحتوي المشروع على مسارات API عامة (RESTful API endpoints) مسجلة في `routes/api.php` لوحدة التقارير (ملف `routes/api.php` يقتصر فقط على `/ping` ومسار حسابات الغاز `aga8`).  
> جميع مسارات التقارير تعمل ضمن **Web Routes** (`routes/web.php`) وتحت حماية جلسة تسجيل الدخول `web` guard، ولكنها تقدم أيضًا استجابات JSON لخدمة واجهات الـ Frontend عبر AJAX.

### Endpoints الداخلية التفاعلية (Web-API Ajax Endpoints)

#### 1. جلب تفاصيل الموقع والأجهزة لمعاينة المهمة
* **المسار:** `GET /admin/reports/mission-details/{mission}`
* **الوسائط والـ Query:** `?report_id={id}` (اختياري، يمرر عند التعديل لمعرفة الأجهزة التي تملك بيانات مسجلة مسبقًا).
* **الحماية:** `auth:web`, `permission:view reports`
* **المعاملات:** كائن المهمة `Mission $mission` عبر الـ Route Model Binding.
* **هيكل استجابة الـ JSON الفعلي:**
```json
{
  "mission": {
    "id": 14,
    "reference": "MIS-2026-088"
  },
  "site": {
    "id": 5,
    "name": "Station Compression Hassi R'Mel",
    "short_name": "SC-HRM",
    "full_name": "Station Compression Hassi R'Mel",
    "site_code": "HRM-01",
    "location": "Hassi R'Mel, Laghouat"
  },
  "instruments": [
    {
      "id": 102,
      "tag_number": "PT-2041",
      "serial_number": "SN-7841120",
      "instrument_type": "Transmitter",
      "image_path": "pt_2041.jpg",
      "image_url": "http://app.gmtm-dz.com/assets/img_instruments/pt_2041.jpg",
      "status": "active",
      "has_data": true
    }
  ],
  "calibrators": {
    "all": [ /* جميع عتاد المعايرة المعتمد */ ],
    "pressure": [ /* عتاد معايرة الضغط */ ],
    "dp_pressure": [ /* عتاد الضغط التفاضلي */ ],
    "electrical": [ /* أجهزة القياس وتوليد التيار */ ],
    "transmitter_temperature": [ /* مولدات حرارة المرسلات */ ],
    "probe_thermal": [ /* الأحواض والأفران الحرارية */ ],
    "probe_resistance": [ /* علب المقاومات العشرية */ ]
  }
}
```

#### 2. جلب جميع الأجهزة النشطة في النظام
* **المسار:** `GET /admin/reports/all-instruments-json`
* **الحماية:** `auth:web`, `permission:view reports`
* **المخرجات:** مصفوفة JSON بكافة الأجهزة ذات الحالة `active` مستثنى منها الفئات غير المشمولة بالتقارير الحلقية (`NON_REPORTABLE_TYPES`).

---

## 15. Request / Response Structures

### أ. استجابة حفظ نقاط المرسل (`storeTransmitterSaisie`)
* **طلب الإرسال (Form Data):**
```text
verification_date: 2026-09-25
ambient_temperature: 24.5
ambient_pressure: 1.013
calibrator_1: 18
calibrator_2: 32
points[0][applied_percentage]: 0
points[0][reference_value]: 0.00000
points[0][measured_signal]: 4.00200
points[0][indicated_value]: 0.01000
...
points[9][applied_percentage]: 0
points[9][reference_value]: 0.00000
points[9][measured_signal]: 3.99800
points[9][indicated_value]: -0.00500
```
* **الاستجابة:** `RedirectResponse (302)` إلى مسار الإدخال مصحوبًا برسالة جلسة سريعة (`with('success', '...')`).

### ب. أخطاء التحقق (Validation Errors)
في حال نقص نقطة من النقاط العشر أو إدخال قيم نصية غير عددية:
* **الاستجابة:** `RedirectResponse (302)` إلى الصفحة السابقة (`back()`) مع تمرير مصفوفة الـ `$errors` التي تظهر أعلى النموذج في مكون `alert alert-danger`.

---

## 16. Frontend Components

تتألف واجهة المستخدم من قوالب Blade المتناسقة المعتمدة على محرك تصميم موحد ونظام الثيمات (Dark/Light Mode):

1. **`admin.reports.index` (جدول الاستعراض الرئيسي):**
   * شريط أدوات علوي موحد (`.bord .bord-full-distribution`).
   * مرشحات تفاعلية: فلتر المهمة، فلتر تاريخ الإنشاء (من / إلى)، فلتر حالة التقرير (قيد الإنجاز / مكتمل)، والبحث النصي برقم التقرير.
   * زر إضافة تقرير جديد المشروط بصلاحية `@can('create reports')`.
   * جدول البيانات التفاعلي مع ترقيم الصفحات (`Pagination`).
   * شارات الحالة اللونية (`.rpt-badge-completed` بالأخضر، `.rpt-badge-progress` بالبرتقالي).
   * أزرار الإجراءات الثلاثية: عرض التقرير (Eye)، تعديل التقرير (Edit)، وحذف التقرير (Delete مع تأكيد SweetAlert/Confirm).

2. **`admin.reports.create` و `admin.reports.edit` (شاشات الإنشاء والتحرير):**
   * حقل رقم التقرير المقترح آليًا أو المحفوظ.
   * قائمة اختيار المهمة التي تطلق طلب Fetch فوري عند التغيير (`onchange`).
   * صندوق قراءة اسم الموقع المرتبط آليًا (`readonly-site-input`).
   * شاشة المعايير الافتراضية المضمنة (`report-instruments.partials.calibrators_card`): مقسمة إلى بطاقات أنيقة (ضغط، ضغط تفاضلي، تيار، حرارة، مسابير).
   * جدول الأجهزة المشمولة بالتقرير: يعرض صور الأجهزة المصغرة مع ميزة التكبير عند التحويم (`.img-thumb:hover transform: scale(2.2)`).
   * زر حذف الجهاز من التقرير (`removeInstrumentRow`) مع آلية حفظ المسودة في `sessionStorage` لمنع فقدان التعديلات عند تحديث الصفحة أو الأخطاء.

3. **`admin.reports.show` (لوحة متابعة إنجاز التقرير):**
   * بطاقة معلومات ثلاثية الأعمدة (Column 1: معلومات التقرير والمهمة، Column 2: شريط ونسب الإنجاز التراكمي وإحصائيات الأجهزة، Column 3: الحالة الشاملة للتقرير).
   * أزرار تصدير واستيراد الإكسيل المباشر (مع مؤشر دوران التحميل `fa-spinner fa-spin`).
   * أزرار المعاينة والطباعة المباشرة لملفات الـ PDF بنسختيها (`EMT` و `NotEMT`).
   * جدول تفصيلي للأجهزة بحالاتها المترولوجية المحسوبة آنيًا (`À faire` رمادي، `En cours` برتقالي، `Conforme` أخضر، `Non conforme` أحمر).
   * زر إغلاق واعتماد التقرير النهائي (`Clôturer le rapport`): معطل تلقائيًا حتى تبلغ نسبة الإنجاز 100%.

4. **`admin.calibration_instruments.*` (واجهات الإدخال الميداني):**
   * جداول مخصصة لحسابات الصعود والنزول، حقول إدخال رقمية مدعومة بنظام التدقيق، وحسابات فورية.

---

## 17. Frontend → Backend Flow

### مخطط تدفق عملية استيراد ملف إكسيل الميداني ومعايرته آليًا:

```text
المستخدم في المتصفح (show.blade.php)
       │
       │  1. النقر على زر "استيراد ملف Excel"
       ▼
عنصر إدخال خفي <input type="file" id="directExcelFileInput">
       │
       │  2. اختيار الملف وتشغيل حدث onchange
       ▼
دالة جافاسكريبت تقوم بتفعيل الـ Spinner وإرسال النموذج تلقائياً
       │
       │  3. POST /admin/reports/{report}/import-excel
       ▼
طبقة التوجيه والمصادقة (web.php + Middleware: permission:edit reports)
       │
       │  4. استدعاء الدالة
       ▼
ReportController::importExcel(Request $request, Report $report, OamMetrologyService $service)
       │
       │  5. التحقق من صيغة وحجم الملف
       ▼
$request->validate(['excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240'])
       │
       │  6. تفويض المعالجة لخدمة الاستيراد
       ▼
ReportDataImport::import($filePath, $report->id)
       │
       │  7. فتح وقراءة أوراق العمل عبر PhpOffice\PhpSpreadsheet
       ▼
فحص رقم التقرير ومطابقته -> استخراج نقاط القياس -> تقييم الخطأ المترولوجي عبر OamMetrologyService
       │
       │  8. تحديث قاعدة البيانات في معاملة واحدة (DB::transaction)
       ▼
إعادة التوجيه إلى صفحة التقرير مع وميض رسالة النجاح (redirect()->route('admin.reports.show', ...))
```

---

## 18. Backend → Database Flow

### مخطط التدفق العكسي عند إنشاء التقرير (`ReportController::store`):

```text
البيانات المعتمدة من النموذج (Validated Data)
       │
       │  1. تصفية ومعالجة مصفوفة الأجهزة المستبعدة:
       │     excludedIds = diff(siteInstruments, includedIds)
       ▼
Report::create([
    'mission_id' => ...,
    'report_number' => ...,
    'status' => 'progress',
    'excluded_instrument_ids' => $excludedIds,
    'default_calibrators' => $defaultCalibrators,
])
       │
       │  2. تنفيذ استعلام الإدراج الأساسي في MySQL:
       │     INSERT INTO reports (...) VALUES (...)
       ▼
إطلاق حدث نموذج Eloquent التلقائي (static::created)
       │
       │  3. استدعاء دالة: $report->initializeVerificationPoints()
       ▼
بدء معاملة قاعدة بيانات مجمعة (DB::transaction)
       │
       ├──> أ) INSERT INTO report_instruments (report_mission_id, instrument_id, sequence...)
       │
       ├──> ب) لأجهزة الإرسال (Transmitter):
       │       - INSERT INTO transmitter_verifications (...)
       │       - INSERT INTO transmitter_verification_points (10 نقاط بقيم فارغة وحدود EMT أولية)
       │
       ├──> ج) للمسابير الحرارية (Probe):
       │       - INSERT INTO probe_verifications (...)
       │       - INSERT INTO probe_verification_points (5 نقاط بقيم فارغة وحدود EMT أولية)
       │
       └──> د) لحاسبات التدفق (FlowComputer):
               - فحص القنوات في flow_computer_transmitters
               - INSERT INTO flow_computer_verifications لكل قناة
               - INSERT INTO flow_computer_verification_points (10 نقاط لكل قناة)
       │
       │  4. مزامنة المعايير الافتراضية إن وجدت:
       ▼
$report->syncDefaultCalibratorsToAllInstruments()
       │
       └──> INSERT INTO *_verification_calibrators (verification_id, calibrator_id, role)
       │
       │  5. تأكيد المعاملة وإتمام الحفظ بنجاح (COMMIT)
       ▼
MySQL Database (حالة الاستقرار والجاهزية للإدخال الميداني)
```

---

## 19. Database → Frontend Flow

### مخطط استرجاع وتجميع بيانات التقرير للعرض (`ReportController::show`):

```text
قاعدة بيانات MySQL (جداول reports, missions, sites, instruments, verifications, points)
       │
       │  1. استعلام محمل مسبقاً (Eager Loading) لمنع مشكلة N+1:
       │     $report->load(['mission.site.instruments.specifications.grandeur', ...])
       ▼
مجموعة أجهزة الموقع النشطة المسترجعة (Eloquent Collection)
       │
       │  2. تصفية واستبعاد الأجهزة:
       │     - استبعاد المعرفات في excluded_instrument_ids
       │     - استبعاد أنواع NON_REPORTABLE_TYPES (ما لم تحوِ بيانات قديمة)
       ▼
حلقة المعالجة والتقييم الحسابي لكل جهاز ($instruments as $instrument)
       │
       ├──> استخراج مجال القياس (Range Min - Max + Symbol)
       │
       └──> استدعاء الدالة الخاصة: calculateInstrumentStatus($report->id, $instrument)
               │
               ├── فحص سجلات الفحص ونقاط القياس في قاعدة البيانات
               ├── إذا لم توجد نقاط أو جميعها فارغة -> 'À faire' (رمادي)
               ├── إذا كانت النقاط غير مكتملة أو أقل من العدد المقرر -> 'En cours' (برتقالي)
               ├── إذا اكتملت وجميعها مطابقة (every is_conforme == 1) -> 'Conforme' (أخضر)
               └── إذا كان هناك نقطة واحدة غير مطابقة -> 'Non conforme' (أحمر)
       │
       │  3. تجميع الإحصائيات الشاملة:
       │     stats: { total, termines, en_cours, non_conformes }
       │     progressPercentage = round((termines / total) * 100)
       ▼
تمرير مصفوفات $appareilsData و $stats و $progressPercentage إلى قالب Blade
       │
       │  4. تصيير الواجهة في المتصفح:
       ▼
عرض لوحة المتابعة، شريط التقدم التفاعلي، وجداول الأجهزة المشفرة بالألوان
```

---

## 20. Authentication & Authorization

### أ. المصادقة (Authentication)
* **الحارس المعتمد:** الحارس الافتراضي `web` المعتمد على الجلسات المشفرة (`sessions` table).
* لا يمكن لأي مستخدم غير مسجل الدخول الوصول لأي مسار من مسارات التقارير؛ يتم تحويله تلقائيًا إلى مسار تسجيل الدخول `/login`.

### ب. الصلاحيات والتفويض (Authorization & Permissions)
* يعتمد النظام على مكتبة **Spatie Laravel Permission**.
* الصلاحيات الأربع المخصصة لوحدة التقارير ومواقع تطبيقها:
  1. **`view reports`**:
     * تطبق على مسارات: `index`, `show`, `pdf`, `pdf_not_emt`, `export_excel`, `mission_details`, `all_instruments_json`, `curve`.
     * تمنع المستخدم غير المخول من رؤية القوائم أو تحميل الوثائق وتلقي خطأ `403 Forbidden`.
  2. **`create reports`**:
     * تطبق على مسارات: `create`, `store`.
     * تحمي شاشة الإنشاء وزر الإضافة في الواجهة عبر وسم Blade: `@can('create reports')`.
  3. **`edit reports`**:
     * تطبق على مسارات: `edit`, `update`, `close`, `syncCalibrators`, `import_excel`، وكافة مسارات `saisie` للأجهزة.
     * تمنع تعديل محتوى التقرير أو تغيير معاييره أو استيراد بياناته.
  4. **`delete reports`**:
     * تطبق على مسار: `destroy`.
     * تمنع تنفيذ الحذف التسلسلي للتقرير إلا لمن يحمل الصلاحية صراحة.
* **[A. معلومة مؤكدة]:** لا توجد فئة Policy خاصة باسم `ReportPolicy` في مجلد `app/Policies` (الملف الوحيد الموجود هو `PermissionPolicy.php`)؛ يعتمد التحقق البرمجي بالكامل على:
  ```php
  Gate::authorize('view reports');
  Gate::authorize('create reports');
  Gate::authorize('edit reports');
  Gate::authorize('delete reports');
  ```
  أو عبر وسيط التوجيه:
  ```php
  Route::middleware(['permission:view reports'])
  ```

---

## 21. Business Rules

تم استخراج وتوثيق كافة قواعد العمل التشغيلية والمترولوجية من الكود:

### 1. قاعدة استبعاد الأجهزة الخاصة (Exclusion Rule)
* **القاعدة:** تُستبعد أجهزة الكروماتوغراف (`Chromatograph`) والبروفر (`Prover`) ومقاييس السعة العيارية (`StandardGauge`, `TestMeasure`, `Gauge`) تلقائيًا ونهائيًا من التقرير الحلقي القياسي، وتوجه إلى نماذج ووحدات مستقلة.
* **الملفات المنفذة:** `Report::NON_REPORTABLE_TYPES`، `ReportController::store, edit, getMissionDetails, show`، واختبار الميزة `ReportInstrumentsExclusionTest.php`.

### 2. قاعدة اكتمال الفحص قبل الإغلاق (Closure Integrity Rule)
* **القاعدة:** لا يمكن إغلاق التقرير الميداني واعتماده نهائيًا وتحويل حالته إلى `completed` إلا إذا تم فحص وتقييم 100% من أجهزة التقرير وكانت النتيجة النهائية لكل جهاز إما `Conforme` أو `Non conforme`.
* **الملفات المنفذة:** `ReportController::close` و `resources/views/admin/reports/show.blade.php`.

### 3. قاعدة حماية البيانات الميدانية من الحذف العرضي (Data Protection Rule)
* **القاعدة:** يُمنع منعًا باتًا إلغاء تحديد أو إزالة أي جهاز من التقرير في شاشة التعديل (`edit`) إذا كان ذلك الجهاز قد سُجلت له نقاط أو بيانات فحص مسبقة في قاعدة البيانات، ويُجبر المستخدم على الاحتفاظ به لحماية التاريخ المترولوجي.
* **الملفات المنفذة:** `ReportController::update`.

### 4. قاعدة التقييم المترولوجي الصارم (Metrological Strictness)
* **القاعدة:** يُعتبر الجهاز "غير مطابق" (`Non conforme` - باللون الأحمر) بمجرد تجاوز نقطة فحص واحدة فقط لحد الخطأ الأقصى المسموح به ($\text{Absolute Error} > \text{EMT}$)، ولا يُقبل أي تسامح إحصائي أو متوسط حسابي يعوض فشل نقطة فردية في أجهزة الضغط والحرارة.
* **الملفات المنفذة:** `OamMetrologyService::evaluateTransmitter, evaluateProbePt100, evaluateADC`.

### 5. قاعدة مطابقة رقم التقرير في ملف الإكسيل المستورد (Spreadsheet Identity Verification)
* **القاعدة:** يرفض محرك الاستيراد قراءة أو تطبيق أي ملف إكسيل ميداني إذا كان رقم التقرير المسجل داخل ترويسة ملف الإكسيل غير مطابق تمامًا للتقرير الحالي المفتوح، وذلك لتفادي تداخل بيانات المواقع والآبار عن طريق الخطأ.
* **الملفات المنفذة:** `ReportDataImport::import`.

---

## 22. Exception Handling

تم رصد آلية إدارة الاستثناءات عبر الكود الفعلي:
1. **استثناءات الصلاحيات (`AuthorizationException`):**
   * عند فشل `Gate::authorize(...)`، يُلقي Laravel استثناء تفويض يتم اعتراضه تلقائيًا من قِبل معالج الاستثناءات الرئيسي ليعيد صفحة خطأ `403 Forbidden`.
2. **استثناءات العثور على النماذج (`ModelNotFoundException`):**
   * عند استدعاء معرف تقرير أو جهاز غير موجود عبر الربط التلقائي، يُعاد تلقائيًا خطأ `404 Not Found`.
3. **أخطاء التحقق من المدخلات (`ValidationException`):**
   * يتم اعتراضها تلقائيًا وإرجاع المستخدم للصفحة السابقة مع الحقول القديمة (`withInput()`) ومصفوفة الخطأ `$errors`.
4. **أخطاء استيراد ملفات الإكسيل الميدانية (`InvalidArgumentException` & `\Throwable`):**
   * معالجة صريحة داخل كتلة `try / catch` في `ReportController::importExcel`:
     * الأخطاء المنطقية (`InvalidArgumentException` مثل عدم تطابق رقم التقرير): يعاد التوجيه مع وميض رسالة خطأ واضحة `with('error', $e->getMessage())`.
     * الأخطاء الاستثنائية وانهيار الملفات (`\Throwable`): يتم تدوين الخطأ تفصيليًا في سجلات النظام (`Log::error('Report Excel Import Error: ...')`)، وإرجاع رسالة فشل للمستخدم دون انهيار التطبيق (`500`).
5. **إلغاء تخزين مسارات التحميل (`no-download-history`):**
   * استخدام وسيط لمنع تسجيل مسارات تنزيل الـ PDF والـ Excel في جلسة المتصفح `_previous.url`، لتفادي مشكلة إعادة تنزيل الملف عند استخدام `redirect()->back()`.

---

## 23. HTTP Status Codes

جدول أكواد الحالة المستخدمة فعليًا في الكود:

| رمز الحالة (Code) | المعنى القياسي | الاستخدام الفعلي في وحدة التقارير |
| :--- | :--- | :--- |
| `200 OK` | نجاح استرجاع البيانات | عرض واجهات Blade (الفهرس، العرض، الإنشاء، التعديل، المنحنى)، بث ملفات PDF، وتنزيل Excel، واستجابات AJAX JSON. |
| `302 Found` | إعادة توجيه بعد إجراء | بعد عمليات الحفظ (`store`)، التحديث (`update`)، الحذف (`destroy`)، الإغلاق (`close`)، واستيراد الإكسيل (`importExcel`). |
| `403 Forbidden` | رفض الوصول لعدم وجود صلاحية | عند محاولة الوصول لأي مسار دون امتلاك الصلاحية المناسبة في Spatie Permissions. |
| `404 Not Found` | السجل غير موجود | عند تمرير معرف تقرير أو جهاز غير موجود في قاعدة البيانات (`whereNumber('report')`). |
| `422 Unprocessable Entity` | فشل التحقق من صحة المدخلات | يتم إرجاعه آليًا بواسطة Laravel عند إرسال طلبات الـ Form Requests ببيانات ناقصة أو غير مطابقة. |
| `500 Internal Server Error` | خطأ داخلي في الخادم | قد يقع نظريًا في حال نفاذ الذاكرة أثناء توليد PDF ضخم، ولتفاديه وُضع كود صريح: `ini_set('memory_limit', '512M')`. |

---

## 24. Report Lifecycle

```text
[1. التخطيط والإنشاء - Draft / Creation]
   ├── اختيار المهمة الميدانية (Mission)
   ├── توليد رقم التقرير آلياً (RPT-YYYY-NNN)
   ├── استرجاع أجهزة موقع المهمة (باستثناء NON_REPORTABLE_TYPES)
   ├── استبعاد أو تضمين أجهزة محددة (excluded_instrument_ids)
   └── تحديد معايير المعايرة الافتراضية
                   │
                   ▼
[2. الحقن الأولي المترولوجي - Auto-Initialization]
   ├── حدث static::created في نموذج Report
   ├── تسجيل ربط الأجهزة بجدول report_instruments بترتيب تسلسلي
   └── إدراج سجلات الفحص الأولية في جداول verifications وحقن نقاط القياس
       (10 نقاط للمرسلات، 5 للمسابير، 10 لقنوات الحاسبات) بقيم Null وحدود EMT
                   │
                   ▼
[3. قيد الإنجاز وجمع القياسات - In Progress]
   ├── حالة التقرير في النظام: 'progress'
   ├── حالة الجهاز تبدأ كـ 'À faire' (رمادي)
   ├── تسجيل القياسات الميدانية (عبر واجهة Saisie أو استيراد Excel)
   ├── تحول حالة الجهاز إلى 'En cours' (برتقالي) أثناء الإدخال الجزئي
   └── التقييم المترولوجي المباشر لكل نقطة:
       ├── مطابقة جميع النقاط للـ EMT  ──> تصبح حالة الجهاز 'Conforme' (أخضر)
       └── فشل نقطة واحدة في الـ EMT ──> تصبح حالة الجهاز 'Non conforme' (أحمر)
                   │
                   ▼
[4. المراجعة والاعتماد - Review & Audit]
   ├── مراقبة شريط الإنجاز التراكمي في لوحة التقرير
   ├── فحص منحنيات الخطأ المترولوجي (Error Curves) لكل جهاز وقناة
   ├── تصدير ومطابقة تقارير الـ PDF بنسختيها (EMT الرسمية / NotEMT الخام)
   └── بلوغ نسبة الإنجاز 100% (جميع الأجهزة أصبحت إما Conforme أو Non conforme)
                   │
                   ▼
[5. الإغلاق النهائي - Completion / Closure]
   ├── المستخدم ينقر "Clôturer le rapport"
   ├── التحقق البرمجي الصارم من اكتمال كافة الفحوصات وعدم وجود أجهزة معلقة
   └── تحديث حالة التقرير إلى: 'completed' (قفل التقرير)
```

---

## 25. Source of Truth

جدول الحقيقة المرجعية للبيانات الأساسية المستعرضة في التقرير:

| المعلومة المستعرضة (Displayed Information) | المصدر الحقيقي للبيانة (Source of Truth) | آلية وموقع الحساب / الجلب (Calculation / Retrieval) |
| :--- | :--- | :--- |
| **رقم التقرير (Report Number)** | جدول `reports.report_number` | حقل مباشر في قاعدة البيانات (مولد مسبقًا عبر `Report::generateReportNumber`). |
| **الموقع الجغرافي والزبون** | جداول `sites` و `customers` | مسترجع عبر العلاقات: `$report->mission->site->customer`. |
| **المجال المترولوجي للجهاز (Range)** | جدول `instrument_specifications` | منسق عبر مواصفات الجهاز: `$instrument->specifications->first()->range_min - range_max`. |
| **القيمة المقاسة المحسوبة للمرسل** | حساب برمجي Backend | معادلة تحويل التيار 4-20 mA في `TransmitterVerificationPoint::getCalculatedValueAttribute`. |
| **حد الخطأ الأقصى المسموح به (EMT)** | قواعد بيانات اللوائح المترولوجية | محسوب برمجيًا في `OamMetrologyService` بناءً على نوع المائع والمقدار والتكنولوجيا ونطاق القياس. |
| **حالة مطابقة النقطة المترولوجية** | حقل `is_conforme` في جداول النقاط | ناتج المقارنة الرياضية المباشرة: `abs($error) <= $emt`. |
| **حالة الجهاز العامة (Badge)** | حساب فوري تجميعي | دالة `ReportController::calculateInstrumentStatus` بالاعتماد على اكتمال نقاط القياس ومطابقتها. |
| **نسبة الإنجاز الإجمالية للتقرير** | حساب فوري تجميعي | دالة `ReportController::show`: `round(($termines / $total) * 100)`. |
| **أجهزة المعايرة المعتمدة للدور 1 و 2** | جداول الربط الوسيطة `*_verification_calibrators` | مسترجعة من حقل `calibrator_id` المرتبط برقم الدور `role` وشهادة المعايرة الأحدث للمعد المرجعي. |

---

## 26. Dependency Map

### أ. الاعتمادية بين طبقات كود التقارير:

```text
ReportController / CalibrationInstrumentsController / ReportPdfController
   │
   ├──> Services Layer:
   │    ├── OamMetrologyService (المعادلات الفيزيائية وحدود EMT)
   │    ├── CalibratorResolutionService (تصنيف وفلترة عتاد المعايرة المعتمد)
   │    ├── ChromatographMetrologyService (معايير تكرارية وخواص الغاز الطبيعي)
   │    └── SvgChartService (التوليد الهندسي لمنحنيات الـ SVG)
   │
   ├──> IO Processing Layer:
   │    ├── ReportExport -> DeviceSheetExport, ChromatographSheetExport, ReportSummarySheetExport
   │    ├── ReportDataImport (تحليل ومعالجة جداول Excel الميدانية)
   │    └── Barryvdh\DomPDF\Facade\Pdf (محرك تصيير الـ PDF)
   │
   ├──> Eloquent Models Layer:
   │    ├── Report (الأحداث، الحذف التسلسلي، الحقن الآلي)
   │    ├── TransmitterVerification & Points & Calibrators
   │    ├── ProbeVerification & Points & Calibrators
   │    ├── FlowComputerVerification & Points & Calibrators
   │    └── ChromatographVerification & Composition & Physical & Calibrators
   │
   └──> External Domain Modules (الارتباط الخارجي بالمشروع):
        ├── Missions Module (Mission Model)
        ├── Sites & Customers Module (Site, Customer Models)
        ├── Instruments Module (Instrument, InstrumentSpecification, Grandeur Models)
        ├── Equipment Module (Equipment Model - أجهزة المعايرة المخبرية المعتمدة)
        └── Authentication & Authorization (User Model, Spatie Permissions)
```

---

## 27. End-to-End Execution Traces

### تتبع تفصيلي لعملية إدخال وحفظ قياسات مرسل ضغط (`Transmitter Saisie Flow`):

```text
[1. متصفح العميل]:
    المستخدم يدخل 10 نقاط في نموذج: resources/views/admin/calibration_instruments/transmitter.blade.php
    ينقر على زر: "Enregistrer les données"
    إرسال الطلب: POST /admin/reports/{report}/instruments/{instrument}/saisie/transmitter
       │
[2. التوجيه والمصادقة - Routing & Middleware]:
    routes/web.php (السطر 447)
    Middleware: auth:web, permission:edit reports
       │
[3. طبقة التحقق - Validation Layer]:
    App\Http\Requests\Calibration\StoreTransmitterRequest
    التحقق من: verification_date (إلزامي), points (مصفوفة ذات 10 عناصر), إشارات القياس
       │
[4. المتحكم - Controller Method]:
    App\Http\Controllers\Admin\CalibrationInstrumentsController::storeTransmitterSaisie()
    استخراج المواصفات الفنية للجهاز:
    $specs = $instrument->specification;
    $span = $specs->range_max - $specs->range_min;
       │
[5. إدارة المعاملة - Database Transaction]:
    DB::transaction(function () {
        - تحديث أو إنشاء رأس الفحص:
          TransmitterVerification::updateOrCreate(['report_mission_id' => ..., 'instrument_id' => ...], [...])
          
        - ربط وتحديث أجهزة المعايرة بالأدوار المحددة صراحة:
          $verification->syncCalibratorByRole($calibrator1, 1);
          $verification->syncCalibratorByRole($calibrator2, 2);
          
        - حذف النقاط السابقة وإعادة بنائها المترولوجي:
          TransmitterVerificationPoint::where('verification_id', $verification->id)->delete();
          
        - حلقة تكرار على النقاط العشر:
          foreach ($validated['points'] as $index => $point) {
              استدعاء خدمة التقييم المترولوجي:
              $eval = $this->oamService->evaluateTransmitter($span, $min, $refVal, $mA, $indicated, ...);
              
              تجهيز مصفوفة النقطة:
              $pointsData[] = [
                  'step_order' => $index + 1,
                  'cycle_phase' => ($index + 1 <= 5) ? 'Ascending' : 'Descending',
                  'absolute_error' => $eval['error'],
                  'emt_limit' => $eval['emt'],
                  'is_conforme' => (int) $eval['is_conforme'],
                  ...
              ];
          }
          
        - إدراج مجمع للنقاط:
          TransmitterVerificationPoint::insert($pointsData);
          
        - تحديث الحالة الشاملة للمرسل:
          $verification->update(['overall_status' => $allConforme]);
    });
       │
[6. الاستجابة وإعادة التوجيه - HTTP Response]:
    إعادة توجيه 302: redirect()->route('admin.reports.saisie', ['report' => ..., 'instrument' => ...])
    وميض رسالة الجلسة: with('success', 'Données du transmetteur enregistrées avec succès.')
       │
[7. المتصفح]:
    إعادة تحميل الصفحة وتحديث شارات الامتثال المترولوجي وألوان المنحنى البياني فوراً.
```

---

## 28. Architectural Observations

### A. مشكلات معمارية مؤكدة (Confirmed Issues)
1. **الاعتماد الكثيف على استعلامات الماكرو اليدوية المباشرة (`DB::table`) داخل كود الـ Eloquent Models:**
   * في نموذج `Report.php` (الأسطر 54–140)، يتم تنفيذ الحذف التسلسلي والحقن عبر استدعاءات `DB::table()` خام متعددة لكل جدول فرعي. هذا النمط يتجاوز أي أحداث نماذج أو مراقبين (`Observers`) قد ترتبط مستقبلاً بنماذج الفحص الفردية.
2. **غياب Form Request للعمليات الأساسية للتقرير (`store` و `update`):**
   * كود التحقق من صحة مدخلات إنشاء التقرير وتحديثه مكتوب بطريقة مدمجة داخل المتحكم (`$request->validate([...])`)، مما يجعل المتحكم محملاً بتفاصيل تقنية كان يجب عزلها في Form Request مخصص لتوحيد المعايير مع بقية وحدات التطبيق.
3. **غياب طبقة التجريد للتقارير والسياسات (`Policies`):**
   * يتم فحص الصلاحيات عبر استدعاء مباشر لـ `Gate::authorize('...')` وسلاسل Spatie المباشرة بدلًا من استخدام سياسات لارافيل القياسية المربوطة بالنماذج (`ReportPolicy`).

### B. مخاطر معمارية محتملة (Potential Risks)
1. **استهلاك الذاكرة في توليد ملفات الـ PDF الكبيرة متعددة الأجهزة:**
   * تم رفع سقف الذاكرة يدويًا في `ReportPdfController` إلى `512M` ووقت التنفيذ إلى `300s`. في المواقع الضخمة التي تضم عشرات الأجهزة (خاصة مع وجود صور الأجهزة وشهادات المعايرة)، قد يؤدي التوليد المتزامن من عدة مستخدمين إلى إرهاق خادم الويب (Memory Spikes).
2. **الحذف الفيزيائي الفوري للبيانات المترولوجية (Hard Deletion):**
   * لا يحتوي جدول `reports` ولا جداول الفحص الفرعية على حقل `deleted_at` (غياب تام لخاصية Soft Deletes). حذف أي تقرير يؤدي فورًا إلى محو جميع القياسات ونقاط المعايرة الميدانية من قاعدة البيانات بصفة نهائية لا يمكن استرجاعها إلا عبر النسخ الاحتياطية.

### C. الترابط والتشابك بين الوحدات (Coupling)
* هناك ترابط قوي ومباشر (`Tight Coupling`) بين وحدة التقارير ووحدة عتاد المعايرة `Equipment` من خلال البحث المعتمد على التعابير النمطية للنصوص (`Regex text matching` في `CalibratorResolutionService`). إذا قام مسؤول النظام بتعديل تسمية معد معايير في جدول `equipment` بطريقة لا تطابق النمط، قد يفشل المحرك في تصنيفه ضمن العائلة المناسبة.

---

## 29. Technical Debt

1. **تطابق أعمدة جدول نقاط حاسبة التدفق (`FlowComputerVerificationPoint`):**
   * في نموذج `FlowComputerVerificationPoint.php`، تم إدراج أعمدة في `$fillable` مثل (`standard_resistor`, `measured_voltage`, `equivalent_value`) بينما الأعمدة الفعلية المنفذة في قاعدة بيانات MySQL ومتحكم الإدخال هي (`expected_signal`, `measured_signal`, `expected_value`). لا يتسبب ذلك في عطل لأن المتحكم يستخدم `insert($pointsData)` المباشر، ولكنه يمثل دينًا تقنيًا يجب تنظيفه ليتطابق مع الـ Schema الفعلي.
2. **الازدواجية في حسابات الحالة المترولوجية (`Status Calculation Duplication`):**
   * دالة `calculateInstrumentStatus` مكررة بمنطقها البرمجي داخل `ReportController`، ونفس المنطق تقريبًا مكرر داخل `ReportExport::calculateStatus`. كان الأجدر عزل هذا المنطق في خدمة مترولوجية موحدة أو Accessor على نموذج الجهاز المرتبط بالتقرير.
3. **عزل الكروماتوغراف غير المكتمل كليًا:**
   * تم عزل أجهزة الكروماتوغراف في موديول مستقل ومسارات خاصة، ولكن كود التقرير والـ PDF والتصدير لا يزال يحتفظ ببقايا استعلامات الكروماتوغراف كمسار خلفي للتوافقية (`Backward Compatibility`)، مما يزيد من حجم وتشعب الكود.

---

## 30. Final Architecture Summary

تمثل وحدة التقارير (`Reports Module`) نظامًا ناضجًا وعالي الدقة مبنيًا خصيصًا لتلبية متطلبات الهندسة المترولوجية القانونية الصارمة في قطاع النفط والغاز الجزائري.

* **نقاط القوة المعمارية:**
  1. **الالتزام المطلق بالمعايير الدولية والقانونية:** تطبيق دقيق لمعادلات IEC 60751 ومعايير OIML R 140 / R 117 بدون أي تبسيط مخل.
  2. **الحقن الآلي للبنية التحتية للفحص:** إنشاء التقرير يولد تلقائيًا مصفوفة الفحص الكاملة لجميع الأجهزة دون الحاجة لتدخل بشري لتحديد النقاط.
  3. **دعم بيئات العمل الميدانية بدون إنترنت (Offline-First Capability):** من خلال دورة التصدير والاستيراد المتقدمة لملفات الإكسيل متعددة الأوراق مع إعادة الحساب والتدقيق الآلي.
  4. **وثائق PDF رسمية عالية الجودة:** مصممة بمعايير طباعية دقيقة (Landscape A4) تتضمن شارات الاعتماد وتواقيع الأطراف الثلاثية (المفتش، مسؤول القياس، والزبون).

* **التوصيات المستقبلية للمطور الجديد:**
  * احترام دورة الحذف والتهيئة التلقائية داخل أحداث `Report::booted()`.
  * عدم المساس بمعادلات ومحددات الـ EMT داخل `OamMetrologyService` دون مرجعية قانونية موثقة من ديوان المترولوجيا OAM.
  * الانتباه إلى أن إضافة أي حقول جديدة في جداول نقاط المعايرة يتطلب تحديثًا متزامنًا في ثلاثة ملفات رئيسية: متحكم الإدخال `CalibrationInstrumentsController`، محرك الاستيراد `ReportDataImport`، وقوالب الـ PDF في `resources/views/pdf/`.
