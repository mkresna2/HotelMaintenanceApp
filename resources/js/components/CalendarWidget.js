import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

/**
 * Initialize FullCalendar for Maintenance Scheduling
 * Supports drag-and-drop rescheduling and event clicks
 */
export function initCalendar() {
    const calendarEl = document.getElementById('maintenance-calendar');
    
    if (!calendarEl) return null;

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: true, // Enable drag-and-drop
        droppable: true, // Allow dropping events
        selectable: true,
        events: '/api/schedules/events', // Load events from API
        eventClick: function(info) {
            // Handle event click - show details or edit modal
            window.location.href = `/schedules/${info.event.id}`;
        },
        dateClick: function(info) {
            // Handle date click - create new schedule
            window.location.href = `/schedules/create?date=${info.dateStr}`;
        },
        eventDrop: function(info) {
            // Handle drag-and-drop rescheduling
            updateScheduleDate(info.event.id, info.event.start);
        },
        eventResize: function(info) {
            // Handle event resize
            updateScheduleDate(info.event.id, info.event.start);
        },
        height: 'auto',
        locale: 'en'
    });

    calendar.render();
    return calendar;
}

/**
 * Update schedule date via API when dragged/resized
 */
async function updateScheduleDate(scheduleId, newDate) {
    try {
        const response = await fetch(`/api/schedules/${scheduleId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                start_date: newDate.toISOString(),
                _method: 'PATCH'
            })
        });

        if (!response.ok) {
            throw new Error('Failed to update schedule');
        }

        // Show success notification
        showNotification('Schedule updated successfully', 'success');
    } catch (error) {
        console.error('Error updating schedule:', error);
        showNotification('Failed to update schedule', 'error');
        throw error;
    }
}

/**
 * Show temporary notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '9999';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Auto-initialize if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCalendar);
} else {
    initCalendar();
}
