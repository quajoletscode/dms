import { onMounted, onUnmounted, ref } from 'vue';

type Theme = 'light' | 'dark';

export function useTheme() {
    const theme = ref<Theme>('light');

    // Helper function to update the HTML class
    const applyThemeClass = (newTheme: Theme) => {
        document.documentElement.classList.remove('light', 'dark');
        document.documentElement.classList.add(newTheme);
    };

    // Helper function to handle theme changes
    const handleSystemThemeChange = (event: MediaQueryListEvent) => {
        theme.value = event.matches ? 'dark' : 'light';
        applyThemeClass(theme.value);
    };

    onMounted(() => {
        // 1. Check for a saved theme in localStorage
        const savedTheme = localStorage.getItem(
            'eschool-theme',
        ) as Theme | null;

        if (savedTheme) {
            theme.value = savedTheme;
        } else {
            // 2. If no saved theme, use the system preference
            const prefersDark = window.matchMedia(
                '(prefers-color-scheme: dark)',
            ).matches;
            theme.value = prefersDark ? 'dark' : 'light';
        }

        // 3. Apply the initial theme
        applyThemeClass(theme.value);

        // 4. Add a listener for system theme changes
        window
            .matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', handleSystemThemeChange);
    });

    onUnmounted(() => {
        // Clean up the event listener when the component is unmounted
        window
            .matchMedia('(prefers-color-scheme: dark)')
            .removeEventListener('change', handleSystemThemeChange);
    });

    const toggleTheme = () => {
        // Toggle the theme and save to localStorage
        const newTheme = theme.value === 'light' ? 'dark' : 'light';
        theme.value = newTheme;
        localStorage.setItem('eschool-theme', newTheme);
        applyThemeClass(newTheme);
    };

    return {
        theme,
        toggleTheme,
    };
}
