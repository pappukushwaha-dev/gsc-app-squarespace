<?php
$shopId = $_SESSION['shop_id']
    ?? $_SESSION['shop_id']
    ?? null;;

?>


<!-- Review Modal -->
<div id="reviewModal"
    class="fixed inset-0 flex bg-cstm-black-40 backdrop-blur-sm items-center justify-center z-30 shadow-lg hidden">

    <div
        class="bg-white dark:bg-neutral-800 rounded-xl shadow-2xl py-6 px-2 sm:px-8 w-full max-w-2xl border-b-6 border-cstm-primary relative">

        <!-- Alert Message -->
        <div id="alertContainer" class="mb-4 transition-all duration-300"></div>

        <!-- Close Button -->
        <button id="closeReviewModal" class="absolute z-10 right-4 top-4 text-gray-500 hover:text-gray-700">
            ✕
        </button>

        <h2 class="text-xl font-bold text-center text-neutral-800 mb-4">
            How likely are you to recommend Google Search Console for performance benefits?
        </h2>

        <!-- Ratings -->
        <div class="mb-2">
            <div id="ratingButtons" class="flex justify-between"></div>
        </div>

        <p class="text-sm text-center text-neutral-400 mb-4 flex justify-between font-medium">
            <span>Not at all likely</span>
            <i class="fa-solid fa-arrow-right-long"></i>
            <span>Extremely likely</span>
        </p>

        <!-- Comment box (only for <=7) -->
        <div id="commentBox" class="hidden mb-4">
            <label class="text-sm font-semibold text-neutral-700 dark:text-gray-400">
                Please share your comments:
            </label>

            <textarea id="reviewComment"
                class="w-full py-1 px-2 border border-gray-300 dark:border-neutral-600 rounded-lg transition duration-150 dark:bg-neutral-800"
                rows="3" placeholder="Tell us what we can improve..."></textarea>
        </div>

        <div class="flex justify-center gap-4 pt-6 border-t border-gray-200 dark:border-neutral-600">
            <button id="reviewRemindLater" class="btn btn-cstm-muted">
                Remind Me Later
            </button>

            <button id="reviewSubmit" class="btn btn-cstm-primary">
                Submit Feedback
            </button>
        </div>
    </div>
</div>




<script>
/* GSC review flow (2026-11-24, shared by every platform's popup).
   - opening the popup first asks api/check-review.php; if this store already sent
     feedback in the last 24 hours, a click shows "already submitted today" and
     nothing can be sent (automatic prompts simply do not open);
   - 8-10: saved first, then (where the platform has one) the app store review page;
   - 1-7: comment box, sent with Submit Feedback;
   - api/save-review.php enforces the same one-per-day rule on the server. */
window.GSCReviewFlow = (function (cfg) {
    const $ = (id) => document.getElementById(id);
    const DONE_TEXT = "You have already submitted your feedback today. Thank you! You can share it again tomorrow.";

    function msg(text, type) {
        const box = $("alertContainer");
        if (!box) return;
        box.innerHTML = "";
        if (!text) return;
        const cls = type === "error" ? "bg-danger-100 text-danger-600 border-danger-600"
            : (type === "success" ? "bg-success-100 text-success-700 border-success-600"
            : "bg-warning-100 text-warning-600 border-warning-600");
        const div = document.createElement("div");
        div.className = cls + " border-l-4 p-3 rounded-md text-sm font-medium";
        div.textContent = text;
        box.appendChild(div);
    }

    function setLocked(locked) {
        document.querySelectorAll("#ratingButtons .review-numbers").forEach((b) => {
            b.disabled = locked;
            b.classList.toggle("opacity-40", locked);
            b.classList.toggle("cursor-not-allowed", locked);
            if (locked) b.classList.remove("review-numbers-selected");
        });
        const submit = $("reviewSubmit"), comment = $("reviewComment"), box = $("commentBox"), remind = $("reviewRemindLater");
        if (submit) { submit.disabled = locked; submit.classList.toggle("opacity-50", locked); submit.classList.toggle("cursor-not-allowed", locked); }
        if (comment) comment.disabled = locked;
        if (locked && box) box.classList.add("hidden");
        if (remind) remind.classList.toggle("hidden", locked);
    }
    function lock(text) { setLocked(true); msg(text || DONE_TEXT, "success"); }
    function unlock() { setLocked(false); }

    // The answer is fetched once when the page loads and kept, so a click opens the
    // popup at once instead of waiting for the network (it looked like a dead button).
    let known = null, knownAt = 0, pending = null;
    const FRESH_MS = 5 * 60 * 1000;
    function remember(d) { known = d; knownAt = Date.now(); }
    function status(force) {
        if (!cfg.storeId) return Promise.resolve(null);
        if (!force && known && Date.now() - knownAt < FRESH_MS) return Promise.resolve(known);
        if (pending) return pending;
        pending = fetch("api/check-review.php?" + cfg.idParam + "=" + encodeURIComponent(cfg.storeId),
                { credentials: "same-origin", cache: "no-store" })
            .then((r) => r.json())
            .then((d) => { if (d && d.success) { remember(d); return d; } return null; })
            .catch(() => null)          // the server still refuses a second review
            .finally(() => { pending = null; });
        return pending;
    }
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => status());
    } else {
        status();
    }

    async function post(rating, comment) {
        try {
            const body = { rating: rating, comment: comment };
            body[cfg.idParam] = cfg.storeId;
            const r = await fetch("api/save-review.php", {
                method: "POST", credentials: "same-origin",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(body)
            });
            const d = await r.json().catch(() => ({}));
            if (d && d.success) return d;
            return { success: false, code: d && d.code, message: (d && d.message) || "Could not save your feedback. Please try again." };
        } catch (e) {
            return { success: false, message: "Network error. Please try again." };
        }
    }

    function closeModal() {
        const m = $("reviewModal");
        if (m) { m.classList.add("hidden"); m.classList.remove("flex"); }
    }

    function done(text) {
        remember({ success: true, exists: true, submitted: 1, reviewed_today: true });
        try { localStorage.setItem("reviewSubmitted", "1"); localStorage.removeItem("reviewNextPopupTime"); } catch (e) {}
        closeModal();
        if (typeof showAlert === "function") showAlert(text || "Thank you! Your feedback has been submitted.", "success");
        const t = $("openReviewModal");
        if (t && t.getAttribute("data-redirect-after") === "true") {
            setTimeout(() => { window.location.href = "index.php"; }, 800);
        }
    }

    function failed(r) {
        if (r.code === "already_today") { remember({ success: true, exists: true, reviewed_today: true }); lock(r.message); }
        else msg(r.message, "error");
    }

    let busy = false;
    async function saveHigh(rating) {
        if (busy) return;
        busy = true;
        // The tab is opened inside the click (no popup blocker) and only pointed
        // at the review page once the rating is saved.
        const tab = cfg.reviewUrl ? window.open("", "_blank") : null;
        if (tab) { try { tab.document.title = "Opening review page..."; tab.document.body.textContent = "Opening the review page..."; } catch (e) {} }
        msg("Saving your rating...", "success");
        const r = await post(rating, "");
        busy = false;
        if (!r.success) { if (tab) tab.close(); failed(r); return; }
        if (cfg.reviewUrl) {
            if (tab) tab.location.href = cfg.reviewUrl; else window.open(cfg.reviewUrl, "_blank");
            done("Thank you! Please share your review on the " + cfg.storeName + ".");
        } else {
            done("Thank you for your rating!");
        }
    }

    async function submitLow(rating, submitBtn) {
        const comment = ($("reviewComment") || {}).value ? $("reviewComment").value.trim() : "";
        if (!comment) { msg("Please tell us what we can improve.", "warning"); return; }
        submitBtn.disabled = true;
        const label = submitBtn.textContent;
        submitBtn.textContent = "Submitting...";
        const r = await post(rating, comment);
        submitBtn.textContent = label && label !== "Submitting..." ? label : "Submit Feedback";
        submitBtn.disabled = false;
        if (!r.success) { failed(r); return; }
        done(r.message);
    }

    /* Wrap an "open the popup" function: check first. A click on an already-reviewed
       store shows the locked popup; an automatic prompt does not open at all. */
    function guardOpen(rawOpen) {
        return async function (ev) {
            const manual = !!(ev && ev.type === "click");
            const fresh = known && Date.now() - knownAt < FRESH_MS ? known : null;

            if (manual) {
                // A click always opens the popup immediately.
                await rawOpen();
                if (fresh) {
                    if (fresh.reviewed_today) lock(); else unlock();
                    return;
                }
                setLocked(true);
                msg("Checking...", "success");
                const st = await status();
                if (st && st.reviewed_today) lock(); else { unlock(); msg(""); }
                return;
            }

            // Automatic prompts: never open for a store that reviewed today.
            const st = fresh || await status();
            if (st && st.reviewed_today) return;
            await rawOpen();
            unlock();
        };
    }

    return { cfg, msg, lock, unlock, status, post, saveHigh, submitLow, guardOpen, closeModal, done };
})({ idParam: "shop_id", storeId: <?= json_encode((string) ($shopId ?? '')) ?>, reviewUrl: "", storeName: "Squarespace Extensions" });
</script>
<script>
    document.addEventListener("DOMContentLoaded", () => {

        const modal =
            document.getElementById("reviewModal");

        const commentBox =
            document.getElementById("commentBox");

        const submitBtn =
            document.getElementById("reviewSubmit");

        const openBtn =
            document.getElementById("openReviewModal");

        const closeBtn =
            document.getElementById("closeReviewModal");

        const remindBtn =
            document.getElementById("reviewRemindLater");

        const alertContainer =
            document.getElementById("alertContainer");

        let selectedRating = null;


        // Check "already reviewed today" before the popup opens (GSCReviewFlow.guardOpen).
        const openReviewModalHandler = GSCReviewFlow.guardOpen(() => openReviewModalRaw());

        function openReviewModalRaw() {

            modal.classList.remove("hidden");

            modal.classList.add("flex");

            alertContainer.innerHTML = "";

            selectedRating = null;

            commentBox.classList.add("hidden");

            document.getElementById(
                "reviewComment"
            ).value = "";

            document.querySelectorAll(
                ".review-numbers"
            ).forEach(btn => {

                btn.classList.remove(
                    "review-numbers-selected"
                );
            });

            submitBtn.disabled = false;

            submitBtn.textContent =
                "Submit Feedback";

            remindBtn.classList.remove(
                "hidden"
            );

            closeBtn.classList.remove(
                "hidden"
            );
        }

        /*
        ============================================
        | RATING BUTTONS
        ============================================
        */

        const ratingContainer =
            document.getElementById(
                "ratingButtons"
            );

        ratingContainer.innerHTML = "";

        for (let i = 1; i <= 10; i++) {

            const btn =
                document.createElement("button");

            btn.textContent = i;

            btn.dataset.value = i;

            btn.className =
                `review-numbers w-10 aspect-[1] flex items-center justify-center rounded-full text-white font-bold cursor-pointer transition bg-c${i}`;
            btn.addEventListener("click", () => {
                if (btn.disabled) return;
                selectedRating = i;
                GSCReviewFlow.msg("");
                [...ratingContainer.children].forEach(b => b.classList.remove("review-numbers-selected"));
                btn.classList.add("review-numbers-selected");
                // 1-7: tell us what to improve (sent with Submit Feedback)
                if (i < 8) {
                    commentBox.classList.remove("hidden");
                    return;
                }
                // 8-10: save first, then the review page
                commentBox.classList.add("hidden");
                GSCReviewFlow.saveHigh(i);
            });
            ratingContainer.appendChild(btn);
        }
        window.openReviewModal = openReviewModalHandler;

        /*
        ============================================
        | OPEN MODAL
        ============================================
        */

        openBtn?.addEventListener(
            "click",
            openReviewModalHandler
        );

        const urlParams =
            new URLSearchParams(
                window.location.search
            );

        const shouldAutoOpen =
            sessionStorage.getItem(
                "autoOpenReviewModal"
            ) === "true" ||
            urlParams.get("auto_review") === "1";

        if (shouldAutoOpen) {

            sessionStorage.removeItem(
                "autoOpenReviewModal"
            );

            if (
                urlParams.get("auto_review")
            ) {

                const cleanUrl =
                    window.location.pathname +
                    window.location.search
                        .replace(
                            /([?&])auto_review=1/,
                            ''
                        )
                        .replace(
                            /[?&]$/,
                            ''
                        );

                window.history.replaceState({},
                    '',
                    cleanUrl
                );
            }

            setTimeout(() => {

                openReviewModalHandler();

            }, 1200);
        }

        /*
        ============================================
        | CLOSE MODAL
        ============================================
        */

        closeBtn?.addEventListener(
            "click",
            () => {

                modal.classList.add("hidden");

                modal.classList.remove("flex");

                const triggerBtn =
                    document.getElementById(
                        'openReviewModal'
                    );

                const shouldRedirect =
                    triggerBtn?.getAttribute(
                        'data-redirect-after'
                    ) === 'true';

                if (shouldRedirect) {

                    window.location.href =
                        "index.php";
                }
            }
        );
        /*
        ============================================
        | REMIND LATER
        ============================================
        */

        remindBtn.addEventListener(
            "click",
            () => {

                modal.classList.add("hidden");

                const triggerBtn =
                    document.getElementById(
                        'openReviewModal'
                    );

                const shouldRedirect =
                    triggerBtn?.getAttribute(
                        'data-redirect-after'
                    ) === 'true';

                if (shouldRedirect) {

                    window.location.href =
                        "index.php";
                }
            }
        );

        /*
        ============================================
        | SUBMIT FEEDBACK
        ============================================
        */

        submitBtn.addEventListener("click", async () => {
            if (submitBtn.disabled) return;
            if (selectedRating === null) {
                GSCReviewFlow.msg("Please select a rating first.", "warning");
                return;
            }
            if (selectedRating >= 8) {
                GSCReviewFlow.saveHigh(selectedRating);
                return;
            }
            GSCReviewFlow.submitLow(selectedRating, submitBtn);
        });

    });
</script>