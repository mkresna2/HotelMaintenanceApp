import './bootstrap';

import Alpine from 'alpinejs';
import { initCalendar } from './components/CalendarWidget';
import { initStatusTracker } from './components/StatusTracker';
import { initNotificationBell } from './components/NotificationBell';
import { initDashboardCharts } from './components/DashboardCharts';

window.Alpine = Alpine;

Alpine.start();

// Initialize all components when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('HotelMaint Pro initialized');
    
    // Initialize components
    initCalendar();
    initStatusTracker();
    initNotificationBell();
    initDashboardCharts();
});

// Make functions available globally for inline handlers if needed
window.initCalendar = initCalendar;
window.initStatusTracker = initStatusTracker;
window.initNotificationBell = initNotificationBell;
window.initDashboardCharts = initDashboardCharts;
