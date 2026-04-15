<?php

declare(strict_types=1);

namespace App\Core;

final class WebsiteContent
{
    public static function load(): array
    {
        $defaults = self::defaults();
        $path = self::storagePath();

        if (!is_file($path)) {
            return $defaults;
        }

        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return self::merge($defaults, $decoded);
    }

    public static function save(array $content): void
    {
        $path = self::storagePath();
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create website content storage directory.');
        }

        $payload = json_encode(
            $content,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($payload === false) {
            throw new \RuntimeException('Unable to encode website content.');
        }

        if (file_put_contents($path, $payload) === false) {
            throw new \RuntimeException('Unable to save website content.');
        }
    }

    public static function defaults(): array
    {
        return [
            'home' => [
                'hero_kicker' => 'دارالعلوم عالی الحاج سید منصور نادری',
                'hero_title' => 'مرکز آموزشی منظم برای رشد علمی، اخلاقی و معرفی حرفه‌ای فعالیت‌ها',
                'hero_text' => 'این وبسایت برای معرفی دارالعلوم، دسترسی عمومی به مقالات، نمایش فعالیت‌های آموزشی و ایجاد ارتباط روشن و حرفه‌ای با مراجعین و علاقه‌مندان طراحی شده است.',
                'slides' => [
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
                ],
            ],
            'about' => [
                'title' => 'درباره ما',
                'lead' => 'دارالعلوم با رویکرد آموزشی، اخلاقی و اداری منظم، محیطی سالم و روشن برای رشد علمی شاگردان فراهم می‌سازد.',
                'paragraph_1' => 'دارالعلوم عالی الحاج سید منصور نادری یک نهاد آموزشی متعهد به تربیه نسل آگاه، منظم و مسئولیت‌پذیر است. این مرکز با استفاده از کادر علمی مجرب و ساختار اداری منظم، تلاش می‌کند خدمات آموزشی را با کیفیت بهتر و دسترسی روشن‌تر ارائه نماید.',
                'paragraph_2' => 'تمرکز ما بر آموزش مؤثر، انضباط آموزشی، شفافیت در مدیریت و فراهم‌سازی بستر مناسب برای دسترسی شاگردان و اساتید به منابع علمی است. صفحه مقالات نیز بخشی از همین رویکرد برای اشتراک دانش و محتوای علمی با مردم می‌باشد.',
                'points' => [
                    'کادر علمی متعهد',
                    'مدیریت منظم اطلاعات',
                    'محیط آموزشی سالم',
                    'دسترسی عمومی به مقالات',
                ],
                'main_image' => '/assets/images/chiefwithallstaff.jpg',
                'main_image_alt' => 'کارمندان دارالعلوم',
                'secondary_image' => '/assets/images/allstaff.jpg',
                'secondary_image_alt' => 'تیم دارالعلوم',
            ],
            'contact' => [
                'title' => 'تماس با ما',
                'lead' => 'برای دریافت معلومات بیشتر یا ایجاد ارتباط مستقیم با دارالعلوم، از این بخش استفاده کنید.',
                'intro_title' => 'راه‌های ارتباطی',
                'intro_text' => 'برای دریافت معلومات بیشتر، پیگیری امور آموزشی و یا برقراری ارتباط رسمی، می‌توانید از راه‌های زیر با دارالعلوم در تماس شوید.',
                'address' => 'جوار مسجد الحاج سید منصور نادری، چهارراهی پروژه تایمنی، کابل، افغانستان',
                'phone' => '۰۷۷۰۹۲۲۷۹۰',
                'quick_title' => 'دسترسی سریع',
            ],
        ];
    }

    private static function storagePath(): string
    {
        return dirname(__DIR__, 2) . '/storage/website_content.json';
    }

    private static function merge(mixed $defaults, mixed $stored): mixed
    {
        if (!is_array($defaults) || !is_array($stored)) {
            return $stored;
        }

        if (self::isList($defaults)) {
            $firstDefault = $defaults[0] ?? null;
            if (!is_array($firstDefault)) {
                return array_values($stored);
            }

            $merged = [];
            foreach ($defaults as $index => $defaultItem) {
                $merged[$index] = array_key_exists($index, $stored)
                    ? self::merge($defaultItem, $stored[$index])
                    : $defaultItem;
            }
            return $merged;
        }

        $merged = $defaults;
        foreach ($defaults as $key => $defaultValue) {
            if (array_key_exists($key, $stored)) {
                $merged[$key] = self::merge($defaultValue, $stored[$key]);
            }
        }

        return $merged;
    }

    private static function isList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
