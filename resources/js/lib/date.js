import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);

/**
 * Format a date using a standard date format (e.g. "Jun 29, 2026").
 */
export function formatDate(value, format = 'MMM D, YYYY') {
    if (!value) {
        return '';
    }

    return dayjs(value).format(format);
}

/**
 * Format a date including the time (e.g. "Jun 29, 2026 3:45 PM").
 */
export function formatDateTime(value, format = 'MMM D, YYYY h:mm A') {
    if (!value) {
        return '';
    }

    return dayjs(value).format(format);
}

/**
 * Render a human-friendly relative time (e.g. "2 days ago", "in 3 hours").
 */
export function fromNow(value) {
    if (!value) {
        return '';
    }

    return dayjs(value).fromNow();
}

export { dayjs };
