<?php
/**
 * partials/cancel-popup.php  (included by partials/sidebar.php)
 *
 * 4-step cancellation flow for paid Stripe subscriptions:
 *   1/4 why did you install  ->  2/4 what the app does for that goal
 *   3/4 why are you leaving  ->  4/4 matching help + "Cancel subscription"
 * The final button calls api/subscription-cancel.php, which sets Stripe
 * cancel_at_period_end=true: the paid plan stays until its expiry date.
 *
 * JS API: CancellationFlow.open(), .close(), .resume()
 */
require_once __DIR__ . '/../includes/gsc_cancel.php';

$gcfStoreId = (string)($shopIdForApi ?? ($instanceId ?? ''));
$gcfSub     = gscCancelFindSub($pdo, $gcfStoreId);
$gcfConfig  = [
    'storeId'     => $gcfStoreId,
    'token'       => $gcfStoreId !== '' ? gscCancelToken($gcfStoreId) : '',
    'api'         => 'api/subscription-cancel.php',
    'qs'          => 'shop_id=' . rawurlencode($gcfStoreId),
    'state'       => $gcfSub['state'] ?? 'none',
    'plan'        => (string)($gcfSub['plan_name'] ?? 'paid plan'),
    'billing'     => (string)($gcfSub['billing_period'] ?? ''),
    'accessUntil' => (string)($gcfSub['access_until'] ?? ''),
];
?>
<style>
  #gcf-overlay,#gcf-cfm{--gcf-bg:#fff;--gcf-fg:#111827;--gcf-mute:#6b7280;--gcf-line:#e5e7eb;--gcf-soft:#f3f6fb;--gcf-pri:var(--bg-primary,#458cf5);--gcf-pri-soft:rgba(69,140,245,.10);--gcf-danger:#dc2626;
    position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:16px;background:rgba(17,24,39,.55);backdrop-filter:blur(3px)}
  .dark #gcf-overlay,.dark #gcf-cfm{--gcf-bg:#1f2937;--gcf-fg:#f3f4f6;--gcf-mute:#9ca3af;--gcf-line:#374151;--gcf-soft:#273244;--gcf-pri-soft:rgba(69,140,245,.18);--gcf-danger:#f87171}
  #gcf-overlay.gcf-open{display:flex}
  #gcf-modal{position:relative;width:100%;max-width:560px;max-height:92vh;overflow-y:auto;background:var(--gcf-bg);color:var(--gcf-fg);border-radius:16px;padding:24px;box-shadow:0 20px 50px rgba(0,0,0,.25);font-size:14px;line-height:1.5}
  #gcf-modal *{box-sizing:border-box}
  .gcf-x{position:absolute;right:14px;top:14px;border:0;background:transparent;color:var(--gcf-mute);font-size:22px;line-height:1;cursor:pointer;padding:4px 8px;border-radius:8px}
  .gcf-x:hover{background:var(--gcf-soft)}
  .gcf-prog{display:flex;align-items:center;gap:10px;margin:0 32px 18px 0;font-size:12px;color:var(--gcf-mute);font-weight:600}
  .gcf-bar{flex:1;height:6px;border-radius:99px;background:var(--gcf-line);overflow:hidden}
  .gcf-bar>i{display:block;height:100%;width:25%;background:var(--gcf-pri);border-radius:99px;transition:width .35s ease}
  .gcf-step{display:none;animation:gcfIn .28s ease}
  .gcf-step.gcf-on{display:block}
  @keyframes gcfIn{from{opacity:0;transform:translateX(14px)}to{opacity:1;transform:none}}
  .gcf-h{font-size:19px;font-weight:700;margin:0 0 4px}
  .gcf-sub{color:var(--gcf-mute);margin:0 0 16px}
  .gcf-opts{display:grid;gap:8px}
  .gcf-opt{display:flex;align-items:center;gap:10px;width:100%;text-align:left;padding:11px 14px;border:1.5px solid var(--gcf-line);border-radius:12px;background:transparent;color:inherit;cursor:pointer;font:inherit;transition:border-color .15s,background .15s}
  .gcf-opt:hover{border-color:var(--gcf-pri)}
  .gcf-opt.gcf-sel{border-color:var(--gcf-pri);background:var(--gcf-pri-soft)}
  .gcf-opt .gcf-dot{flex:0 0 16px;height:16px;border-radius:50%;border:2px solid var(--gcf-line)}
  .gcf-opt.gcf-sel .gcf-dot{border-color:var(--gcf-pri);box-shadow:inset 0 0 0 3px var(--gcf-bg);background:var(--gcf-pri)}
  .gcf-ta{width:100%;margin-top:8px;padding:10px 12px;border:1.5px solid var(--gcf-line);border-radius:10px;background:transparent;color:inherit;font:inherit;resize:vertical;min-height:70px;display:none}
  .gcf-ta.gcf-show{display:block}
  .gcf-err{color:var(--gcf-danger);font-size:13px;margin-top:8px;min-height:1px}
  .gcf-card{background:var(--gcf-soft);border-radius:12px;padding:16px;margin-bottom:12px}
  .gcf-card b{display:block;margin-bottom:4px}
  .gcf-tip{border-left:3px solid var(--gcf-pri);padding:2px 0 2px 12px;color:var(--gcf-mute);margin:10px 0 0}
  .gcf-note{font-size:13px;color:var(--gcf-mute);margin:12px 0 0}
  .gcf-act{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin-top:20px}
  .gcf-act .gcf-right{display:flex;flex-wrap:wrap;gap:8px;margin-left:auto}
  .gcf-btn{border:1.5px solid transparent;border-radius:10px;padding:9px 16px;font:inherit;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:6px}
  .gcf-btn[disabled]{opacity:.6;cursor:wait}
  .gcf-pri{background:var(--gcf-pri);color:#fff}
  .gcf-ghost{background:transparent;color:var(--gcf-fg);border-color:var(--gcf-line)}
  .gcf-link{background:transparent;color:var(--gcf-mute);padding-left:0;padding-right:0}
  .gcf-danger{background:transparent;color:var(--gcf-danger);border-color:var(--gcf-danger)}
  .gcf-center{text-align:center}
  .gcf-icon{width:52px;height:52px;border-radius:50%;margin:4px auto 12px;display:flex;align-items:center;justify-content:center;font-size:26px;background:var(--gcf-pri-soft);color:var(--gcf-pri)}
  /* confirmation dialog (above the flow) */
  #gcf-overlay,#gcf-cfm{--gcf-ok:#059669;--gcf-ok-soft:rgba(5,150,105,.10);--gcf-danger-soft:rgba(220,38,38,.08)}
  .dark #gcf-overlay,.dark #gcf-cfm{--gcf-ok:#34d399;--gcf-ok-soft:rgba(52,211,153,.14);--gcf-danger-soft:rgba(248,113,113,.14)}
  #gcf-cfm{position:fixed;inset:0;z-index:10000;display:none;align-items:center;justify-content:center;padding:16px;background:rgba(15,23,42,.6);backdrop-filter:blur(3px)}
  #gcf-cfm.gcf-open{display:flex}
  #gcf-cfm-box{width:100%;max-width:460px;max-height:92vh;overflow-y:auto;background:var(--gcf-bg);color:var(--gcf-fg);border-radius:16px;box-shadow:0 24px 60px rgba(0,0,0,.28);font-size:14px;line-height:1.55;animation:gcfPop .18s ease-out}
  #gcf-cfm-box *{box-sizing:border-box}
  #gcf-cfm-box:focus{outline:none}
  @keyframes gcfPop{from{opacity:0;transform:translateY(8px) scale(.98)}to{opacity:1;transform:none}}
  .gcf-cfm-top{display:flex;gap:14px;align-items:flex-start;padding:24px 24px 0}
  .gcf-cfm-ic{flex:0 0 44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--gcf-pri-soft);color:var(--gcf-pri)}
  .gcf-cfm-ic svg{width:22px;height:22px}
  .gcf-cfm-ic.gcf-tone-danger{background:var(--gcf-danger-soft);color:var(--gcf-danger)}
  .gcf-cfm-ic.gcf-tone-ok{background:var(--gcf-ok-soft);color:var(--gcf-ok)}
  #gcf-cfm-h{font-size:18px;font-weight:700;line-height:1.3;margin:2px 0 4px;color:var(--gcf-fg)}
  #gcf-cfm-body{margin:0;color:var(--gcf-mute)}
  .gcf-cfm-rows{margin:18px 24px 0;border:1px solid var(--gcf-line);border-radius:12px;overflow:hidden}
  .gcf-cfm-rows:empty{display:none}
  .gcf-cfm-row{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:11px 14px}
  .gcf-cfm-row+.gcf-cfm-row{border-top:1px solid var(--gcf-line)}
  .gcf-cfm-row span{color:var(--gcf-mute)}
  .gcf-cfm-row b{font-weight:600;text-align:right}
  .gcf-cfm-row b.gcf-v-ok{color:var(--gcf-ok)}
  .gcf-cfm-row b.gcf-v-off{color:var(--gcf-danger)}
  .gcf-cfm-note{display:flex;gap:8px;margin:14px 24px 0;font-size:13px;color:var(--gcf-mute)}
  .gcf-cfm-note:empty{display:none}
  .gcf-cfm-note svg{flex:0 0 16px;width:16px;height:16px;margin-top:2px}
  .gcf-cfm-foot{display:flex;justify-content:flex-end;gap:10px;margin-top:22px;padding:16px 24px;border-top:1px solid var(--gcf-line);background:var(--gcf-soft);border-radius:0 0 16px 16px}
  .gcf-cfm-foot .gcf-btn{min-width:120px}
  .gcf-cfm-foot .gcf-ghost{background:var(--gcf-bg)}
  .gcf-danger-solid{background:var(--gcf-danger);color:#fff;border-color:var(--gcf-danger)}
  .gcf-btn:focus-visible{outline:2px solid var(--gcf-pri);outline-offset:2px}
  @media (max-width:480px){.gcf-cfm-top{padding:20px 18px 0}.gcf-cfm-rows,.gcf-cfm-note{margin-left:18px;margin-right:18px}.gcf-cfm-foot{flex-direction:column-reverse;padding:14px 18px}.gcf-cfm-foot .gcf-btn{width:100%}}
  @media (max-width:480px){#gcf-modal{padding:18px}.gcf-act{flex-direction:column-reverse;align-items:stretch}.gcf-act .gcf-right{margin-left:0;flex-direction:column-reverse}.gcf-btn{width:100%}}
</style>

<div id="gcf-overlay" role="dialog" aria-modal="true" aria-labelledby="gcf-title">
  <div id="gcf-modal">
    <button type="button" class="gcf-x" aria-label="Close" onclick="CancellationFlow.close()">&times;</button>

    <div class="gcf-prog" id="gcf-prog"><span id="gcf-prog-txt">Step 1 of 4</span><div class="gcf-bar"><i id="gcf-prog-bar"></i></div></div>

    <!-- 1/4 -->
    <section class="gcf-step" data-step="1">
      <h2 class="gcf-h" id="gcf-title">Why did you install the app?</h2>
      <p class="gcf-sub">Pick the main goal you had.</p>
      <div class="gcf-opts" id="gcf-join"></div>
      <textarea class="gcf-ta" id="gcf-join-other" maxlength="1000" placeholder="Tell us your goal"></textarea>
      <div class="gcf-err" id="gcf-err-1"></div>
      <div class="gcf-act">
        <button type="button" class="gcf-btn gcf-link" data-gcf="keep">Keep my plan</button>
        <div class="gcf-right"><button type="button" class="gcf-btn gcf-pri" data-gcf="next">Continue</button></div>
      </div>
    </section>

    <!-- 2/4 -->
    <section class="gcf-step" data-step="2">
      <h2 class="gcf-h" id="gcf-value-h"></h2>
      <p class="gcf-sub">Here is how the app helps with that.</p>
      <div class="gcf-card"><div id="gcf-value-body"></div><p class="gcf-tip" id="gcf-value-tip"></p></div>
      <a class="gcf-btn gcf-ghost" id="gcf-value-link" href="#" data-gcf="offer-link"></a>
      <div class="gcf-act">
        <button type="button" class="gcf-btn gcf-link" data-gcf="back">&larr; Back</button>
        <div class="gcf-right">
          <button type="button" class="gcf-btn gcf-ghost" data-gcf="keep">Keep the app</button>
          <button type="button" class="gcf-btn gcf-pri" data-gcf="next">Continue</button>
        </div>
      </div>
    </section>

    <!-- 3/4 -->
    <section class="gcf-step" data-step="3">
      <h2 class="gcf-h">Why are you leaving?</h2>
      <p class="gcf-sub">Your answer helps us improve.</p>
      <div class="gcf-opts" id="gcf-leave"></div>
      <textarea class="gcf-ta" id="gcf-leave-other" maxlength="1000" placeholder="What went wrong? (required)"></textarea>
      <div class="gcf-err" id="gcf-err-3"></div>
      <div class="gcf-act">
        <button type="button" class="gcf-btn gcf-link" data-gcf="back">&larr; Back</button>
        <div class="gcf-right"><button type="button" class="gcf-btn gcf-pri" data-gcf="next">Continue</button></div>
      </div>
    </section>

    <!-- 4/4 -->
    <section class="gcf-step" data-step="4">
      <h2 class="gcf-h" id="gcf-offer-h"></h2>
      <p class="gcf-sub" id="gcf-offer-sub"></p>
      <div class="gcf-card"><div id="gcf-offer-body"></div></div>
      <a class="gcf-btn gcf-pri" id="gcf-offer-link" href="#" data-gcf="offer-link"></a>
      <p class="gcf-note" id="gcf-access-note"></p>
      <div class="gcf-err" id="gcf-err-4"></div>
      <div class="gcf-act">
        <button type="button" class="gcf-btn gcf-link" data-gcf="back">&larr; Back</button>
        <div class="gcf-right">
          <button type="button" class="gcf-btn gcf-ghost" data-gcf="keep">Keep my plan</button>
          <button type="button" class="gcf-btn gcf-danger" id="gcf-confirm" data-gcf="cancel">Cancel subscription</button>
        </div>
      </div>
    </section>

    <!-- done -->
    <section class="gcf-step gcf-center" data-step="5">
      <div class="gcf-icon">&#10003;</div>
      <h2 class="gcf-h" id="gcf-done-h">Auto-renewal cancelled</h2>
      <p class="gcf-sub" id="gcf-done-body"></p>
      <div class="gcf-act" style="justify-content:center"><button type="button" class="gcf-btn gcf-pri" data-gcf="reload">Done</button></div>
    </section>
  </div>
</div>

<div id="gcf-cfm" role="alertdialog" aria-modal="true" aria-labelledby="gcf-cfm-h" aria-describedby="gcf-cfm-body">
  <div id="gcf-cfm-box" tabindex="-1">
    <div class="gcf-cfm-top">
      <div class="gcf-cfm-ic" id="gcf-cfm-icon" aria-hidden="true"></div>
      <div><h2 id="gcf-cfm-h"></h2><p id="gcf-cfm-body"></p></div>
    </div>
    <div class="gcf-cfm-rows" id="gcf-cfm-rows"></div>
    <p class="gcf-cfm-note" id="gcf-cfm-note"></p>
    <div class="gcf-cfm-foot">
      <button type="button" class="gcf-btn gcf-ghost" id="gcf-cfm-no"></button>
      <button type="button" class="gcf-btn gcf-pri" id="gcf-cfm-yes"></button>
    </div>
  </div>
</div>

<script>
(function () {
  var CFG = <?= json_encode($gcfConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var page = function (p) { return p + (p.indexOf('?') === -1 ? '?' : '&') + CFG.qs; };

  var JOIN = [
    ['traffic',  'Track my Google search traffic'],
    ['keywords', 'Find the keywords people use to find me'],
    ['indexing', 'Get my pages showing on Google'],
    ['sitemap',  'Submit and monitor my sitemap'],
    ['reports',  'Get simple SEO reports'],
    ['ai',       'Ask an AI assistant about my search data'],
    ['other',    'Other']
  ];
  var VALUE = {
    traffic:  ['Your search traffic, in one place', 'The dashboard shows clicks, impressions, CTR and average position straight from Google Search Console, without logging in to Google.', 'Tip: compare the last 28 days with the period before to see what is growing.', 'Open dashboard', 'dashboard.php'],
    keywords: ['See every search term that finds you', 'The SEO report lists the queries your pages appear for, with clicks, impressions and position for each.', 'Tip: queries at position 8-20 are the quickest wins - improve those pages first.', 'Open SEO report', 'gsc-report.php'],
    indexing: ['Know what Google can and cannot see', 'Action Center turns your Search Console data into a short list of what to fix next to grow traffic and rankings.', 'Tip: make sure your sitemap is submitted so Google finds new pages faster.', 'Open Action Center', 'action-center.php'],
    sitemap:  ['Sitemap handled for you', 'Sitemap Manager submits your sitemap to Google and shows its status, so new pages are discovered faster.', 'Tip: re-check the sitemap after adding many new products or pages.', 'Open Sitemap Manager', 'sitemap.php'],
    reports:  ['SEO reports without the jargon', 'The SEO report summarises your Search Console data so you can see performance at a glance.', 'Tip: check it weekly - trends matter more than single days.', 'Open SEO report', 'gsc-report.php'],
    ai:       ['Ask questions about your data', 'Connect an AI assistant to your Search Console data and ask things like "which pages lost clicks this month?"', 'Tip: start with "what are my top growing queries?"', 'Open AI connections', 'mcp-settings.php'],
    other:    ['We would love to help', 'Tell our support team what you need - we reply to every ticket and often add features merchants ask for.', 'Tip: include your store URL so we can look right away.', 'Contact support', 'manage-tickets.php']
  };
  var LEAVE = [
    ['no_results',  'Not seeing expected results'],
    ['data_issue',  'Data is missing or not updating'],
    ['connection',  'Trouble connecting Google Search Console'],
    ['complicated', 'Too complicated to use'],
    ['pricing',     'Pricing is too high'],
    ['switched',    'Switched to another tool'],
    ['no_need',     'No longer need the app'],
    ['other',       'Other']
  ];
  // [heading, subtitle, body, offer button label, offer page, offer_type]
  var OFFER = {
    no_results:  ['SEO takes a little time', 'Google often needs 2-4 weeks to reflect changes.', 'Our team can review your Search Console setup and point out quick wins for your store - free.', 'Ask for a free review', 'manage-tickets.php', 'seo_review'],
    data_issue:  ['Let us check your data', 'Search Console data normally arrives with a 2-3 day delay.', 'If it is older than that, your Google connection may need a refresh. Send us a ticket and we will check it for you.', 'Report the data issue', 'manage-tickets.php', 'data_check'],
    connection:  ['We can fix the connection', 'Most connection problems take a minute to solve.', 'Run the setup wizard again to reconnect Google Search Console, or open a ticket and we will do it with you.', 'Re-run setup wizard', 'setup-wizard.php', 'reconnect'],
    complicated: ['Free guided setup', 'You do not have to figure it out alone.', 'The setup wizard walks you through every step, and our team can set it up with you over a support ticket.', 'Start guided setup', 'setup-wizard.php', 'guided_setup'],
    pricing:     ['Nothing is lost when the plan ends', 'You will not be charged again.', 'After your paid period you move to the Free plan automatically - your Google connection and data stay, and you can upgrade again anytime.', '', '', 'free_plan_info'],
    switched:    ['Tell us what was missing', 'We read every answer.', 'If another tool does something we do not, let us know - many of our features came from merchant requests.', 'Send feedback', 'manage-tickets.php', 'feedback'],
    no_need:     ['Thanks for being with us', 'Your data stays safe.', 'Your Google connection and data are kept on the Free plan, so you can pick up where you left off anytime.', '', '', 'none'],
    other:       ['Can we help?', 'Thanks for telling us.', 'If something is not working, our support team usually solves it within a day.', 'Contact support', 'manage-tickets.php', 'support']
  };

  var el = function (id) { return document.getElementById(id); };
  var overlay = el('gcf-overlay');
  var S = { step: 1, join: null, leave: null, offerType: null, offerSelected: null, done: false };

  /* In-app confirmation dialog.
     o = {tone: 'primary'|'danger'|'ok', icon, title, body, rows: [[label, value, 'ok'|'off']], note, yes, no}
     no = '' hides the second button. Resolves true (yes), false (no button), null (backdrop / Escape). */
  var ICONS = {
    renew:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 4v5h-5"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>',
    warn:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
    card:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/></svg>',
    info:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
    alert:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>'
  };
  var cfm = el('gcf-cfm'), cfmDone = null;
  function confirmBox(o) {
    return new Promise(function (resolve) {
      var tone = o.tone || 'primary';
      el('gcf-cfm-icon').className = 'gcf-cfm-ic' + (tone !== 'primary' ? ' gcf-tone-' + tone : '');
      el('gcf-cfm-icon').innerHTML = ICONS[o.icon] || ICONS.info;
      el('gcf-cfm-h').textContent = o.title;
      el('gcf-cfm-body').textContent = o.body || '';
      el('gcf-cfm-rows').innerHTML = (o.rows || []).filter(function (r) { return r[1]; }).map(function (r) {
        return '<div class="gcf-cfm-row"><span>' + esc(r[0]) + '</span><b' + (r[2] ? ' class="gcf-v-' + r[2] + '"' : '') + '>' + esc(r[1]) + '</b></div>';
      }).join('');
      el('gcf-cfm-note').innerHTML = o.note ? ICONS.info + '<span>' + esc(o.note) + '</span>' : '';
      var yes = el('gcf-cfm-yes'), no = el('gcf-cfm-no');
      yes.textContent = o.yes || 'OK';
      yes.className = 'gcf-btn ' + (tone === 'danger' ? 'gcf-danger-solid' : 'gcf-pri');
      no.textContent = o.no === undefined ? 'Cancel' : o.no;
      no.style.display = no.textContent ? '' : 'none';
      cfmDone = function (v) { cfm.classList.remove('gcf-open'); cfmDone = null; resolve(v); };
      cfm.classList.add('gcf-open');
      setTimeout(function () { el('gcf-cfm-box').focus(); }, 30);  // keyboard users Tab to the buttons
    });
  }
  el('gcf-cfm-yes').addEventListener('click', function () { if (cfmDone) cfmDone(true); });
  el('gcf-cfm-no').addEventListener('click', function () { if (cfmDone) cfmDone(false); });
  cfm.addEventListener('click', function (e) { if (e.target === cfm && cfmDone) cfmDone(null); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && cfmDone) { e.stopImmediatePropagation(); cfmDone(null); }
  }, true);
  function notice(title, body, tone) { return confirmBox({ title: title, body: body, yes: 'OK', no: '', tone: tone || 'primary', icon: tone === 'danger' ? 'alert' : 'info' }); }
  var planLabel = function () {
    var b = (CFG.billing || '').toLowerCase();
    return CFG.plan + (b ? ' · ' + b.charAt(0).toUpperCase() + b.slice(1) : '');
  };

  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

  function renderOpts(wrapId, list, key, otherId) {
    var wrap = el(wrapId);
    wrap.innerHTML = list.map(function (o) {
      return '<button type="button" class="gcf-opt" data-v="' + o[0] + '"><span class="gcf-dot"></span>' + esc(o[1]) + '</button>';
    }).join('');
    wrap.addEventListener('click', function (e) {
      var b = e.target.closest('.gcf-opt'); if (!b) return;
      wrap.querySelectorAll('.gcf-opt').forEach(function (x) { x.classList.toggle('gcf-sel', x === b); });
      S[key] = b.getAttribute('data-v');
      el(otherId).classList.toggle('gcf-show', S[key] === 'other');
      el(key === 'join' ? 'gcf-err-1' : 'gcf-err-3').textContent = '';
    });
  }
  renderOpts('gcf-join', JOIN, 'join', 'gcf-join-other');
  renderOpts('gcf-leave', LEAVE, 'leave', 'gcf-leave-other');

  function label(list, v) { for (var i = 0; i < list.length; i++) if (list[i][0] === v) return list[i][1]; return v; }

  function answers() {
    return {
      step: S.step,
      join_reason: S.join ? label(JOIN, S.join) : null,
      join_extra: S.join === 'other' ? el('gcf-join-other').value.trim() : null,
      leave_reason: S.leave ? label(LEAVE, S.leave) : null,
      leave_extra: S.leave === 'other' ? el('gcf-leave-other').value.trim() : null,
      offer_type: S.offerType,
      offer_selected: S.offerSelected
    };
  }

  function api(action, extra) {
    var body = Object.assign({ store_id: CFG.storeId, token: CFG.token, action: action, answers: answers() }, extra || {});
    return fetch(CFG.api, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body), keepalive: action === 'track' })
      .then(function (r) { return r.json().catch(function () { return { success: false }; }); });
  }
  function track(finalAction) { api('track', finalAction ? { final_action: finalAction } : null).catch(function () {}); }

  function show(n) {
    S.step = n;
    overlay.querySelectorAll('.gcf-step').forEach(function (s) { s.classList.toggle('gcf-on', +s.getAttribute('data-step') === n); });
    el('gcf-prog').style.display = n > 4 ? 'none' : '';
    el('gcf-prog-txt').textContent = 'Step ' + Math.min(n, 4) + ' of 4';
    el('gcf-prog-bar').style.width = (Math.min(n, 4) * 25) + '%';
    el('gcf-modal').scrollTop = 0;
  }

  function setLink(a, text, href) {
    a.style.display = text ? '' : 'none';
    if (text) { a.textContent = text; a.href = page(href); }
  }

  function fillValue() {
    var v = VALUE[S.join] || VALUE.other;
    el('gcf-value-h').textContent = v[0];
    el('gcf-value-body').textContent = v[1];
    el('gcf-value-tip').textContent = v[2];
    setLink(el('gcf-value-link'), v[3], v[4]);
  }

  function fillOffer() {
    var o = OFFER[S.leave] || OFFER.other;
    S.offerType = o[5];
    el('gcf-offer-h').textContent = o[0];
    el('gcf-offer-sub').textContent = o[1];
    el('gcf-offer-body').textContent = o[2];
    setLink(el('gcf-offer-link'), o[3], o[4]);
    el('gcf-access-note').textContent = CFG.accessUntil
      ? 'If you cancel, you keep your ' + CFG.plan + ' plan until ' + CFG.accessUntil + ' and will not be charged again.'
      : 'If you cancel, you keep your plan until the end of the period you paid for and will not be charged again.';
    el('gcf-err-4').textContent = '';
  }

  function next() {
    if (S.step === 1) {
      if (!S.join) { el('gcf-err-1').textContent = 'Please choose an option.'; return; }
      fillValue();
    } else if (S.step === 3) {
      if (!S.leave) { el('gcf-err-3').textContent = 'Please choose a reason.'; return; }
      if (S.leave === 'other' && !el('gcf-leave-other').value.trim()) { el('gcf-err-3').textContent = 'Please tell us a bit more.'; return; }
      fillOffer();
    }
    show(S.step + 1);
    track();
  }

  function doCancel(btn) {
    var until = CFG.accessUntil;
    confirmBox({
      tone: 'danger', icon: 'warn',
      title: 'Cancel your subscription?',
      body: 'Auto-renewal will be turned off and you will not be charged again. '
        + 'Your plan stays fully active for the time you have already paid for.',
      rows: [
        ['Plan', planLabel()],
        ['Full access until', until],
        ['Auto-renewal', 'Off', 'off'],
        [until ? 'From ' + until : 'After that', 'Free plan']
      ],
      note: 'Your Google connection and data are kept. Changed your mind? Turn auto-renewal back on anytime'
        + (until ? ' before ' + until : '') + ' from Current Plan.',
      yes: 'Cancel subscription', no: 'Keep my plan'
    }).then(function (ok) {
      if (ok) reallyCancel(btn);
      else if (ok === false) { track('kept_subscription'); S.done = true; close(); }  // "Keep my plan"
    });
  }
  function reallyCancel(btn) {
    btn.disabled = true; btn.textContent = 'Cancelling...';
    api('cancel').then(function (j) {
      if (!j || !j.success) throw new Error((j && j.message) || 'Something went wrong. Please try again.');
      // Done: reload so the sidebar and Current Plan show the new state (no success screen).
      S.done = true;
      btn.textContent = 'Cancelled';
      location.reload();
    }).catch(function (e) {
      el('gcf-err-4').textContent = e.message;
      btn.disabled = false; btn.textContent = 'Cancel subscription';
    });
  }

  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) { close(); return; }
    var b = e.target.closest('[data-gcf]'); if (!b) return;
    var a = b.getAttribute('data-gcf');
    if (a === 'next') next();
    else if (a === 'back') show(Math.max(1, S.step - 1));
    else if (a === 'keep') { track('kept_subscription'); S.done = true; close(); }
    else if (a === 'offer-link') { S.offerSelected = b.textContent; track('accepted_offer'); S.done = true; }
    else if (a === 'cancel') doCancel(b);
    else if (a === 'reload') location.reload();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && overlay.classList.contains('gcf-open')) close(); });

  function open() {
    if (CFG.state === 'cancel_scheduled') {
      notice('Auto-renewal is already off', 'Your ' + CFG.plan + ' plan stays active until ' + (CFG.accessUntil || 'the end of the paid period') + '. You can turn auto-renewal back on from Current Plan.');
      return;
    }
    if (CFG.state !== 'active') {
      notice('No active subscription', 'This store has no paid subscription that renews, so there is nothing to cancel.');
      return;
    }
    S = { step: 1, join: null, leave: null, offerType: null, offerSelected: null, done: false };
    overlay.querySelectorAll('.gcf-opt').forEach(function (x) { x.classList.remove('gcf-sel'); });
    overlay.querySelectorAll('.gcf-ta').forEach(function (x) { x.value = ''; x.classList.remove('gcf-show'); });
    overlay.querySelectorAll('.gcf-err').forEach(function (x) { x.textContent = ''; });
    overlay.classList.add('gcf-open');
    show(1);
  }
  function close() {
    if (!overlay.classList.contains('gcf-open')) return;
    overlay.classList.remove('gcf-open');
    if (S.step === 5) { location.reload(); return; }
    if (cfmDone) cfmDone(null);
    if (!S.done) track('abandoned');
  }
  /* "Keep my plan". The API reads the subscription in Stripe:
       scheduled cancel  -> removed in Stripe, plan active again
       immediate cancel  -> same price restarted on the saved card, first
                            charge when the paid period ends
       Either way the page reloads with plan_kept=1. */
  function resume(btn, opts) {
    opts = opts || {};
    var ask = opts.noConfirm ? Promise.resolve(true) : confirmBox({
      tone: 'ok', icon: 'renew',
      title: 'Keep your ' + CFG.plan + ' plan?',
      body: 'Auto-renewal will be turned back on, so your plan continues without any interruption.',
      rows: [
        ['Plan', planLabel()],
        ['Auto-renewal', 'On', 'ok'],
        ['Next renewal', CFG.accessUntil],
        ['Charged today', 'Nothing']
      ],
      note: 'Your saved card is charged on the renewal date. You can cancel again anytime before then.',
      yes: 'Keep my plan', no: 'Not now'
    });
    ask.then(function (ok) { if (ok) reallyResume(btn); });
  }
  function reallyResume(btn) {
    var label = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Please wait...'; }
    var reset = function () { if (btn) { btn.disabled = false; btn.textContent = label; } };
    api('resume').then(function (j) {
      if (j && j.success) {
        var u = new URL(location.href);
        u.searchParams.delete('keep_plan');
        u.searchParams.set('plan_kept', '1');
        location.replace(u.toString());
        return;
      }
      if (j && j.needs_card && j.checkout_url) {
        // No saved card: Stripe "save card" page (cannot be framed -> top window).
        return confirmBox({
          icon: 'card',
          title: 'Add a card to keep your plan',
          body: 'There is no card on file for auto-renewal. You will be taken to Stripe\u2019s secure page to save one, then brought back here.',
          rows: [['Charged today', 'Nothing'], ['First charge', CFG.accessUntil]],
          note: 'Stripe only saves the card now. Nothing is charged until the renewal date.',
          yes: 'Continue to Stripe', no: 'Not now'
        })
          .then(function (go) {
            if (!go) { reset(); return; }
            try { window.top.location.href = j.checkout_url; } catch (e) { location.href = j.checkout_url; }
          });
      }
      throw new Error((j && j.message) || 'Something went wrong. Please try again.');
    }).catch(function (e) {
      notice('Could not turn auto-renewal on', e.message, 'danger');
      reset();
    });
  }

  window.CancellationFlow = { open: open, close: close, resume: resume, state: CFG.state };
})();
</script>
