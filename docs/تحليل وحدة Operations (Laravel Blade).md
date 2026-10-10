# تحليل وحدة Operations (Laravel Blade)

Oct 8, 2026 · @farme 06

## نظرة عامة

الملف عبارة عن 25 قالب Blade (حوالي 7,900 سطر) لوحدة عمليات ميدانية تشمل البعثات والعقود والملحقات (PV و BL) وأنواع المواد والضمانات البنكية.

الواجهة مبنية بـ Tailwind وAlpine.js، والصلاحيات عبر `@can`. التحليل اقتصر على القوالب: لم أرَ الـ controllers ولا الـ models ولا الـ routes، لذلك البنود المرتبطة بها مذكورة كـ «تحقق من كذا» ولا أجزم بها.

## مشاكل عالية الأولوية (مؤكدة من الكود)

### ثغرات XSS

- `missions/create.blade.php:286,444` (وما يقابلها في `edit:287,445`): الاسم يُحقن داخل نص JS في `x-show` هكذا: `'{{ strtolower($employee->full_name …) }}'`. أي اسم فيه apostrophe يكسر الشرط (مثل M'hamed أو "d'étalonnage")، ويمكن لمن يملك صلاحية تعديل الأسماء حقن JS. الحل: استخدم البيانات الموجودة أصلاً في `allEmployees`، أو `@js()`.
- `contracts/create` و`contracts/edit`: `value="${data.designation}"` داخل `innerHTML` (مثلاً `edit:190`)، وكذلك `${t.designation}`. هذا stored DOM-XSS. ابنِ العناصر بـ DOM أو بـ `x-for`.

### أعطال تظهر بعد فشل التحقق (validation)

- `missions/create:34` و`edit:35`: `chiefId: {{ old('chief_id', …) }}` يُطبع بلا اقتباس. إذا كانت القيمة فارغة يصبح الكود `chiefId: ,` فيتعطل مكوّن Alpine كله. استخدم `Js::from`.
- `article-types:45` و`warranties:51`: `editFormAction: ''`. عند فشل التعديل يُعاد فتح المودال، وإعادة الإرسال تذهب لعنوان خاطئ (405).
- `orders/edit.blade.php` لا يعرض أي أخطاء تحقق إطلاقاً (لا يحتوي `$errors`).

### قد يستهدف سجلاً خاطئاً

- `warranties:212,224` و`article-types:157,169`: `array_merge(['warranty'=>$id], request()->query())`. الـ query تطغى على معرّف السجل، فرابط مثل `?warranty=7` يوجّه أزرار التعديل والحذف إلى السجل 7 بينما المودال يعرض اسم صف آخر. اعكس ترتيب الدمج.

### المستندات المطبوعة

- `orders/print`: إذا لم تُحدّد مركبة ولا "كل المركبات"، يطبع «Tous les véhicules» (سطر 264) — تفويض غير مقصود. «Date de retour» ثابتة «Fin de mission» وتتجاهل `ended_at` الذي يُعدّل في النموذج. خانة «Motif» تُقتطع بـ ellipsis مع `max-width:0` (سطر 249).
- `print_single:265` يطبع 18 سطراً كحد أقصى، و`print_singleBL:262` يطبع 16. ما زاد يُحذف بصمت من وثيقة محاسبية.
- في PV، الكمية صفر تُطبع «Forfait» (سطر 291). في BL تُطبع الكمية المخططة بدل الفعلية (سطر 283).
- `equipments-list:514` فيه `class` مكرر مع `display:none`، لذلك «Cert. OK» لا يظهر في الطباعة أبداً، وعمود الشهادة نفسه `no-print`.
- `equipments-list:495` و`show:286,289`: `->category?->label()` على قيمة قد تكون string يسبب Fatal. الكود في أعلى نفس الملف يعترف أنها قد تكون string.
- قوالب الطباعة تستخدم `$contract` ولا تعرّفه (على عكس `show`)، فيجب أن يمررها الـ controller.

## سلامة البيانات المالية (تحقق من الـ controllers)

هذه البنود مستندة إلى ما تظهره القوالب فقط، وتحتاج تأكيداً من المنطق الخلفي.

- **تسعير الملاحق:** أسطر الملحق تخزّن `contract_item_id` والكميات فقط، والسعر يُقرأ من بند العقد الحالي. تعديل سعر بند عقد (تسمح به `contracts/edit`) يعيد تسعير الملحقات المعتمدة. احفظ لقطة للسعر.
- **الاعتماد المباشر:** `attachments/create:145` يسمح بإنشاء ملحق «Approved» مباشرة. الاعتماد والإرجاع والتعديل كلها تحت صلاحية `edit attachments`، والتعديل متاح حتى بعد الاعتماد.
- **مرجع الملحق:** حقل `code_ref` قابل للتعديل في `edit` وللقراءة فقط في `create`.
- **صلاحيات الإحصاءات:** صفحتا الإحصاءات (الربح، أجور الموظفين اليومية، مصاريف المكتب) بلا `@can`. تأكد أن الـ route محمي.

## أخطاء وظيفية أصغر

- `missions/statistics:51` يعرض `contract_number ?? title`. العقود تستخدم `reference`، فالقيمة غالباً فارغة.
- `attachments/show:69` يعرض `attachment_number ?? title`، فاسم الحذف في المودال فارغ. الحقل الصحيح `code_ref`.
- صفحات العقود تقرأ `$warranty->bank` بينما صفحة الضمانات تستخدم `bank_name`. اسم البنك لن يظهر في العقود.
- بنود العقد والملحق لا تُستعاد من `old('items')` بعد فشل التحقق.
- تغيير «Nature» أو «Billing Cycle» في `attachments/create` يصفّر الكميات المُدخلة، لأنه يستدعي `autoFillItems()`.
- `contracts.blade.php` و`missions.blade.php` صفحات stub بجدول فارغ ثابت، بينما `attachments.blade.php` يعمل `@include` للـ index. وعدّادات `index.blade.php` ثابتة على `0`.
- العملة: `DA` (28 مرة) و`DZD` (18) و`DA/j` مختلطة، وتنسيق الأرقام يختلف بين الصفحات.
- `orders/edit`: إلغاء تحديد خانة `all_vehicles` مع فشل التحقق يعيد تحديدها بسبب `old()`.
- `missions/create:24-30` يرمي نماذج `contracts` و`sites` و`vehicles` كاملة إلى المتصفح. مرّر الحقول اللازمة فقط.
- خيارات المواقع المخفية بـ `class="hidden"` لا تُخفى في Safari، فتتجاوز فلترة العميل.
- `missions/show:102`: `map_link` في `href` بدون التحقق من الـ scheme (خطر `javascript:`).

## ملاحظات تنظيمية

- نماذج create وedit شبه متطابقة، ويمكن دمجها في partial.
- هناك منطق في الـ view (مثل `statistics:425`)، وبدائل `?? $contract->method()` قد تكرر الاستعلامات.
- مفاتيح الإحصاءات غير متسقة (`total_depenses` مقابل `total_expenses`).
- اسم GMTM ثابت في 6 ملفات، و«Tax Rate: 0%» ثابتة.
- `article-types` و`warranties`: حدث `-inner` لا يستمع له أحد، وإرسال مزدوج للحدث نفسه.
- خطر N+1 في قائمة الملحقات (`items->count()` وaccessor `total_amount`).

## الخطوات التالية

1. إرفاق الـ controllers والـ models والـ routes لتأكيد البنود المعلّقة.
2. إصلاح ثغرات XSS أولاً.
3. إصلاح أعطال الـ validation.
4. إصلاح قوالب الطباعة.
