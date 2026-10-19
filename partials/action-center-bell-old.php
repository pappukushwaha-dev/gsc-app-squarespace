<?php
/**
 * partials/action-center-bell.php  (Squarespace)
 *
 * Ported from the Wix app. The markup, styling and behaviour are
 * unchanged — only the two lines that resolve the instance and the base
 * URL differ, because this app does both differently.
 *
 * Action Center button for the navbar.
 * Include from partials/navbar.php after the Installation Guide button:
 *
 *     <?php include __DIR__ . '/action-center-bell.php'; ?>
 *
 * NOTE ON STYLING
 * This project ships a precompiled Tailwind build, so arbitrary classes
 * like w-[380px] do not exist in the CSS. All sizing and positioning is
 * therefore done in the scoped stylesheet below.
 */

// Both session spellings. This codebase writes the key inconsistently —
// some files use 'shop_id', others 'shop_id' — and reading only one
// means the bell silently disappears on whichever pages set the other.
$acInstanceId = $shopId
    ?? ($_SESSION['shop_id'] ?? null)
    ?? ($_SESSION['shop_id'] ?? null);

// APP_BASE is what every other page in this app uses, so it comes first.
// The dirname() fallback would give this file's own directory — /partials
// — rather than the app root, so it is a last resort only.
if (defined('APP_BASE') && trim((string)APP_BASE) !== '') {
    $acBase = '/' . trim((string)APP_BASE, '/');
} else {
    $acBase = rtrim((string)($_ENV['BASE_URL'] ?? getenv('BASE_URL') ?: ''), '/');
    if ($acBase === '') {
        $acBase = rtrim(dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    }
}

$acApiUrl = $acBase . '/api/insights/index.php';

if ($acInstanceId):
?>

<style>
/* ---------- Action Center — all styles scoped to .ac-* ---------- */
.ac-wrap { position: relative; display: inline-block; }

/* Icon-only square button */
.ac-btn {
    width: 40px;
    height: 40px;
    padding: 0;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    cursor: pointer;
    background: #fff;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 2px rgba(2,6,23,.05);
    transition: border-color .15s, box-shadow .15s;
}
.ac-btn:hover {
    border-color: var(--clr-primary, #487fff);
    box-shadow: 0 2px 8px rgba(2,6,23,.08);
}
.dark .ac-btn { background: #262626; border-color: #404040; }

.ac-btn-icon { font-size: 22px; line-height: 1; }

.ac-badge {
    position: absolute; top: -6px; right: -6px;
    min-width: 20px; height: 20px; padding: 0 5px;
    border-radius: 999px;
    background: #dc2626; color: #fff;
    font-size: 11px; font-weight: 700; line-height: 1;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px rgba(0,0,0,.06);
}
.dark .ac-badge { border-color: #262626; }

/* ---------- popup ---------- */
.ac-pop {
    position: absolute; top: calc(100% + 8px); right: 0;
    width: 400px; max-width: calc(100vw - 32px);
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 12px 40px rgba(2,6,23,.16);
    z-index: 60; overflow: hidden;
    text-align: left;
}
.dark .ac-pop { background: #404040; border-color: #525252; }

.ac-head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb; white-space: nowrap;
}
.dark .ac-head { border-color: #525252; }
.ac-head h6 {
    margin: 0; font-size: 15px; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
    color: #111827;
}
.dark .ac-head h6 { color: #fff; }

.ac-link {
    background: none; border: 0; padding: 0; cursor: pointer;
    font-size: 12px; font-weight: 500; white-space: nowrap;
    color: var(--clr-primary, #487fff);
}
.ac-link:hover { text-decoration: underline; }
.ac-link:disabled { opacity: .4; cursor: default; text-decoration: none; }

.ac-strip {
    display: flex; padding: 10px 16px;
    background: #f8fafc; border-bottom: 1px solid #e5e7eb;
}
.dark .ac-strip { background: #333; border-color: #525252; }
.ac-strip > div {
    flex: 1; min-width: 0; padding-left: 12px;
    border-left: 1px solid #e5e7eb;
}
.ac-strip > div:first-child { padding-left: 0; border-left: 0; }
.dark .ac-strip > div { border-color: #525252; }
.ac-strip .k {
    margin: 0 0 2px; font-size: 10px; letter-spacing: .06em;
    text-transform: uppercase; color: #9ca3af; white-space: nowrap;
}
.ac-strip .v { margin: 0; font-size: 15px; font-weight: 700; white-space: nowrap; }

.ac-list { max-height: 340px; overflow-y: auto; }

.ac-item {
    display: flex; gap: 10px; padding: 12px 16px;
    text-decoration: none; border-bottom: 1px solid #f1f5f9;
    transition: background .12s;
}
.dark .ac-item { border-color: #525252; }
.ac-item:last-child { border-bottom: 0; }
.ac-item:hover { background: #f8fafc; }
.dark .ac-item:hover { background: #333; }
.ac-item.is-read { background: #fbfcfd; }
.dark .ac-item.is-read { background: #3a3a3a; }

.ac-sev  { width: 4px; border-radius: 999px; flex: none; }
.ac-main { flex: 1; min-width: 0; }
.ac-title { display: block; font-size: 13px; font-weight: 600; color: #111827; }
.dark .ac-title { color: #fff; }
.ac-item.is-read .ac-title { font-weight: 500; }
.ac-ent {
    display: block; font-size: 11px; color: #6b7280; margin-top: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ac-why { display: block; font-size: 12px; color: #4b5563; margin-top: 4px; line-height: 1.45; }
.dark .ac-why { color: #d4d4d4; }

.ac-imp { text-align: right; flex: none; }
.ac-imp .n { display: block; font-size: 13px; font-weight: 700; white-space: nowrap; }
.ac-imp .u { display: block; font-size: 10px; color: #9ca3af; white-space: nowrap; }

.ac-empty { padding: 36px 20px; text-align: center; }
.ac-empty .t { margin: 0 0 4px; font-size: 13px; font-weight: 600; color: #111827; }
.dark .ac-empty .t { color: #fff; }
.ac-empty .s { margin: 0; font-size: 12px; color: #6b7280; }

.ac-foot { padding: 10px 12px; background: #f8fafc; }
.dark .ac-foot { background: #333; }
.ac-cta {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; height: 40px; border-radius: 8px; text-decoration: none;
    font-size: 14px; font-weight: 500; white-space: nowrap;
    background: var(--clr-primary, #487fff); color: #fff;
}
.ac-cta:hover { opacity: .92; color: #fff; }

@media (max-width: 480px) {
    .ac-pop { position: fixed; left: 16px; right: 16px; width: auto; top: 64px; }
}
</style>

<div class="ac-wrap">

    <button type="button" id="ac-btn" class="ac-btn"
        aria-haspopup="true" aria-expanded="false" aria-label="Action Center">
        <iconify-icon icon="noto:high-voltage" class="ac-btn-icon"></iconify-icon>
        <span id="ac-count" class="ac-badge"></span>
    </button>

    <div id="ac-pop" class="ac-pop" style="display:none;">

        <div class="ac-head">
            <h6>
                <iconify-icon icon="noto:high-voltage" style="font-size:18px;"></iconify-icon>
                Action Center
                <span id="ac-new-tag" style="font-size:10px;font-weight:700;padding:2px 6px;
                      border-radius:999px;background:#fee2e2;color:#dc2626;"></span>
            </h6>
            <div class="flex items-center gap-3">
                <button type="button" id="ac-mark-all" class="ac-link">Mark all read</button>
                <button type="button" id="ac-close" class="flex h-8 w-8 items-center justify-center rounded-lg text-neutral-400 transition-all duration-200 bg-neutral-100 hover:bg-neutral-200 hover:text-neutral-900 hover:rotate-90 dark:text-neutral-300 dark:hover:bg-neutral-700 dark:hover:text-danger-400"
                    aria-label="Close Action Center">
                    <iconify-icon icon="mdi:close" class="text-lg"></iconify-icon>
                </button>
            </div>
        </div>

        <div class="ac-strip">
            <div>
                <p class="k">At risk</p>
                <p class="v" id="ac-risk" style="color:#dc2626;">—</p>
            </div>
            <div>
                <p class="k">Available</p>
                <p class="v" id="ac-opp" style="color:#16a34a;">—</p>
            </div>
            <div>
                <p class="k">Open</p>
                <p class="v" id="ac-open" style="color:#111827;">—</p>
            </div>
        </div>

        <div id="ac-list" class="ac-list">
            <div class="ac-empty"><p class="s">Loading…</p></div>
        </div>

        <div class="ac-foot">
            <a href="action-center.php" class="ac-cta">
                Go to Action Center <span id="ac-total" style="opacity:.75;"></span>
                <iconify-icon icon="solar:arrow-right-linear" style="font-size:18px;"></iconify-icon>
            </a>
        </div>
    </div>
</div>

<script>
// Declared on window rather than as a top-level const.
//
// This partial is included on every page. A bare `const` at document scope
// throws "Identifier has already been declared" the moment anything else
// declares the same name — and that is a parse error, so it takes down
// every other script on the page with it, not just this one.
// Which page counts as "the dashboard", where the panel opens by itself.
//
// The Wix build tests for index.php, because that is its dashboard. Here
// the dashboard is dashboard.php and index.php is a small redirect — so
// that same test is false on every page and the panel never auto-opens.
// Listing the names rather than hardcoding one keeps this correct if the
// file is renamed again.
//
// Declared on window rather than as a top-level const: this partial loads
// on every page, and a bare `const` throws "already declared" the moment
// anything else uses the name — a parse error that takes down every other
// script on the page, not just this one.
window.isHomePage = <?=
    in_array(basename((string)($_SERVER['PHP_SELF'] ?? '')),
             ['dashboard.php', 'index.php'], true) ? 'true' : 'false'
?>;
(function () {
    const API      = <?= json_encode($acApiUrl) ?>;
    const btn      = document.getElementById('ac-btn');
    const pop      = document.getElementById('ac-pop');
    const count    = document.getElementById('ac-count');
    const list     = document.getElementById('ac-list');
    const markAll  = document.getElementById('ac-mark-all');
    const closeBtn = document.getElementById('ac-close');
    let listLoaded = false;

    const SEV = {
        critical: '#dc2626',
        high:     '#d97706',
        medium:   '#2563eb',
        low:      '#16a34a'
    };

    const fmt = n => Number(n || 0).toLocaleString();
    const esc = s => String(s ?? '').replace(/[&<>"']/g,
        c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

    async function api(body) {
        const res = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }


    closeBtn.addEventListener('click', function (e) {
        e.stopPropagation();

        pop.style.display = 'none';
        btn.setAttribute('aria-expanded', 'false');
    });

    function empty(title, sub) {
        return `<div class="ac-empty">
            <p class="t">${title}</p>
            <p class="s">${sub}</p>
        </div>`;
    }

    async function loadSummary() {
        try {
            const d = await api({ action: 'summary' });
            if (!d || !d.success) return;
            const s = d.summary;

            count.textContent   = s.unread > 9 ? '9+' : s.unread;
            count.style.display = s.unread ? 'flex' : 'flex';

            document.getElementById('ac-risk').textContent    = '−' + fmt(s.at_risk);
            document.getElementById('ac-opp').textContent     = '+' + fmt(s.available);
            document.getElementById('ac-open').textContent    = fmt(s.open_count);
            document.getElementById('ac-total').textContent   = '(' + fmt(s.open_count) + ')';
            document.getElementById('ac-new-tag').textContent = s.unread ? s.unread + ' new' : '';
            markAll.disabled = !s.unread;
        } catch (e) {
            markAll.disabled = true;
        }
    }

    async function loadList() {
        try {
            const d = await api({ action: 'feed', status: 'open', limit: 4 });

            if (!d.success || !d.rows || !d.rows.length) {
                list.innerHTML = empty('Nothing needs attention',
                                       'New insights appear after the daily check.');
                return;
            }

            list.innerHTML = d.rows.map(r => {
                const gain = r.impact_clicks >= 0;
                const read = r.status !== 'new';
                const why  = r.why_text || '';
                return `
                <a href="action-center.php?focus=${r.id}" class="ac-item ${read ? 'is-read' : ''}">
                    <span class="ac-sev" style="background:${SEV[r.severity] || '#9ca3af'};"></span>
                    <span class="ac-main">
                        <span class="ac-title">${esc(r.title)}</span>
                        <span class="ac-ent">${esc(r.entity)}</span>
                        <span class="ac-why">${esc(why.length > 95 ? why.slice(0, 95) + '…' : why)}</span>
                    </span>
                    <span class="ac-imp">
                        <span class="n" style="color:${gain ? '#16a34a' : '#dc2626'};">
                            ${gain ? '+' : '−'}${fmt(Math.abs(r.impact_clicks))}
                        </span>
                        <span class="u">clicks/mo</span>
                        <button class="border-0 py-1 px-2 text-xs bg-cstm-primary text-white rounded-md leading-normal">Issue Fix</button>
                    </span>
                </a>`;
            }).join('');

        } catch (e) {
            list.innerHTML = empty('Insights not available yet',
                                   'Your Search Console data is still being collected.');
        }
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        document.getElementById('dropdownProfile')?.classList.add('hidden');

        const open = pop.style.display === 'none';
        pop.style.display = open ? 'block' : 'none';
        btn.setAttribute('aria-expanded', String(open));

        if (open && !listLoaded) { listLoaded = true; loadList(); }
    });

    pop.addEventListener('click', e => e.stopPropagation());

    document.addEventListener('click', function () {
        pop.style.display = 'none';
        btn.setAttribute('aria-expanded', 'false');
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            pop.style.display = 'none';
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    markAll.addEventListener('click', async function (e) {
        e.stopPropagation();
        try {
            await api({ action: 'mark_all' });
            await loadSummary();
            await loadList();
        } catch (err) { /* ignore */ }
    });

    if (isHomePage) {
        pop.style.display = 'block';
        btn.setAttribute('aria-expanded', 'true');

        if (!listLoaded) {
            listLoaded = true;
            loadList();
        }
    }

    loadSummary();
    setInterval(loadSummary, 60000);
})();
</script>

<?php endif; ?>