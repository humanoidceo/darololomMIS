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
        $searchQuery = $this->normalizeSearchQuery((string) ($_GET['q'] ?? ''));
        [$whereSql, $searchBindings] = $this->buildSearchFilter($searchQuery);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        $db = Database::connection();
        $countStmt = $db->prepare('SELECT COUNT(*) FROM theses' . $whereSql);
        $this->bindParams($countStmt, $searchBindings);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $pageSize));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $pageSize;
        }

        $stmt = $db->prepare(
            'SELECT *
             FROM theses' . $whereSql . '
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );
        $this->bindParams($stmt, $searchBindings);
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
            'searchQuery' => $searchQuery,
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
        $searchQuery = $this->normalizeSearchQuery((string) ($_GET['q'] ?? ''));
        [$whereSql, $searchBindings] = $this->buildSearchFilter($searchQuery);

        $formData = $_SESSION['_old'] ?? [];
        clear_old();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        $db = Database::connection();
        $countStmt = $db->prepare('SELECT COUNT(*) FROM theses' . $whereSql);
        $this->bindParams($countStmt, $searchBindings);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $pageSize));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $pageSize;
        }

        $stmt = $db->prepare(
            'SELECT *
             FROM theses' . $whereSql . '
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );
        $this->bindParams($stmt, $searchBindings);
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
            'searchQuery' => $searchQuery,
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
        $redirectUrl = $this->manageRedirectUrl();

        $id = $this->intParam($params, 'id');
        $thesis = $this->thesisById($id);
        if (!$thesis) {
            flash('error', 'پایان‌نامه مورد نظر پیدا نشد.');
            $this->redirect($redirectUrl);
        }

        $stmt = Database::connection()->prepare('DELETE FROM theses WHERE id = :id');
        $stmt->execute(['id' => $id]);

        flash('success', 'پایان‌نامه با موفقیت حذف شد.');
        $this->redirect($redirectUrl);
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

    private function normalizeSearchQuery(string $query): string
    {
        $query = preg_replace('/\s+/u', ' ', trim($query));

        return is_string($query) ? $query : '';
    }

    private function buildSearchFilter(string $searchQuery): array
    {
        if ($searchQuery === '') {
            return ['', []];
        }

        $terms = preg_split('/\s+/u', $searchQuery, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($terms === []) {
            return ['', []];
        }

        $clauses = [];
        $bindings = [];

        foreach (array_values($terms) as $index => $term) {
            $term = $this->normalizeSearchToken($term);
            $studentParam = ':search_student_' . $index;
            $advisorParam = ':search_advisor_' . $index;
            $yearParam = ':search_year_' . $index;
            $abstractParam = ':search_abstract_' . $index;
            $clauses[] = '(student_name LIKE ' . $studentParam
                . ' OR advisor_name LIKE ' . $advisorParam
                . ' OR CAST(completion_year AS CHAR) LIKE ' . $yearParam
                . ' OR abstract_text LIKE ' . $abstractParam . ')';
            $bindings[$studentParam] = '%' . $term . '%';
            $bindings[$advisorParam] = '%' . $term . '%';
            $bindings[$yearParam] = '%' . $term . '%';
            $bindings[$abstractParam] = '%' . $term . '%';
        }

        return [' WHERE ' . implode(' AND ', $clauses), $bindings];
    }

    private function bindParams(\PDOStatement $stmt, array $bindings): void
    {
        foreach ($bindings as $name => $value) {
            $stmt->bindValue($name, $value, PDO::PARAM_STR);
        }
    }

    private function normalizeSearchToken(string $value): string
    {
        return strtr($value, [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
    }

    private function manageRedirectUrl(): string
    {
        $query = $this->normalizeSearchQuery((string) ($_POST['redirect_q'] ?? ''));
        $page = max(1, (int) ($_POST['redirect_page'] ?? 1));
        $parameters = [];

        if ($query !== '') {
            $parameters['q'] = $query;
        }

        if ($page > 1) {
            $parameters['page'] = (string) $page;
        }

        return '/theses/manage' . ($parameters !== [] ? '?' . http_build_query($parameters) : '');
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
