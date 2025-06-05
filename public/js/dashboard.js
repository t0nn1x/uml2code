// Dashboard Management Module
const DashboardManager = (function () {
    "use strict";

    // Initialize the dashboard
    function init() {
        loadStatistics();
    }

    async function loadStatistics() {
        try {
            // Get the current locale from the URL or use 'en' as default
            const locale = window.location.pathname.split('/')[1] || 'en';
            const response = await fetch(`/${locale}/api/history/stats`);

            if (!response.ok) {
                throw new Error('Failed to fetch statistics');
            }

            const text = await response.text();
            let data;

            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid response format');
            }

            if (data.success) {
                const stats = data.stats;

                // Update counters
                document.getElementById('diagrams-count').textContent = stats.parse || 0;
                document.getElementById('files-count').textContent = (stats.convert || 0) + (stats.generate || 0);

                // Animate numbers
                animateValue('diagrams-count', 0, stats.parse || 0, 1000);
                animateValue('files-count', 0, (stats.convert || 0) + (stats.generate || 0), 1000);

                // Load recent activity
                loadRecentActivity();
            }
        } catch (error) {
            console.error('Failed to load statistics:', error);
            // Fallback to localStorage
            const stats = {
                diagrams: localStorage.getItem('uml2code_diagrams_count') || 0,
                files: localStorage.getItem('uml2code_files_count') || 0
            };

            document.getElementById('diagrams-count').textContent = stats.diagrams;
            document.getElementById('files-count').textContent = stats.files;
        }
    }

    async function loadRecentActivity() {
        try {
            // Get the current locale from the URL or use 'en' as default
            const locale = window.location.pathname.split('/')[1] || 'en';
            const response = await fetch(`/${locale}/api/history/general`);
            const data = await response.json();

            if (data.success && data.history.length > 0) {
                const activityList = document.getElementById('activity-list');
                activityList.innerHTML = '';

                // Show only the 5 most recent
                data.history.slice(0, 5).forEach(entry => {
                    const li = createActivityItem(entry);
                    activityList.appendChild(li);
                });
            }
        } catch (error) {
            console.error('Failed to load recent activity:', error);
        }
    }

    function createActivityItem(entry) {
        const li = document.createElement('li');
        li.className = 'px-4 py-4 sm:px-6 hover:bg-gray-50 cursor-pointer';
        li.onclick = () => {
            if (window.HistoryManager) {
                window.HistoryManager.viewDetail(entry.id);
            }
        };

        const date = new Date(entry.createdAt);
        const timeAgo = getTimeAgo(date);

        const actionLabels = {
            'convert': 'Converted UML to Code',
            'parse': 'Parsed UML Diagram', 
            'generate': 'Generated Code'
        };

        const actionColors = {
            'convert': 'text-green-600',
            'parse': 'text-blue-600',
            'generate': 'text-purple-600'
        };

        li.innerHTML = `
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 ${actionColors[entry.actionType]}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">${actionLabels[entry.actionType]}</p>
                        <p class="text-sm text-gray-500">${entry.fileCount} files • ${entry.diagramType}</p>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    ${timeAgo}
                </div>
            </div>
        `;

        return li;
    }

    function getTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);

        let interval = seconds / 31536000;
        if (interval > 1) {
            return Math.floor(interval) + ' years ago';
        }

        interval = seconds / 2592000;
        if (interval > 1) {
            return Math.floor(interval) + ' months ago';
        }

        interval = seconds / 86400;
        if (interval > 1) {
            return Math.floor(interval) + ' days ago';
        }

        interval = seconds / 3600;
        if (interval > 1) {
            return Math.floor(interval) + ' hours ago';
        }

        interval = seconds / 60;
        if (interval > 1) {
            return Math.floor(interval) + ' minutes ago';
        }

        return 'Just now';
    }

    function animateValue(id, start, end, duration) {
        if (start === end) {
            return;
        }

        const range = end - start;
        const increment = end > start ? 1 : -1;
        const stepTime = Math.abs(Math.floor(duration / range));
        const obj = document.getElementById(id);
        let current = start;
        const timer = setInterval(function() {
            current += increment;
            obj.textContent = current;
            if (current == end) {
                clearInterval(timer);
            }
        }, stepTime);
    }

    // Public API
    return {
        init: init
    };
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    DashboardManager.init();
});

// Make globally available
window.DashboardManager = DashboardManager; 
