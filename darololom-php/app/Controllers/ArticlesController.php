<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

final class ArticlesController extends Controller
{
    public function index(array $params = []): void
    {
        $this->authorize('access_teachers', 'شما اجازه دسترسی به بخش مقالات را ندارید.', '/');
        $this->ensureArticlesTable();

        $formData = $_SESSION['_old'] ?? [];
        clear_old();

        $selectedAuthorId = (int) ($formData['author_id'] ?? 0);
        $selectedYear = (int) ($formData['publication_year'] ?? 0);
        if ($selectedYear < 1300 || $selectedYear > 1500) {
            $selectedYear = 0;
        }

        $selectedAuthor = $selectedAuthorId > 0 ? $this->teacherById($selectedAuthorId) : null;
        $selectedAuthorLabel = trim((string) ($formData['author_name_display'] ?? ''));
        if ($selectedAuthorLabel === '' && $selectedAuthor) {
            $selectedAuthorLabel = $this->teacherLabel($selectedAuthor);
        }

        $db = Database::connection();
        $stmt = $db->query(
            'SELECT a.*, t.name AS author_name, t.father_name AS author_father_name
             FROM articles a
             JOIN teachers t ON t.id = a.author_id
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT 20'
        );

        $this->render('articles/index', [
            'title' => 'مقالات',
            'articles' => $stmt->fetchAll(),
            'selectedAuthorId' => $selectedAuthorId,
            'selectedAuthorLabel' => $selectedAuthorLabel !== '' ? $selectedAuthorLabel : 'انتخاب مولف',
            'selectedYear' => $selectedYear,
            'selectedYearLabel' => $selectedYear > 0 ? to_persian_number((string) $selectedYear) : 'انتخاب سال تالیف',
            'formAction' => url('/articles/store'),
        ]);
    }

    public function store(array $params = []): void
    {
        $this->authorize('access_teachers', 'شما اجازه ثبت مقاله را ندارید.', '/articles');
        $this->csrfCheck();
        $this->ensureArticlesTable();

        $authorId = (int) ($_POST['author_id'] ?? 0);
        $publicationYear = (int) ($_POST['publication_year'] ?? 0);

        if ($authorId <= 0) {
            with_old($_POST);
            flash('error', 'انتخاب مولف الزامی است.');
            $this->redirect('/articles');
        }

        if ($publicationYear < 1300 || $publicationYear > 1500) {
            with_old($_POST);
            flash('error', 'سال تالیف باید بین ۱۳۰۰ تا ۱۵۰۰ باشد.');
            $this->redirect('/articles');
        }

        if (!$this->teacherExists($authorId)) {
            with_old($_POST);
            flash('error', 'مولف انتخاب‌شده معتبر نیست.');
            $this->redirect('/articles');
        }

        $uploadError = $this->validateArticleFile();
        if ($uploadError !== null) {
            with_old($_POST);
            flash('error', $uploadError);
            $this->redirect('/articles');
        }

        $filePath = upload_file('article_file', 'articles', ['pdf', 'doc', 'docx']);
        if ($filePath === null) {
            with_old($_POST);
            flash('error', 'اپلود فایل مقاله ناموفق بود.');
            $this->redirect('/articles');
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO articles (author_id, publication_year, file_path, created_by, created_at)
             VALUES (:author_id, :publication_year, :file_path, :created_by, NOW())'
        );
        $stmt->execute([
            'author_id' => $authorId,
            'publication_year' => $publicationYear,
            'file_path' => $filePath,
            'created_by' => auth_id() ?: null,
        ]);

        clear_old();
        flash('success', 'مقاله با موفقیت اپلود شد.');
        $this->redirect('/articles');
    }

    public function apiTeachers(array $params = []): void
    {
        $this->authorize('access_teachers', 'شما اجازه جستجوی اساتید را ندارید.', '/articles');

        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $db = Database::connection();
        $whereSql = '';
        if ($q !== '') {
            $whereSql = 'WHERE (t.name LIKE :q OR t.father_name LIKE :q OR t.id_number LIKE :q)';
        }

        $stmt = $db->prepare(
            'SELECT t.id, t.name, t.father_name
             FROM teachers t
             ' . $whereSql . '
             ORDER BY t.name ASC, t.id ASC
             LIMIT :limit OFFSET :offset'
        );

        if ($q !== '') {
            $stmt->bindValue(':q', '%' . $q . '%');
        }
        $stmt->bindValue(':limit', $limit + 1, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $limit);
        }

        $items = array_map(function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'name' => trim((string) ($row['name'] ?? '')),
                'father_name' => trim((string) ($row['father_name'] ?? '')),
                'label' => $this->teacherLabel($row),
            ];
        }, $rows);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'items' => $items,
            'has_more' => $hasMore,
            'page' => $page,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function teacherExists(int $teacherId): bool
    {
        $stmt = Database::connection()->prepare('SELECT id FROM teachers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $teacherId]);
        return (bool) $stmt->fetch();
    }

    private function teacherById(int $teacherId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, father_name
             FROM teachers
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $teacherId]);
        $teacher = $stmt->fetch();
        return $teacher ?: null;
    }

    private function teacherLabel(array $teacher): string
    {
        $name = trim((string) ($teacher['name'] ?? ''));
        $fatherName = trim((string) ($teacher['father_name'] ?? ''));
        $label = $name !== '' ? $name : '—';
        if ($fatherName !== '') {
            $label .= ' (' . $fatherName . ')';
        }

        return $label;
    }

    private function validateArticleFile(): ?string
    {
        if (empty($_FILES['article_file']) || ($_FILES['article_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'لطفاً فایل مقاله را انتخاب کنید.';
        }

        if (($_FILES['article_file']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return 'اپلود فایل مقاله ناموفق بود.';
        }

        $original = (string) ($_FILES['article_file']['name'] ?? '');
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
            return 'فایل مقاله باید فقط PDF یا Word باشد.';
        }

        return null;
    }

    private function ensureArticlesTable(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        $db = Database::connection();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS articles (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                author_id INT UNSIGNED NOT NULL,
                publication_year SMALLINT UNSIGNED NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                created_by INT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_articles_author (author_id),
                INDEX idx_articles_year (publication_year),
                CONSTRAINT fk_articles_author FOREIGN KEY (author_id) REFERENCES teachers(id) ON DELETE CASCADE,
                CONSTRAINT fk_articles_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $ensured = true;
    }
}
