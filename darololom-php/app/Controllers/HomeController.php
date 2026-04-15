<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\WebsiteContent;

final class HomeController extends Controller
{
    public function index(array $params = []): void
    {
        $websiteContent = WebsiteContent::load();
        $homeContent = (array) ($websiteContent['home'] ?? []);
        $aboutContent = (array) ($websiteContent['about'] ?? []);
        $contactContent = (array) ($websiteContent['contact'] ?? []);

        $heroSlides = [
            ...array_map(static function (array $slide): array {
                return [
                    'image' => (string) ($slide['image'] ?? ''),
                    'alt' => (string) ($slide['alt'] ?? ''),
                    'title' => (string) ($slide['title'] ?? ''),
                    'text' => (string) ($slide['text'] ?? ''),
                ];
            }, (array) ($homeContent['slides'] ?? [])),
        ];

        $featureCards = [
            [
                'title' => 'آرشیف مقالات',
                'text' => 'تمام مقالات اپلودشده برای مطالعه و دانلود در یک صفحه عمومی و منظم در دسترس عموم قرار دارد.',
                'link' => url('/articles'),
                'label' => 'مشاهده مقالات',
            ],
            [
                'title' => (string) ($aboutContent['title'] ?? 'درباره ما'),
                'text' => 'با چشم‌انداز، ارزش‌ها و ساختار آموزشی دارالعلوم بیشتر آشنا شوید و مسیر فعالیت‌های علمی ما را ببینید.',
                'link' => '#about-us',
                'label' => 'رفتن به درباره ما',
            ],
            [
                'title' => (string) ($contactContent['title'] ?? 'تماس با ما'),
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
            'homeContent' => $homeContent,
            'aboutContent' => $aboutContent,
            'contactContent' => $contactContent,
            'heroSlides' => $heroSlides,
            'featureCards' => $featureCards,
            'galleryItems' => $galleryItems,
        ]);
    }
}
