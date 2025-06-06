// History Management Module
const HistoryManager = (function () {
  "use strict";

  let currentAction = "general";
  let currentHistory = [];
  let currentDetailEntry = null;

  // Initialize the history module
  function init() {
    setupEventListeners();
  }

  // Setup event listeners
  function setupEventListeners() {
    // History modal close buttons
    const closeBtn = document.getElementById("history-close-btn");
    if (closeBtn) {
      closeBtn.addEventListener("click", closeHistoryModal);
    }
    
    const closeXBtn = document.getElementById("history-close-x-btn");
    if (closeXBtn) {
      closeXBtn.addEventListener("click", closeHistoryModal);
    }

    // History detail modal close button
    const detailCloseBtn = document.getElementById("history-detail-close-btn");
    if (detailCloseBtn) {
      detailCloseBtn.addEventListener("click", closeDetailModal);
    }

    // History tabs
    document.querySelectorAll(".history-tab").forEach((tab) => {
      tab.addEventListener("click", function () {
        const action = this.dataset.action;
        switchTab(action);
      });
    });

    // Click outside to close
    document
      .getElementById("history-modal")
      .addEventListener("click", function (e) {
        if (e.target === this) {
          closeHistoryModal();
        }
      });

    document
      .getElementById("history-detail-modal")
      .addEventListener("click", function (e) {
        if (e.target === this) {
          closeDetailModal();
        }
      });

    // Download all button in detail modal
    const downloadAllBtn = document.getElementById(
      "history-detail-download-all"
    );
    if (downloadAllBtn) {
      downloadAllBtn.addEventListener("click", downloadAllFiles);
    }
  }

  // Open history modal
  function openHistoryModal(action = "general") {
    currentAction = action;
    document.getElementById("history-modal").classList.remove("hidden");
    switchTab(action);
  }

  // Close history modal
  function closeHistoryModal() {
    document.getElementById("history-modal").classList.add("hidden");
  }

  // Close detail modal
  function closeDetailModal() {
    document.getElementById("history-detail-modal").classList.add("hidden");
    currentDetailEntry = null;
  }

  // Switch tab
  function switchTab(action) {
    currentAction = action;

    // Update tab styling
    document.querySelectorAll(".history-tab").forEach((tab) => {
      if (tab.dataset.action === action) {
        tab.classList.add("active", "border-indigo-500", "text-indigo-600");
        tab.classList.remove(
          "border-transparent",
          "text-gray-500",
          "hover:text-gray-700",
          "hover:border-gray-300"
        );
      } else {
        tab.classList.remove("active", "border-indigo-500", "text-indigo-600");
        tab.classList.add(
          "border-transparent",
          "text-gray-500",
          "hover:text-gray-700",
          "hover:border-gray-300"
        );
      }
    });

    // Load history
    loadHistory(action);
  }

  // Load history from API
  async function loadHistory(action) {
    showLoading();
    hideError();

    try {
      // Get the current locale from the URL or use 'en' as default
      const locale = window.location.pathname.split('/')[1] || 'en';
      const response = await fetch(`/${locale}/api/history/${action}`);
      const data = await response.json();

      if (data.success) {
        currentHistory = data.history;
        displayHistory(data.history);
      } else {
        showError(data.error || "Failed to load history");
      }
    } catch (error) {
      showError("Network error: " + error.message);
    }
  }

  // Display history in table
  function displayHistory(history) {
    hideLoading();

    const tbody = document.getElementById("history-tbody");
    tbody.innerHTML = "";

    if (history.length === 0) {
      document.getElementById("history-content").classList.remove("hidden");
      document.getElementById("history-empty").classList.remove("hidden");
      return;
    }

    document.getElementById("history-empty").classList.add("hidden");
    document.getElementById("history-content").classList.remove("hidden");

    history.forEach((entry) => {
      const row = createHistoryRow(entry);
      tbody.appendChild(row);
    });
  }

  // Create history table row
  function createHistoryRow(entry) {
    const tr = document.createElement("tr");

    // Format date
    const date = new Date(entry.createdAt);
    const dateStr = date.toLocaleDateString() + " " + date.toLocaleTimeString();

    // Action badge
    const actionBadge = getActionBadge(entry.actionType);

    // File names list
    const filesList = entry.fileNames.slice(0, 3).join(", ");
    const moreFiles =
      entry.fileNames.length > 3
        ? ` (+${entry.fileNames.length - 3} more)`
        : "";

    tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${dateStr}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${actionBadge}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${entry.diagramType}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
                <span class="font-medium">${entry.fileCount} files</span><br>
                <span class="text-xs">${filesList}${moreFiles}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button class="text-indigo-600 hover:text-indigo-900" onclick="HistoryManager.viewDetail(${entry.id})">
                    View
                </button>
            </td>
        `;

    return tr;
  }

  // Get action badge HTML
  function getActionBadge(actionType) {
    const badges = {
      convert:
        '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Convert</span>',
      parse:
        '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Parse</span>',
      generate:
        '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">Generate</span>',
    };

    return badges[actionType] || actionType;
  }

  // View history detail
  async function viewDetail(id) {
    try {
      // Get the current locale from the URL or use 'en' as default
      const locale = window.location.pathname.split('/')[1] || 'en';
      const response = await fetch(`/${locale}/api/history/${id}`);
      const data = await response.json();

      if (data.success) {
        currentDetailEntry = data.entry;
        showDetail(data.entry);
      } else {
        alert(data.error || "Failed to load history detail");
      }
    } catch (error) {
      alert("Network error: " + error.message);
    }
  }

  // Show detail modal
  function showDetail(entry) {
    const content = document.getElementById("history-detail-content");
    content.innerHTML = "";

    // Add file viewers
    entry.files.forEach((file, index) => {
      const fileDiv = document.createElement("div");
      fileDiv.className =
        "bg-white rounded-md border border-gray-200 overflow-hidden";
      fileDiv.innerHTML = `
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                    <span class="font-medium text-sm text-gray-700">${
                      file.filename
                    }</span>
                    <div>
                        <button class="text-sm text-indigo-600 hover:text-indigo-500 mr-2" onclick="HistoryManager.copyFile(${index})">
                            Copy
                        </button>
                        <button class="text-sm text-indigo-600 hover:text-indigo-500" onclick="HistoryManager.downloadFile(${index})">
                            Download
                        </button>
                    </div>
                </div>
                <div class="p-4">
                    <pre class="text-xs overflow-x-auto"><code>${escapeHtml(
                      file.content
                    )}</code></pre>
                </div>
            `;
      content.appendChild(fileDiv);
    });

    document.getElementById("history-detail-modal").classList.remove("hidden");
  }

  // Copy file content
  function copyFile(index) {
    if (currentDetailEntry && currentDetailEntry.files[index]) {
      const content = currentDetailEntry.files[index].content;
      navigator.clipboard.writeText(content).then(() => {
        // Show success feedback
        event.target.textContent = "Copied!";
        event.target.classList.add("text-green-600");
        setTimeout(() => {
          event.target.textContent = "Copy";
          event.target.classList.remove("text-green-600");
        }, 2000);
      });
    }
  }

  // Download single file
  function downloadFile(index) {
    if (currentDetailEntry && currentDetailEntry.files[index]) {
      const file = currentDetailEntry.files[index];
      const blob = new Blob([file.content], { type: "text/plain" });
      const link = document.createElement("a");
      link.href = URL.createObjectURL(blob);
      link.download = file.filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }

  // Download all files as ZIP
  async function downloadAllFiles() {
    if (!currentDetailEntry || !window.JSZip) {
      alert("JSZip library not loaded");
      return;
    }

    const zip = new JSZip();
    currentDetailEntry.files.forEach((file) => {
      zip.file(file.filename, file.content);
    });

    const content = await zip.generateAsync({ type: "blob" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(content);
    link.download = `history_${currentDetailEntry.id}_files.zip`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  // Show loading state
  function showLoading() {
    document.getElementById("history-loading").classList.remove("hidden");
    document.getElementById("history-content").classList.add("hidden");
    document.getElementById("history-error").classList.add("hidden");
  }

  // Hide loading state
  function hideLoading() {
    document.getElementById("history-loading").classList.add("hidden");
  }

  // Show error
  function showError(message) {
    document.getElementById("history-error-message").textContent = message;
    document.getElementById("history-error").classList.remove("hidden");
    document.getElementById("history-loading").classList.add("hidden");
    document.getElementById("history-content").classList.add("hidden");
  }

  // Hide error
  function hideError() {
    document.getElementById("history-error").classList.add("hidden");
  }

  // Escape HTML
  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // Public API
  return {
    init: init,
    open: openHistoryModal,
    viewDetail: viewDetail,
    copyFile: copyFile,
    downloadFile: downloadFile,
  };
})();

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  HistoryManager.init();
});

// Make globally available
window.HistoryManager = HistoryManager;
