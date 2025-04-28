// static/calendar.js

const monthNames = [
    "Январь", "Февраль", "Март", "Апрель", "Май", "Июнь",
    "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"
];

let begin_day = new Date();
let open_day = null;

document.addEventListener("DOMContentLoaded", () => {
    renderCalendar();
    updateMonthDisplay();
});

function renderCalendar() {
    document.getElementById('product').innerHTML = '';
    document.getElementById('hygiene').innerHTML = '';
    getCalendar('product');
    getCalendar('hygiene');
}

function getCalendar(type) {
    let date = begin_day.toISOString().split('T')[0];

    fetch(`/api/calendar/get_days?type=${type}&date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.response) {
                const remains = data.response;
                for (let i = 0; i < 18; i++) {
                    let day = addDays(begin_day, i);
                    let id = day.toISOString().split('T')[0];
                    const quantity = remains[id] || 0;
                    let color = quantity <= 0.5 ? 'background-color: green;' : '';

                    const button = `
                        <button type="button" class="btn btn-success" style="margin:0; width:5.55%; ${color}"
                            onclick="openDayHandler('${type}', '${id}')">${day.getDate()}</button>`;

                    document.getElementById(type).innerHTML += button;
                }
            }
        })
        .catch(err => console.error(err));
}

function openDayHandler(type, date) {
    open_day = date;

    // Получение потребностей на дату
    fetch(`/api/calendar/get_needs?date=${date}&type=${type}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('needs-list').innerHTML = `<h4>Потребности на ${date}:</h4>`;
            for (const [category, amount] of Object.entries(data.response)) {
                document.getElementById('needs-list').innerHTML += `<h5>${category}: ${amount}</h5>`;
            }
            document.getElementById('match-deliver-block').style.display = 'block';
        });

    // Получение списка доставок
    fetch(`/api/calendar/get_deliveries?date=${date}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('delivers-list').innerHTML = '<h4>Список доставок:</h4>';
            document.getElementById('donors-list').innerHTML = '<h4>Благотворители:</h4>';

            data.response.forEach(deliver => {
                document.getElementById('delivers-list').innerHTML += `<h5>Доставка №${deliver.deliver_id}: ${deliver.product_type} — ${deliver.quantity}</h5>`;
                document.getElementById('donors-list').innerHTML += `<h5>${deliver.donor_name}</h5>`;
            });
        });
}

function changeDay(num) {
    begin_day = addDays(begin_day, num);
    renderCalendar();
    updateMonthDisplay();
}

function updateMonthDisplay() {
    let monthsText = monthNames[begin_day.getMonth()];
    const laterDay = addDays(begin_day, 17);
    if (laterDay.getMonth() !== begin_day.getMonth()) {
        monthsText += '-' + monthNames[laterDay.getMonth()];
    }
    document.getElementById('months').innerText = monthsText;
}

function addDays(date, days) {
    const result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
}

function showMatch() {
    document.getElementById('match-deliver').style.display = 'block';
}

function matchDeliver() {
    const quantity = document.getElementById('txtQuantity').value;
    const type = document.querySelector('input[name="rdbType"]:checked').value;

    if (quantity === "") {
        alert('Введите предполагаемое количество!');
        return;
    }

    fetch(`/api/calendar/match_delivery`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            date: open_day,
            quantity: quantity,
            type: type
        })
    })
    .then(response => response.json())
    .then(data => {
        const matches = data.response;
        const matchResult = document.getElementById('match-result');
        matchResult.innerHTML = `<h4>Результат подбора:</h4>`;
        for (const [product, qty] of Object.entries(matches)) {
            matchResult.innerHTML += `<h5>${product} — ${qty}</h5>`;
        }
    });
}

function logout() {
    localStorage.clear();
    window.location.href = "/frontend/login.html";
}
