export function formatEventMoney(centavos: number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(centavos / 100);
}
export function eventCentavos(pesos: string | number): number | '' {
    const value = String(pesos).trim();
    if (!/^\d+(?:\.\d{1,2})?$/.test(value)) return '';
    const [whole, fraction = ''] = value.split('.');
    const amount = Number(whole) * 100 + Number(fraction.padEnd(2, '0'));
    return Number.isSafeInteger(amount) ? amount : '';
}
