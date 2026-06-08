const translations = {
    en: {
        welcome: "Welcome to AgriMobile",
        login: "Login",
        register: "Register",
        logout: "Logout",
        listProducts: "List Products",
        checkPrice: "Check Price Recommendations",
        orders: "Orders",
        profile: "Profile",
        search: "Search Products",
        placeOrder: "Place Order",
        addProduct: "Add Product",
        productName: "Product Name",
        price: "Price (₱/kg)",
        quantity: "Quantity (kg)",
        description: "Description",
        origin: "Origin",
        category: "Category",
        submit: "Submit",
        back: "Back",
        loading: "Loading...",
        confidence: "Confidence",
        high: "High",
        medium: "Medium",
        low: "Low",
        acceptPrice: "Accept Price",
        farmer: "Farmer",
        buyer: "Buyer",
        email: "Email",
        password: "Password",
        confirmPassword: "Confirm Password",
        username: "Username",
        displayName: "Display Name",
        address: "Address",
        noAccount: "No account?",
        haveAccount: "Already have an account?",
        role: "Role",
        voiceNav: "Voice Navigation",
        pricePrediction: "AI Price Prediction",
        cropType: "Crop Type"
    },
    tl: {
        welcome: "Maligayang pagdating sa AgriMobile",
        login: "Mag-login",
        register: "Magrehistro",
        logout: "Mag-logout",
        listProducts: "Ilista ang mga Produkto",
        checkPrice: "Tingnan ang Rekomendasyon ng Presyo",
        orders: "Mga Order",
        profile: "Profile",
        search: "Maghanap ng Produkto",
        placeOrder: "Mag-order",
        addProduct: "Magdagdag ng Produkto",
        productName: "Pangalan ng Produkto",
        price: "Presyo (₱/kg)",
        quantity: "Dami (kg)",
        description: "Paglalarawan",
        origin: "Pinagmulan",
        category: "Kategorya",
        submit: "Isumite",
        back: "Bumalik",
        loading: "Naglo-load...",
        confidence: "Kumpiyansa",
        high: "Mataas",
        medium: "Katamtaman",
        low: "Mababa",
        acceptPrice: "Tanggapin ang Presyo",
        farmer: "Magsasaka",
        buyer: "Mamimili",
        email: "Email",
        password: "Password",
        confirmPassword: "Kumpirmahin",
        username: "Username",
        displayName: "Pangalan",
        address: "Address",
        noAccount: "Walang account?",
        haveAccount: "May account na?",
        role: "Tungkulin",
        voiceNav: "Boses",
        pricePrediction: "AI Presyo",
        cropType: "Uri ng Tanim"
    },
    hil: {
        welcome: "Maayo nga pag-abot",
        login: "Mag-login",
        register: "Magrehistro",
        logout: "Mag-logout",
        listProducts: "Ibutang Produkto",
        checkPrice: "Tan-awa Presyo",
        orders: "Order",
        profile: "Profile",
        search: "Pangitaa",
        placeOrder: "Mag-order",
        addProduct: "Magdugang Produkto",
        productName: "Ngalan Produkto",
        price: "Presyo (₱/kg)",
        quantity: "Kadamuon",
        description: "Paglarawan",
        origin: "Ginhalean",
        category: "Kategorya",
        submit: "Isumite",
        back: "Balik",
        loading: "Nagakarga",
        confidence: "Kumpiyansa",
        high: "Mataas",
        medium: "Tunga",
        low: "Mababa",
        acceptPrice: "Akwentahan",
        farmer: "Mag-uuma",
        buyer: "Mamalitay",
        email: "Email",
        password: "Password",
        confirmPassword: "Kumpirmahon",
        username: "Username",
        displayName: "Pangalan",
        address: "Address",
        noAccount: "Wala pa?",
        haveAccount: "May account?",
        role: "Papel",
        voiceNav: "Tingog",
        pricePrediction: "AI Presyo",
        cropType: "Klase sang Tanom"
    }
};

let currentLang = localStorage.getItem('appLanguage') || 'en';

function t(key) {
    return translations[currentLang][key] || translations.en[key] || key;
}

function setLanguage(lang) {
    currentLang = lang;
    localStorage.setItem('appLanguage', lang);
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (el.placeholder) el.placeholder = t(key);
        else if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') {
        } else {
            el.textContent = t(key);
        }
    });
}

export { t, setLanguage, currentLang };