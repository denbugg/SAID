// static/login.js

document.getElementById("loginForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const phone = document.getElementById("phone").value;
    const password = document.getElementById("password").value;

    const response = await fetch("http://127.0.0.1:8000/api/auth/login", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            phone: phone,
            password: password
        }),
    });

    if (response.ok) {
        const data = await response.json();
        localStorage.setItem("user_id", data.id);
        localStorage.setItem("role", data.role);

        if (data.role === "donor") {
            window.location.href = "/frontend/calendar.html";
        } else if (data.role === "warehouse_operator") {
            window.location.href = "/frontend/accept_delivery.html";
        } else if (data.role === "needer") {
            window.location.href = "/frontend/market.html";
        } else {
            document.getElementById("error").innerText = "Неизвестная роль пользователя.";
        }
    } else {
        const errorData = await response.json();
        document.getElementById("error").innerText = errorData.detail;
    }
});
