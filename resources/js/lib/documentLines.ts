export function computeLineTotal({
    qty,
    unitPrice,
    discount,
    tax,
}: {
    qty: number | string;
    unitPrice: number | string;
    discount: number | string;
    tax: number | string;
}): number {
    return Number(qty) * Number(unitPrice) - Number(discount) + Number(tax);
}
