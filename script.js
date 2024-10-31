document.addEventListener('DOMContentLoaded', function() {
    const authForm = document.getElementById('authForm');
    const cansForm = document.getElementById('cansForm');
    const requestsTable = document.getElementById('requestsTable').getElementsByTagName('tbody')[0];

    const users = {
        user1: { password: 'pass1', role: 'user', wallet: '0x1234567890abcdef' },
        user2: { password: 'pass2', role: 'user', wallet: '0xabcdef1234567890' },
        admin: { password: 'adminpass', role: 'admin', wallet: '0x0000000000000000' }
    };

    if (authForm) {
        authForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const login = document.getElementById('login').value;
            const password = document.getElementById('password').value;
            const role = document.getElementById('role').value;

            if (users[login] && users[login].password === password && users[login].role === role) {
                localStorage.setItem('login', login);
                localStorage.setItem('role', role);
                localStorage.setItem('wallet', users[login].wallet);
                window.location.href = role === 'admin' ? 'administrator.html' : 'user.html';
            } else {
                alert('Неверный логин, пароль или роль');
            }
        });
    }

    if (cansForm) {
        const login = localStorage.getItem('login');
        const role = localStorage.getItem('role');
        const wallet = localStorage.getItem('wallet');
        document.getElementById('login').textContent = login;
        document.getElementById('role').textContent = role;
        document.getElementById('wallet').textContent = wallet;

        cansForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const cans = document.getElementById('cans').value;
            const request = { login, password: users[login].password, cans };
            localStorage.setItem('request', JSON.stringify(request));
            alert('Запрос отправлен администратору');
            window.location.href = 'authorization.html';
        });
    }

    if (requestsTable) {
        const login = localStorage.getItem('login');
        const role = localStorage.getItem('role');
        document.getElementById('login').textContent = login;
        document.getElementById('role').textContent = role;

        const request = JSON.parse(localStorage.getItem('request'));
        if (request) {
            const newRow = requestsTable.insertRow();
            newRow.insertCell(0).textContent = request.login;
            newRow.insertCell(1).textContent = request.password;
            newRow.insertCell(2).textContent = request.cans;
            const confirmCell = newRow.insertCell(3);
            const confirmBtn = document.createElement('button');
            confirmBtn.textContent = 'Подтвердить';
            confirmBtn.addEventListener('click', function() {
                alert('Запрос обработан');
                localStorage.removeItem('request');
                window.location.href = 'authorization.html';
            });
            confirmCell.appendChild(confirmBtn);
        }
    }
});