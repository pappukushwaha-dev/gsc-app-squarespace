<?php
// partials/instruction-popup.php — Instruction popup for setup wizard
?>
<!-- Instruction Popup -->
<div id="instruction-popup" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="document.getElementById('instruction-popup').classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white dark:bg-neutral-800 w-full max-w-lg p-6 rounded-lg shadow-xl">
        <h3 class="text-lg font-bold mb-4 text-neutral-800 dark:text-white">Setup Instructions</h3>
        <div class="space-y-4 text-sm text-neutral-600 dark:text-neutral-300">
            <p><strong>Step 1:</strong> Enter your Squarespace site URL. We verify it's a Squarespace site and get your site ID.</p>
            <p><strong>Step 2:</strong> Connect your Google account, create a Search Console property, and verify your domain.</p>
            <p><strong>Step 3:</strong> Submit your sitemap (usually <code>https://yoursite.com/sitemap.xml</code>) to start tracking.</p>
        </div>
        <div class="mt-6 flex justify-end">
            <button onclick="document.getElementById('instruction-popup').classList.add('hidden')" class="px-4 py-2 bg-cstm-primary text-white rounded hover:bg-blue-600">Got it</button>
        </div>
    </div>
</div>
