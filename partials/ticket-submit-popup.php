<?php
// partials/ticket-submit-popup.php — Support ticket popup trigger
// Same pattern as Wix/Shopify partials/ticket-submit-popup.php

$title = $title ?? 'Google Search Console — Squarespace';
?>
<!-- Ticket Submit Popup Trigger Button -->
<button id="support-ticket-btn"
    class="px-4 py-2 bg-cstm-primary text-white rounded-lg text-sm font-medium hover:bg-blue-600 transition-colors">
    <iconify-icon icon="bx:support" width="16" height="16" class="mr-1"></iconify-icon>
    Support
</button>

<!-- Popup (hidden by default) -->
<div id="ticket-popup" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="document.getElementById('ticket-popup').classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white dark:bg-neutral-800 w-full max-w-md p-6 rounded-lg shadow-xl">
        <h3 class="text-lg font-bold mb-4 text-neutral-800 dark:text-white">Submit Support Ticket</h3>
        <form id="ticketForm">
            <div class="mb-4">
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Subject</label>
                <input type="text" name="subject" class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Message</label>
                <textarea name="message" rows="4" class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white" required></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('ticket-popup').classList.add('hidden')" class="px-4 py-2 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-cstm-primary text-white rounded hover:bg-blue-600">Submit</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('ticketForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const res = await fetch('api/create_ticket.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            alert('Ticket submitted successfully!');
            document.getElementById('ticket-popup').classList.add('hidden');
            e.target.reset();
        } else {
            alert('Error: ' + (data.message || 'Failed to submit'));
        }
    } catch (err) {
        alert('Network error.');
    }
});
</script>
