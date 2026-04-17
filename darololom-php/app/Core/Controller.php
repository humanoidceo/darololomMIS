<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function csrfCheck(): void
    {
        $token = $_POST['_token'] ?? '';
        if (!hash_equals(csrf_token(), $token)) {
            if (request_accepts_html()) {
                if (request_exceeds_post_max_size()) {
                    flash('error', 'حجم فایل یا مجموع فایل‌های انتخاب‌شده از حد مجاز سرور بیشتر است. ' . upload_limits_text());
                } else {
                    flash('error', 'اعتبار فرم منقضی شده یا درخواست معتبر نیست. لطفاً صفحه را تازه کرده و دوباره تلاش کنید.');
                }

                $this->redirect(previous_path('/'));
            }

            http_response_code(419);
            echo request_exceeds_post_max_size()
                ? 'Uploaded data exceeds server limits'
                : 'CSRF token mismatch';
            exit;
        }
    }

    protected function intParam(array $params, string $key): int
    {
        return (int) ($params[$key] ?? 0);
    }

    protected function requireAuth(): array
    {
        $user = auth_user();
        if (!$user) {
            $this->redirect('/login');
        }

        return $user;
    }

    protected function onlySuperAdmin(string $message = 'شما اجازه دسترسی به این بخش را ندارید.', string $redirect = '/'): void
    {
        $this->requireAuth();
        if (is_super_admin()) {
            return;
        }

        flash('error', $message);
        $this->redirect($redirect);
    }

    protected function authorize(string $permission, string $message = 'شما اجازه انجام این عملیات را ندارید.', string $redirect = '/'): void
    {
        $this->requireAuth();
        if (can($permission)) {
            return;
        }

        flash('error', $message);
        $this->redirect($redirect);
    }
}
