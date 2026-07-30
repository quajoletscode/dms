export function initials(name?: string): string {
    if (!name) {
        return '';
    }

    const names = name.trim().split(' ');

    if (names.length === 0) {
        return '';
    }

    if (names.length === 1) {
        return names[0].charAt(0).toUpperCase();
    }

    return `${names[0].charAt(0)}${names[names.length - 1].charAt(0)}`.toUpperCase();
}
export function truncateText(text = '', maxLength = 15): string {
    if (text && text.length > maxLength) {
        return text.substring(0, maxLength) + '...';
    }

    return text;
}
export const capitalizeWords = (str: string) => {
    return str.toLowerCase().replace(/\b\w/g, (char) => char.toUpperCase());
};

export function useString() {
    return {
        initials,
        truncateText,
        capitalizeWords,
    };
}
