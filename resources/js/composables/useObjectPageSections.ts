import { onBeforeUnmount, onMounted, ref } from 'vue';

export interface ObjectPageSectionDef {
    id: string;
    label: string;
}

export function useObjectPageSections(sections: () => ObjectPageSectionDef[]) {
    const activeId = ref<string | null>(null);
    let observer: IntersectionObserver | null = null;

    onMounted(() => {
        const defs = sections();

        if (defs.length === 0) {
            return;
        }

        activeId.value = defs[0].id;

        observer = new IntersectionObserver(
            (entries) => {
                const visible = entries
                    .filter((entry) => entry.isIntersecting)
                    .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

                if (visible[0]) {
                    activeId.value = visible[0].target.id;
                }
            },
            { rootMargin: '-120px 0px -70% 0px', threshold: 0 },
        );

        for (const section of defs) {
            const el = document.getElementById(section.id);

            if (el) {
                observer.observe(el);
            }
        }
    });

    onBeforeUnmount(() => {
        observer?.disconnect();
    });

    function scrollTo(id: string) {
        document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        activeId.value = id;
    }

    return { activeId, scrollTo };
}
