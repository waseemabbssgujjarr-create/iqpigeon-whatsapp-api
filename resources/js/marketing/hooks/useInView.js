import { useEffect, useRef, useState } from 'react';

export function useInView(options = {}) {
    const ref = useRef(null);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const el = ref.current;
        if (!el) {
            return undefined;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setVisible(true);
                    if (options.once !== false) {
                        observer.disconnect();
                    }
                } else if (options.once === false) {
                    setVisible(false);
                }
            },
            { threshold: options.threshold ?? 0.12, rootMargin: options.rootMargin ?? '0px' },
        );

        observer.observe(el);

        return () => observer.disconnect();
    }, [options.once, options.threshold, options.rootMargin]);

    return [ref, visible];
}
