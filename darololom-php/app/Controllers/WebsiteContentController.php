<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\WebsiteContent;

final class WebsiteContentController extends Controller
{
    public function index(array $params = []): void
    {
        $this->onlySuperAdmin('تنها سوپر ادمین اجازه مدیریت محتوای وبسایت را دارد.', '/dashboard');
        clear_old();

        $content = WebsiteContent::load();

        $this->render('website_content/index', [
            'title' => 'ادیت محتوای وبسایت',
            'content' => $content,
        ]);
    }

    public function update(array $params = []): void
    {
        $this->onlySuperAdmin('تنها سوپر ادمین اجازه مدیریت محتوای وبسایت را دارد.', '/dashboard');
        $this->csrfCheck();

        $content = $this->contentFromRequest(WebsiteContent::load());

        try {
            $content = $this->handleUploads($content);
            WebsiteContent::save($content);
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('/website-content');
        }

        flash('success', 'محتوای وبسایت با موفقیت ذخیره شد.');
        $this->redirect('/website-content');
    }

    private function contentFromRequest(array $current): array
    {
        $content = $current;

        $content['home']['hero_kicker'] = trim((string) ($_POST['home_hero_kicker'] ?? ''));
        $content['home']['hero_title'] = trim((string) ($_POST['home_hero_title'] ?? ''));
        $content['home']['hero_text'] = trim((string) ($_POST['home_hero_text'] ?? ''));

        foreach ($content['home']['slides'] as $index => $slide) {
            $item = $index + 1;
            $content['home']['slides'][$index]['title'] = trim((string) ($_POST['home_slide_' . $item . '_title'] ?? ''));
            $content['home']['slides'][$index]['text'] = trim((string) ($_POST['home_slide_' . $item . '_text'] ?? ''));
            $content['home']['slides'][$index]['alt'] = trim((string) ($_POST['home_slide_' . $item . '_alt'] ?? ''));
        }

        $content['about']['title'] = trim((string) ($_POST['about_title'] ?? ''));
        $content['about']['lead'] = trim((string) ($_POST['about_lead'] ?? ''));
        $content['about']['paragraph_1'] = trim((string) ($_POST['about_paragraph_1'] ?? ''));
        $content['about']['paragraph_2'] = trim((string) ($_POST['about_paragraph_2'] ?? ''));
        $content['about']['main_image_alt'] = trim((string) ($_POST['about_main_image_alt'] ?? ''));
        $content['about']['secondary_image_alt'] = trim((string) ($_POST['about_secondary_image_alt'] ?? ''));
        $content['about']['points'] = $this->normalizeLines((string) ($_POST['about_points'] ?? ''));

        $content['contact']['title'] = trim((string) ($_POST['contact_title'] ?? ''));
        $content['contact']['lead'] = trim((string) ($_POST['contact_lead'] ?? ''));
        $content['contact']['intro_title'] = trim((string) ($_POST['contact_intro_title'] ?? ''));
        $content['contact']['intro_text'] = trim((string) ($_POST['contact_intro_text'] ?? ''));
        $content['contact']['address'] = trim((string) ($_POST['contact_address'] ?? ''));
        $content['contact']['phone'] = trim((string) ($_POST['contact_phone'] ?? ''));
        $content['contact']['quick_title'] = trim((string) ($_POST['contact_quick_title'] ?? ''));

        return $content;
    }

    private function handleUploads(array $content): array
    {
        foreach ($content['home']['slides'] as $index => $slide) {
            $item = $index + 1;
            $content['home']['slides'][$index]['image'] = $this->uploadedImageOrExisting(
                'home_slide_' . $item . '_image',
                'website_content/home',
                (string) ($slide['image'] ?? '')
            );
        }

        $content['about']['main_image'] = $this->uploadedImageOrExisting(
            'about_main_image',
            'website_content/about',
            (string) ($content['about']['main_image'] ?? '')
        );

        $content['about']['secondary_image'] = $this->uploadedImageOrExisting(
            'about_secondary_image',
            'website_content/about',
            (string) ($content['about']['secondary_image'] ?? '')
        );

        return $content;
    }

    private function uploadedImageOrExisting(string $field, string $subDir, string $existing): string
    {
        $uploaded = upload_file($field, $subDir, ['jpg', 'jpeg', 'png', 'webp']);
        if ($uploaded === null && $this->isFileUploaded($field)) {
            throw new \RuntimeException('آپلود یکی از عکس‌ها ناموفق بود. فقط JPG، PNG و WEBP مجاز است.');
        }

        return $uploaded ?? $existing;
    }

    private function isFileUploaded(string $field): bool
    {
        return !empty($_FILES[$field])
            && (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    private function normalizeLines(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $items = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $items[] = $line;
            }
        }

        return $items;
    }
}
