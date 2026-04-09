<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class HomeController extends Controller
{
    public function index(array $params = []): void
    {
        $heroSlides = [
            [
                'image' => '/assets/images/chiefofdarolom.jpg',
                'alt' => 'ریاست دارالعلوم',
                'title' => 'آموزش دینی و عصری در یک فضای منظم و حرفه‌ای',
                'text' => 'دارالعلوم عالی الحاج سید منصور نادری با تمرکز بر کیفیت آموزشی، انضباط اداری و رشد علمی نسل جوان فعالیت می‌کند.',
            ],
            [
                'image' => '/assets/images/allstaff.jpg',
                'alt' => 'کارمندان دارالعلوم',
                'title' => 'همکاری منسجم میان اساتید و مدیریت',
                'text' => 'تعهد، تجربه و هماهنگی کادر اداری و آموزشی، زیربنای خدمات پایدار و قابل اعتماد این مرکز است.',
            ],
            [
                'image' => '/assets/images/duringexam.jpg',
                'alt' => 'جریان امتحان',
                'title' => 'ارزیابی دقیق و محیط آموزشی پویا',
                'text' => 'فرآیندهای منظم درسی و امتحانی، زمینه رشد بهتر شاگردان و مدیریت شفاف اطلاعات آموزشی را فراهم می‌سازد.',
            ],
        ];

        $featureCards = [
            [
                'title' => 'آرشیف مقالات',
                'text' => 'تمام مقالات اپلودشده برای مطالعه و دانلود در یک صفحه عمومی و منظم در دسترس عموم قرار دارد.',
                'link' => url('/articles'),
                'label' => 'مشاهده مقالات',
            ],
            [
                'title' => 'درباره ما',
                'text' => 'با چشم‌انداز، ارزش‌ها و ساختار آموزشی دارالعلوم بیشتر آشنا شوید و مسیر فعالیت‌های علمی ما را ببینید.',
                'link' => '#about-us',
                'label' => 'رفتن به درباره ما',
            ],
            [
                'title' => 'تماس با ما',
                'text' => 'راه‌های ارتباطی، آدرس و معلومات ضروری برای ارتباط مستقیم با دارالعلوم در این بخش گردآوری شده است.',
                'link' => '#contact-us',
                'label' => 'رفتن به تماس با ما',
            ],
        ];

        $galleryItems = [
            ['image' => '/assets/images/allstaff.jpg', 'alt' => 'نمای داخلی دارالعلوم', 'caption' => 'فضای آموزشی و محیط منظم'],
            ['image' => '/assets/images/teacherone.jpg', 'alt' => 'فعالیت علمی', 'caption' => 'برنامه‌های آموزشی و علمی'],
            ['image' => '/assets/images/teachertwo.jpg', 'alt' => 'محیط آموزشی', 'caption' => 'محیط سالم برای رشد علمی'],
            ['image' => '/assets/images/chiefwithallstaff.jpg', 'alt' => 'همراهی مدیریت و کارمندان', 'caption' => 'هماهنگی مدیریت و کادر اداری'],
            ['image' => '/assets/images/duringexam2.jpg', 'alt' => 'امتحانات', 'caption' => 'نظم در ارزیابی و امتحانات'],
            ['image' => '/assets/images/chiefofdarololom2.jpg', 'alt' => 'ریاست', 'caption' => 'رهبری و جهت‌دهی آموزشی'],
        ];

        $this->render('home/index', [
            'title' => 'خانه',
            'heroSlides' => $heroSlides,
            'featureCards' => $featureCards,
            'galleryItems' => $galleryItems,
        ]);
    }
}
