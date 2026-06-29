import { formatDate, formatDateTime, fromNow } from '@/lib/date';

/**
 * Date formatting helpers for use inside Vue components.
 */
export function useDate() {
    return {
        formatDate,
        formatDateTime,
        fromNow,
    };
}
