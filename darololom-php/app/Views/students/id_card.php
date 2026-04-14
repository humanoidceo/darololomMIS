<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>کارت شناسایی دانش‌آموز</title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/student_card_print.css')) ?>">
    <style>
        .id-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .id-actions .btn {
            border: 0;
            border-radius: 10px;
            padding: 10px 18px;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
        }
        .id-actions .btn-print {
            background: #059669;
        }
        .id-actions .btn-back {
            background: #334155;
        }

        .id-card-shell {
            width: 440px;
            height: 620px;
            margin: 0 auto;
            background:
                radial-gradient(circle at top left, rgba(232, 87, 42, 0.16), transparent 34%),
                radial-gradient(circle at bottom right, rgba(15, 118, 110, 0.12), transparent 28%),
                linear-gradient(180deg, #fffaf5 0%, #ffffff 100%);
            border-radius: 28px;
            border: 1px solid rgba(232, 87, 42, 0.18);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
            overflow: hidden;
            direction: rtl;
            position: relative;
        }
        .id-card-shell::before,
        .id-card-shell::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
        }
        .id-card-shell::before {
            width: 180px;
            height: 180px;
            top: -74px;
            left: -54px;
            background: rgba(232, 87, 42, 0.08);
        }
        .id-card-shell::after {
            width: 160px;
            height: 160px;
            bottom: 76px;
            right: -60px;
            background: rgba(15, 118, 110, 0.08);
        }
        .id-card-header {
            background: linear-gradient(135deg, #b93817 0%, #e8572a 58%, #ffb04d 100%);
            padding: 22px 24px 96px;
            position: relative;
            overflow: hidden;
        }
        .id-card-header::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(120deg, rgba(255,255,255,0.12), transparent 38%),
                radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 36%);
        }
        .id-card-logo-circle {
            position: absolute;
            top: 24px;
            right: 24px;
            width: 52px;
            height: 52px;
            border-radius: 999px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.16);
            z-index: 1;
        }
        .id-card-logo-circle img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 999px;
        }
        .id-card-title {
            margin-top: 8px;
            text-align: center;
            color: #fff;
            position: relative;
            z-index: 1;
        }
        .id-card-title h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            line-height: 1.35;
        }
        .id-card-title p {
            margin: 8px 0 0;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.92);
        }
        .id-card-curve {
            margin-top: -34px;
        }
        .id-card-photo-wrap {
            display: flex;
            justify-content: center;
            margin-top: -88px;
            margin-bottom: 14px;
            position: relative;
            z-index: 4;
        }
        .id-card-photo {
            width: 126px;
            height: 126px;
            border-radius: 28px;
            border: 4px solid #fff;
            background: linear-gradient(180deg, #f8fafc, #e5e7eb);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.18);
        }
        .id-card-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .id-card-photo svg {
            width: 58px;
            height: 58px;
            color: #9ca3af;
        }
        .id-card-body {
            padding: 0 22px 18px;
            background: transparent;
            height: calc(100% - 208px);
            display: flex;
            flex-direction: column;
        }
        .id-card-name {
            text-align: center;
            margin-bottom: 14px;
        }
        .id-card-name h2 {
            margin: 0;
            font-size: 26px;
            color: #b93817;
            font-weight: 900;
            line-height: 1.2;
        }
        .id-card-name p {
            margin: 6px 0 0;
            font-size: 12px;
            color: #475569;
            font-weight: 700;
        }
        .id-card-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 12px;
            margin-top: 10px;
            border-radius: 999px;
            background: rgba(232, 87, 42, 0.1);
            color: #b93817;
            font-size: 11px;
            font-weight: 800;
            border: 1px solid rgba(232, 87, 42, 0.15);
        }
        .id-card-panel {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 20px;
            padding: 14px 14px 12px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        }
        .id-card-info {
            font-size: 14px;
        }
        .id-card-row {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed rgba(148, 163, 184, 0.45);
        }
        .id-card-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: 0;
        }
        .id-card-row .label {
            min-width: 94px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }
        .id-card-row .sep {
            color: #111827;
            font-weight: 700;
        }
        .id-card-row .value {
            color: #111827;
            font-weight: 700;
            flex: 1;
            line-height: 1.6;
        }
        .id-card-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }
        .id-card-meta-box {
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(241, 245, 249, 0.94));
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 16px;
            padding: 10px 12px;
            text-align: center;
        }
        .id-card-meta-box .meta-label {
            display: block;
            margin-bottom: 4px;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
        }
        .id-card-meta-box .meta-value {
            display: block;
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.5;
        }
        .id-card-footer {
            margin-top: auto;
            background: linear-gradient(135deg, #0f766e 0%, #1d4ed8 100%);
            color: #f8fafc;
            text-align: center;
            padding: 14px 18px;
            border-radius: 18px;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.9;
            letter-spacing: 0.1px;
        }
        .id-card-footer strong {
            display: block;
            font-size: 12px;
            margin-bottom: 2px;
        }

        @media print {
            .id-actions {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<?php
$imagePath = trim((string) ($student['image_path'] ?? ''));
$issueDateText = trim((string) ($issueDate ?? date('Y-m-d')));
$expiryDateText = trim((string) ($expiryDate ?? date('Y-m-d', strtotime('+1 year'))));
$levelName = trim((string) ($student['level_name'] ?? 'نامشخص'));
$validityYearsValue = (int) ($validityYears ?? (((string) ($student['level_code'] ?? '') === 'aali') ? 2 : 3));
$schoolAddress = 'چهارراهی پروژه تایمنی، جوار مسجد جامع الحاج سید منصور نادری، کابل';
$formatCardDate = static function (string $date): string {
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return e($date);
    }

    return to_persian_number(date('Y / m / d', $timestamp));
};
?>
<div class="id-actions">
    <button type="button" class="btn btn-print" onclick="window.print()">چاپ مستقیم</button>
    <a href="<?= e(url('/students')) ?>" class="btn btn-back">بازگشت</a>
</div>

<div id="printCard" class="id-card-shell">
    <div class="id-card-header">
        <div class="id-card-logo-circle">
            <img src="<?= e(url('/assets/images/logo.jpg')) ?>" alt="لوگو" onerror="this.style.display='none';">
        </div>
        <div class="id-card-title">
          <h4>دارالعلوم عالی الحاج سید منصور نادری</h4>
            <p>کارت شناسایی شاگردان</p>
        </div>
    </div>

    <div class="id-card-curve">
        <svg viewBox="0 0 440 60" width="100%" height="60" preserveAspectRatio="none">
            <path d="M 0,60 Q 220,0 440,60 L 440,60 L 0,60 Z" fill="white"/>
        </svg>
    </div>

    <div class="id-card-photo-wrap">
        <div class="id-card-photo">
            <?php if ($imagePath !== ''): ?>
                <img src="<?= e(url($imagePath)) ?>" alt="<?= e((string) ($student['name'] ?? 'دانش‌آموز')) ?>">
            <?php else: ?>
                <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
            <?php endif; ?>
        </div>
    </div>

    <div class="id-card-body">
        <div class="id-card-name">
            <h2><?= e((string) ($student['name'] ?? 'نام دانش‌آموز')) ?></h2>
            <p>فرزند <?= e((string) (($student['father_name'] ?? '') !== '' ? $student['father_name'] : '-')) ?></p>
        </div>

        <div class="id-card-panel">
            <div class="id-card-info">
                <div class="id-card-row">
                    <span class="label">سطح تحصیلی</span>
                    <span class="sep">:</span>
                    <span class="value"><?= e($levelName) ?></span>
                </div>
                <div class="id-card-row">
                    <span class="label">نمبر تذکره</span>
                    <span class="sep">:</span>
                    <span class="value"><?= e((string) (($student['id_number'] ?? '') !== '' ? $student['id_number'] : '-')) ?></span>
                </div>
               
                <div class="id-card-row">
                    <span class="label">تاریخ صدور</span>
                    <span class="sep">:</span>
                    <span class="value"><?= $formatCardDate($issueDateText) ?></span>
                </div>
                <div class="id-card-row">
                    <span class="label">تاریخ اعتبار</span>
                    <span class="sep">:</span>
                    <span class="value"><?= $formatCardDate($expiryDateText) ?></span>
                </div>
                 <div class="id-card-footer">
            <?= e($schoolAddress) ?>
        </div>
            </div>

        
        </div>

        
    </div>
</div>
</body>
</html>
