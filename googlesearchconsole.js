<?php
// googlesearchconsole.js — Frontend JS for GSC features
document.addEventListener('DOMContentLoaded', function() {
    // Copy verification token
    const copyBtn = document.getElementById('copyVerificationToken');
    if (copyBtn) {
        copyBtn.addEventListener('click', async function() {
            const textarea = document.getElementById('verificationToken');
            if (textarea) {
                try {
                    await navigator.clipboard.writeText(textarea.value);
                    this.textContent = 'Copied!';
                    setTimeout(() => { this.textContent = 'Copy Token'; }, 1800);
                } catch (err) {
                    alert('Failed to copy.');
                }
            }
        });
    }

    // Auto-check property status
    const checkBtn = document.getElementById('checkPropertyStatus');
    if (checkBtn) {
        checkBtn.addEventListener('click', async function() {
            const instanceId = this.dataset.instance;
            try {
                const res = await fetch(`api/google/check_property_status.php?instanceId=${instanceId}`);
                const data = await res.json();
                if (data.verified) {
                    alert('Property is verified!');
                    location.reload();
                } else {
                    alert('Property not yet verified.');
                }
            } catch (e) {
                alert('Error checking property status.');
            }
        });
    }
});
