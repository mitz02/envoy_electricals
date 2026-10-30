/**
 * Convert numerical amount to English words (Naira & Kobo).
 */
const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
    'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

function convertUnderThousand(n) {
    if (n === 0) return '';
    if (n < 20) return ones[n];
    if (n < 100) {
        return tens[Math.floor(n / 10)] + (n % 10 !== 0 ? ' ' + ones[n % 10] : '');
    }
    return ones[Math.floor(n / 100)] + ' Hundred' + (n % 100 !== 0 ? ' and ' + convertUnderThousand(n % 100) : '');
}

export function numberToNairaWords(amount) {
    const num = parseFloat(amount);
    if (isNaN(num) || num <= 0) return '';

    const naira = Math.floor(num);
    const kobo = Math.round((num - naira) * 100);

    if (naira === 0 && kobo === 0) return 'Zero Naira Only';

    const billions = Math.floor(naira / 1_000_000_000);
    const millions = Math.floor((naira % 1_000_000_000) / 1_000_000);
    const thousands = Math.floor((naira % 1_000_000) / 1_000);
    const remainder = naira % 1_000;

    let parts = [];
    if (billions > 0) parts.push(convertUnderThousand(billions) + ' Billion');
    if (millions > 0) parts.push(convertUnderThousand(millions) + ' Million');
    if (thousands > 0) parts.push(convertUnderThousand(thousands) + ' Thousand');
    if (remainder > 0) parts.push(convertUnderThousand(remainder));

    let words = parts.join(' ').trim();
    if (!words) words = 'Zero';
    words += ' Naira';

    if (kobo > 0) {
        words += ' and ' + convertUnderThousand(kobo) + ' Kobo Only';
    } else {
        words += ' Only';
    }

    return words;
}
