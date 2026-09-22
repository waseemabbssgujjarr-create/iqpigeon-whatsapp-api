import { useInView } from '../hooks/useInView';

export default function Reveal({ children, className = '', delay = 0, as: Tag = 'div' }) {
    const [ref, visible] = useInView({ once: true });

    return (
        <Tag
            ref={ref}
            className={`iqp-reveal ${visible ? 'is-visible' : ''} ${className}`}
            style={{ transitionDelay: visible ? `${delay}ms` : undefined }}
        >
            {children}
        </Tag>
    );
}
