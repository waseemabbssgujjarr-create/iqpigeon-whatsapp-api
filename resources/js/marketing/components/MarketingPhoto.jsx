export default function MarketingPhoto({ src, alt, className = '', priority = false, aspect = 'portrait' }) {
    const aspectClass = aspect === 'landscape' ? 'iqp-photo--landscape' : 'iqp-photo--portrait';

    return (
        <figure className={`iqp-photo ${aspectClass} ${className}`.trim()}>
            <img src={src} alt={alt} loading={priority ? 'eager' : 'lazy'} decoding="async" />
        </figure>
    );
}
