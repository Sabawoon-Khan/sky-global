import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const faPath = path.join(__dirname, '..', 'lang', 'fa.json');
const data = JSON.parse(fs.readFileSync(faPath, 'utf8'));

const phraseReplacements = [
    ['حقوق و دستمزد', 'معاش'],
    ['به‌روزرسانی', 'تازه‌سازی'],
    ['علامت‌گذاری', 'نشان‌گذاری'],
    ['غیرفعال‌سازی', 'غیرفعال کردن'],
    ['فعال‌سازی مجدد', 'دوباره فعال کردن'],
    ['فعال‌سازی', 'فعال کردن'],
    ['آرشیوشده', 'بایگانی‌شده'],
    ['آرشیو بلندمدت', 'بایگانی بلندمدت'],
    ['آرشیو اسناد', 'بایگانی اسناد'],
    ['در آرشیو', 'در بایگانی'],
    ['آرشیو', 'بایگانی'],
    ['فراداده', 'اطلاعات تکمیلی'],
    ['پیکربندی‌شده', 'تنظیم‌شده'],
    ['پیکربندی', 'تنظیم'],
    ['جایگزینی', 'تبدیل'],
    ['افزودن', 'اضافه کردن'],
    ['بارگذاری‌شده', 'آپلود شده'],
    ['بارگذاری شده', 'آپلود شده'],
    ['بارگذاری', 'آپلود'],
    ['دانلود', 'بارگیری'],
    ['فاکتورها', 'بل‌ها'],
    ['فاکتور', 'بل'],
    ['هزینه‌های', 'مصارف'],
    ['هزینه‌ها', 'مصارف'],
    ['درآمد', 'عاید'],
    ['متأسفانه', 'متاسفانه'],
    ['جابه‌جا', 'منتقل'],
    ['ماژولار', 'جداگانه'],
    ['مشاهده', 'نمایش'],
    ['تأیید', 'تایید'],
    ['فهرست', 'لیست'],
    ['سکو', 'بستر'],
    ['در برنامه', 'در اپلیکیشن'],
    ['پردازش', 'پروسس'],
    ['حضور و غیاب', 'حاضری'],
];

function convertSalaryTerms(text) {
    if (text.includes('تمامی حقوق محفوظ')) {
        return text;
    }

    return text
        .replace(/اجرای ماهانه حقوق/g, 'اجرای ماهانه معاش')
        .replace(/اجرای حقوق/g, 'اجرای معاش')
        .replace(/پردازش حقوق/g, 'پروسس معاش')
        .replace(/حقوق پروسس/g, 'معاش پروسس')
        .replace(/تعدیلات حقوق/g, 'تعدیلات معاش')
        .replace(/درجه حقوق/g, 'درجه معاش')
        .replace(/سابقه حقوق/g, 'سابقه معاش')
        .replace(/ردیف‌های حقوق/g, 'ردیف‌های معاش')
        .replace(/مبلغ حقوقی/g, 'مبلغ معاش')
        .replace(/تعدیل حقوقی/g, 'تعدیل معاش')
        .replace(/حقوقی/g, 'معاش')
        .replace(/حقوق /g, 'معاش ')
        .replace(/ حقوق/g, ' معاش')
        .replace(/حقوق$/g, 'معاش');
}

function convertExpenseTerms(text) {
    return text
        .replace(/مصارف سربار/g, 'مصارف عمومی')
        .replace(/سابقه مصارف/g, 'سابقه مصرف')
        .replace(/حذف سابقه مصارف/g, 'حذف سابقه مصرف')
        .replace(/\bهزینه\b/g, 'مصرف');
}

function convert(text) {
    if (typeof text !== 'string') {
        return text;
    }

    let result = text;
    for (const [from, to] of phraseReplacements) {
        result = result.split(from).join(to);
    }

    result = convertSalaryTerms(result);
    result = convertExpenseTerms(result);
    result = result.replace(/معاش و دستمزد/g, 'معاش');
    result = result.replace(/پروسس پروسس/g, 'پروسس');

    return result;
}

const overrides = {
    Administration: 'اداره',
    Platform: 'بستر',
    'Manage your profile and account settings':
        'پروفایل و تنظیمات حساب خود را اداره کنید',
    Password: 'پاسورد',
    'Confirm password': 'تایید پاسورد',
    'Log in': 'داخل شدن',
    'Sign in': 'داخل شدن',
    'Log out': 'خارج شدن',
    Filter: 'فلتر',
    Click: 'کلیک',
    'Management Information System': 'سیستم مدیریت معلومات',
    'Run your operations from a single, secure platform':
        'عملیات خود را از یک بستر واحد و امن اداره کنید',
    'Modular tools that work together across your entire workflow.':
        'ابزارهای جداگانه که در تمام گردش کار شما با هم کار می‌کنند.',
    'Manage clients, partners, and organization profiles in one place.':
        'مراجع، شریک‌ها و پروفایل‌های سازمان را در یک جا اداره کنید.',
    Client: 'مراجع',
    'Register clients, partners, and procurement bodies':
        'ثبت مراجع، شریک‌ها و نهادهای تدارکات',
    'Register a client, partner, or procurement body with full details':
        'ثبت مراجع، شریک یا نهاد تدارکات با جزئیات کامل',
    'Client billing and payment status': 'بل مراجع و وضعیت پرداخت',
    'Client complaint': 'شکایت مراجع',
    'Client & project': 'مراجع و پروژه',
    'Projects and contract value by client type':
        'پروژه‌ها و ارزش قرارداد بر اساس نوع مراجع',
    'Procurement requests and tenders from clients':
        'درخواست‌های تدارکات و مناقصه از مراجع',
    'Add government bodies, NGOs, private companies, and other clients you bid to or serve.':
        'نهادهای دولتی، موسسات، شرکت‌های خصوصی و سایر مراجعی که برایشان مناقصه می‌دهید یا خدمت می‌کنید را اضافه کنید.',
    'Overhead & Salaries': 'مصارف عمومی و معاش‌ها',
    'Office rent, salaries, utilities — not tied to a project':
        'کرایه دفتر، معاش‌ها، خدمات — مرتبط با پروژه نیست',
    'Office Rent': 'کرایه دفتر',
    Utilities: 'خدمات',
    Salary: 'معاش',
    'Salary & Contracts': 'معاش و قراردادها',
    'Salary Grade': 'درجه معاش',
    'Salary History': 'سابقه معاش',
    'No salary records.': 'سابقه معاش ثبت نشده است.',
    'Project finance, overhead, salaries, and other income':
        'مالی پروژه، مصارف عمومی، معاش‌ها و سایر عایدات',
    'All rights reserved.': 'تمامی حقوق محفوظ است.',
    'Sorry, the page you are looking for does not exist or may have been moved.':
        'متاسفانه صفحه‌ای که می‌گردید وجود ندارد یا شاید منتقل شده باشد.',
    'You do not have permission to view this page. Contact your administrator if you believe this is a mistake.':
        'شما اجازه دیدن این صفحه را ندارید. اگر فکر می‌کنید اشتباه است، با مدیر سیستم تماس بگیرید.',
    Approve: 'تایید',
    'Password updated.': 'پاسورد تازه شد.',
    'Update the language used across the application.':
        'زبان مورد استفاده در اپلیکیشن را تازه کنید.',
    'Profile updated.': 'پروفایل تازه شد.',
    'Update settings for :name.': 'تنظیمات :name را تازه کنید.',
    'Update the appearance settings for your account':
        'تنظیمات ظاهر حساب خود را تازه کنید',
    'Update metadata or replace the file':
        'اطلاعات تکمیلی را تازه کنید یا فایل را عوض کنید',
    'Update Roles': 'تازه کردن نقش‌ها',
    'Role updated.': 'نقش تازه شد.',
    'Update the name and permissions for :name.':
        'نام و اجازه‌های :name را تازه کنید.',
    'Update pricing and scope — saved on this project only':
        'قیمت و محدوده را تازه کنید — فقط در این پروژه ذخیره می‌شود',
    Category: 'کتگوری',
    Department: 'ریاست',
    'Select department': 'انتخاب ریاست',
    Designation: 'بست',
    Contractors: 'قراردادی‌ها',
    contractors: 'قراردادی',
    Contractor: 'قراردادی',
    'Add Contractor': 'اضافه کردن قراردادی',
    'All Contractors': 'همه قراردادی‌ها',
    'Search contractors...': 'جستجوی قراردادی‌ها...',
    'No contractors found.': 'قراردادی یافت نشد.',
    'Manage contractor personnel and agreements':
        'اداره پرسونل و قراردادهای قراردادی‌ها',
    'Register a new contractor': 'ثبت قراردادی جدید',
    'Save contractor': 'ذخیره قراردادی',
    'Edit Contractor': 'ویرایش قراردادی',
    'Contractor profile and agreements': 'پروفایل و قراردادهای قراردادی',
    'Contractor service agreements': 'قراردادهای خدماتی قراردادی',
    'Contractor profile': 'پروفایل قراردادی',
    'Employees and contractors working on this project':
        'کارمندان و قراردادی‌های این پروژه',
    'Handle employees, contractors, payroll, and attendance records.':
        'کارمندان، قراردادی‌ها، معاش و سوابق حاضری را اداره کنید.',
    'Record a monthly attendance entry for an employee or contractor':
        'ثبت حاضری ماهانه برای کارمند یا قراردادی',
    Attendance: 'حاضری',
    'Record attendance': 'ثبت حاضری',
    'Record monthly attendance, approve records, then process payroll':
        'ثبت حاضری ماهانه، تایید سوابق و بعد پروسس معاش',
    'Monthly attendance records': 'سوابق حاضری ماهانه',
    'No attendance records for this period.':
        'سابقه حاضری برای این دوره وجود ندارد.',
    'Attendance Records': 'سوابق حاضری',
    'View attendance': 'نمایش حاضری',
    'Approved attendance': 'حاضری تایید شده',
    'Review payroll line items generated from approved attendance':
        'بررسی ردیف‌های معاش ساخته شده از حاضری تایید شده',
    'Records for :period ready for payroll':
        'سوابق :period آماده پروسس معاش',
    'Approve attendance, record any bonus/deduction/advance for :period, then process this run.':
        'حاضری را تایید کنید، هر پاداش/کسر/پیش‌پرداخت برای :period را ثبت کنید، بعد این اجرا را پروسس کنید.',
    'approved attendance': 'حاضری تایید شده',
    'Record attendance, approve it, then create a new payroll run or re-process if your workflow allows it.':
        'حاضری را ثبت کنید، تایید کنید، بعد اجرای معاش جدید بسازید یا اگر امکان دارد دوباره پروسس کنید.',
    'Go to attendance': 'رفتن به حاضری',
    'This run was processed, but no approved attendance existed for :period.':
        'این اجرا پروسس شد، اما حاضری تایید شده‌ای برای :period نبود.',
    'Amounts from attendance; net = base + bonus − deductions − advance':
        'مبالغ از حاضری؛ خالص = پایه + پاداش − کسرها − پیش‌پرداخت',
    'Bulk attendance': 'حاضری گروهی',
    'Attendance History': 'سابقه حاضری',
    'No attendance records.': 'سابقه حاضری ثبت نشده.',
    'Days present': 'روزهای حاضر',
    'Days absent': 'روزهای غایب',
    Present: 'حاضر',
    Absent: 'غایب',
    'Record many staff at once': 'ثبت حاضری چند کارمند یکجا',
    'Manage staff records and employment details':
        'اداره سوابق کارکنان و جزئیات استخدام',
    'Manage users': 'اداره کاربران',
    'User Management': 'اداره کاربران',
    'Role Management': 'اداره نقش‌ها',
    Permissions: 'اجازه‌ها',
    permission: 'اجازه',
    permissions: 'اجازه',
    'Define roles and assign permissions to control access':
        'تعریف نقش‌ها و دادن اجازه‌ها برای کنترل دسترسی',
    'Create a new role with specific permissions':
        'ایجاد نقش جدید با اجازه‌های مشخص',
    'Update the name and permissions for :name.':
        'نام و اجازه‌های :name را تازه کنید.',
    'Access denied': 'دسترسی رد شد',
    'Non-project income such as investments or grants':
        'عاید غیرپروژه‌ای مثل سرمایه‌گذاری یا کمک‌ها',
    'e.g. Grant, Investment': 'مثلاً کمک، سرمایه‌گذاری',
    'Other Income': 'سایر عایدات',
    'No other income records.': 'سابقه عاید دیگر ثبت نشده.',
    'Income Sources': 'منابع عاید',
    'Project vs other income over 6 months':
        'عاید پروژه در برابر سایر عایدات در ۶ ماه',
    'Other Income Trend': 'روند سایر عایدات',
    Overhead: 'مصارف عمومی',
    'Monitor income, expenses, overhead, and project profitability.':
        'عاید، مصارف، مصارف عمومی و سودآوری پروژه را زیر نظر بگیرید.',
    'Income, expenses, and invoices': 'عاید، مصارف و بل‌ها',
    'Track project income, expenses, and invoices':
        'پیگیری عاید، مصارف و بل‌های پروژه',
    'Total Income': 'مجموع عاید',
    'Total Expenses': 'مجموع مصارف',
    Expenses: 'مصارف',
    'Project Income': 'عاید پروژه',
    'Project Expenses': 'مصارف پروژه',
    'Operational and project costs': 'مصارف عملیاتی و پروژه‌ای',
    'No income records found.': 'سابقه عاید یافت نشد.',
    'No expense records found.': 'سابقه مصرف یافت نشد.',
    'Delete income record': 'حذف سابقه عاید',
    'Delete expense record': 'حذف سابقه مصرف',
    'Add income': 'اضافه کردن عاید',
    'Edit income': 'ویرایش عاید',
    Expense: 'مصرف',
    'Add expense': 'اضافه کردن مصرف',
    'Edit expense': 'ویرایش مصرف',
    'Revenue, expenses, and project profitability':
        'عاید، مصارف و سودآوری پروژه',
    'Income vs expense by project': 'عاید در برابر مصرف به تفکیک پروژه',
    'Project income vs expenses': 'عاید پروژه در برابر مصارف',
    'Monthly income minus expenses': 'عاید ماهانه منهای مصارف',
    'Income vs expenses over 6 months': 'عاید در برابر مصارف در ۶ ماه',
    'Project Income vs Expense': 'عاید پروژه در برابر مصرف',
    'Top Projects by Income': 'برترین پروژه‌ها بر اساس عاید',
    'Project Income Trend': 'روند عاید پروژه',
    'Project Expense Trend': 'روند مصارف پروژه',
    Income: 'عاید',
    'View finance': 'نمایش مالی',
    'View Finance': 'نمایش مالی',
    'View HR': 'نمایش منابع انسانی',
    View: 'نمایش',
    'View run details': 'نمایش جزئیات اجرا',
    'View Projects': 'نمایش پروژه‌ها',
    'View competitors': 'نمایش رقبا',
    'NGO': 'موسسه',
    'Private Company': 'شرکت خصوصی',
    'Human Resources': 'منابع بشری',
    HR: 'منابع بشری',
    'View HR': 'نمایش منابع بشری',
    'Guarantee letters, certificates, and other HR documents':
        'ضمانت‌نامه‌ها، گواهی‌ها و سایر اسناد منابع بشری',
    'Upload guarantee forms, certificates, and other HR documents':
        'آپلود ضمانت‌نامه، گواهی‌ها و سایر اسناد منابع بشری',
    'Configure HR document types such as guarantee forms, certificates, and clearances':
        'تنظیم انواع اسناد منابع بشری مانند ضمانت‌نامه، گواهی‌ها و تصدیق‌نامه‌ها',
    'Overview of bidding, projects, finance, and HR':
        'نمای کلی مناقصه، پروژه‌ها، مالی و منابع بشری',
    'Streamline bidding, projects, finance, and HR with one integrated workspace built for security and service organizations.':
        'مناقصه، پروژه‌ها، مالی و منابع بشری را با یک فضای کاری یکپارچه که برای سازمان‌های امنیتی و خدماتی ساخته شده، ساده کنید.',
    'Employee forms': 'فورم‌های کارمند',
    'Form Types': 'انواع فورم',
    'Form type': 'نوع فورم',
    'Select form type': 'انتخاب نوع فورم',
    'Add form': 'اضافه کردن فورم',
    'Upload form': 'آپلود فورم',
    'Add form type': 'اضافه کردن نوع فورم',
    'Edit form type': 'ویرایش نوع فورم',
    'Delete form type': 'حذف نوع فورم',
    'Form :number': 'فورم :number',
    Form: 'فورم',
    'e.g. Guarantee Form': 'مثلاً فورم ضمانت',
    'No form types configured.': 'هیچ نوع فورمی تنظیم نشده.',
    'No forms uploaded yet.': 'هنوز فورمی آپلود نشده.',
    'No forms added yet. Click below to attach employee forms.':
        'هنوز فورمی اضافه نشده. برای پیوست فورم‌های کارمند روی دکمه زیر کلیک کنید.',
    'No form types configured. Add types in Settings → Form Types first.':
        'هیچ نوع فورمی تنظیم نشده. ابتدا در تنظیمات ← انواع فورم اضافه کنید.',
    'form types configured': 'نوع فورم تنظیم شده',
    'Replace file': 'عوض کردن فایل',
    File: 'فایل',
    'Download file': 'بارگیری فایل',
    Upload: 'آپلود',
    'Add attachment': 'اضافه کردن پیوست',
    'Mark as archived': 'نشان‌گذاری به‌عنوان بایگانی',
    'Central repository for incoming and outgoing documents':
        'مخزن مرکزی اسناد ورودی و خروجی',
    'Are you sure you want to delete ":name"? This cannot be undone.':
        'مطمئن هستید «:name» را حذف می‌کنید؟ این کار برگشت ندارد.',
    'Delete ":name"? This cannot be undone.':
        '«:name» حذف شود؟ این کار برگشت ندارد.',
    'This cannot be undone.': 'این کار برگشت ندارد.',
    'Delete invoice :label? This cannot be undone.':
        'بل :label حذف شود؟ این کار برگشت ندارد.',
    Welcome: 'خوش آمدید',
    'Go to dashboard': 'رفتن به داشبورد',
    'Open dashboard': 'باز کردن داشبورد',
    Dashboard: 'داشبورد',
    Settings: 'تنظیمات',
    'Language settings': 'تنظیمات زبان',
    Language: 'زبان',
    'Appearance settings': 'تنظیمات ظاهر',
    'Profile settings': 'تنظیمات پروفایل',
    'Security settings': 'تنظیمات امنیت',
    Email: 'ایمیل',
    Phone: 'تلفن',
    'Tax ID': 'نمبر مالیاتی',
    'Tax / registration ID': 'نمبر مالیاتی / ثبت',
    'Tazkira number': 'نمبر تذکره',
    Province: 'ولایت',
    'Select province': 'انتخاب ولایت',
    'Full address': 'آدرس کامل',
    'Current address': 'آدرس فعلی',
    Notes: 'یادداشت',
    'Internal notes about this organization':
        'یادداشت‌های داخلی درباره این سازمان',
    'Optional reason': 'دلیل اختیاری',
    Description: 'توضیح',
    'Personal details': 'جزئیات شخصی',
    'Personal Information': 'معلومات شخصی',
    'Job details': 'جزئیات وظیفه',
    'Project info': 'معلومات پروژه',
    'Document details': 'جزئیات سند',
    Details: 'جزئیات',
    Summary: 'خلاصه',
    Overview: 'نمای کلی',
    Analytics: 'تحلیل',
    'Bidding Analytics': 'تحلیل مناقصه',
    'Finance Analytics': 'تحلیل مالی',
    Bidding: 'مناقصه',
    'Win Rate': 'نرخ بری',
    won: 'بری',
    lost: 'مات',
    Winning: 'برنده',
    'Mark won': 'نشان‌گذاری برنده',
    'Mark lost': 'نشان‌گذاری مات',
    'Won / Lost': 'بری / مات',
    'Loss Details': 'جزئیات مات',
    'Loss details': 'جزئیات مات',
    'Win Rate Overview': 'نمای کلی نرخ بری',
    'Win Rate Trend': 'روند نرخ بری',
    'Monthly win rate percentage': 'درصد نرخ بری ماهانه',
    'Competitor Intel': 'معلومات رقبا',
    'Competitor Intelligence': 'معلومات رقبا',
    'Recorded competitor bids': 'پیشنهادهای ثبت‌شده رقبا',
    Competitors: 'رقبا',
    'Add competitor': 'اضافه کردن رقیب',
    'Known competitor bids for this opportunity':
        'پیشنهادهای شناخته‌شده رقبا برای این فرصت',
    'Recorded competitor bids across projects':
        'پیشنهادهای رقبا ثبت‌شده در پروژه‌ها',
    'Security Operations': 'عملیات امنیتی',
    'Security incident': 'حادثه امنیتی',
    'Static guards': 'نگهبان ثابت',
    'Mobile patrol': 'گشت سیار',
    'What happened, how it was resolved': 'چه شد و چگونه حل شد',
    'What happened:': 'چه شد:',
    'What happened? e.g. guard killed, vehicle breakdown, client dispute...':
        'چه شد؟ مثلاً کشته شدن نگهبان، خرابی موتر، اختلاف با مراجع...',
    'What happened': 'چه شد',
    'Describe the incident or problem': 'حادثه یا مشکل را شرح دهید',
    'How we fixed it:': 'چگونه حل کردیم:',
    'How we fixed it': 'چگونه حل کردیم',
    'Describe the resolution and actions taken':
        'حل مشکل و اقدامات انجام‌شده را شرح دهید',
    'Equipment failure': 'خرابی تجهیزات',
    Equipment: 'تجهیزات',
    'Personnel / casualty': 'پرسونل / تلفات',
    'Incident Reports': 'گزارش‌های حادثه',
    'Incident type': 'نوع حادثه',
    'Report issue': 'گزارش مشکل',
    'Issue title': 'عنوان مشکل',
    Issues: 'مشکلات',
    'Edit issue': 'ویرایش مشکل',
    'Delete issue': 'حذف مشکل',
    'Track problems and resolutions for this project':
        'پیگیری مشکلات و راه‌حل‌های این پروژه',
    Resolve: 'حل',
    Resolved: 'حل‌شده',
    'Mark completed': 'نشان‌گذاری تکمیل‌شده',
    completed: 'تکمیل‌شده',
    Completed: 'تکمیل‌شده',
    'Projects Done': 'پروژه‌های تکمیل‌شده',
    Pending: 'منتظر',
    pending: 'منتظر',
    'pending bids': 'پیشنهاد منتظر',
    'Pending Bids': 'پیشنهادهای منتظر',
    'In progress': 'در جریان',
    'Mark in progress': 'نشان‌گذاری در جریان',
    Ongoing: 'در جریان',
    'Under review': 'در حال بررسی',
    Draft: 'پیش‌نویس',
    'Revert to draft': 'برگشت به پیش‌نویس',
    Suspended: 'معلق',
    Suspend: 'معلق کردن',
    Cancelled: 'لغوشده',
    Cancel: 'لغو',
    Open: 'باز',
    Closed: 'بسته',
    Active: 'فعال',
    Inactive: 'غیرفعال',
    Disabled: 'غیرفعال',
    'Mark inactive': 'نشان‌گذاری غیرفعال',
    'Mark active': 'نشان‌گذاری فعال',
    'Mark as sent': 'نشان‌گذاری ارسال‌شده',
    'Mark as paid': 'نشان‌گذاری پرداخت‌شده',
    'Mark overdue': 'نشان‌گذاری پاته',
    Outstanding: 'پاته',
    'Due Date': 'تاریخ پرداخت',
    Receipt: 'رسید',
    Invoice: 'بل',
    'Invoice #': 'نمبر بل',
    Invoices: 'بل‌ها',
    'Delete invoice': 'حذف بل',
    'Cancel invoice': 'لغو بل',
    'No invoices found.': 'بلی یافت نشد.',
    'Client billing and payment status': 'بل مراجع و وضعیت پرداخت',
    'Project-specific billing rates': 'نرخ‌های بل مخصوص پروژه',
    'Recorded payments and receipts': 'پرداخت‌ها و رسیدهای ثبت‌شده',
    Currency: 'اسعار',
    Amount: 'مبلغ',
    'Our Amount': 'مبلغ ما',
    'Our bid amount': 'مبلغ پیشنهاد ما',
    'Winning Amount': 'مبلغ برنده',
    'Contract value': 'ارزش قرارداد',
    'Contract Value': 'ارزش قرارداد',
    'Estimated value': 'ارزش تخمینی',
    Value: 'ارزش',
    'Net Cash Flow': 'جریان پول نقد خالص',
    'Net Finance (USD)': 'خالص مالی (دالر)',
    'Net Margin': 'حاشیه خالص',
    Net: 'خالص',
    'Total net pay': 'مجموع معاش خالص',
    Margin: 'حاشیه',
    'Top Projects by Margin': 'برترین پروژه‌ها بر اساس حاشیه',
    'Project Profitability': 'سودآوری پروژه',
    'No profitability data available.': 'داده سودآوری موجود نیست.',
    Bonus: 'جایزه',
    Deduction: 'کسر',
    Deductions: 'کسرها',
    Advance: 'پیش‌پرداخت',
    'Payroll Adjustments': 'تعدیلات معاش',
    Payroll: 'معاش',
    'Monthly payroll runs': 'اجرای ماهانه معاش',
    'Monthly payroll runs and disbursements':
        'اجرای ماهانه معاش و پرداخت‌ها',
    'No payroll runs recorded.': 'اجرای معاش ثبت نشده.',
    'Process payroll': 'پروسس معاش',
    'Payroll processed': 'معاش پروسس شد',
    'No payroll amounts were calculated for this period.':
        'مبلغ معاش برای این دوره محاسبه نشد.',
    'No payroll adjustments.': 'تعدیل معاش ثبت نشده.',
    'Payroll Adjustments': 'تعدیلات معاش',
    'New adjustment': 'تعدیل جدید',
    'Save adjustment': 'ذخیره تعدیل',
    'Remove adjustment?': 'تعدیل حذف شود؟',
    'Bulk adjustments': 'تعدیلات گروهی',
    'Manage adjustments': 'اداره تعدیلات',
    'Add or edit adjustments': 'اضافه یا ویرایش تعدیلات',
    adjustment: 'تعدیل',
    adjustments: 'تعدیلات',
    'Pending adjustments': 'تعدیلات منتظر',
    Applied: 'اعمال‌شده',
    'New run': 'اجرای جدید',
    'Run details': 'جزئیات اجرا',
    'View run details': 'نمایش جزئیات اجرا',
    'Processed by': 'پروسس‌شده توسط',
    'Line Items': 'ردیف‌ها',
    'Line items': 'ردیف‌ها',
    'No line items recorded.': 'ردیفی ثبت نشده.',
    'No line items generated': 'ردیفی تولید نشد',
    ':count line items': ':count ردیف',
    'Personnel entries in this payroll run':
        'ورودی‌های پرسنل در این اجرای معاش',
    'Next step': 'قدم بعد',
    'Select all': 'انتخاب همه',
    'Select person': 'انتخاب شخص',
    'Select staff': 'انتخاب کارکنان',
    'Select type': 'انتخاب نوع',
    'Select organization': 'انتخاب سازمان',
    'Select gender': 'انتخاب جنسیت',
    'Select category': 'انتخاب کتگوری',
    'Select the organization and name this opportunity':
        'سازمان را انتخاب و این فرصت را نام‌گذاری کنید',
    None: 'هیچ',
    Add: 'اضافه',
    Create: 'ساختن',
    'Create project': 'ساختن پروژه',
    'Create record': 'ساختن سابقه',
    'Create User': 'ساختن کاربر',
    'Create Role': 'ساختن نقش',
    'Create first organization': 'ساختن اولین سازمان',
    'Create accounts, assign roles, and manage access':
        'ساختن حساب، دادن نقش و اداره دسترسی',
    'Create a new system user with an initial password and roles':
        'ساختن کاربر جدید با پاسورد اولیه و نقش‌ها',
    'Create a project to start bidding — win or lose, everything stays on one record.':
        'یک پروژه بسازید تا مناقصه را آغاز کنید — بری یا مات، همه چیز در یک سابقه می‌ماند.',
    New: 'جدید',
    'New Project': 'پروژه جدید',
    'New Bid': 'پیشنهاد جدید',
    'New opportunity': 'فرصت جدید',
    'New report': 'گزارش جدید',
    'Add User': 'اضافه کردن کاربر',
    'Add Role': 'اضافه کردن نقش',
    'Add Organization': 'اضافه کردن سازمان',
    'Add Employee': 'اضافه کردن کارمند',
    'Add organization': 'اضافه کردن سازمان',
    'Add first opportunity': 'اضافه کردن اولین فرصت',
    'Add Overhead / Salary': 'اضافه کردن مصارف عمومی / معاش',
    'Add Other Income': 'اضافه کردن سایر عاید',
    Save: 'ذخیره',
    'Save record': 'ذخیره سابقه',
    'Save employee': 'ذخیره کارمند',
    'Save organization': 'ذخیره سازمان',
    'Save bid details': 'ذخیره جزئیات پیشنهاد',
    'Save changes': 'ذخیره تغییرات',
    Edit: 'ویرایش',
    Delete: 'حذف',
    Remove: 'حذف',
    Close: 'بستن',
    'Close project': 'بستن پروژه',
    Resume: 'ادامه',
    Back: 'برگشت',
    'Back to list': 'برگشت به لیست',
    'Go back': 'برگشت',
    Reject: 'رد',
    'Reject record': 'رد سابقه',
    Assign: 'تعیین',
    'Assign person': 'تعیین فرد',
    'Assigned Personnel': 'پرسونل تعیین‌شده',
    'Project Assignments': 'تخصیص پروژه',
    'Not assigned to any project.': 'به هیچ پروژه‌ای تخصیص داده نشده.',
    'No personnel assigned yet.': 'هنوز پرسنلی تعیین نشده.',
    Personnel: 'پرسونل',
    'Personnel type': 'نوع پرسونل',
    Person: 'شخص',
    'People & amounts': 'افراد و مبالغ',
    Staff: 'کارکنان',
    Employees: 'کارمندان',
    Employee: 'کارمند',
    employees: 'کارمند',
    users: 'کاربر',
    Users: 'کاربران',
    'System Users': 'کاربران سیستم',
    'registered users': 'کاربر ثبت‌شده',
    'No users found.': 'کاربری یافت نشد.',
    'User created.': 'کاربر ساخته شد.',
    Roles: 'نقش‌ها',
    'Edit roles': 'ویرایش نقش‌ها',
    'System Roles': 'نقش‌های سیستم',
    'roles configured': 'نقش تنظیم‌شده',
    'No roles configured.': 'نقشی تنظیم نشده.',
    'Role created.': 'نقش ساخته شد.',
    'Role deleted.': 'نقش حذف شد.',
    'Delete role': 'حذف نقش',
    'Edit role': 'ویرایش نقش',
    Protected: 'محافظت‌شده',
    'e.g. Project Lead': 'مثلاً رهبر پروژه',
    'Role on site': 'نقش در سایت',
    Organizations: 'سازمان‌ها',
    Organization: 'سازمان',
    organization: 'سازمان',
    organizations: 'سازمان',
    ':count organizations': ':count سازمان',
    'All Organizations': 'همه سازمان‌ها',
    'Organization Types': 'انواع سازمان',
    'Organization type': 'نوع سازمان',
    'Organization name': 'نام سازمان',
    'Organization profile': 'پروفایل سازمان',
    'Active organizations': 'سازمان‌های فعال',
    'No organizations yet': 'هنوز سازمانی نیست',
    'Delete organization': 'حذف سازمان',
    'Save organization': 'ذخیره سازمان',
    'Edit Organization': 'ویرایش سازمان',
    Projects: 'پروژه‌ها',
    Project: 'پروژه',
    projects: 'پروژه',
    ':count projects': ':count پروژه',
    'All Projects': 'همه پروژه‌ها',
    'All projects': 'همه پروژه‌ها',
    'Active Projects': 'پروژه‌های فعال',
    'No projects yet': 'هنوز پروژه‌ای نیست',
    'No projects linked yet.': 'هنوز پروژه‌ای مرتبط نشده.',
    'New Project': 'پروژه جدید',
    Archive: 'بایگانی',
    'Document Archive': 'بایگانی اسناد',
    'Archived Documents': 'اسناد بایگانی‌شده',
    'Archived document': 'سند بایگانی‌شده',
    'Move to long-term archive': 'انتقال به بایگانی بلندمدت',
    'Move this document to long-term archive? It will no longer appear in the active list.':
        'این سند به بایگانی بلندمدت منتقل شود؟ دیگر در لیست فعال نمایش داده نمی‌شود.',
    'Search archive...': 'جستجو در بایگانی...',
    'No documents in archive.': 'سندی در بایگانی نیست.',
    'Register and manage all your documents': 'ثبت و اداره همه اسناد شما',
    'Register new document': 'ثبت سند جدید',
    'Register document': 'ثبت سند',
    Documents: 'اسناد',
    Document: 'سند',
    ':count documents': ':count سند',
    'Edit document': 'ویرایش سند',
    'Document date': 'تاریخ سند',
    'Expiring Documents': 'اسناد در حال انقضا',
    'No documents expiring within 30 days.':
        'سندی در ۳۰ روز آینده منقضی نمی‌شود.',
    'Issued date': 'تاریخ صدور',
    'Expiry date': 'تاریخ انقضا',
    'Requires expiry date': 'نیاز به تاریخ انقضا',
    'Expiry required': 'انقضا الزامی',
    Expires: 'انقضا',
    Issued: 'صادر شده',
    Uploaded: 'آپلود شده',
    'Uploaded by': 'آپلود شده توسط',
    Received: 'دریافت‌شده',
    Sent: 'ارسال‌شده',
    Incoming: 'ورودی',
    Outgoing: 'خروجی',
    Internal: 'داخلی',
    Direction: 'جهت',
    Reference: 'مرجع',
    'Reference #': 'نمبر مرجع',
    Code: 'کد',
    Title: 'عنوان',
    Name: 'نام',
    'First name': 'نام',
    'Last name': 'ت Famili',
};

// Fix typo in last name override
overrides['Last name'] = 'نام خانوادگی';

const out = {};
for (const [k, v] of Object.entries(data)) {
    out[k] = convert(v);
}

for (const [k, v] of Object.entries(overrides)) {
    if (k in out) {
        out[k] = v;
    }
}

fs.writeFileSync(faPath, `${JSON.stringify(out, null, 4)}\n`, 'utf8');
console.log(`Afghanized ${Object.keys(out).length} fa.json entries`);
