export const daysOfWeek = [
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
];
export const daysOfWeekShort = [
    'Sun',
    'Mon',
    'Tue',
    'Wed',
    'Thu',
    'Fri',
    'Sat',
];
export const months = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];
export const months_short = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oc',
    'Nov',
    'Dec',
];
// Helper function to get the ordinal suffix for the day
export const ordinalSuffix = (day: number) => {
    // Suffix for 11th, 12th, 13th is 'th'
    if (day > 3 && day < 21) {
        return 'th';
    }

    switch (day % 10) {
        case 1:
            return 'st';
        case 2:
            return 'nd';
        case 3:
            return 'rd';
        default:
            return 'th';
    }
};
export function formatFullDate(date: Date) {
    // Arrays for full day and month names

    // --- Get Date and Time Components ---
    const weekday = daysOfWeek[date.getDay()];
    const dayOfMonth = date.getDate();
    const dayWithSuffix = `${dayOfMonth}${ordinalSuffix(dayOfMonth)}`;
    const month = months[date.getMonth()];
    const year = date.getFullYear();

    let hours = date.getHours();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // The hour '0' should be '12'

    const minutes = String(date.getMinutes()).padStart(2, '0');

    // --- Assemble the Final String ---
    return `${weekday}, ${dayWithSuffix} ${month} ${year} ${hours}:${minutes} ${ampm}`;
}

export function formatDateMonth(date: Date | string) {
    if (typeof date === 'string') {
        date = new Date(date);
    }

    const weekday = daysOfWeekShort[date.getDay()];
    const dayOfMonth = date.getDate();
    const month = months_short[date.getMonth()];
    const dayWithSuffix = `${dayOfMonth}${ordinalSuffix(dayOfMonth)}`;

    return `${weekday}, ${month} ${dayWithSuffix}`;
}

export const formatDateFull = (date: Date | string) => {
    if (typeof date === 'string') {
        date = new Date(date);
    }

    const weekday = daysOfWeek[date.getDay()];
    const dayOfMonth = date.getDate();
    const month = months_short[date.getMonth()];
    const dayWithSuffix = `${dayOfMonth}${ordinalSuffix(dayOfMonth)}`;
    const year = date.getFullYear();

    return `${weekday}, ${dayWithSuffix} ${month} ${year}`;
};

export function formatDateForInput(date: Date): string {
    const year = date.getFullYear();
    // Month is 0-indexed, so add 1 and pad with '0' if less than 10
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    // Day of the month, pad with '0' if less than 10
    const day = date.getDate().toString().padStart(2, '0');

    return `${year}-${month}-${day}`;
}

export const formatTimeForInput = (date: Date): string => {
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');

    return `${hours}:${minutes}`;
};

export function formatDateTimeForInput(date: Date): string {
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

export const toHumanDate = (timestamp: number | string | Date | null) => {
    if (!timestamp) {
        return '—';
    }

    // Handle both string dates and integer timestamps
    const date =
        timestamp instanceof Date
            ? timestamp
            : typeof timestamp === 'number'
              ? new Date(timestamp * 1000)
              : new Date(timestamp);

    return date.toLocaleDateString('en-GH', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

export const toRelativeTime = (timestamp: Date | number) => {
    const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
    const secondsAgo = Math.floor(
        (Date.now() -
            (timestamp instanceof Date
                ? timestamp.getTime()
                : timestamp * 1000)) /
            1000,
    );

    if (secondsAgo < 60) {
        return 'Just now';
    }

    if (secondsAgo < 3600) {
        return rtf.format(-Math.floor(secondsAgo / 60), 'minute');
    }

    if (secondsAgo < 86400) {
        return rtf.format(-Math.floor(secondsAgo / 3600), 'hour');
    }

    if (secondsAgo < 2592000) {
        // less than 30 days
        return rtf.format(-Math.floor(secondsAgo / 86400), 'day');
    }

    if (secondsAgo < 31104000) {
        // less than 12 months
        return rtf.format(-Math.floor(secondsAgo / 2592000), 'month');
    }

    if (secondsAgo < 315360000) {
        // less than 10 years
        return rtf.format(-Math.floor(secondsAgo / 31104000), 'year');
    }

    return toHumanDate(timestamp);
};

export const formatTime12Hour = (date: Date | string) => {
    if (typeof date === 'string') {
        date = new Date(date);
    }

    let hours = date.getHours();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${hours}:${minutes} ${ampm}`;
};

// Groups a date into a WhatsApp-style label: "Today", "Yesterday", or its full date.
export const dateGroupLabel = (date: Date | string) => {
    if (typeof date === 'string') {
        date = new Date(date);
    }

    const startOfDay = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
    const diffDays = Math.round((startOfDay(new Date()) - startOfDay(date)) / 86400000);

    if (diffDays === 0) {
        return 'Today';
    }

    if (diffDays === 1) {
        return 'Yesterday';
    }

    return formatDateFull(date);
};

export function useDate() {
    return {
        formatFullDate,
        formatDateMonth,
        formatDateForInput,
        toHumanDate,
        toRelativeTime,
        formatDateTimeForInput,
        formatDateFull,
        formatTimeForInput,
        formatTime12Hour,
        dateGroupLabel,
    };
}
