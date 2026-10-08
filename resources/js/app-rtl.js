import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * GMTM ERP — RTL BiDi Number & Date Stabilizer
 * Automatically isolates numbers, dates, references, currency values,
 * and telephone numbers to prevent browser BiDi reordering/inversion in RTL mode.
 */
function isolateBidiElements(root = document) {
    if (document.documentElement.getAttribute('dir') !== 'rtl') {
        return;
    }

    // Common date patterns (DD/MM/YYYY, YYYY-MM-DD, DD-MM-YYYY, etc.)
    const dateRegex = /^\s*\d{1,4}[/-]\d{1,2}[/-]\d{1,4}\s*$/;
    // Formatted monetary amounts or pure numbers (e.g. "1 500 000.00", "1500.50 DA", "120 DZD", "15%", "#12")
    const numberRegex = /^\s*#?\d[\d\s,.]*(?:\s*(?:DA|DZD|EUR|USD|%|€|\$|د\.ج|دج))?\s*$/i;
    // Alphanumeric references with dashes/slashes (e.g. "CTR-2026-001", "GRT-2024-001", "FLK-700G")
    const refRegex = /^\s*[A-Z0-9]+(?:[-/][A-Z0-9]+)+\s*$/i;
    // Phone numbers (e.g. "+213 555 12 34 56", "0555 12 34 56")
    const phoneRegex = /^\s*\+?\d[\d\s-]{7,}\d\s*$/;

    // Fast-path: query candidate elements that typically display tabular data, badges, and values
    const candidates = root.querySelectorAll('span, td, dd, div, a, p, b, strong, em, time, label');

    for (let i = 0; i < candidates.length; i++) {
        const el = candidates[i];

        // Skip elements with multiple element children or already isolated or ignored
        if (el.children.length > 0 || el.hasAttribute('dir') || el.classList.contains('no-bidi-fix')) {
            continue;
        }

        const text = el.textContent;
        if (!text || text.length > 40) {
            continue;
        }

        // Check if text is a date, number/amount, reference, or phone
        if (dateRegex.test(text) || numberRegex.test(text) || refRegex.test(text) || phoneRegex.test(text)) {
            el.setAttribute('dir', 'ltr');
            el.style.unicodeBidi = 'isolate';
            if (el.tagName === 'SPAN' || el.tagName === 'A' || el.tagName === 'B' || el.tagName === 'STRONG') {
                el.style.display = 'inline-block';
            }
        }
    }
}

// Execute on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => isolateBidiElements());
} else {
    isolateBidiElements();
}

// Observe dynamic DOM additions (Alpine modals, pagination updates, filters)
const bidiObserver = new MutationObserver((mutations) => {
    for (let i = 0; i < mutations.length; i++) {
        const added = mutations[i].addedNodes;
        for (let j = 0; j < added.length; j++) {
            const node = added[j];
            if (node.nodeType === Node.ELEMENT_NODE) {
                isolateBidiElements(node);
            }
        }
    }
});

bidiObserver.observe(document.body || document.documentElement, { childList: true, subtree: true });

Alpine.start();

