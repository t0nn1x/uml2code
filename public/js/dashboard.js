// Dashboard Management Module
const DashboardManager = (function () {
    "use strict";

    let charts = {};

    // Initialize the dashboard
    function init() {
        loadDashboardData();
    }

    async function loadDashboardData() {
        try {
            // Load all dashboard data in parallel
            await Promise.all([
                loadSummaryStatistics(),
                loadRecentActivity(),
                loadActivityTrends(),
                loadLanguageUsage()
            ]);
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            showErrorState();
        }
    }

    async function loadSummaryStatistics() {
        try {
            const locale = window.location.pathname.split('/')[1] || 'en';
            const response = await fetch(`/${locale}/api/dashboard/summary`);

            if (!response.ok) {
                throw new Error('Failed to fetch summary statistics');
            }

            const data = await response.json();

            if (data.success) {
                const stats = data.stats;

                // Update counters with animation
                animateValue('diagrams-count', 0, stats.diagrams_processed, 800);
                animateValue('files-count', 0, stats.files_generated, 800);
                animateValue('lines-count', 0, stats.lines_of_code, 800);
                animateValue('total-actions-count', 0, stats.total_actions, 800);
            } else {
                console.error('Failed to load statistics:', data.message || 'Unknown error');
            }
        } catch (error) {
            console.error('Failed to load summary statistics:', error);
            // Fallback to zero values
            document.getElementById('diagrams-count').textContent = '0';
            document.getElementById('files-count').textContent = '0';
            document.getElementById('lines-count').textContent = '0';
            document.getElementById('total-actions-count').textContent = '0';
        }
    }

    async function loadRecentActivity() {
        try {
            const locale = window.location.pathname.split('/')[1] || 'en';
            const response = await fetch(`/${locale}/api/dashboard/activity?limit=5`);
            const data = await response.json();

            if (data.success && data.activity.length > 0) {
                const activityList = document.getElementById('activity-list');
                activityList.innerHTML = '';

                data.activity.forEach(entry => {
                    const li = createActivityItem(entry);
                    activityList.appendChild(li);
                });
            } else {
                const activityList = document.getElementById('activity-list');
                activityList.innerHTML = '<li class="px-4 py-4 sm:px-6 text-center text-gray-500"><p>No recent activity</p></li>';
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
                        <p class="text-sm text-gray-500">
                            ${entry.fileCount} files • ${entry.diagramType}
                            ${entry.programmingLanguage ? ` • ${entry.programmingLanguage}` : ''}
                            ${entry.totalLinesOfCode ? ` • ${entry.totalLinesOfCode} lines` : ''}
                        </p>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    ${timeAgo}
                </div>
            </div>
        `;

        return li;
    }

    async function loadActivityTrends() {
        try {
            const locale = window.location.pathname.split('/')[1] || 'en';
            const response = await fetch(`/${locale}/api/dashboard/trends`);
            const data = await response.json();

            if (data.success) {
                createActivityTrendsChart(data.trends);
            } else {
                console.error('Failed to load trends:', data.message || 'Unknown error');
                showChartError('activity-trends-chart');
            }
        } catch (error) {
            console.error('Failed to load activity trends:', error);
            showChartError('activity-trends-chart');
        }
    }

    async function loadLanguageUsage() {
        try {
            const locale = window.location.pathname.split('/')[1] || 'en';
            const response = await fetch(`/${locale}/api/dashboard/languages`);
            const data = await response.json();

            if (data.success) {
                createLanguageUsageChart(data.languages);
            }
        } catch (error) {
            console.error('Failed to load language usage:', error);
            showChartError('language-usage-chart');
        }
    }

    function createActivityTrendsChart(trendsData) {
        const container = document.getElementById('activity-trends-chart');
        
        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            showChartError('activity-trends-chart');
            console.error('Chart.js library not loaded');
            return;
        }
        
        // Check if data is valid
        if (!trendsData || !Array.isArray(trendsData)) {
            showNoDataMessage('activity-trends-chart', 'No activity data available');
            return;
        }

        const ctx = document.createElement('canvas');
        container.innerHTML = '';
        container.appendChild(ctx);

        // Prepare data for Chart.js
        const labels = trendsData.map(item => item.date);
        const convertData = trendsData.map(item => item.convert || 0);
        const parseData = trendsData.map(item => item.parse || 0);
        const generateData = trendsData.map(item => item.generate || 0);

        try {
            charts.activityTrends = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Convert',
                        data: convertData,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: false,
                        tension: 0.1
                    },
                    {
                        label: 'Parse',
                        data: parseData,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: false,
                        tension: 0.1
                    },
                    {
                        label: 'Generate',
                        data: generateData,
                        borderColor: 'rgb(147, 51, 234)',
                        backgroundColor: 'rgba(147, 51, 234, 0.1)',
                        fill: false,
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        } catch (error) {
            console.error('Error creating activity trends chart:', error);
            showChartError('activity-trends-chart');
        }
    }

    function createLanguageUsageChart(languageData) {
        const container = document.getElementById('language-usage-chart');
        
        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            showChartError('language-usage-chart');
            console.error('Chart.js library not loaded');
            return;
        }
        
        if (!languageData || languageData.length === 0) {
            showNoDataMessage('language-usage-chart', 'No language data available');
            return;
        }

        const ctx = document.createElement('canvas');
        container.innerHTML = '';
        container.appendChild(ctx);

        // Prepare data for Chart.js
        const labels = languageData.map(item => item.language);
        const data = languageData.map(item => item.count);
        // Extensive color palette for many programming languages
        const colors = [
            '#4F46E5', // Indigo - PHP
            '#EF4444', // Red - Java
            '#10B981', // Green - Python
            '#F59E0B', // Amber - JavaScript
            '#8B5CF6', // Purple - C#
            '#06B6D4', // Cyan - C++
            '#F97316', // Orange - Go
            '#84CC16', // Lime - Kotlin
            '#EC4899', // Pink - Swift
            '#6366F1', // Blue - TypeScript
            '#14B8A6', // Teal - Rust
            '#F472B6', // Rose - Ruby
            '#A78BFA', // Light Purple - Scala
            '#34D399', // Emerald - Dart
            '#FBBF24', // Yellow - C
            '#FB7185', // Light Red - Perl
            '#60A5FA', // Light Blue - R
            '#A3E635', // Light Green - Elixir
            '#FACC15', // Gold - MATLAB
            '#F87171', // Coral - Lua
            '#818CF8', // Lavender - Haskell
            '#4ADE80', // Mint - Julia
            '#FDE047', // Lemon - Shell
            '#FB923C', // Peach - Objective-C
            '#C084FC', // Orchid - F#
            '#22D3EE', // Sky Blue - Clojure
            '#FDE047', // Bright Yellow - COBOL
            '#FF6B6B', // Salmon - Assembly
            '#4ECDC4', // Turquoise - Pascal
            '#45B7D1'  // Steel Blue - Fortran
        ];

        try {
            charts.languageUsage = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, data.length),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating language usage chart:', error);
            showChartError('language-usage-chart');
        }
    }

    function showChartError(containerId) {
        const container = document.getElementById(containerId);
        container.innerHTML = '<div class="flex items-center justify-center h-full"><div class="text-red-500">Failed to load chart</div></div>';
    }

    function showNoDataMessage(containerId, message) {
        const container = document.getElementById(containerId);
        container.innerHTML = `<div class="flex items-center justify-center h-full"><div class="text-gray-500">${message}</div></div>`;
    }

    function showErrorState() {
        // Show error messages in statistics if needed
        console.error('Dashboard is in error state');
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

        const obj = document.getElementById(id);
        const range = Math.abs(end - start);
        
        // Use more efficient animation for larger numbers
        const frameRate = 60; // 60 FPS
        const totalFrames = Math.ceil(duration / (1000 / frameRate));
        const increment = range / totalFrames;
        
        let current = start;
        let frame = 0;
        
        const timer = setInterval(function() {
            frame++;
            current = start + (increment * frame * (end > start ? 1 : -1));
            
            // Ensure we don't overshoot
            if ((end > start && current >= end) || (end < start && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            
            obj.textContent = Math.floor(current);
        }, 1000 / frameRate);
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
