import { API_BASE_URL } from './config.js';

async function apiRequest(endpoint, method = 'GET', body = null, isFormData = false) {
    const headers = {};
    if (!isFormData) headers['Content-Type'] = 'application/json';
    const options = {
        method,
        headers,
        credentials: 'include',
    };
    if (body) {
        options.body = isFormData ? body : JSON.stringify(body);
    }
    const response = await fetch(`${API_BASE_URL}/${endpoint}`, options);
    let data;
    const text = await response.text();
    try {
        data = JSON.parse(text);
    } catch (e) {
        console.error('Invalid JSON:', text);
        throw new Error('Server returned invalid response');
    }
    if (!response.ok) throw new Error(data.msg || 'Request failed');
    return data;
}

function getUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
}

export async function register(email, password, role, username, address) {
    const data = await apiRequest('auth.php?action=register', 'POST', { email, password, role, username, address });
    if (data.user) localStorage.setItem('user', JSON.stringify(data.user));
    return data;
}

export async function login(email, password) {
    const data = await apiRequest('auth.php?action=login', 'POST', { email, password });
    if (data.user) localStorage.setItem('user', JSON.stringify(data.user));
    return data;
}

export async function logout() {
    await fetch(`${API_BASE_URL}/logout.php`, { method: 'POST', credentials: 'include' });
    localStorage.removeItem('user');
    window.location.href = 'login.html';
}

export async function addProduct(formData) {
    return await apiRequest('products.php', 'POST', formData, true);
}

export async function getProducts() {
    return await apiRequest('products.php', 'GET');
}

export async function getMyProducts() {
    return await apiRequest('products.php?my=1', 'GET');
}

export async function deleteProduct(productId) {
    return await apiRequest('products.php', 'DELETE', { product_id: productId });
}

export async function updateProduct(productId, productData) {
    return await apiRequest('products.php', 'PUT', { id: productId, ...productData });
}

export async function placeOrder(product_id, quantity) {
    return await apiRequest('orders.php', 'POST', { product_id, quantity });
}

export async function getMyOrders() {
    return await apiRequest('orders.php', 'GET');
}

export async function markOrderReceived(orderId) {
    return await apiRequest('orders.php?action=mark_received', 'POST', { order_id: orderId });
}

export async function markPaymentReceived(orderId) {
    return await apiRequest('orders.php?action=mark_paid', 'POST', { order_id: orderId });
}

export async function recordPayment(orderId, amount, method, notes) {
    return await apiRequest('orders.php?action=record_payment', 'POST', { order_id: orderId, amount, payment_method: method, notes });
}

export async function updateOrderStatus(orderId, status, courier, tracking) {
    return await apiRequest('orders.php?action=update_status', 'POST', { order_id: orderId, status, delivery_courier: courier, tracking_number: tracking });
}

export async function adminFetch(endpoint) {
    return await apiRequest(`admin.php/${endpoint}`, 'GET');
}

export async function adminDelete(endpoint, id) {
    return await apiRequest(`admin.php/${endpoint}/${id}`, 'DELETE');
}

export async function adminUpdateRole(userId, role) {
    return await apiRequest(`admin.php/users/${userId}/role`, 'PUT', { role });
}

export { getUser, apiRequest };