export function saveOfflineDraft(draft) {
    let drafts = JSON.parse(localStorage.getItem('offlineProductDrafts') || '[]');
    drafts.push(draft);
    localStorage.setItem('offlineProductDrafts', JSON.stringify(drafts));
    alert('Product saved offline. Will sync when online.');
}

export async function syncOfflineDrafts(apiRequest) {
    const drafts = JSON.parse(localStorage.getItem('offlineProductDrafts') || '[]');
    if (drafts.length === 0) return;
    for (const draft of drafts) {
        try {
            const formData = new FormData();
            Object.keys(draft).forEach(key => formData.append(key, draft[key]));
            await apiRequest('products.php', 'POST', formData, true);
        } catch(e) { console.warn(e); }
    }
    localStorage.removeItem('offlineProductDrafts');
    if (drafts.length) alert(`${drafts.length} product(s) synced!`);
}

export function isOnline() { return navigator.onLine; }