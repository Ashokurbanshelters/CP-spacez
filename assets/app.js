function openCPModal() {
    document.getElementById('cpModal').style.display = 'flex';
}

function closeCPModal() {
    document.getElementById('cpModal').style.display = 'none';
    document.getElementById('cpForm').reset();
}

// Handle CP form submission
document.getElementById('cpForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        firm_name: document.getElementById('cp_firm_name').value,
        cp_name: document.getElementById('cp_name').value,
        mobile: document.getElementById('cp_mobile').value,
        email: document.getElementById('cp_email').value,
        rera: document.getElementById('cp_rera').value
    };

    try {
        const response = await fetch('save-cp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            // Add the new CP to the searchable list
            if (typeof channelPartnersData !== 'undefined') {
                channelPartnersData.push(result.cp);
                
                // Select the newly added CP
                const searchInput = document.getElementById('channel_partner_search');
                const hiddenInput = document.getElementById('channel_partner');
                const selectedInfo = document.getElementById('selected_cp_info');
                
                if (searchInput && hiddenInput && selectedInfo) {
                    searchInput.value = `${result.cp.firm_name} - ${result.cp.cp_name}`;
                    hiddenInput.value = result.cp.id;
                    selectedInfo.innerHTML = `<strong>Selected:</strong> ${result.cp.cp_name} (${result.cp.mobile})`;
                }
            }

            // Close modal
            closeCPModal();

            // Show success message
            alert('Channel Partner added successfully!');
        } else {
            alert('Error adding Channel Partner: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

// Lead form validation
document.getElementById('leadForm')?.addEventListener('submit', function(e) {
    const channelPartner = document.getElementById('channel_partner')?.value;
    
    if (!channelPartner) {
        e.preventDefault();
        alert('Please select a Channel Partner');
        document.getElementById('channel_partner_search')?.focus();
        return false;
    }
});

// Close modal when clicking outside
document.getElementById('cpModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'cpModal') {
        closeCPModal();
    }
});
