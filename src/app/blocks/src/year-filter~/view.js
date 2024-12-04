/**
 * WordPress dependencies
 */
import { store, getContext } from "@wordpress/interactivity";

const { state, actions } = store('data-tables', {
    state: {
        donationYears: window.interactivity_state.donationYears || {},
        donorTypes: window.interactivity_state.donorTypes || {},
        selectedDonationYear: window.interactivity_state.donationYear || 'all',
        selectedDonorType: window.interactivity_state.donorType || 'all',
    },
    actions: {
        handleYearChange(event) {
            const selectedYear = event.target.value;
            state.selectedDonationYear = selectedYear;
            actions.loadTable();
        },
        handleDonorTypeChange(event) {
            const selectedDonorType = event.target.value;
            state.selectedDonorType = selectedDonorType;
            actions.loadTable();
        },
        loadTable() {
            const year = state.selectedDonationYear;
            const donorType = state.selectedDonorType;
            // Logic to load table based on selected values
        }
    },
    callbacks: {
        log: () => {
            console.log('State updated:', state);
        }
    }
});