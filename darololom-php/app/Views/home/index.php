<section class="home-hero" id="top">
    <div class="row home-hero-row">
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="home-hero-copy wow fadeInUp" data-wow-delay="0.1s">
                <span class="home-kicker">دارالعلوم عالی الحاج سید منصور نادری</span>
                <h1>مرکز آموزشی منظم برای رشد علمی، اخلاقی و معرفی حرفه‌ای فعالیت‌ها</h1>
                <p>
                    این وبسایت برای معرفی دارالعلوم، دسترسی عمومی به مقالات، نمایش فعالیت‌های آموزشی و ایجاد ارتباط روشن و حرفه‌ای با مراجعین و علاقه‌مندان طراحی شده است.
                </p>
              
                <div class="home-hero-actions">
                    <a href="<?= e(url('/articles')) ?>" class="btn btn-default home-primary-btn">مشاهده مقالات</a>
                    <a href="#about-us" class="btn btn-default home-secondary-btn">درباره ما</a>
                    <a href="#contact-us" class="btn btn-default home-secondary-btn">تماس با ما</a>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="home-hero-media wow fadeInUp" data-wow-delay="0.2s">
                <?php foreach ($heroSlides as $index => $slide): ?>
                    <article class="home-slide-card<?= $index === 0 ? ' is-featured' : '' ?>">
                        <div class="home-slide-image-wrap">
                            <img src="<?= e(file_url((string) $slide['image'])) ?>" alt="<?= e((string) $slide['alt']) ?>" class="home-slide-image">
                        </div>
                        <div class="home-slide-body">
                            <h3><?= e((string) $slide['title']) ?></h3>
                            <p><?= e((string) $slide['text']) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="home-impact-strip">
    <div class="row">
        <div class="col-lg-4 col-md-4 col-sm-6">
          
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6">
         
        </div>
        
    </div>
</section>

<section class="home-links-section">
   

    <div class="row">
        <?php foreach ($featureCards as $index => $card): ?>
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="news-thumb home-link-card wow fadeInUp" data-wow-delay="<?= e(number_format(0.1 + ($index * 0.1), 1)) ?>s">
                    <div class="news-info">
                        <h3><?= e((string) $card['title']) ?></h3>
                        <p><?= e((string) $card['text']) ?></p>
                        <a href="<?= e((string) $card['link']) ?>" class="home-link-arrow"><?= e((string) $card['label']) ?></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-about-section" id="about-us">
    <div class="row home-about-row">
        <div class="col-lg-5 col-md-5 col-sm-12">
            <div class="home-about-image-stack wow fadeInUp" data-wow-delay="0.1s">
                <img src="<?= e(file_url('/assets/images/chiefwithallstaff.jpg')) ?>" alt="کارمندان دارالعلوم" class="home-about-main-image">
                <img src="<?= e(file_url('/assets/images/allstaff.jpg')) ?>" alt="تیم دارالعلوم" class="home-about-secondary-image">
            </div>
        </div>
        <div class="col-lg-7 col-md-7 col-sm-12">
            <div class="home-about-copy wow fadeInUp" data-wow-delay="0.2s">
                <div class="section-title">
                    <h2>درباره ما</h2>
                    <p class="home-section-lead">دارالعلوم با رویکرد آموزشی، اخلاقی و اداری منظم، محیطی سالم و روشن برای رشد علمی شاگردان فراهم می‌سازد.</p>
                </div>
                <p>
                    دارالعلوم عالی الحاج سید منصور نادری یک نهاد آموزشی متعهد به تربیه نسل آگاه، منظم و مسئولیت‌پذیر است. این مرکز با استفاده از کادر علمی مجرب و ساختار اداری منظم، تلاش می‌کند خدمات آموزشی را با کیفیت بهتر و دسترسی روشن‌تر ارائه نماید.
                </p>
                <p>
                    تمرکز ما بر آموزش مؤثر، انضباط آموزشی، شفافیت در مدیریت و فراهم‌سازی بستر مناسب برای دسترسی شاگردان و اساتید به منابع علمی است. صفحه مقالات نیز بخشی از همین رویکرد برای اشتراک دانش و محتوای علمی با مردم می‌باشد.
                </p>
                <div class="home-about-points">
                    <span>کادر علمی متعهد</span>
                    <span>مدیریت منظم اطلاعات</span>
                    <span>محیط آموزشی سالم</span>
                    <span>دسترسی عمومی به مقالات</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="home-gallery-section">
    <div class="section-title wow fadeInUp" data-wow-delay="0.1s">
        <h2>گالری فعالیت‌ها</h2>
    </div>

    <div class="row">
        <?php foreach ($galleryItems as $index => $item): ?>
            <div class="col-lg-4 col-md-4 col-sm-6">
                <figure class="home-gallery-card wow fadeInUp" data-wow-delay="<?= e(number_format(0.1 + (($index % 3) * 0.1), 1)) ?>s">
                    <img src="<?= e(file_url((string) $item['image'])) ?>" alt="<?= e((string) $item['alt']) ?>">
                    <figcaption><?= e((string) $item['caption']) ?></figcaption>
                </figure>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-contact-section" id="contact-us">
    <div class="section-title wow fadeInUp" data-wow-delay="0.1s">
        <h2>تماس با ما</h2>
        <p class="home-section-lead">برای دریافت معلومات بیشتر یا ایجاد ارتباط مستقیم با دارالعلوم، از این بخش استفاده کنید.</p>
    </div>

    <div class="row">
        <div class="col-lg-7 col-md-7 col-sm-12">
            <div class="news-thumb home-contact-card wow fadeInUp" data-wow-delay="0.1s">
                <div class="news-info">
                    <h3>راه‌های ارتباطی</h3>
                    <p>
                        برای دریافت معلومات بیشتر، پیگیری امور آموزشی و یا برقراری ارتباط رسمی، می‌توانید از راه‌های زیر با دارالعلوم در تماس شوید.
                    </p>
                    <div class="home-contact-list">
                        <div><strong>آدرس:</strong> جوار مسجد الحاج سید منصور نادری، چهارراهی پروژه تایمنی، کابل، افغانستان</div>
                        <div><strong>شماره تماس:</strong>۰۷۷۰۹۲۲۷۹۰</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5 col-md-5 col-sm-12">
            <div class="news-thumb home-contact-card home-contact-highlight wow fadeInUp" data-wow-delay="0.2s">
                <div class="news-info">
                    <h3>دسترسی سریع</h3>
                    <div class="home-contact-actions">
                        <a href="<?= e(url('/articles')) ?>" class="btn btn-default home-primary-btn">رفتن به مقالات</a>
                        <?php if (auth_check()): ?>
                            <a href="<?= e(url('/dashboard')) ?>" class="btn btn-default home-secondary-btn">ورود به داشبورد</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
