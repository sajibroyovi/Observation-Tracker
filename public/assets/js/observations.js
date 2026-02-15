/**
 * observations.js - Observation Modal JavaScript Functions
 * 
 * This file contains all JavaScript functionality for the Observation Entry modal,
 * including team selection, image validation, and UI interactions.
 * 
 * Author: Shift Handover Application
 * Last Modified: 2026-02-08
 */

// ============================================================================
// TEAM SELECTION FUNCTIONALITY
// ============================================================================

/**
 * Initialize the team selection dropdown with checkbox-less selection
 * Handles team selection UI, badge rendering, and label updates
 */
function initializeTeamSelection() {
    const teamItems = document.querySelectorAll('.team-item');
    const selectAll = document.getElementById('selectAllTeams');
    const label = document.getElementById('teamDropdownLabel');
    const button = document.getElementById('teamDropdown');
    const container = document.getElementById('selectedTeamsContainer');
    const placeholder = document.getElementById('noTeamsPlaceholder');

    if (!teamItems.length) return; // Exit if no team items found

    /**
     * Updates the UI based on current team selection
     * - Renders selected team badges
     * - Updates dropdown label text
     * - Manages visual states
     */
    function updateTeamUI() {
        const checked = [];
        container.innerHTML = '';

        teamItems.forEach(item => {
            const checkbox = item.querySelector('.team-checkbox');
            const checkIcon = item.querySelector('.check-icon');

            if (checkbox.checked) {
                item.classList.add('bg-primary-soft', 'text-primary');
                checkIcon.classList.remove('opacity-0');
                checked.push(checkbox.value);

                // Create and append badge
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-2 rounded-pill transition-all hover-scale shadow-sm';
                badge.style.fontSize = '0.75rem';
                badge.innerHTML = `<i class="fa-solid fa-users me-1"></i> ${checkbox.value}`;
                container.appendChild(badge);
            } else {
                item.classList.remove('bg-primary-soft', 'text-primary');
                checkIcon.classList.add('opacity-0');
            }
        });

        // Update dropdown label and state
        if (checked.length === 0) {
            label.textContent = 'Select Impacted Teams';
            button.classList.remove('border-primary');
            if (placeholder) container.appendChild(placeholder);
        } else {
            if (checked.length === teamItems.length) {
                label.textContent = 'All Teams Selected';
            } else if (checked.length === 1) {
                label.textContent = checked[0];
            } else {
                label.textContent = checked.length + ' Teams Selected';
            }
            button.classList.add('border-primary');
        }
    }

    // Attach click handlers to team items
    teamItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.stopPropagation();
            const checkbox = this.querySelector('.team-checkbox');
            checkbox.checked = !checkbox.checked;
            updateTeamUI();
        });
    });

    // Attach handler to "Select All" button
    if (selectAll) {
        selectAll.addEventListener('click', function (e) {
            e.stopPropagation();
            const anyUnchecked = Array.from(teamItems).some(item => !item.querySelector('.team-checkbox').checked);
            teamItems.forEach(item => {
                item.querySelector('.team-checkbox').checked = anyUnchecked;
            });
            updateTeamUI();
        });
    }

    // Initialize UI on page load
    updateTeamUI();
}

// ============================================================================
// IMAGE UPLOAD VALIDATION
// ============================================================================

/**
 * Validates the number of images selected in file input
 * Enforces a maximum of 2 images and updates the visual counter
 * 
 * @param {HTMLInputElement} input - The file input element
 */
function validateImageCount(input) {
    const badge = document.getElementById('imageCountBadge');
    if (!badge) return; // Exit if badge element not found

    const count = input.files.length;

    if (count > 2) {
        alert("Please select maximum 2 images.");
        input.value = "";
        badge.textContent = "0 / 2 selected";
        badge.classList.remove('bg-primary', 'text-white', 'bg-info');
        badge.classList.add('bg-light', 'text-muted');
    } else {
        badge.textContent = count + " / 2 selected";
        if (count > 0) {
            badge.classList.remove('bg-light', 'text-muted');
            badge.classList.add('bg-info', 'text-white');
        } else {
            badge.classList.remove('bg-info', 'text-white');
            badge.classList.add('bg-light', 'text-muted');
        }
    }
}

// ============================================================================
// INITIALIZATION
// ============================================================================

/**
 * Initialize all observation modal functionality when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function () {
    // Initialize team selection if elements exist
    if (document.querySelector('.team-item')) {
        initializeTeamSelection();
    }
});

// Export functions for global access
window.validateImageCount = validateImageCount;
window.initializeTeamSelection = initializeTeamSelection;
