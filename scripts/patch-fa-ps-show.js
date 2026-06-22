import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');

const en = JSON.parse(fs.readFileSync(path.join(root, 'lang/en.json'), 'utf8'));
const fa = JSON.parse(fs.readFileSync(path.join(root, 'lang/fa.json'), 'utf8'));
const ps = JSON.parse(fs.readFileSync(path.join(root, 'lang/ps.json'), 'utf8'));

/** Show-page keys (archive, bidding, HR, payroll, org, project) with Dari and Pashto values. */
const translations = {
    'Move this document to long-term archive? It will no longer appear in the active list.': {
        fa: 'این سند به آرشیو بلندمدت منتقل شود؟ دیگر در فهرست فعال نمایش داده نمی‌شود.',
        ps: 'دا سند د اوږدمهالته آرشیف ته ولاړ شي؟ نور به په فعال لیست کې نه ښکاري.',
    },
    'Archived document': {
        fa: 'سند آرشیوشده',
        ps: 'آرشیف شوی سند',
    },
    'Back to list': {
        fa: 'بازگشت به فهرست',
        ps: 'لیست ته بېرته',
    },
    'Move to long-term archive': {
        fa: 'انتقال به آرشیو بلندمدت',
        ps: 'اوږدمهالته آرشیف ته ولیږد',
    },
    'Document details': {
        fa: 'جزئیات سند',
        ps: 'د سند جزئیات',
    },
    'Metadata and linked records': {
        fa: 'فراداده و سوابق مرتبط',
        ps: 'میټاډیټا او تړلي ریکارډونه',
    },
    'Document date': {
        fa: 'تاریخ سند',
        ps: 'د سند نیټه',
    },
    Received: {
        fa: 'دریافت‌شده',
        ps: 'ترلاسه شوی',
    },
    Sent: {
        fa: 'ارسال‌شده',
        ps: 'لیږل شوی',
    },
    'Uploaded by': {
        fa: 'بارگذاری‌شده توسط',
        ps: 'پورته شوی له خوا',
    },
    File: {
        fa: 'فایل',
        ps: 'فایل',
    },
    'Edit document': {
        fa: 'ویرایش سند',
        ps: 'سند سم کړئ',
    },
    'Update metadata or replace the file': {
        fa: 'به‌روزرسانی فراداده یا جایگزینی فایل',
        ps: 'میټاډیټا تازه کړئ یا فایل بدل کړئ',
    },
    Incoming: {
        fa: 'ورودی',
        ps: 'راتلونکی',
    },
    Outgoing: {
        fa: 'خروجی',
        ps: 'وتونکی',
    },
    Internal: {
        fa: 'داخلی',
        ps: 'داخلي',
    },
    'Replace file': {
        fa: 'جایگزینی فایل',
        ps: 'فایل بدل کړئ',
    },
    'Winning Amount': {
        fa: 'مبلغ برنده',
        ps: 'د بریا مقدار',
    },
    'Line Items': {
        fa: 'ردیف‌ها',
        ps: 'کرښې',
    },
    'No line items recorded.': {
        fa: 'ردیفی ثبت نشده است.',
        ps: 'هیڅ کرښه ثبت شوې نه ده.',
    },
    Qty: {
        fa: 'تعداد',
        ps: 'شمېر',
    },
    Total: {
        fa: 'مجموع',
        ps: 'ټول',
    },
    'Competitor Intelligence': {
        fa: 'اطلاعات رقبا',
        ps: 'د سیالانو معلومات',
    },
    'Known competitor bids for this opportunity': {
        fa: 'پیشنهادهای شناخته‌شده رقبا برای این فرصت',
        ps: 'د دې فرصت لپاره د پیژندل شویو سیالانو وړاندیزونه',
    },
    'Loss Details': {
        fa: 'جزئیات باخت',
        ps: 'د بریالي نه کېدو جزئیات',
    },
    'Winner:': {
        fa: 'برنده:',
        ps: 'برېاب:',
    },
    'Reason:': {
        fa: 'دلیل:',
        ps: 'سبب:',
    },
    Published: {
        fa: 'منتشرشده',
        ps: 'خپور شوی',
    },
    'Estimated value': {
        fa: 'ارزش تخمینی',
        ps: 'اټکل شوی ارزښت',
    },
    Duration: {
        fa: 'مدت',
        ps: 'موده',
    },
    ':count months': {
        fa: ':count ماه',
        ps: ':count میاشتې',
    },
    'No scope defined.': {
        fa: 'محدوده‌ای تعریف نشده است.',
        ps: 'هیڅ ساحه تعریف شوې نه ده.',
    },
    'Bids submitted for this opportunity': {
        fa: 'پیشنهادهای ارسالی برای این فرصت',
        ps: 'د دې فرصت لپاره سپارل شوي وړاندیزونه',
    },
    'No bids yet.': {
        fa: 'هنوز پیشنهادی وجود ندارد.',
        ps: 'تر اوسه هیڅ وړاندیز نشته.',
    },
    'Contractor profile and agreements': {
        fa: 'پروفایل و قراردادهای پیمانکار',
        ps: 'د قراردادکار پروفایل او تړونونه',
    },
    'Personal Information': {
        fa: 'اطلاعات شخصی',
        ps: 'شخصي معلومات',
    },
    Agreements: {
        fa: 'توافق‌نامه‌ها',
        ps: 'تړونونه',
    },
    'Contractor service agreements': {
        fa: 'توافق‌نامه‌های خدماتی پیمانکار',
        ps: 'د قراردادکار د خدمت تړونونه',
    },
    'No agreements on file.': {
        fa: 'توافق‌نامه‌ای در پرونده نیست.',
        ps: 'په ریکارډ کې هیڅ تړون نشته.',
    },
    Agreement: {
        fa: 'توافق‌نامه',
        ps: 'تړون',
    },
    Rates: {
        fa: 'نرخ‌ها',
        ps: 'نرخونه',
    },
    'Project-specific billing rates': {
        fa: 'نرخ‌های صورتحساب مخصوص پروژه',
        ps: 'د پروژې ځانګړي بل نرخونه',
    },
    'No rates configured.': {
        fa: 'نرخی پیکربندی نشده است.',
        ps: 'هیڅ نرخ ترتیب شوی نه دی.',
    },
    Daily: {
        fa: 'روزانه',
        ps: 'ورځنی',
    },
    Monthly: {
        fa: 'ماهانه',
        ps: 'میاشتنی',
    },
    Effective: {
        fa: 'مؤثر',
        ps: 'نافذ',
    },
    General: {
        fa: 'عمومی',
        ps: 'عمومي',
    },
    Employment: {
        fa: 'استخدام',
        ps: 'دنده',
    },
    'Salary Grade': {
        fa: 'درجه حقوق',
        ps: 'د معاش درجه',
    },
    'Salary History': {
        fa: 'سابقه حقوق',
        ps: 'د معاش تاریخچه',
    },
    'No salary records.': {
        fa: 'سابقه حقوقی ثبت نشده است.',
        ps: 'د معاش ریکارډ نشته.',
    },
    Contracts: {
        fa: 'قراردادها',
        ps: 'قراردادونه',
    },
    'Employment contract records': {
        fa: 'سوابق قرارداد استخدام',
        ps: 'د دندې د قرارداد ریکارډونه',
    },
    'No contracts on file.': {
        fa: 'قراردادی در پرونده نیست.',
        ps: 'په ریکارډ کې هیڅ قرارداد نشته.',
    },
    Contract: {
        fa: 'قرارداد',
        ps: 'قرارداد',
    },
    Bonus: {
        fa: 'پاداش',
        ps: 'جایزه',
    },
    Deduction: {
        fa: 'کسر',
        ps: 'کسر',
    },
    Advance: {
        fa: 'پیش‌پرداخت',
        ps: 'مخکینۍ تادیه',
    },
    'Review payroll line items generated from approved attendance': {
        fa: 'بررسی ردیف‌های حقوق تولیدشده از حضور و غیاب تأییدشده',
        ps: 'د تایید شوې حاضرۍ څخه جوړ شوي د معاش کرښې وګورئ',
    },
    'View attendance': {
        fa: 'مشاهده حضور و غیاب',
        ps: 'حاضري وګورئ',
    },
    'Manage adjustments': {
        fa: 'مدیریت تعدیلات',
        ps: 'سمونونه اداره کړئ',
    },
    'Process payroll': {
        fa: 'پردازش حقوق',
        ps: 'معاش پروسس کړئ',
    },
    'Approved attendance': {
        fa: 'حضور و غیاب تأییدشده',
        ps: 'تایید شوې حاضري',
    },
    'Records for :period ready for payroll': {
        fa: 'سوابق :period آماده پردازش حقوق',
        ps: 'د :period ریکارډونه د معاش لپاره چمتو دي',
    },
    'Line items': {
        fa: 'ردیف‌ها',
        ps: 'کرښې',
    },
    'Personnel entries in this payroll run': {
        fa: 'ورودی‌های پرسنل در این اجرای حقوق',
        ps: 'په دې معاش پروسه کې د پرسونل ثبتونه',
    },
    'Total net pay': {
        fa: 'مجموع حقوق خالص',
        ps: 'ټول خالص معاش',
    },
    'Combined net amount for this run': {
        fa: 'مجموع مبلغ خالص این اجرا',
        ps: 'د دې پروسې ټول خالص مقدار',
    },
    'Pending adjustments': {
        fa: 'تعدیلات در انتظار',
        ps: 'پاتې سمونونه',
    },
    'Bonus, deductions, and advances recorded for :period are applied automatically when you process this run.': {
        fa: 'پاداش، کسرها و پیش‌پرداخت‌های ثبت‌شده برای :period هنگام پردازش این اجرا به‌طور خودکار اعمال می‌شوند.',
        ps: 'د :period لپاره ثبت شوي جایزې، کسرونه او مخکینۍ تادیات کله چې دا پروسه پروسس کړئ په اوتومات ډول پلي کیږي.',
    },
    'No adjustments recorded for this month yet. Add advances, bonuses, or deductions before processing payroll.': {
        fa: 'هنوز تعدیلی برای این ماه ثبت نشده است. قبل از پردازش حقوق، پیش‌پرداخت، پاداش یا کسر اضافه کنید.',
        ps: 'تر اوسه د دې میاشتې لپاره سمون ثبت شوی نه دی. مخکې له معاش پروسس څخه مخکینۍ تادیات، جایزې یا کسرونه زیات کړئ.',
    },
    'Add or edit adjustments': {
        fa: 'افزودن یا ویرایش تعدیلات',
        ps: 'سمونونه زیات یا سم کړئ',
    },
    'Next step': {
        fa: 'گام بعدی',
        ps: 'راتلونکی ګام',
    },
    'Approve attendance, record any bonus/deduction/advance for :period, then process this run.': {
        fa: 'حضور و غیاب را تأیید کنید، هر پاداش/کسر/پیش‌پرداخت برای :period را ثبت کنید، سپس این اجرا را پردازش کنید.',
        ps: 'حاضري تایید کړئ، د :period لپاره هر جایزه/کسر/مخکینۍ تادیه ثبت کړئ، بیا دا پروسه پروسس کړئ.',
    },
    'You currently have': {
        fa: 'در حال حاضر دارید',
        ps: 'اوس تاسو لرئ',
    },
    'approved attendance': {
        fa: 'حضور و غیاب تأییدشده',
        ps: 'تایید شوې حاضري',
    },
    record: {
        fa: 'سابقه',
        ps: 'ریکارډ',
    },
    records: {
        fa: 'سوابق',
        ps: 'ریکارډونه',
    },
    and: {
        fa: 'و',
        ps: 'او',
    },
    adjustment: {
        fa: 'تعدیل',
        ps: 'سمون',
    },
    adjustments: {
        fa: 'تعدیلات',
        ps: 'سمونونه',
    },
    Click: {
        fa: 'کلیک کنید',
        ps: 'کلیک وکړئ',
    },
    'when you are ready to calculate amounts.': {
        fa: 'وقتی آماده محاسبه مبالغ هستید.',
        ps: 'کله چې د مقدارونو محاسبه لپاره چمتو یاست.',
    },
    'No line items generated': {
        fa: 'ردیفی تولید نشد',
        ps: 'هیڅ کرښه جوړه شوې نه ده',
    },
    'This run was processed, but no approved attendance existed for :period.': {
        fa: 'این اجرا پردازش شد، اما حضور و غیاب تأییدشده‌ای برای :period وجود نداشت.',
        ps: 'دا پروسه پروسس شوه، خو د :period لپاره تایید شوې حاضري نه وه.',
    },
    'Record attendance, approve it, then create a new payroll run or re-process if your workflow allows it.': {
        fa: 'حضور و غیاب را ثبت کنید، تأیید کنید، سپس اجرای حقوق جدید ایجاد کنید یا در صورت امکان دوباره پردازش کنید.',
        ps: 'حاضري ثبت کړئ، تایید یې کړئ، بیا نوې معاش پروسه جوړه کړئ یا که ستاسو کاري جریان اجازه ورکوي بیا پروسس کړئ.',
    },
    'Go to attendance': {
        fa: 'رفتن به حضور و غیاب',
        ps: 'حاضري ته لاړ شئ',
    },
    'Payroll processed': {
        fa: 'حقوق پردازش شد',
        ps: 'معاش پروسس شو',
    },
    ':count line items · Total net :amount': {
        fa: ':count ردیف · مجموع خالص :amount',
        ps: ':count کرښې · ټول خالص :amount',
    },
    'Line items appear after processing; amounts include pending adjustments for this month': {
        fa: 'ردیف‌ها پس از پردازش نمایش داده می‌شوند؛ مبالغ شامل تعدیلات در انتظار این ماه است',
        ps: 'کرښې د پروسس وروسته ښکاري؛ مقدارونه د دې میاشتې پاتې سمونونه شامل دي',
    },
    'Amounts from attendance; net = base + bonus − deductions − advance': {
        fa: 'مبالغ از حضور و غیاب؛ خالص = پایه + پاداش − کسرها − پیش‌پرداخت',
        ps: 'مقدارونه له حاضرۍ؛ خالص = بنسټ + جایزه − کسرونه − مخکینۍ تادیه',
    },
    'Line items will appear here after you process this run.': {
        fa: 'ردیف‌ها پس از پردازش این اجرا اینجا نمایش داده می‌شوند.',
        ps: 'کرښې به د دې پروسې پروسس وروسته دلته ښکاره شي.',
    },
    'No payroll amounts were calculated for this period.': {
        fa: 'مبلغ حقوقی برای این دوره محاسبه نشد.',
        ps: 'د دې مودې لپاره معاش محاسبه شوی نه دی.',
    },
    'Bonus total:': {
        fa: 'مجموع پاداش:',
        ps: 'ټول جایزه:',
    },
    'Deductions total:': {
        fa: 'مجموع کسرها:',
        ps: 'ټول کسرونه:',
    },
    'Advance total:': {
        fa: 'مجموع پیش‌پرداخت:',
        ps: 'ټول مخکینۍ تادیه:',
    },
    Base: {
        fa: 'پایه',
        ps: 'بنسټ',
    },
    Deductions: {
        fa: 'کسرها',
        ps: 'کسرونه',
    },
    Net: {
        fa: 'خالص',
        ps: 'خالص',
    },
    Save: {
        fa: 'ذخیره',
        ps: 'خوندي کړئ',
    },
    'Organization profile': {
        fa: 'پروفایل سازمان',
        ps: 'د سازمان پروفایل',
    },
    Summary: {
        fa: 'خلاصه',
        ps: 'لنډیز',
    },
    Contacts: {
        fa: 'مخاطبین',
        ps: 'اړیکې',
    },
    Primary: {
        fa: 'اصلی',
        ps: 'اصلي',
    },
    'Procurement opportunities': {
        fa: 'فرصت‌های تدارکات',
        ps: 'د تدارکاتو فرصتونه',
    },
    'RFPs and tenders from this organization': {
        fa: 'درخواست‌های پیشنهاد و مناقصه از این سازمان',
        ps: 'د دې سازمان RFP او مناقصې',
    },
    'Deadline:': {
        fa: 'مهلت:',
        ps: 'وروستۍ نیټه:',
    },
    'Not set': {
        fa: 'تنظیم نشده',
        ps: 'ټاکل شوی نه دی',
    },
    'No opportunities recorded yet.': {
        fa: 'هنوز فرصتی ثبت نشده است.',
        ps: 'تر اوسه هیڅ فرصت ثبت شوی نه دی.',
    },
    'Won contracts and active service delivery': {
        fa: 'قراردادهای برنده و ارائه خدمات فعال',
        ps: 'برېابي قراردادونه او فعاله خدمت رسونه',
    },
    'No projects linked yet.': {
        fa: 'هنوز پروژه‌ای مرتبط نشده است.',
        ps: 'تر اوسه هیڅ پروژه تړلې نه ده.',
    },
    'Scope summary': {
        fa: 'خلاصه محدوده',
        ps: 'د ساحې لنډیز',
    },
    'Delete ":name"? This cannot be undone.': {
        fa: '«:name» حذف شود؟ این عمل قابل بازگشت نیست.',
        ps: '«:name» حذف شي؟ دا بیرته نشي راګرځیدلی.',
    },
    'Delete issue': {
        fa: 'حذف مشکل',
        ps: 'ستونزه حذف کړئ',
    },
    'Project info': {
        fa: 'اطلاعات پروژه',
        ps: 'د پروژې معلومات',
    },
    'Contract (when won)': {
        fa: 'قرارداد (در صورت برد)',
        ps: 'قرارداد (کله چې برېابي)',
    },
    'Contract value': {
        fa: 'ارزش قرارداد',
        ps: 'د قرارداد ارزښت',
    },
    'Loss details': {
        fa: 'جزئیات باخت',
        ps: 'د بریالي نه کېدو جزئیات',
    },
    'Our bid details': {
        fa: 'جزئیات پیشنهاد ما',
        ps: 'زموږ د وړاندیز جزئیات',
    },
    'Update pricing and scope — saved on this project only': {
        fa: 'به‌روزرسانی قیمت و محدوده — فقط در این پروژه ذخیره می‌شود',
        ps: 'نرخ او ساحه تازه کړئ — یوازې په دې پروژه کې خوندي کیږي',
    },
    'Save bid details': {
        fa: 'ذخیره جزئیات پیشنهاد',
        ps: 'د وړاندیز جزئیات خوندي کړئ',
    },
    'Other bidders (optional)': {
        fa: 'سایر پیشنهاددهندگان (اختیاری)',
        ps: 'نور وړاندیز کوونکي (اختیاري)',
    },
    '(estimated)': {
        fa: '(تخمینی)',
        ps: '(اټکل شوی)',
    },
    'Add competitor': {
        fa: 'افزودن رقیب',
        ps: 'سیال زیات کړئ',
    },
    'Company name': {
        fa: 'نام شرکت',
        ps: 'د شرکت نوم',
    },
    Add: {
        fa: 'افزودن',
        ps: 'زیات کړئ',
    },
    'Add income': {
        fa: 'افزودن درآمد',
        ps: 'عاید زیات کړئ',
    },
    Receipt: {
        fa: 'رسید',
        ps: 'رسید',
    },
    'Add expense': {
        fa: 'افزودن هزینه',
        ps: 'لګښت زیات کړئ',
    },
    'Recent entries': {
        fa: 'ورودی‌های اخیر',
        ps: 'وروستي ثبتونه',
    },
    'Activity timeline': {
        fa: 'جدول زمانی فعالیت',
        ps: 'د فعالیت مهال ویش',
    },
    'Track problems and resolutions for this project': {
        fa: 'پیگیری مشکلات و راه‌حل‌های این پروژه',
        ps: 'د دې پروژې ستونزې او حلونه تعقیب کړئ',
    },
    'Report issue': {
        fa: 'گزارش مشکل',
        ps: 'ستونزه راپور کړئ',
    },
    'Issue title': {
        fa: 'عنوان مشکل',
        ps: 'د ستونزې سرلیک',
    },
    Low: {
        fa: 'کم',
        ps: 'کم',
    },
    Medium: {
        fa: 'متوسط',
        ps: 'منځنی',
    },
    High: {
        fa: 'زیاد',
        ps: 'لوړ',
    },
    Critical: {
        fa: 'بحرانی',
        ps: 'بحراني',
    },
    'Add attachment': {
        fa: 'افزودن پیوست',
        ps: 'ضمیمه زیات کړئ',
    },
    Upload: {
        fa: 'بارگذاری',
        ps: 'پورته کړئ',
    },
    'Edit income': {
        fa: 'ویرایش درآمد',
        ps: 'عاید سم کړئ',
    },
    'Edit expense': {
        fa: 'ویرایش هزینه',
        ps: 'لګښت سم کړئ',
    },
    'Edit issue': {
        fa: 'ویرایش مشکل',
        ps: 'ستونزه سم کړئ',
    },
    Severity: {
        fa: 'شدت',
        ps: 'شدت',
    },
    'In progress': {
        fa: 'در حال انجام',
        ps: 'پرمختګ کې',
    },
    Resolved: {
        fa: 'حل‌شده',
        ps: 'حل شوی',
    },
};

function isStillEnglish(key, value, enValue) {
    return value === key || value === enValue;
}

let faUpdated = 0;
let psUpdated = 0;
let faSkipped = 0;
let psSkipped = 0;

for (const [key, { fa: faVal, ps: psVal }] of Object.entries(translations)) {
    if (!(key in en)) {
        console.warn(`Warning: key missing from en.json: ${JSON.stringify(key)}`);
        continue;
    }

    const enValue = en[key];

    if (key in fa) {
        if (isStillEnglish(key, fa[key], enValue)) {
            fa[key] = faVal;
            faUpdated++;
        } else {
            faSkipped++;
        }
    }

    if (key in ps) {
        if (isStillEnglish(key, ps[key], enValue)) {
            ps[key] = psVal;
            psUpdated++;
        } else {
            psSkipped++;
        }
    }
}

fs.writeFileSync(path.join(root, 'lang/fa.json'), `${JSON.stringify(fa, null, 4)}\n`);
fs.writeFileSync(path.join(root, 'lang/ps.json'), `${JSON.stringify(ps, null, 4)}\n`);

console.log(`fa.json: updated ${faUpdated} keys, skipped ${faSkipped} (already translated)`);
console.log(`ps.json: updated ${psUpdated} keys, skipped ${psSkipped} (already translated)`);
