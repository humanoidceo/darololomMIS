# DarolOlom PHP Rewrite

این پروژه بازنویسی سیستم Django به `PHP + MySQL + HTML/CSS` است با طراحی و استایل مبتنی بر فولدر `health`.

## ساختار پروژه

- `app/Core` هسته‌ی برنامه (Router, Controller, View, Database)
- `app/Controllers` کنترلر هر بخش به‌صورت جداگانه
- `app/Views` ویوهای ماژولار (`students`, `teachers`, `classes`, `subjects`, `grades`, `contracts`, `dashboard`)
- `public/assets/health` تمام فایل‌های اصلی استایل/فونت/JS قالب health
- `public/assets/css/modules` استایل اختصاصی هر ماژول
- `database/schema.sql` دیتابیس کامل MySQL
- `database/seeder.sql` داده اولیه سوپرادمین

## نصب و اجرا

1. ساخت دیتابیس:

```bash
mysql -u root -p < database/schema.sql
```

2. اجرای seeder (سوپرادمین اولیه):

```bash
mysql -u root -p darololom_php < database/seeder.sql
```

اگر دیتابیس از قبل وجود دارد و فقط می‌خواهید تغییرات جدید (حساب شاگرد/استاد) اعمال شود، دوباره همین دو فایل را اجرا کنید.

3. تنظیم env (اختیاری):

```bash
cp .env.example .env
```

4. اجرای سرور توسعه:

```bash
cd public
php -S localhost:8080 router.php
```

5. آدرس برنامه:

- `http://localhost:8080`

## لاگین اولیه

- ایمیل سوپرادمین: `modeer@modeer.com`
- رمز عبور: `Adam2050@`

نکته: بعد از اولین ورود، بهتر است رمز عبور را فوراً از داخل سیستم تغییر دهید.

## ماژول‌های پیاده‌سازی‌شده

- داشبورد
- مدیریت دانش‌آموزان (CRUD + رفتار + سرتفیکت + نتایج + تقدیرنامه + ارتقا به متوسطه)
- مدیریت اساتید (CRUD + رفتار + تقدیرنامه)
- مدیریت صنوف (CRUD)
- مدیریت مضامین (CRUD)
- ثبت نمرات
- قرارداد اساتید
- API جستجوی صنف
- لاگین/لاگ‌اوت
- مدیریت کاربران و صلاحیت‌ها (Permission-based access)
- حساب شاگرد/استاد با ایمیل و رمز عبور (ورود مستقل)
- صفحه «حساب من» برای شاگرد و استاد (نمایش فقط اطلاعات خود + تغییر ایمیل/رمز)
- ثبت/ویرایش نمره توسط استاد فقط برای صنوف و مضامین اختصاص‌یافته خودش

## نکته طراحی

استایل تمام صفحات با CSS/JS و visual language قالب `health` یک‌دست شده و RTL برای فارسی/دری تنظیم شده است.
