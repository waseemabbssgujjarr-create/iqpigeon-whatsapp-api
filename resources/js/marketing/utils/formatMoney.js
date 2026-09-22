export function formatMoney(cents, currency = 'USD') {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currency?.toUpperCase() || 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format((cents || 0) / 100);
}
