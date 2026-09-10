import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');

const enPath = path.join(root, 'lang/en.json');
const faPath = path.join(root, 'lang/fa.json');
const psPath = path.join(root, 'lang/ps.json');

const en = JSON.parse(fs.readFileSync(enPath, 'utf8'));
const fa = JSON.parse(fs.readFileSync(faPath, 'utf8'));
const ps = JSON.parse(fs.readFileSync(psPath, 'utf8'));

/** New stock, invoice, and shareholder UI strings. */
const translations = {
    'Stock / Inventory': {
        fa: 'موجودی / انبار',
        ps: 'ذخیره / ګودام',
    },
    'Items in depot': {
        fa: 'اقلام در انبار',
        ps: 'په ګودام کې توکي',
    },
    Categories: {
        fa: 'دسته‌ها',
        ps: 'کټګورۍ',
    },
    'Low stock': {
        fa: 'موجودی کم',
        ps: 'کمه ذخیره',
    },
    'Depot stock': {
        fa: 'موجودی انبار',
        ps: 'د ګودام ذخیره',
    },
    'Add item': {
        fa: 'افزودن قلم',
        ps: 'توکی اضافه کړئ',
    },
    SKU: {
        fa: 'کد کالا',
        ps: 'SKU',
    },
    Unit: {
        fa: 'واحد',
        ps: 'واحد',
    },
    'Initial quantity': {
        fa: 'مقدار اولیه',
        ps: 'لومړنی مقدار',
    },
    'Save to stock': {
        fa: 'ذخیره در انبار',
        ps: 'په ذخیره کې خوندي کړئ',
    },
    'Search name or SKU': {
        fa: 'جستجوی نام یا کد کالا',
        ps: 'نوم یا SKU ولټوئ',
    },
    'All categories': {
        fa: 'همه دسته‌ها',
        ps: 'ټولې کټګورۍ',
    },
    'No stock items yet. Add guns, radios, and other goods to the depot.': {
        fa: 'هنوز قلمی در انبار نیست. سلاح، بی‌سیم و سایر اجناس را اضافه کنید.',
        ps: 'لا تر اوسه ذخیره خالي ده. ټوپکونه، راډیوګانې او نور توکي اضافه کړئ.',
    },
    Item: {
        fa: 'قلم',
        ps: 'توکی',
    },
    'On hand': {
        fa: 'موجود',
        ps: 'په لاس کې',
    },
    Adjust: {
        fa: 'تعدیل',
        ps: 'سمون',
    },
    Issue: {
        fa: 'صدور',
        ps: 'صادرول',
    },
    'Adjustment (+/-)': {
        fa: 'تعدیل (+/-)',
        ps: 'سمون (+/-)',
    },
    'e.g. 10 or -2': {
        fa: 'مثلاً ۱۰ یا ۲-',
        ps: 'لکه ۱۰ یا -۲',
    },
    'Update stock': {
        fa: 'به‌روزرسانی موجودی',
        ps: 'ذخیره تازه کړئ',
    },
    'To project': {
        fa: 'به پروژه',
        ps: 'پروژې ته',
    },
    'To personnel': {
        fa: 'به پرسنل',
        ps: 'پرسونل ته',
    },
    'Issue to project': {
        fa: 'صدور به پروژه',
        ps: 'پروژې ته صادرول',
    },
    'e.g. AK-47 Rifle': {
        fa: 'مثلاً کلاشنیکف',
        ps: 'لکه کلاشینکوف',
    },
    'e.g. GUN-AK47-001': {
        fa: 'مثلاً GUN-AK47-001',
        ps: 'لکه GUN-AK47-001',
    },
    'e.g. Weapons, Radios': {
        fa: 'مثلاً سلاح، بی‌سیم',
        ps: 'لکه وسلې، راډیوګانې',
    },
    'Equipment on this project': {
        fa: 'تجهیزات این پروژه',
        ps: 'د دې پروژې تجهیزات',
    },
    'No stock items issued to this project yet.': {
        fa: 'هنوز قلمی از انبار به این پروژه صادر نشده است.',
        ps: 'لا تر اوسه دې پروژې ته له ذخیرې څخه توکي نه دي صادر شوي.',
    },
    Issued: {
        fa: 'صادر شده',
        ps: 'صادر شوی',
    },
    Returned: {
        fa: 'برگشت‌شده',
        ps: 'بېرته شوی',
    },
    'On site': {
        fa: 'در محل',
        ps: 'په ساحه کې',
    },
    Return: {
        fa: 'بازگشت',
        ps: 'بېرته',
    },
    'Issue from stock': {
        fa: 'صدور از انبار',
        ps: 'له ذخیرې څخه صادرول',
    },
    'Select item': {
        fa: 'انتخاب قلم',
        ps: 'توکی وټاکئ',
    },
    Quantity: {
        fa: 'مقدار',
        ps: 'مقدار',
    },
    'Stock is reduced from the depot when you issue items.': {
        fa: 'با صدور اقلام، موجودی انبار کم می‌شود.',
        ps: 'کله چې توکي صادرېږي، د ګودام ذخیره کميږي.',
    },
    Shareholders: {
        fa: 'سهامداران',
        ps: 'ونډه‌والان',
    },
    'Capital received': {
        fa: 'سرمایه دریافت‌شده',
        ps: 'ترلاسه شوې پانګه',
    },
    'Project spent': {
        fa: 'هزینه پروژه',
        ps: 'د پروژې لګښت',
    },
    'Still owed to shareholders': {
        fa: 'باقی‌مانده بدهی به سهامداران',
        ps: 'ونډه‌والانو ته پاتې پور',
    },
    'No shareholders on this project. Add partners who invest capital.': {
        fa: 'سهامداری در این پروژه نیست. شرکایی که سرمایه می‌گذارند اضافه کنید.',
        ps: 'په دې پروژه کې ونډه‌وال نشته. پانګه‌وال شریکان اضافه کړئ.',
    },
    Invested: {
        fa: 'سرمایه‌گذاری‌شده',
        ps: 'پانګه اچول شوې',
    },
    Outstanding: {
        fa: 'باقی‌مانده',
        ps: 'پاتې',
    },
    'Add capital': {
        fa: 'افزودن سرمایه',
        ps: 'پانګه اضافه کړئ',
    },
    'Return share': {
        fa: 'بازگشت سهم',
        ps: 'ونډه بېرته ورکړئ',
    },
    'Amount to return': {
        fa: 'مبلغ بازگشت',
        ps: 'د بېرته ورکولو اندازه',
    },
    Record: {
        fa: 'ثبت',
        ps: 'ثبت',
    },
    In: {
        fa: 'ورودی',
        ps: 'ننوت',
    },
    Out: {
        fa: 'خروجی',
        ps: 'وتل',
    },
    'Add shareholder': {
        fa: 'افزودن سهامدار',
        ps: 'ونډه‌وال اضافه کړئ',
    },
    'No contact': {
        fa: 'بدون تماس',
        ps: 'اړیکه نشته',
    },
    'Share %': {
        fa: 'درصد سهم',
        ps: 'د ونډې٪',
    },
    'Initial capital (AFN)': {
        fa: 'سرمایه اولیه (افغانی)',
        ps: 'لومړنۍ پانګه (افغانۍ)',
    },
    'Select client': {
        fa: 'انتخاب مشتری',
        ps: 'پیرودونکی وټاکئ',
    },
    'Optional project': {
        fa: 'پروژه اختیاری',
        ps: 'اختیاري پروژه',
    },
    'Issue date': {
        fa: 'تاریخ صدور',
        ps: 'د صادرولو نیټه',
    },
    'Due date': {
        fa: 'تاریخ سررسید',
        ps: 'د پای نیټه',
    },
    Subtotal: {
        fa: 'جمع جزئی',
        ps: 'فرعي مجموعه',
    },
    Tax: {
        fa: 'مالیات',
        ps: 'مالیه',
    },
    'Save invoice': {
        fa: 'ذخیره بل',
        ps: 'رسید خوندي کړئ',
    },
    Draft: {
        fa: 'پیش‌نویس',
        ps: 'مسوده',
    },
    Paid: {
        fa: 'پرداخت‌شده',
        ps: 'ورکړل شوی',
    },
    Overdue: {
        fa: 'سررسید گذشته',
        ps: 'ناوخته',
    },
    Attachment: {
        fa: 'پیوست',
        ps: 'ضمیمه',
    },
    Inventory: {
        fa: 'موجودی',
        ps: 'ذخیره',
    },
};

let added = 0;

for (const [key, locales] of Object.entries(translations)) {
    if (!(key in en)) {
        en[key] = key;
        added++;
    }

    if (!(key in fa)) {
        fa[key] = locales.fa;
    }

    if (!(key in ps)) {
        ps[key] = locales.ps;
    }
}

const sortObject = (obj) =>
    Object.fromEntries(Object.entries(obj).sort(([a], [b]) => a.localeCompare(b)));

fs.writeFileSync(enPath, `${JSON.stringify(sortObject(en), null, 4)}\n`);
fs.writeFileSync(faPath, `${JSON.stringify(sortObject(fa), null, 4)}\n`);
fs.writeFileSync(psPath, `${JSON.stringify(sortObject(ps), null, 4)}\n`);

console.log(`Added ${added} new English keys; fa/ps updated.`);
