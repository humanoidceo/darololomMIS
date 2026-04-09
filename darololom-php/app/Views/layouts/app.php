<?php
declare(strict_types=1);

$currentUser = auth_user();
$isLoggedIn = $currentUser !== null;
$currentRole = (string) ($currentUser['role'] ?? '');
$isStudentRole = $currentRole === 'student';
$isTeacherRole = $currentRole === 'teacher';
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
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/grades.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/contracts.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/auth.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/users.css')) ?>">
</head>
<body id="top" class="rtl-body">
    <section class="preloader">
        <div class="spinner"><span class="spinner-rotate"></span></div>
    </section>

    <header class="site-header">
        <div class="container">
            <div class="site-header-main">
                <div class="site-header-brand">
                    <span class="site-header-logo-wrap">
                        <img src="<?= e(url('/assets/images/logo.jpg')) ?>" alt="لوگوی دارالعلوم" class="site-header-logo" onerror="this.style.display='none';">
                    </span>
                    <p>دارالعلوم عالی الحاج سید منصور نادری</p>
                    <nav class="site-header-quick-nav" aria-label="گزینه‌های هدر">
                        <a href="<?= e(url('/')) ?>">خانه</a>
                        <a href="<?= e(url('/articles')) ?>">مقالات</a>
                        <a href="<?= e(url('/#about-us')) ?>">درباره ما</a>
                        <a href="<?= e(url('/#contact-us')) ?>">تماس با ما</a>
                    </nav>
                </div>
                    
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
            <div class="row">
                <div class="col-md-8 col-sm-6">
                    <div class="footer-thumb">
                        <h4 class="wow fadeInUp" data-wow-delay="0.4s">دارالعلوم  عالی الحاج سید منصور نادری</h4>
                    <p>
                        جوار مسجد الحاج سید منصور نادری، چهارراهی پروژه تایمنی، کابل، افغانستان
                    </p> </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="footer-thumb">
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
                            <?php else: ?>
                                <li><a href="<?= e(url('/')) ?>">خانه</a></li>
                                <li><a href="<?= e(url('/articles')) ?>">مقالات</a></li>
                                <li><a href="<?= e(url('/#about-us')) ?>">درباره ما</a></li>
                                <li><a href="<?= e(url('/#contact-us')) ?>">تماس با ما</a></li>
                            <?php endif; ?>
                        </ul>

                        <p class="footer-copy">© <?= date('Y') ?> دارالعلوم عالی الحاج سید منصور نادری - همه حقوق محفوظ است.</p>
                    </div>
                </div>
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
