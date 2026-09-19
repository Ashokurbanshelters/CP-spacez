<?php
session_start();
require_once 'id-helper.php'; // Add this line

if (!isset($_SESSION['customer_phone'])) {
    header('Location: index.php');
    exit;
}

$phone = $_SESSION['customer_phone'];

// Load channel partners
$cpFile = 'data/channel_partners.json';
$channelPartners = [];

if (file_exists($cpFile)) {
    $channelPartners = json_decode(file_get_contents($cpFile), true) ?: [];
}

$projects = [
    'Platinum Landmark',
    'Platinum Green Fields',
    'Suraksha Sunrise Park',
    'Urban Tranquil',
    'Sri Nandana Paradise',
    'PANCHAJANYAA'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Registration - Lead Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .dropdown-container {
            position: relative;
        }
        
        .dropdown-search {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            margin-top: 0.25rem;
            max-height: 300px;
            overflow-y: auto;
            z-index: 50;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: none;
        }
        
        .dropdown-search.visible {
            display: block;
        }
        
        .dropdown-item {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.15s;
        }
        
        .dropdown-item:hover {
            background-color: #f3f4f6;
        }
        
        .dropdown-item.selected {
            background-color: #dbeafe;
        }
        
        .dropdown-item.highlighted {
            background-color: #eff6ff;
        }
        
        .no-results {
            padding: 0.75rem;
            text-align: center;
            color: #6b7280;
        }
        
        .cp-details {
            display: flex;
            flex-direction: column;
        }
        
        .cp-name {
            font-weight: 600;
            color: #1f2937;
        }
        
        .cp-info {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.125rem;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Lead Registration</h1>
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Step 2: Lead Information</h2>
                
                <form id="leadForm" action="save-lead.php" method="POST">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                    
                    <!-- Customer Phone Number (Read-only from Step 1) -->
                    <div class="mb-4">
                        <label for="customer_phone_display" class="block text-sm font-medium text-gray-700 mb-1">
                            Customer Phone Number
                        </label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-100 text-gray-500 text-sm">
                                +91
                            </span>
                            <input 
                                type="tel" 
                                id="customer_phone_display" 
                                value="<?php echo htmlspecialchars($phone); ?>"
                                readonly
                                class="flex-1 rounded-none rounded-r-md border border-gray-300 px-3 py-2 bg-gray-50 text-gray-700 cursor-not-allowed"
                            >
                        </div>
                        <p class="mt-1 text-xs text-gray-500">This number was entered in Step 1</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Customer Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                required
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email (Optional)
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="spouse_phone" class="block text-sm font-medium text-gray-700 mb-1">
                            Spouse Phone (Optional)
                        </label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                +91
                            </span>
                            <input 
                                type="tel" 
                                id="spouse_phone" 
                                name="spouse_phone" 
                                pattern="[0-9]{10}" 
                                maxlength="10"
                                placeholder="10-digit number (optional)"
                                class="flex-1 rounded-none rounded-r-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="project" class="block text-sm font-medium text-gray-700 mb-1">
                            Project Interested <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="project" 
                            name="project" 
                            required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">Select Project</option>
                            <?php foreach($projects as $project): ?>
                            <option value="<?php echo htmlspecialchars($project); ?>">
                                <?php echo htmlspecialchars($project); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="channel_partner_search" class="block text-sm font-medium text-gray-700 mb-1">
                            Channel Partner <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <div class="flex-1 dropdown-container">
                                <input type="hidden" id="channel_partner" name="channel_partner" required>
                                <input 
                                    type="text" 
                                    id="channel_partner_search"
                                    placeholder="Search by name, firm or phone number..."
                                    autocomplete="off"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                <div id="cp_dropdown" class="dropdown-search"></div>
                            </div>
                            <button 
                                type="button" 
                                onclick="openCPModal()"
                                class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition duration-200"
                            >
                                Add CP
                            </button>
                        </div>
                        <div id="selected_cp_info" class="mt-2 text-sm text-gray-600"></div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="remarks" class="block text-sm font-medium text-gray-700 mb-1">
                            Remarks
                        </label>
                        <textarea 
                            id="remarks" 
                            name="remarks" 
                            rows="3"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        ></textarea>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200 font-medium">
                            Save Lead
                        </button>
                        <a href="index.php" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 transition duration-200 font-medium text-center">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Channel Partner Modal -->
    <div id="cpModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Add Channel Partner</h3>
            
            <form id="cpForm">
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Firm Name <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="cp_firm_name" 
                        required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                </div>
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        CP Name <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="cp_name" 
                        required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                </div>
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mobile <span class="text-red-500">*</span>
                    </label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                            +91
                        </span>
                        <input 
                            type="tel" 
                            id="cp_mobile" 
                            pattern="[0-9]{10}" 
                            maxlength="10"
                            required
                            class="flex-1 rounded-none rounded-r-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="cp_email" 
                        required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        RERA Number
                    </label>
                    <input 
                        type="text" 
                        id="cp_rera"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                </div>
                
                <div class="flex gap-3">
                    <button 
                        type="submit" 
                        class="flex-1 bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition duration-200"
                    >
                        Save CP
                    </button>
                    <button 
                        type="button" 
                        onclick="closeCPModal()"
                        class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 transition duration-200"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Channel Partners data from PHP
        const channelPartnersData = <?php echo json_encode($channelPartners); ?>;
        
        // Searchable dropdown functionality
        const searchInput = document.getElementById('channel_partner_search');
        const hiddenInput = document.getElementById('channel_partner');
        const dropdown = document.getElementById('cp_dropdown');
        const selectedInfo = document.getElementById('selected_cp_info');
        let highlightedIndex = -1;
        
        // Show dropdown on focus
        searchInput.addEventListener('focus', function() {
            showDropdown();
        });
        
        // Filter on input (searches by name, firm, and phone number)
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterChannelPartners(searchTerm);
            hiddenInput.value = ''; // Clear hidden input when searching
            selectedInfo.textContent = '';
        });
        
        // Keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            const items = dropdown.querySelectorAll('.dropdown-item:not(.no-results)');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                highlightedIndex = Math.min(highlightedIndex + 1, items.length - 1);
                updateHighlight(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                highlightedIndex = Math.max(highlightedIndex - 1, 0);
                updateHighlight(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (highlightedIndex >= 0 && items[highlightedIndex]) {
                    const cp = channelPartnersData.find(c => c.id === items[highlightedIndex].dataset.id);
                    if (cp) selectChannelPartner(cp);
                }
            } else if (e.key === 'Escape') {
                hideDropdown();
            }
        });
        
        function updateHighlight(items) {
            items.forEach((item, index) => {
                if (index === highlightedIndex) {
                    item.classList.add('highlighted');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('highlighted');
                }
            });
        }
        
        function showDropdown() {
            filterChannelPartners(searchInput.value);
            dropdown.classList.add('visible');
        }
        
        function hideDropdown() {
            dropdown.classList.remove('visible');
            highlightedIndex = -1;
        }
        
        function filterChannelPartners(searchTerm) {
    dropdown.innerHTML = '';
    highlightedIndex = -1;
    
    const filtered = channelPartnersData.filter(cp => {
        const searchString = `${cp.firm_name} ${cp.cp_name} ${cp.mobile}`.toLowerCase();
        return searchString.includes(searchTerm);
    });
    
    if (filtered.length === 0) {
        dropdown.innerHTML = '<div class="no-results">No channel partners found</div>';
    } else {
        filtered.forEach(cp => {
            const item = document.createElement('div');
            item.className = 'dropdown-item';
            item.dataset.id = cp.id;
            // Updated: Only show firm name and CP name
            item.innerHTML = `
                <div class="cp-details">
                    <div class="cp-name">${escapeHtml(cp.firm_name)} - ${escapeHtml(cp.cp_name)}</div>
                </div>
            `;
            item.addEventListener('click', () => selectChannelPartner(cp));
            dropdown.appendChild(item);
        });
    }
    
    dropdown.classList.add('visible');
}
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function selectChannelPartner(cp) {
            const displayMobile = cp.mobile.replace('+91', '');
            searchInput.value = `${cp.firm_name} - ${cp.cp_name}`;
            hiddenInput.value = cp.id;
            selectedInfo.innerHTML = `<strong>Selected:</strong> ${escapeHtml(cp.cp_name)} `;
            hideDropdown();
        }
        
        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                hideDropdown();
            }
        });
    </script>
    
    <script src="assets/app.js"></script>
</body>
</html>