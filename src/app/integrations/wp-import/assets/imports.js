document.addEventListener('DOMContentLoaded', function () {
    const runImportsButton = document.getElementById('run-imports');
    const importsForm = document.getElementById('run-imports-form');

    if (runImportsButton && importsForm) {
        runImportsButton.addEventListener('click', function () {

            const importIds = Array.from(
                importsForm.querySelectorAll('input[name="import_ids[]"]:checked')
            ).map(input => input.value);

            if (importIds.length === 0) {
                alert('Please select at least one import to run.');
                return;
            }


            const data = new URLSearchParams({
                action: wpImports.action, // Use the action from PHP
                import_ids: importIds.join(','),
                security: wpImports.nonce
            });

            // Disable the button to prevent multiple clicks
            runImportsButton.disabled = true;

            fetch(wpImports.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: data
            })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        alert('Imports completed successfully!');
                        console.log('Results:', response.data.results);
                    } else {
                        alert(response.data.message || 'An error occurred while running imports.');
                        console.error('Error:', response);
                    }
                })
                .catch(error => {
                    alert('A network error occurred.');
                    console.error('Network Error:', error);
                })
                .finally(() => {
                    // Re-enable the button
                    runImportsButton.disabled = false;
                });
        });
    }
});
