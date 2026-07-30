// Prints the exact PDF served by a download endpoint, so "print" and "download" can never drift out
// of sync — both always render from the same backend PDF template.
export function usePdfPrint() {
    const printPdf = (url: string) => {
        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.src = url;

        iframe.onload = () => {
            iframe.contentWindow?.focus();
            iframe.contentWindow?.print();
        };

        document.body.appendChild(iframe);

        setTimeout(() => iframe.remove(), 60000);
    };

    return { printPdf };
}
