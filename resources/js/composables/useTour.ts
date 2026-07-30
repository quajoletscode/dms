import { ref } from 'vue';

export interface TourStep {
    target: string; // matches a `data-tour="<id>"` attribute on the page
    title: string;
    body: string;
    placement?: 'top' | 'bottom' | 'left' | 'right';
}

const STORAGE_KEY = 'fuelaxis-tours-completed';

const isActive = ref(false);
const tourId = ref<string | null>(null);
const steps = ref<TourStep[]>([]);
const stepIndex = ref(0);

const readCompleted = (): string[] => {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        return raw ? (JSON.parse(raw) as string[]) : [];
    } catch {
        return [];
    }
};

const markCompleted = (id: string): void => {
    const completed = readCompleted();

    if (!completed.includes(id)) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify([...completed, id]));
    }
};

export function useTour() {
    const hasCompleted = (id: string): boolean => readCompleted().includes(id);

    const startTour = (id: string, tourSteps: TourStep[]): void => {
        if (tourSteps.length === 0) {
            return;
        }

        tourId.value = id;
        steps.value = tourSteps;
        stepIndex.value = 0;
        isActive.value = true;
    };

    const end = (): void => {
        if (tourId.value) {
            markCompleted(tourId.value);
        }

        isActive.value = false;
        tourId.value = null;
        steps.value = [];
        stepIndex.value = 0;
    };

    const next = (): void => {
        if (stepIndex.value >= steps.value.length - 1) {
            end();

            return;
        }

        stepIndex.value += 1;
    };

    const back = (): void => {
        if (stepIndex.value > 0) {
            stepIndex.value -= 1;
        }
    };

    return {
        isActive,
        tourId,
        steps,
        stepIndex,
        startTour,
        next,
        back,
        skip: end,
        finish: end,
        hasCompleted,
    };
}
