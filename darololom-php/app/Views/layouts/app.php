<?php
declare(strict_types=1);

use App\Core\WebsiteContent;

$currentUser = auth_user();
$isLoggedIn = $currentUser !== null;
$currentRole = (string) ($currentUser['role'] ?? '');
$isStudentRole = $currentRole === 'student';
$isTeacherRole = $currentRole === 'teacher';
$websiteContent = WebsiteContent::load();
$footerAbout = (array) ($websiteContent['about'] ?? []);
$footerContact = (array) ($websiteContent['contact'] ?? []);
$footerIntro = (string) (($footerAbout['lead'] ?? '') !== '' ? $footerAbout['lead'] : 'دارالعلوم عالی الحاج سید منصور نادری با ساختار منظم آموزشی و اداری، محیطی مناسب برای رشد علمی و اخلاقی شاگردان فراهم ساخته و خدمات خود را به شکل روشن و حرفه‌ای معرفی می‌کند.');
$footerAddress = (string) (($footerContact['address'] ?? '') !== '' ? $footerContact['address'] : 'جوار مسجد الحاج سید منصور نادری، چهارراهی پروژه تایمنی، کابل، افغانستان');
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'سیستم مدیریت') . ' | ' . config('app_name')) ?></title>

    <link rel="stylesheet" href="<?= e(url('/assets/health/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/health/css/font-awesome.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/health/css/animate.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/health/css/tooplate-style.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/dashboard.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/home.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/students.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/teachers.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/classes.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/subjects.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/articles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/books.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/theses.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/grades.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/contracts.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/auth.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/users.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/website-content.css')) ?>">
</head>
<body id="top" class="rtl-body">
    <section class="preloader">
        <div class="spinner"><span class="spinner-rotate"></span></div>
    </section>

    <header class="site-header">
        <div class="container">
            <div class="site-header-main">
                <div class="site-header-identity">
                    <span class="site-header-logo-wrap">
                        <img src="<?= e(url('/assets/images/logo.jpg')) ?>" alt="لوگوی دارالعلوم" class="site-header-logo" onerror="this.style.display='none';">
                    </span>
                    <div class="site-header-copy">
                        <h3 class="site-header-title">دارالعلوم عالی الحاج سید منصور نادری</h3>
                    </div>
                </div>
                <nav class="site-header-quick-nav" aria-label="گزینه‌های هدر">
                    <a href="<?= e(url('/')) ?>">خانه</a>
                    <a href="<?= e(url('/articles')) ?>">مقالات</a>
                    <a href="<?= e(url('/library')) ?>">کتابخانه الکترونیکی</a>
                    <a href="<?= e(url('/theses')) ?>">پایان‌نامه‌ها</a>
                    <a href="<?= e(url('/#about-us')) ?>">درباره ما</a>
                    <a href="<?= e(url('/#contact-us')) ?>">تماس با ما</a>
                </nav>
            </div>
        </div>
    </header>

    <section class="navbar navbar-default navbar-static-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="icon icon-bar"></span>
                    <span class="icon icon-bar"></span>
                    <span class="icon icon-bar"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav navbar-right">
                    <?php if ($isLoggedIn): ?>
                        <?php if ($isStudentRole): ?>
                            <li><a href="<?= e(url('/account')) ?>">حساب من</a></li>
                            <li><a href="<?= e(url('/account?tab=grades')) ?>">نمرات</a></li>
                        <?php elseif ($isTeacherRole): ?>
                            <li><a href="<?= e(url('/account')) ?>">حساب من</a></li>
                            <li><a href="<?= e(url('/grades')) ?>">نمرات صنوف من</a></li>
                        <?php else: ?>
                            <li><a href="<?= e(url('/dashboard')) ?>">داشبورد</a></li>
                            <?php if (can('access_teachers')): ?>
                                <li><a href="<?= e(url('/articles/manage')) ?>">مقالات</a></li>
                            <?php endif; ?>
                            <?php if (can('access_students')): ?>
                                <li><a href="<?= e(url('/students')) ?>">دانش‌آموزان</a></li>
                            <?php endif; ?>
                            <?php if (can('access_teachers')): ?>
                                <li><a href="<?= e(url('/teachers')) ?>">اساتید</a></li>
                            <?php endif; ?>
                            <?php if (can('manage_classes')): ?>
                                <li><a href="<?= e(url('/classes')) ?>">صنوف</a></li>
                            <?php endif; ?>
                            <?php if (can('manage_subjects')): ?>
                                <li><a href="<?= e(url('/subjects')) ?>">مضامین</a></li>
                            <?php endif; ?>
                            <?php if (can('manage_users')): ?>
                                <li><a href="<?= e(url('/users')) ?>">مدیریت کاربران</a></li>
                            <?php endif; ?>
                            <?php if (is_super_admin()): ?>
                                <li><a href="<?= e(url('/library/manage')) ?>">کتابخانه الکترونیکی</a></li>
                                <li><a href="<?= e(url('/theses/manage')) ?>">پایان‌نامه‌ها</a></li>
                                <li><a href="<?= e(url('/website-content')) ?>">ادیت محتوای وبسایت</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <li class="dropdown nav-user-dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-user"></i>
                                <?= e((string) ($currentUser['full_name'] ?? $currentUser['username'] ?? 'کاربر')) ?>
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="dropdown-header">
                                    <?= e((string) ($currentUser['full_name'] ?? $currentUser['username'] ?? 'کاربر')) ?>
                                </li>
                                <li role="separator" class="divider"></li>
                                <li>
                                    <form method="post" action="<?= e(url('/logout')) ?>" class="nav-logout-form">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-link nav-logout-btn">خروج</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="<?= e(url('/login')) ?>">ورود</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </section>

    <main class="app-shell" id="news">
        <div class="container">
            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success"><?= e($msg) ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="alert alert-danger"><?= e($msg) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </div>
    </main>

    <footer class="system-footer" data-stellar-background-ratio="5">
        <div class="container">
            <div class="system-footer-top">
                <div class="system-footer-badge">دارالعلوم عالی الحاج سید منصور نادری</div>
            </div>

            <div class="row system-footer-grid">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="footer-thumb system-footer-card">
                        <h4>معرفی کوتاه</h4>
                        <p>
                            <?= e($footerIntro) ?>
                        </p>
                        <div class="system-footer-tags">
                            <span>آموزش منظم</span>
                            <span>دسترسی عمومی</span>
                            <span>مدیریت حرفه‌ای</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="footer-thumb system-footer-card">
                        <h4>لینک‌های مهم</h4>
                        <ul class="footer-links">
                            <?php if ($isStudentRole): ?>
                                <li><a href="<?= e(url('/account')) ?>">مشاهده مشخصات من</a></li>
                                <li><a href="<?= e(url('/account?tab=grades')) ?>">مشاهده نمرات من</a></li>
                            <?php elseif ($isTeacherRole): ?>
                                <li><a href="<?= e(url('/account')) ?>">مشاهده مشخصات من</a></li>
                                <li><a href="<?= e(url('/grades')) ?>">ثبت نمرات صنوف من</a></li>
                            <?php elseif ($isLoggedIn): ?>
                                <li><a href="<?= e(url('/dashboard')) ?>">داشبورد مدیریتی</a></li>
                                <li><a href="<?= e(url('/articles/manage')) ?>">مدیریت مقالات</a></li>
                                <?php if (is_super_admin()): ?>
                                    <li><a href="<?= e(url('/library/manage')) ?>">مدیریت کتابخانه</a></li>
                                    <li><a href="<?= e(url('/theses/manage')) ?>">مدیریت پایان‌نامه‌ها</a></li>
                                <?php endif; ?>
                                <li><a href="<?= e(url('/articles')) ?>">صفحه عمومی مقالات</a></li>
                                <li><a href="<?= e(url('/library')) ?>">صفحه عمومی کتابخانه</a></li>
                                <li><a href="<?= e(url('/theses')) ?>">صفحه عمومی پایان‌نامه‌ها</a></li>
                            <?php else: ?>
                                <li><a href="<?= e(url('/')) ?>">خانه</a></li>
                                <li><a href="<?= e(url('/articles')) ?>">مقالات</a></li>
                                <li><a href="<?= e(url('/library')) ?>">کتابخانه الکترونیکی</a></li>
                                <li><a href="<?= e(url('/theses')) ?>">پایان‌نامه‌ها</a></li>
                                <li><a href="<?= e(url('/#about-us')) ?>">درباره ما</a></li>
                                <li><a href="<?= e(url('/#contact-us')) ?>">تماس با ما</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-5 col-md-12 col-sm-12">
                    <div class="footer-thumb system-footer-card">
                        <h4>موقعیت دارالعلوم</h4>
                        <p class="system-footer-contact-text"><?= e($footerAddress) ?></p>
                        <div class="system-footer-map-wrap">
                            <iframe
                                class="system-footer-map"
                                title="نقشه موقعیت دارالعلوم"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                src="https://www.google.com/maps?q=%D8%AC%D9%88%D8%A7%D8%B1%20%D9%85%D8%B3%D8%AC%D8%AF%20%D8%A7%D9%84%D8%AD%D8%A7%D8%AC%20%D8%B3%DB%8C%D8%AF%20%D9%85%D9%86%D8%B5%D9%88%D8%B1%20%D9%86%D8%A7%D8%AF%D8%B1%DB%8C%D8%8C%20%DA%86%D9%87%D8%A7%D8%B1%D8%B1%D8%A7%D9%87%DB%8C%20%D9%BE%D8%B1%D9%88%DA%98%D9%87%20%D8%AA%D8%A7%DB%8C%D9%85%D9%86%DB%8C%D8%8C%20%DA%A9%D8%A7%D8%A8%D9%84%D8%8C%20%D8%A7%D9%81%D8%BA%D8%A7%D9%86%D8%B3%D8%AA%D8%A7%D9%86&z=15&output=embed"
                                allowfullscreen>
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>

            <div class="system-footer-bottom">
                <p class="footer-copy">© <?= date('Y') ?> دارالعلوم عالی الحاج سید منصور نادری - همه حقوق محفوظ است.</p>
            </div>
        </div>
    </footer>

    <script src="<?= e(url('/assets/health/js/jquery.js')) ?>"></script>
    <script src="<?= e(url('/assets/health/js/bootstrap.min.js')) ?>"></script>
    <script src="<?= e(url('/assets/health/js/wow.min.js')) ?>"></script>
    <script src="<?= e(url('/assets/health/js/smoothscroll.js')) ?>"></script>
    <script src="<?= e(url('/assets/health/js/custom.js')) ?>"></script>
    <script src="<?= e(url('/assets/js/app.js')) ?>"></script>
</body>
</html>
