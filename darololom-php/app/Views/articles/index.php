<?php
$selectedAuthorId = (int) ($selectedAuthorId ?? 0);
$selectedAuthorLabel = trim((string) ($selectedAuthorLabel ?? ''));
$selectedYear = (int) ($selectedYear ?? 0);
$selectedYearLabel = trim((string) ($selectedYearLabel ?? ''));
?>

<div class="section-title">
    <h2>مقالات</h2>
</div>

<div class="news-thumb article-page-shell">
    <div class="news-info">
        <form method="post" action="<?= e((string) $formAction) ?>" enctype="multipart/form-data" class="module-form article-form-grid">
            <?= csrf_field() ?>

            <div class="form-group">
                <label>مولف</label>
                <input type="hidden" name="author_id" id="article_author_id" value="<?= e((string) $selectedAuthorId) ?>">
                <input type="hidden" name="author_name_display" id="article_author_name_display" value="<?= e($selectedAuthorLabel) ?>">

                <div class="subject-instructor-combo article-author-combo" id="articleAuthorCombo" data-api-url="<?= e(url('/api/articles/teachers')) ?>">
                    <button type="button" class="form-control subject-instructor-trigger" id="articleAuthorTrigger" aria-haspopup="listbox" aria-expanded="false">
                        <span id="articleAuthorTriggerText"><?= e($selectedAuthorLabel !== '' ? $selectedAuthorLabel : 'انتخاب مولف') ?></span>
                        <span class="subject-instructor-arrow" aria-hidden="true">▾</span>
                    </button>
                    <div class="subject-instructor-dropdown" id="articleAuthorDropdown" hidden>
                        <input type="text" id="articleAuthorSearch" class="form-control" placeholder="جستجوی مولف..." autocomplete="off">
                        <div class="subject-instructor-list" id="articleAuthorList" role="listbox"></div>
                        <div class="subject-instructor-status" id="articleAuthorStatus"></div>
                    </div>
                </div>
                <small class="field-help">لیست اساتید به صورت سرچ‌دار و اسکرول ۵تایی نمایش داده می‌شود.</small>
            </div>

            <div class="form-group">
                <label>سال تالیف</label>
                <input type="hidden" name="publication_year" id="article_publication_year" value="<?= e($selectedYear > 0 ? (string) $selectedYear : '') ?>">

                <div class="student-year-combo article-year-combo" id="articleYearCombo">
                    <button type="button" class="form-control student-year-trigger" id="articleYearTrigger" aria-haspopup="listbox" aria-expanded="false">
                        <span id="articleYearTriggerText"><?= e($selectedYearLabel !== '' ? $selectedYearLabel : 'انتخاب سال تالیف') ?></span>
                        <span class="student-year-arrow" aria-hidden="true">▾</span>
                    </button>
                    <div class="student-year-dropdown" id="articleYearDropdown" hidden>
                        <input type="text" id="articleYearSearch" class="form-control" placeholder="جستجوی سال..." autocomplete="off">
                        <div class="student-year-list" id="articleYearList" role="listbox"></div>
                        <div class="student-year-status" id="articleYearStatus"></div>
                    </div>
                </div>
                <small class="field-help">سال‌های ۱۳۰۰ تا ۱۵۰۰ با اسکرول ۵ دانه‌ ۵ دانه نمایش داده می‌شوند.</small>
            </div>

            <div class="form-group full">
                <label>اپلود فایل مقاله</label>
                <input class="form-control" type="file" name="article_file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                <small class="field-help">فقط فایل‌های Word و PDF پذیرفته می‌شود.</small>
            </div>

            <div class="form-actions full article-form-actions">
                <button class="section-btn btn btn-default article-save-btn" type="submit">ذخیره مقاله</button>
                <a class="btn btn-default article-cancel-btn" href="<?= e(url('/articles')) ?>">مشاهده صفحه عمومی</a>
            </div>
        </form>
    </div>
</div>

<div class="news-thumb article-list-shell">
    <div class="news-info">
        <div class="article-list-head">
            <h3>آخرین مقالات اپلودشده</h3>
            <p>برای هر مقاله می‌توانید فایل را مشاهده یا دانلود کنید.</p>
        </div>

        <?php if (!empty($articles)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover article-table">
                    <thead>
                    <tr>
                        <th>مولف</th>
                        <th>سال تالیف</th>
                        <th>فایل</th>
                        <th>تاریخ ثبت</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($articles as $article): ?>
                        <?php
                        $authorName = trim((string) ($article['author_name'] ?? ''));
                        $authorFatherName = trim((string) ($article['author_father_name'] ?? ''));
                        $authorLabel = $authorName !== '' ? $authorName : '—';
                        if ($authorFatherName !== '') {
                            $authorLabel .= ' (' . $authorFatherName . ')';
                        }
                        $filePath = trim((string) ($article['file_path'] ?? ''));
                        $filename = basename($filePath);
                        ?>
                        <tr>
                            <td><?= e($authorLabel) ?></td>
                            <td><?= e(to_persian_number((string) ((int) ($article['publication_year'] ?? 0)))) ?></td>
                            <td class="article-file-cell">
                                <a class="btn btn-xs btn-info" href="<?= e(url($filePath)) ?>" target="_blank">مشاهده</a>
                                <a class="btn btn-xs btn-default" href="<?= e(url($filePath)) ?>" download="<?= e($filename) ?>">دانلود</a>
                            </td>
                            <td><?= e((string) ($article['created_at'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="article-empty-state">
                هنوز هیچ مقاله‌ای ثبت نشده است.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var authorCombo = document.getElementById('articleAuthorCombo');
    var yearCombo = document.getElementById('articleYearCombo');
    if (!authorCombo || !yearCombo) {
        return;
    }

    function normalizeDigits(value) {
        var map = {
            '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
            '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
            '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
            '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
        };

        return String(value || '').replace(/[۰-۹٠-٩]/g, function (char) {
            return map[char] || char;
        });
    }

    function toPersianDigits(value) {
        var en = '0123456789';
        var fa = '۰۱۲۳۴۵۶۷۸۹';
        return String(value || '').replace(/\d/g, function (digit) {
            var index = en.indexOf(digit);
            return index >= 0 ? fa[index] : digit;
        });
    }

    (function setupAuthorCombo() {
        var apiUrl = authorCombo.getAttribute('data-api-url') || '';
        var trigger = document.getElementById('articleAuthorTrigger');
        var triggerText = document.getElementById('articleAuthorTriggerText');
        var dropdown = document.getElementById('articleAuthorDropdown');
        var searchInput = document.getElementById('articleAuthorSearch');
        var list = document.getElementById('articleAuthorList');
        var status = document.getElementById('articleAuthorStatus');
        var authorIdInput = document.getElementById('article_author_id');
        var authorNameInput = document.getElementById('article_author_name_display');

        var state = {
            opened: false,
            loadedOnce: false,
            loading: false,
            page: 1,
            hasMore: true,
            query: '',
            debounceTimer: null
        };

        function setStatus(message) {
            if (status) {
                status.textContent = message;
            }
        }

        function selectedAuthorId() {
            return parseInt((authorIdInput && authorIdInput.value) || '0', 10) || 0;
        }

        function setOpened(opened) {
            state.opened = opened;
            if (dropdown) {
                dropdown.hidden = !opened;
            }
            if (trigger) {
                trigger.setAttribute('aria-expanded', opened ? 'true' : 'false');
            }

            if (opened && !state.loadedOnce) {
                fetchPage(1, '', true);
            }
            if (opened && searchInput) {
                searchInput.focus();
            }
        }

        function buildOption(item) {
            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'subject-instructor-option';
            option.setAttribute('role', 'option');
            option.setAttribute('data-id', String(item.id));
            option.textContent = item.label || item.name || '—';

            if (item.id === selectedAuthorId()) {
                option.classList.add('is-selected');
            }

            option.addEventListener('click', function () {
                var label = item.label || item.name || '—';
                if (authorIdInput) authorIdInput.value = String(item.id || '');
                if (authorNameInput) authorNameInput.value = label;
                if (triggerText) triggerText.textContent = label;
                setOpened(false);
            });

            return option;
        }

        function renderItems(items, reset) {
            if (!list) {
                return;
            }
            if (reset) {
                list.innerHTML = '';
            }

            if (!items || items.length === 0) {
                if (reset) {
                    setStatus('مولفی پیدا نشد.');
                }
                return;
            }

            var fragment = document.createDocumentFragment();
            items.forEach(function (item) {
                fragment.appendChild(buildOption(item));
            });
            list.appendChild(fragment);
        }

        function fetchPage(page, query, reset) {
            if (!apiUrl || state.loading) {
                return;
            }
            state.loading = true;
            setStatus('در حال بارگذاری...');

            var url = new URL(apiUrl, window.location.origin);
            url.searchParams.set('page', String(page));
            if (query) {
                url.searchParams.set('q', query);
            }

            fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    return res.json();
                })
                .then(function (data) {
                    state.loadedOnce = true;
                    state.page = page;
                    state.hasMore = Boolean(data && data.has_more);
                    var items = data && Array.isArray(data.items) ? data.items : [];
                    renderItems(items, reset);

                    if (!items.length && page === 1) {
                        setStatus('مولفی پیدا نشد.');
                    } else if (state.hasMore) {
                        setStatus('برای دریافت موارد بیشتر اسکرول کنید.');
                    } else {
                        setStatus('پایان لیست.');
                    }
                })
                .catch(function () {
                    setStatus('بارگذاری لیست مولفان ناموفق بود.');
                })
                .finally(function () {
                    state.loading = false;
                });
        }

        if (trigger) {
            trigger.addEventListener('click', function () {
                setOpened(!state.opened);
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var nextQuery = (searchInput.value || '').trim();
                if (state.debounceTimer) {
                    clearTimeout(state.debounceTimer);
                }
                state.debounceTimer = setTimeout(function () {
                    state.query = nextQuery;
                    state.page = 1;
                    state.hasMore = true;
                    fetchPage(1, state.query, true);
                }, 220);
            });
        }

        if (list) {
            list.addEventListener('scroll', function () {
                if (!state.opened || state.loading || !state.hasMore) {
                    return;
                }
                var remaining = list.scrollHeight - list.scrollTop - list.clientHeight;
                if (remaining <= 30) {
                    fetchPage(state.page + 1, state.query, false);
                }
            });
        }

        document.addEventListener('click', function (event) {
            if (!state.opened) {
                return;
            }
            if (!authorCombo.contains(event.target)) {
                setOpened(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && state.opened) {
                setOpened(false);
            }
        });
    })();

    (function setupYearCombo() {
        var trigger = document.getElementById('articleYearTrigger');
        var triggerText = document.getElementById('articleYearTriggerText');
        var dropdown = document.getElementById('articleYearDropdown');
        var searchInput = document.getElementById('articleYearSearch');
        var list = document.getElementById('articleYearList');
        var status = document.getElementById('articleYearStatus');
        var yearInput = document.getElementById('article_publication_year');

        var allYears = [];
        for (var year = 1500; year >= 1300; year -= 1) {
            allYears.push(year);
        }

        var state = {
            opened: false,
            loading: false,
            page: 1,
            perPage: 5,
            hasMore: true,
            query: '',
            debounceTimer: null
        };

        function selectedYear() {
            return parseInt((yearInput && yearInput.value) || '0', 10) || 0;
        }

        function setStatus(message) {
            if (status) {
                status.textContent = message;
            }
        }

        function filteredYears() {
            if (!state.query) {
                return allYears.slice();
            }

            return allYears.filter(function (item) {
                return String(item).indexOf(state.query) !== -1;
            });
        }

        function buildOption(yearValue) {
            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'student-year-option';
            option.setAttribute('role', 'option');
            option.setAttribute('data-year', String(yearValue));
            option.textContent = toPersianDigits(yearValue);

            if (yearValue === selectedYear()) {
                option.classList.add('is-selected');
            }

            option.addEventListener('click', function () {
                if (yearInput) yearInput.value = String(yearValue);
                if (triggerText) triggerText.textContent = toPersianDigits(yearValue);
                setOpened(false);
            });

            return option;
        }

        function renderPage(reset) {
            if (!list) {
                return;
            }
            if (reset) {
                list.innerHTML = '';
                state.page = 1;
            }

            var filtered = filteredYears();
            var start = (state.page - 1) * state.perPage;
            var end = start + state.perPage;
            var chunk = filtered.slice(start, end);

            if (reset && chunk.length === 0) {
                setStatus('سال مورد نظر پیدا نشد.');
                state.hasMore = false;
                return;
            }

            var fragment = document.createDocumentFragment();
            chunk.forEach(function (yearValue) {
                fragment.appendChild(buildOption(yearValue));
            });
            list.appendChild(fragment);

            state.hasMore = end < filtered.length;
            setStatus(state.hasMore ? 'برای مشاهده موارد بیشتر اسکرول کنید.' : 'پایان لیست.');
        }

        function loadMore() {
            if (state.loading || !state.hasMore) {
                return;
            }
            state.loading = true;
            state.page += 1;
            renderPage(false);
            state.loading = false;
        }

        function setOpened(opened) {
            state.opened = opened;
            if (dropdown) {
                dropdown.hidden = !opened;
            }
            if (trigger) {
                trigger.setAttribute('aria-expanded', opened ? 'true' : 'false');
            }
            if (opened) {
                renderPage(true);
                if (searchInput) searchInput.focus();
            }
        }

        if (trigger) {
            trigger.addEventListener('click', function () {
                setOpened(!state.opened);
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var nextQuery = normalizeDigits((searchInput.value || '').trim());
                if (state.debounceTimer) {
                    clearTimeout(state.debounceTimer);
                }
                state.debounceTimer = setTimeout(function () {
                    state.query = nextQuery;
                    renderPage(true);
                }, 220);
            });
        }

        if (list) {
            list.addEventListener('scroll', function () {
                if (!state.opened || state.loading || !state.hasMore) {
                    return;
                }
                var remaining = list.scrollHeight - list.scrollTop - list.clientHeight;
                if (remaining <= 30) {
                    loadMore();
                }
            });
        }

        document.addEventListener('click', function (event) {
            if (!state.opened) {
                return;
            }
            if (!yearCombo.contains(event.target)) {
                setOpened(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && state.opened) {
                setOpened(false);
            }
        });
    })();
})();
</script>
