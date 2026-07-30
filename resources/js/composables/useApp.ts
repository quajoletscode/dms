import type { InertiaLinkProps } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';

export const truncateText = (text = '', maxLength = 15): string => {
    if (text && text.length > maxLength) {
        return text.substring(0, maxLength) + '...';
    }

    return text;
};
export function formatNumber(num: number): string {
    const formatter = new Intl.NumberFormat('en-US', {
        notation: 'standard',
        compactDisplay: 'short',
        maximumFractionDigits: 3,
    });

    return formatter.format(num);
}

export function formatNumberAsNotation(num: number): string {
    const formatter = new Intl.NumberFormat('en-US', {
        notation: 'compact',
        compactDisplay: 'short',
        maximumFractionDigits: 3,
    });

    return formatter.format(num);
}

export const ids = (length: number = 5) => {
    const characters =
        'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';

    for (let i = 0; i < length; i++) {
        result += characters.charAt(
            Math.floor(Math.random() * characters.length),
        );
    }

    return result;
};

export const handlePhoneNumberInput = (e: Event | KeyboardEvent) => {
    const input = e.target as HTMLInputElement;

    return input.value?.replace(/[^0-9]/g, '');
};

export const cleanDigitsOnly = (
    rawValue: string | number | null | undefined,
): string => {
    if (rawValue === null || rawValue === undefined) {
        return '';
    }
    // Convert to string (if it's a number) and remove all non-digit characters globally

    return String(rawValue).replace(/[^0-9]/g, '');
};

/**
 * Strips special characters from the beginning of a string.
 *
 * @param {string} str The input string.
 * @returns {string} The string with leading special characters removed.
 */
export function stripSpecialChars(str: string): string | null | undefined {
    // Use a regular expression to match and replace the characters
    if (str) {
        return str.replace(/^[^a-zA-Z0-9]+/, '');
    }

    return '';
}

/**
 * Returns a time-based greeting for the user.
 * @returns {string} The greeting (e.g., "Good morning").
 */
export function getGreeting(): string {
    const hour = new Date().getHours();

    if (hour >= 5 && hour < 12) {
        return 'Good morning';
    } else if (hour >= 12 && hour < 18) {
        return 'Good afternoon';
    } else {
        return 'Good evening';
    }
}

const page = usePage();

export const isFullPathActive = (
    url: NonNullable<InertiaLinkProps['href']>,
) => {
    return computed(() => {
        // Check for an exact match first
        if (page.url === url) {
            return true;
        }

        // Then, check for a hierarchical match
        return page.url.split('?')[0].startsWith(`${url}/`);
    });
};

export const isLinkActive = (url: NonNullable<InertiaLinkProps['href']>) => {
    const c_url = page.url;

    if (typeof url === 'string') {
        return c_url.split('?')[0] === url;
    }

    return false;
};

export const isFilterActive = (key: string) => {
    const searchParams = new URLSearchParams(page.url.split('?')[1]);

    return !!searchParams.get(key);
};

export const formatCurrency = (
    amount: number,
    currencyCode: string = 'GHS',
): string => {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: currencyCode,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        currencyDisplay: 'code',
    }).format(amount);
};

/**
 * Formats a number as a currency string with two decimal places and thousands separators.
 */
export const formatAmount = (value: number) =>
    value.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        currencySign: 'accounting',
        style: 'currency',
        currency: 'GHS',
    });

export const formatNumberWithDecimal = (
    value: number,
    decimalPlaces: number = 2,
): string => {
    return value.toLocaleString('en-US', {
        minimumFractionDigits: decimalPlaces,
        maximumFractionDigits: decimalPlaces,
    });
};
/**
 * Custom composable to attach a keyboard shortcut listener to the document.
 *
 * @param {string} key - The specific key to listen for (e.g., 's', 'Enter', 'Escape').
 * @param {() => void} callback - The function to execute when the specified key is pressed.
 */
export function useKeyboardShortcut(key: string, callback: () => void): void {
    // Define the event handler function with proper type for the KeyboardEvent
    const handler = (event: KeyboardEvent): void => {
        // Check if the pressed key matches the desired key
        if (event.key === key) {
            // Execute the provided callback function
            callback();
        }
    };

    // Attach the event listener when the component using this composable is mounted
    onMounted(() => {
        document.addEventListener('keydown', handler);
    });

    // Remove the event listener when the component is unmounted to prevent memory leaks
    onUnmounted(() => {
        document.removeEventListener('keydown', handler);
    });
}

export function debounce<T extends (...args: any[]) => any>(
    callback: T,
    delay: number = 500,
): (...args: Parameters<T>) => void {
    let timeoutId: ReturnType<typeof setTimeout> | null = null;

    return function (this: ThisParameterType<T>, ...args: Parameters<T>): void {
        if (timeoutId !== null) {
            clearTimeout(timeoutId);
        }

        timeoutId = setTimeout(() => {
            callback.apply(this, args);
        }, delay);
    };
}

export function useApp() {
    return {
        ids,
        formatNumberAsNotation,
        formatNumber,
        truncateText,
        handlePhoneNumberInput,
        stripSpecialChars,
        getGreeting,
        isLinkActive,
        isFullPathActive,
        formatCurrency,
        useKeyboardShortcut,
        debounce,
    };
}
