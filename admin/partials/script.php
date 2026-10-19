<?php
// admin/partials/script.php — Admin scripts
?>
<script>
    function showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `p-4 rounded mb-4 ${
            type === 'success' ? 'bg-green-900 text-green-200 border border-green-700' :
            type === 'error' ? 'bg-red-900 text-red-200 border border-red-700' :
            'bg-blue-900 text-blue-200 border border-blue-700'
        }`;
        alertDiv.textContent = message;
        document.body.insertBefore(alertDiv, document.body.firstChild);
        setTimeout(() => alertDiv.remove(), 5000);
    }
</script>
