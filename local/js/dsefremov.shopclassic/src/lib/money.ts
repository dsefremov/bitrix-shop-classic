const formatter = new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

/** Копейки -> «5 990 ₽». */
export function formatPrice(kopecks: number): string {
    return formatter.format(kopecks / 100);
}
