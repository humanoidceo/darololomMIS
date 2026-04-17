<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

final class ThesesController extends Controller
{
    public function publicIndex(array $params = []): void
    {
        $this->ensureThesesTable();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        $db = Database::connection();
        $countStmt = $db->query('SELECT COUNT(*) FROM theses');
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $pageSize));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $pageSize;
        }

        $stmt = $db->prepare(
            'SELECT *
             FROM theses
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $this->render('theses/public_index', [
            'title' => 'پایان‌نامه‌ها',
            'theses' => $stmt->fetchAll(),
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'totalPages' => $totalPages,
        ]);
    }

    public function show(array $params = []): void
    {
        $this->ensureThesesTable();
        $id = $this->intParam($params, 'id');

        $thesis = $this->thesisById($id);
        if (!$thesis) {
            http_response_code(404);
            $this->render('theses/show', [
                'title' => 'پایان‌نامه پیدا نشد',
                'thesis' => null,
            ]);
            return;
        }

        $this->render('theses/show', [
            'title' => 'مطالعه چکیده پایان‌نامه',
            'thesis' => $thesis,
        ]);
    }

    public function index(array $params = []): void
    {
        $this->onlySuperAdmin('تنها سوپر ادمین اجازه مدیریت پایان‌نامه‌ها را دارد.', '/dashboard');
        $this->ensureThesesTable();

        $formData = $_SESSION['_old'] ?? [];
        clear_old();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        $db = Database::connection();
        $countStmt = $db->query('SELECT COUNT(*) FROM theses');
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $pageSize));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $pageSize;
        }

        $stmt = $db->prepare(
            'SELECT *
             FROM theses
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $this->render('theses/index', [
            'title' => 'پایان‌نامه‌ها',
            'theses' => $stmt->fetchAll(),
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'totalPages' => $totalPages,
            'formAction' => url('/theses/store'),
            'oldStudentName' => (string) ($formData['student_name'] ?? ''),
            'oldAdvisorName' => (string) ($formData['advisor_name'] ?? ''),
            'oldYear' => (string) ($formData['completion_year'] ?? ''),
            'oldAbstract' => (string) ($formData['abstract_text'] ?? ''),
        ]);
    }

    public function store(array $params = []): void
    {
        $this->onlySuperAdmin('تنها سوپر ادمین اجازه مدیریت پایان‌نامه‌ها را دارد.', '/dashboard');
        $this->csrfCheck();
        $this->ensureThesesTable();

        $studentName = trim((string) ($_POST['student_name'] ?? ''));
        $advisorName = trim((string) ($_POST['advisor_name'] ?? ''));
        $completionYear = (int) ($_POST['completion_year'] ?? 0);
        $abstractText = trim((string) ($_POST['abstract_text'] ?? ''));

        if (mb_strlen($studentName) < 3) {
            with_old($_POST);
            flash('error', 'نام محصل حداقل ۳ حرف باشد.');
            $this->redirect('/theses/manage');
        }

        if (mb_strlen($advisorName) < 3) {
            with_old($_POST);
            flash('error', 'نام استاد رهنما حداقل ۳ حرف باشد.');
            $this->redirect('/theses/manage');
        }

        if ($completionYear < 1 || $completionYear > 2500) {
            with_old($_POST);
            flash('error', 'سال باید بین ۱ تا ۲۵۰۰ باشد.');
            $this->redirect('/theses/manage');
        }

        if (mb_strlen($abstractText) < 30) {
            with_old($_POST);
            flash('error', 'چکیده پایان‌نامه حداقل ۳۰ حرف باشد.');
            $this->redirect('/theses/manage');
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO theses (student_name, advisor_name, completion_year, abstract_text, created_by, created_at)
             VALUES (:student_name, :advisor_name, :completion_year, :abstract_text, :created_by, NOW())'
        );
        $stmt->execute([
            'student_name' => $studentName,
            'advisor_name' => $advisorName,
            'completion_year' => $completionYear,
            'abstract_text' => $abstractText,
            'created_by' => auth_id() ?: null,
        ]);

        clear_old();
        flash('success', 'پایان‌نامه با موفقیت ثبت شد.');
        $this->redirect('/theses/manage');
    }

    public function destroy(array $params = []): void
    {
        $this->onlySuperAdmin('تنها سوپر ادمین اجازه مدیریت پایان‌نامه‌ها را دارد.', '/dashboard');
        $this->csrfCheck();
        $this->ensureThesesTable();

        $id = $this->intParam($params, 'id');
        $thesis = $this->thesisById($id);
        if (!$thesis) {
            flash('error', 'پایان‌نامه مورد نظر پیدا نشد.');
            $this->redirect('/theses/manage');
        }

        $stmt = Database::connection()->prepare('DELETE FROM theses WHERE id = :id');
        $stmt->execute(['id' => $id]);

        flash('success', 'پایان‌نامه با موفقیت حذف شد.');
        $this->redirect('/theses/manage');
    }

    private function thesisById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT *
             FROM theses
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $thesis = $stmt->fetch();

        return $thesis ?: null;
    }

    private function ensureThesesTable(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        $db = Database::connection();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS theses (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_name VARCHAR(255) NOT NULL,
                advisor_name VARCHAR(255) NOT NULL,
                completion_year SMALLINT UNSIGNED NOT NULL,
                abstract_text TEXT NOT NULL,
                created_by INT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_theses_student_name (student_name),
                INDEX idx_theses_advisor_name (advisor_name),
                INDEX idx_theses_completion_year (completion_year),
                CONSTRAINT fk_theses_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $ensured = true;
    }
}
