import Alpine from 'alpinejs';

/**
 * Alpine.js component for real-time work order/complaint status tracking
 * Updates status without page reload and renders activity feed
 */
export function initStatusTracker() {
    const trackerEl = document.getElementById('status-tracker');
    
    if (!trackerEl) return;

    document.addEventListener('alpine:init', () => {
        Alpine.data('statusTracker', (initialStatus, initialLogs = []) => ({
            status: initialStatus,
            logs: initialLogs,
            isLoading: false,
            
            // Status color mapping
            statusColors: {
                'open': 'bg-blue-100 text-blue-800',
                'in_progress': 'bg-yellow-100 text-yellow-800',
                'pending_parts': 'bg-orange-100 text-orange-800',
                'resolved': 'bg-green-100 text-green-800',
                'closed': 'bg-gray-100 text-gray-800'
            },

            // Update status via API
            async updateStatus(newStatus) {
                this.isLoading = true;
                
                try {
                    const response = await fetch(window.location.pathname, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            status: newStatus,
                            _method: 'PATCH'
                        })
                    });

                    if (!response.ok) throw new Error('Failed to update status');

                    const data = await response.json();
                    
                    // Update local state
                    this.status = data.status;
                    if (data.log) {
                        this.logs.unshift(data.log); // Add new log to top
                    }

                    // Dispatch event for other components
                    window.dispatchEvent(new CustomEvent('status-updated', { 
                        detail: { status: this.status } 
                    }));

                } catch (error) {
                    console.error('Error updating status:', error);
                    alert('Failed to update status. Please try again.');
                } finally {
                    this.isLoading = false;
                }
            },

            // Format timestamp
            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            // Get formatted status label
            getStatusLabel(status) {
                const labels = {
                    'open': 'Open',
                    'in_progress': 'In Progress',
                    'pending_parts': 'Pending Parts',
                    'resolved': 'Resolved',
                    'closed': 'Closed'
                };
                return labels[status] || status;
            }
        }));
    });

    // Initialize Alpine if not already done
    if (!window.Alpine) {
        window.Alpine = Alpine;
        Alpine.start();
    }
}

// Auto-initialize if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStatusTracker);
} else {
    initStatusTracker();
}
