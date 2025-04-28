// static/scripts.js

// Универсальные функции для работы с API

async function postData(url = "", data = {}) {
    const response = await fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
    });
    return response.json();
}

async function getData(url = "") {
    const response = await fetch(url);
    return response.json();
}

// Проверка авторизации
function checkAuth() {
    const userId = localStorage.getItem("user_id");
    if (!userId) {
        window.location.href = "/frontend/login.html";
    }
}

// Выход из системы
function logout() {
    localStorage.removeItem("user_id");
    localStorage.removeItem("role");
    window.location.href = "/frontend/login.html";
}
