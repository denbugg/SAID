# insert_test_users.py

from db import SessionLocal
from models import models

# Создаем сессию базы данных
db = SessionLocal()

# Проверяем, нет ли уже пользователей
existing_users = db.query(models.User).count()
if existing_users > 0:
    print("Пользователи уже существуют в базе данных. Операция прервана.")
else:
    # Создание тестовых пользователей
    users = [
        models.User(
            name="Благотворитель",
            phone="+70000000001",
            role="donor",
            hashed_password="1234",
            public_key=None,
            balance_tokens=0
        ),
        models.User(
            name="Оператор склада",
            phone="+70000000002",
            role="warehouse_operator",
            hashed_password="1234",
            public_key=None,
            balance_tokens=0
        ),
        models.User(
            name="Оператор пункта выдачи",
            phone="+70000000003",
            role="delivery_operator",
            hashed_password="1234",
            public_key=None,
            balance_tokens=0
        ),
        models.User(
            name="Нуждающийся",
            phone="+70000000004",
            role="needer",
            hashed_password="1234",
            public_key=None,
            balance_tokens=100
        ),
    ]

    db.add_all(users)
    db.commit()
    print("Тестовые пользователи успешно добавлены!")

db.close()
