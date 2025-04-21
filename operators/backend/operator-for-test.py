from flask import Flask, jsonify, request, send_from_directory, send_file
from datetime import datetime
import os
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from io import BytesIO
import io
import uuid

app = Flask(__name__, static_folder='C:/Users/User/gumpom/operators/frontend')

# Данные продуктов
products = [
    {
        "id": "1",
        "name": "Печень",
        "weight": 500,  # грамм
        "expiry": "30.04.2024",
        "price": 30,  # токенов
        "image": "data/liver.jpg",
        "quantity": 1
    },
    {
        "id": "2",
        "name": "Сайра",
        "weight": 300,
        "expiry": "15.05.2024",
        "price": 25,
        "image": "data/saury.jpg",
        "quantity": 1
    },
    {
        "id": "3",
        "name": "Сгущенка",
        "weight": 400,
        "expiry": "31.12.2024",
        "price": 30,
        "image": "data/con_milk.jpg",
        "quantity": 1
    }
]

# Создаем заказы
orders = [
    {
        "id": str(uuid.uuid4())[:6].upper(),
        "items": [products[0], products[1]],
        "date": "28.03.2024",
        "status": "В сборке",
        "confirmation_code": "123456",
        "consignment": None
    },
    {
        "id": str(uuid.uuid4())[:6].upper(),
        "items": [products[1], products[2]],
        "date": "28.03.2024",
        "status": "В сборке",
        "confirmation_code": "567891",
        "consignment": None
    },
    {
        "id": str(uuid.uuid4())[:6].upper(),
        "items": [products[0], products[2]],
        "date": "27.03.2024",
        "status": "В сборке",
        "confirmation_code": "901213",
        "consignment": None
    }
]


# API для получения списка заказов
@app.route('/api/orders', methods=['GET'])
def get_orders():
    # Преобразуем данные для фронтенда
    orders_for_frontend = []
    for order in orders:
        total_quantity = sum(item.get('quantity', 1) for item in order['items'])
        total_weight = sum(item['weight'] for item in order['items'])
        total_price = sum(item['price'] for item in order['items'])

        orders_for_frontend.append({
            "id": order['id'],
            "totalQuantity": total_quantity,
            "totalWeight": f"{total_weight} г",
            "totalPrice": total_price,
            "date": order['date'],
            "status": order['status']
        })

    return jsonify(orders_for_frontend)


# API для получения деталей конкретного заказа
@app.route('/api/orders/<order_id>', methods=['GET'])
def get_order(order_id):
    order = next((o for o in orders if o['id'] == order_id), None)
    if not order:
        return jsonify({"error": "Order not found"}), 404

    # Подготавливаем данные для фронтенда
    items_for_frontend = []
    for item in order['items']:
        items_for_frontend.append({
            "name": item['name'],
            "quantity": 1,
            "weight": item['weight'],
            "expiry": item['expiry'],
            "price": item['price'],
            "image": item['image']
        })

    return jsonify({
        "id": order['id'],
        "items": items_for_frontend,
        "date": order['date'],
        "status": order['status'],
        "confirmation_code": order['confirmation_code']
    })


# API для подтверждения заказа
@app.route('/api/orders/<order_id>/confirm', methods=['POST'])
def confirm_order(order_id):
    order = next((o for o in orders if o['id'] == order_id), None)
    if not order:
        return jsonify({"error": "Order not found"}), 404

    data = request.json
    if not data or 'confirmation_code' not in data:
        return jsonify({"error": "Confirmation code is required"}), 400

    if data['confirmation_code'] != order['confirmation_code']:
        return jsonify({"error": "Invalid confirmation code"}), 400

    # Обновляем статус заказа
    order['status'] = "Выдан"

    return jsonify({"message": "Order confirmed successfully"})


# API для загрузки накладной
@app.route('/api/orders/<order_id>/consignment', methods=['POST'])
def upload_consignment(order_id):
    order = next((o for o in orders if o['id'] == order_id), None)
    if not order:
        return jsonify({"error": "Order not found"}), 404

    if 'file' not in request.files:
        return jsonify({"error": "No file uploaded"}), 400

    file = request.files['file']
    if file.filename == '':
        return jsonify({"error": "No selected file"}), 400

    # Сохраняем файл (в реальной системе нужно добавить проверки и безопасное сохранение)
    filename = f"consignment_{order_id}.pdf"
    file.save(os.path.join('uploads', filename))

    order['consignment'] = filename
    return jsonify({"message": "Consignment uploaded successfully"})


# API для скачивания накладной
@app.route('/api/orders/<order_id>/consignment', methods=['GET'])
def download_consignment(order_id):
    order = next((o for o in orders if o['id'] == order_id), None)
    if not order:
        return jsonify({"error": "Order not found"}), 404

    try:
        # Создаем PDF в памяти
        buffer = BytesIO()
        pdf = canvas.Canvas(buffer, pagesize=A4)

        # Устанавливаем шрифт
        pdf.setFont("Helvetica", 12)

        # Заголовок
        pdf.drawString(50, 800, f"НАКЛАДНАЯ №{order_id}")
        pdf.drawString(50, 780, f"Дата: {datetime.now().strftime('%d.%m.%Y')}")

        # Информация о товарах
        y_position = 750
        pdf.drawString(50, y_position, "№ Наименование Количество Вес Срок годности Цена")
        y_position -= 20
        pdf.line(50, y_position, 550, y_position)
        y_position -= 20

        for i, item in enumerate(order['items'], 1):
            pdf.drawString(50, y_position, f"{i}. {item['name']}")
            pdf.drawString(200, y_position, f"{item['quantity']} шт")
            pdf.drawString(280, y_position, f"{item['weight']} г")
            pdf.drawString(350, y_position, item['expiry'])
            pdf.drawString(450, y_position, f"{item['price']} токенов")
            y_position -= 20

        # Итоговая сумма
        total = sum(item['price'] * item['quantity'] for item in order['items'])
        pdf.line(50, y_position, 550, y_position)
        y_position -= 20
        pdf.drawString(400, y_position, f"ИТОГО: {total} токенов")

        # Сохраняем PDF
        pdf.showPage()
        pdf.save()

        # Возвращаем PDF
        buffer.seek(0)
        return send_file(
            buffer,
            mimetype='application/pdf',
            as_attachment=True,
            download_name=f'Накладная_{order_id}.pdf'
        )

    except Exception as e:
        print(f"Ошибка при генерации PDF: {str(e)}")
        return jsonify({"error": "Failed to generate consignment"}), 500


@app.route('/data/<filename>')
def serve_product_image(filename):
    return send_from_directory('data', filename)


# Обслуживание статических файлов
@app.route('/<path:path>')
def serve_static(path):
    return send_from_directory('../frontend', path)


if __name__ == '__main__':
    # Создаем папку для загрузок, если ее нет
    os.makedirs('uploads', exist_ok=True)
    app.run(debug=True)