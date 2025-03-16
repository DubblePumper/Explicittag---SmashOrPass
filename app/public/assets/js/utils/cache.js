function setCache(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
}

function getCache(key) {
    const item = localStorage.getItem(key);
    try {
        return item ? JSON.parse(item) : null;
    } catch {
        return null;
    }
}

function removeCache(key) {
    localStorage.removeItem(key);
}

