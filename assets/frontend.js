/**
 * Adds a <script type="application/json" id="api-data"> element to the document body
 * containing the API URL. This script runs after the DOM content is fully loaded.
 *
 * The script checks if the element with id="api-data" already exists to prevent
 * adding duplicate elements.
 *
 * @version 1.0.0
 */
document.addEventListener('DOMContentLoaded', function() {
    // Check if the API data script element already exists
    if (document.getElementById('api-data')) {
        return; // Prevent adding duplicate script tag
    }

    // Define the API data object
    var apiData = {
        api_url: apiDataSettings.apiUrl // Get the API URL from settings
    };

    // Create and configure the <script> element
    var script = document.createElement('script');
    script.type = 'application/json';
    script.id = 'api-data';
    script.textContent = JSON.stringify(apiData);

    // Append the <script> element to the document body
    document.body.appendChild(script);
});
